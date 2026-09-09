<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Models\Order;

$pickupRequests  = $queues['pickup_requests'] ?? [];
$processingQueue = $queues['processing'] ?? [];
$deliveryQueue   = $queues['delivery'] ?? [];
$paymentDueQueue = $queues['payment_due'] ?? [];
?>

<div class="space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 font-display">Staff Operations Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Manage daily pickup requests, processing workflows, deliveries, and payment settlements.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/laundry-lms/orders/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm inline-flex items-center">
                + Create Order
            </a>
            <a href="/laundry-lms/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold transition-colors">
                View All Orders &rarr;
            </a>
        </div>
    </div>

    <!-- Section A: Pickup Requests Queue -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-amber-50/50 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <h2 class="text-lg font-bold text-slate-900 font-display">1. Pickup Requests Queue</h2>
                <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($pickupRequests) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Pending Customer Pickups</span>
        </div>

        <?php if (empty($pickupRequests)): ?>
            <div class="p-6 text-center text-slate-400 text-sm">No pending pickup requests at this time.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Job ID</th>
                            <th class="px-4 py-3">Customer & Mobile</th>
                            <th class="px-4 py-3">Pickup Date</th>
                            <th class="px-4 py-3">Address</th>
                            <th class="px-4 py-3">Instructions</th>
                            <th class="px-4 py-3">Assignment</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($pickupRequests as $ord): 
                            $isAssignedToMe = ((int)($ord['staff_id'] ?? 0) === (int)$_SESSION['user_id']);
                            $isUnassigned   = empty($ord['staff_id']);
                            $nextStatus     = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 font-medium"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-4 py-3.5 text-slate-600 text-xs max-w-xs truncate"><?= Security::e($ord['customer_address'] ?? 'N/A') ?></td>
                                <td class="px-4 py-3.5 text-slate-500 text-xs max-w-xs truncate"><?= Security::e($ord['special_instructions'] ?? '-') ?></td>
                                <td class="px-4 py-3.5">
                                    <?php if ($isUnassigned): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Unassigned</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Assigned: <?= Security::e($ord['staff_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 text-right space-x-2">
                                    <?php if ($isUnassigned): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">
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
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">
                                                Mark <?= Security::e(str_replace('_', ' ', $nextStatus)) ?> &rarr;
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

    <!-- Section B: In-Shop Processing Queue -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-blue-50/50 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <h2 class="text-lg font-bold text-slate-900 font-display">2. In-Shop Processing Queue</h2>
                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($processingQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Active Laundry Operations</span>
        </div>

        <?php if (empty($processingQueue)): ?>
            <div class="p-6 text-center text-slate-400 text-sm">No orders currently in processing.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Job ID</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Current Status</th>
                            <th class="px-4 py-3">Pickup Date</th>
                            <th class="px-4 py-3">Delivery Date</th>
                            <th class="px-4 py-3">Total Amount</th>
                            <th class="px-4 py-3">Outstanding</th>
                            <th class="px-4 py-3 text-right">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($processingQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-900"><?= Security::e($ord['customer_name']) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 capitalize">
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-4 py-3.5 text-slate-600"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '-' ?></td>
                                <td class="px-4 py-3.5 font-semibold text-slate-800">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3.5 font-semibold <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php if ($nextStatus): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">
                                                Advance to <?= Security::e(str_replace('_', ' ', $nextStatus)) ?> &rarr;
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

    <!-- Section C: Ready / Delivery Queue -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-purple-50/50 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                <h2 class="text-lg font-bold text-slate-900 font-display">3. Ready / Delivery Queue</h2>
                <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($deliveryQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Ready for Dispatch or Out for Delivery</span>
        </div>

        <?php if (empty($deliveryQueue)): ?>
            <div class="p-6 text-center text-slate-400 text-sm">No orders in ready or delivery queue.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Job ID</th>
                            <th class="px-4 py-3">Customer & Mobile</th>
                            <th class="px-4 py-3">Delivery Address</th>
                            <th class="px-4 py-3">Delivery Date</th>
                            <th class="px-4 py-3">Payment Status</th>
                            <th class="px-4 py-3">Outstanding</th>
                            <th class="px-4 py-3 text-right">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($deliveryQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 max-w-xs truncate"><?= Security::e($ord['customer_address'] ?? 'N/A') ?></td>
                                <td class="px-4 py-3.5 text-slate-600 font-medium"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '-' ?></td>
                                <td class="px-4 py-3.5">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Paid</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Due</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 font-semibold <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php if ($nextStatus): ?>
                                        <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">
                                                Mark <?= Security::e(str_replace('_', ' ', $nextStatus)) ?> &rarr;
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

    <!-- Section D: Payment Due Queue -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-rose-50/50 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <h2 class="text-lg font-bold text-slate-900 font-display">4. Payment Due Queue</h2>
                <span class="bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($paymentDueQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 font-medium">Delivered Orders Pending Payment Settlement</span>
        </div>

        <?php if (empty($paymentDueQueue)): ?>
            <div class="p-6 text-center text-slate-400 text-sm">No delivered orders with pending payment.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Job ID</th>
                            <th class="px-4 py-3">Customer & Mobile</th>
                            <th class="px-4 py-3">Total Amount</th>
                            <th class="px-4 py-3">Amount Paid</th>
                            <th class="px-4 py-3">Outstanding Balance</th>
                            <th class="px-4 py-3">Payment Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($paymentDueQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-slate-800">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3.5 text-slate-600">₹<?= number_format((float)$ord['amount_paid'], 2) ?></td>
                                <td class="px-4 py-3.5 font-bold text-rose-600">₹<?= number_format($outstanding, 2) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 uppercase">
                                        <?= Security::e($ord['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-semibold shadow-sm transition-colors inline-block">
                                        Record Payment &rarr;
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
