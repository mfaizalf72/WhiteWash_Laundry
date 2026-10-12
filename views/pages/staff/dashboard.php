<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Models\Order;

$pickupRequests  = $queues['pickup_requests'] ?? [];
$processingQueue = $queues['processing'] ?? [];
$deliveryQueue   = $queues['delivery'] ?? [];
$paymentDueQueue = $queues['payment_due'] ?? [];

// Helper for semantic order status badge styling matching Slice 1
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pickup_request'       => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'accepted'             => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'picked_up'            => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'inspection_completed' => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'price_confirmed'      => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'processing'           => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'ready'                => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200'],
        'out_for_delivery'     => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200'],
        'delivered'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
        'completed'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
        'cancelled'            => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200']
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200'];
};
?>

<div class="space-y-6">

    <!-- Dashboard Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 font-display">Staff Operations Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Manage daily pickup requests, processing workflows, deliveries, and payment settlements.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/laundry-lms/orders/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm inline-flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Create Order</span>
            </a>
            <a href="/laundry-lms/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors border border-slate-200 inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <span>View All Orders</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>

    <!-- Queue Metric Summary Strip & Navigation Tabs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <a href="#queue-pickups" class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm hover:border-amber-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-amber-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pickups</span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-1.5 tabular-nums"><?= number_format(count($pickupRequests)) ?></div>
            <span class="text-xs text-amber-700 mt-1 block font-medium group-hover:underline">Pending Requests &darr;</span>
        </a>

        <a href="#queue-processing" class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm hover:border-sky-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-sky-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">In-Shop</span>
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-1.5 tabular-nums"><?= number_format(count($processingQueue)) ?></div>
            <span class="text-xs text-sky-700 mt-1 block font-medium group-hover:underline">Active Operations &darr;</span>
        </a>

        <a href="#queue-delivery" class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm hover:border-violet-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-violet-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dispatch</span>
                <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-1.5 tabular-nums"><?= number_format(count($deliveryQueue)) ?></div>
            <span class="text-xs text-violet-700 mt-1 block font-medium group-hover:underline">Ready / Delivery &darr;</span>
        </a>

        <a href="#queue-payment-due" class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm hover:border-rose-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-rose-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Payment Due</span>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-1.5 tabular-nums"><?= number_format(count($paymentDueQueue)) ?></div>
            <span class="text-xs text-rose-700 mt-1 block font-medium group-hover:underline">Delivered Unsettled &darr;</span>
        </a>
    </div>

    <!-- Section 1: Pickup Requests Queue -->
    <div id="queue-pickups" class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-amber-50/40 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 font-display">1. Pickup Requests Queue</h2>
                <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full border border-amber-200"><?= count($pickupRequests) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Pending Customer Pickups</span>
        </div>

        <?php if (empty($pickupRequests)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-50 text-amber-600 mb-3 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No Pending Pickup Requests</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">All pickup requests for this branch have been accepted and dispatched.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[100px]">Job ID</th>
                            <th class="px-4 py-3 min-w-[140px]">Customer &amp; Mobile</th>
                            <th class="px-4 py-3 min-w-[100px] whitespace-nowrap">Pickup Date</th>
                            <th class="px-4 py-3 min-w-[130px]">Address</th>
                            <th class="px-4 py-3 min-w-[120px]">Instructions</th>
                            <th class="px-4 py-3 min-w-[110px]">Assignment</th>
                            <th class="px-4 py-3 min-w-[170px] text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($pickupRequests as $ord): 
                            $isAssignedToMe = ((int)($ord['staff_id'] ?? 0) === (int)$_SESSION['user_id']);
                            $isUnassigned   = empty($ord['staff_id']);
                            $nextStatus     = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 truncate max-w-[150px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="max-w-[140px] truncate" title="<?= Security::e($ord['customer_address'] ?? '') ?>">
                                        <?= Security::e($ord['customer_address'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    <div class="max-w-[130px] truncate" title="<?= Security::e($ord['special_instructions'] ?? '') ?>">
                                        <?= Security::e($ord['special_instructions'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if ($isUnassigned): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Unassigned</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-800 border border-blue-200 max-w-[110px] truncate" title="<?= Security::e($ord['staff_name']) ?>">
                                            <?= Security::e($ord['staff_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                    <?php if ($isUnassigned): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">
                                                Claim Order
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($nextStatus): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                                                <span>Mark <?= Security::e(str_replace('_', ' ', $nextStatus)) ?></span>
                                                <span aria-hidden="true">&rarr;</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: In-Shop Processing Queue -->
    <div id="queue-processing" class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-sky-50/40 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 font-display">2. In-Shop Processing Queue</h2>
                <span class="bg-sky-100 text-sky-800 text-xs font-bold px-2 py-0.5 rounded-full border border-sky-200"><?= count($processingQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Active Laundry Operations</span>
        </div>

        <?php if (empty($processingQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-sky-50 text-sky-600 mb-3 border border-sky-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No Orders in Processing</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">No orders are currently undergoing active washing, drying, or ironing.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[95px]">Job ID</th>
                            <th class="px-4 py-3 min-w-[130px]">Customer</th>
                            <th class="px-4 py-3 min-w-[120px]">Current Status</th>
                            <th class="px-4 py-3 min-w-[95px] whitespace-nowrap">Pickup Date</th>
                            <th class="px-4 py-3 min-w-[95px] whitespace-nowrap">Delivery Date</th>
                            <th class="px-4 py-3 min-w-[85px]">Total</th>
                            <th class="px-4 py-3 min-w-[85px]">Outstanding</th>
                            <th class="px-4 py-3 min-w-[180px] text-right whitespace-nowrap">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($processingQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                            $stBadge     = $getOrderStatusBadge((string)($ord['status'] ?? ''));
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 truncate max-w-[140px]" title="<?= Security::e($ord['customer_name']) ?>">
                                        <?= Security::e($ord['customer_name']) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900 tabular-nums whitespace-nowrap">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3 font-mono font-semibold whitespace-nowrap tabular-nums <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if ($ord['status'] === 'picked_up'): ?>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                            <span>Inspect &amp; Price</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    <?php elseif ($ord['status'] === 'inspection_completed'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 mr-1.5">
                                            Awaiting Approval
                                        </span>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition-colors border border-slate-200 inline-flex items-center focus:outline-none focus:ring-2 focus:ring-slate-400">
                                            Re-inspect / Edit
                                        </a>
                                    <?php elseif ($ord['status'] === 'price_confirmed'): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="processing">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                                                <span>Start Processing</span>
                                                <span aria-hidden="true">&rarr;</span>
                                            </button>
                                        </form>
                                    <?php elseif ($nextStatus): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                                <span>Advance to <?= Security::e(str_replace('_', ' ', $nextStatus)) ?></span>
                                                <span aria-hidden="true">&rarr;</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 3: Ready / Delivery Queue -->
    <div id="queue-delivery" class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-violet-50/40 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-violet-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 font-display">3. Ready / Delivery Queue</h2>
                <span class="bg-violet-100 text-violet-800 text-xs font-bold px-2 py-0.5 rounded-full border border-violet-200"><?= count($deliveryQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Ready for Dispatch or Out for Delivery</span>
        </div>

        <?php if (empty($deliveryQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-violet-50 text-violet-600 mb-3 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002 2V8m-9 4h4"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No Orders Ready for Delivery</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Orders will appear here once laundry processing is marked ready.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[100px]">Job ID</th>
                            <th class="px-4 py-3 min-w-[140px]">Customer &amp; Mobile</th>
                            <th class="px-4 py-3 min-w-[130px]">Delivery Address</th>
                            <th class="px-4 py-3 min-w-[100px] whitespace-nowrap">Delivery Date</th>
                            <th class="px-4 py-3 min-w-[90px]">Payment</th>
                            <th class="px-4 py-3 min-w-[90px]">Outstanding</th>
                            <th class="px-4 py-3 min-w-[160px] text-right whitespace-nowrap">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($deliveryQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 truncate max-w-[140px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="max-w-[140px] truncate" title="<?= Security::e($ord['customer_address'] ?? '') ?>">
                                        <?= Security::e($ord['customer_address'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Paid</span>
                                    <?php elseif ($ord['payment_status'] === 'partial'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Partial</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Due</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold whitespace-nowrap tabular-nums <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if ($nextStatus): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1">
                                                <span>Mark <?= Security::e(str_replace('_', ' ', $nextStatus)) ?></span>
                                                <span aria-hidden="true">&rarr;</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 4: Payment Due Queue -->
    <div id="queue-payment-due" class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-rose-50/40 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 font-display">4. Payment Due Queue</h2>
                <span class="bg-rose-100 text-rose-800 text-xs font-bold px-2 py-0.5 rounded-full border border-rose-200"><?= count($paymentDueQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Delivered Orders Pending Payment Settlement</span>
        </div>

        <?php if (empty($paymentDueQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-50 text-rose-600 mb-3 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">All Delivered Orders Settled</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">There are no delivered orders with outstanding balances.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[100px]">Job ID</th>
                            <th class="px-4 py-3 min-w-[150px]">Customer &amp; Mobile</th>
                            <th class="px-4 py-3 min-w-[95px]">Total</th>
                            <th class="px-4 py-3 min-w-[95px]">Amount Paid</th>
                            <th class="px-4 py-3 min-w-[110px]">Outstanding</th>
                            <th class="px-4 py-3 min-w-[90px]">Status</th>
                            <th class="px-4 py-3 min-w-[150px] text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($paymentDueQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 truncate max-w-[160px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-slate-800 whitespace-nowrap tabular-nums">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3 font-mono text-slate-600 whitespace-nowrap tabular-nums">₹<?= number_format((float)$ord['amount_paid'], 2) ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-600 whitespace-nowrap tabular-nums">₹<?= number_format($outstanding, 2) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 uppercase">
                                        <?= Security::e($ord['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors inline-flex items-center gap-1 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1">
                                        <span>Record Payment</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
