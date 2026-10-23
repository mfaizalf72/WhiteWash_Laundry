<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;

$page          = (int)($page ?? 1);
$totalPages    = (int)($totalPages ?? 1);
$totalOrders   = (int)($totalOrders ?? 0);
$status        = $status ?? '';
$search        = $search ?? '';
$pickupToday   = !empty($pickupToday);
$deliveryToday = !empty($deliveryToday);
$paymentDue    = !empty($paymentDue);

// Build filter query string for pagination links.
// Strips empty values from the base $_GET before applying overrides so that stale
// parameters (e.g. &search= or &status=) are not forwarded to pagination URLs.
// Returns a raw URL query string (not HTML-escaped); callers must use Security::e().
if (!function_exists('buildQueryString')) {
    function buildQueryString(array $override = []): string {
        $params = array_filter($_GET, fn($v) => $v !== '');
        foreach ($override as $k => $v) {
            if ($v === null || $v === false || $v === '') {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        return http_build_query($params);
    }
}

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

    <!-- Directory Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 font-display">Orders Directory</h1>
            <p class="text-sm text-slate-500 mt-1">Total matching orders: <strong class="font-mono font-bold text-slate-900"><?= number_format($totalOrders) ?></strong></p>
        </div>
        <a href="/laundry-lms/orders/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Create New Order</span>
        </a>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
        <form action="/laundry-lms/orders" method="GET" class="flex flex-col sm:flex-row gap-3">
            <?php if ($pickupToday): ?>
                <input type="hidden" name="pickup_today" value="1">
            <?php endif; ?>
            <?php if ($deliveryToday): ?>
                <input type="hidden" name="delivery_today" value="1">
            <?php endif; ?>
            <?php if ($paymentDue): ?>
                <input type="hidden" name="payment_due" value="1">
            <?php endif; ?>

            <div class="relative flex-1">
                <input type="text" name="search" value="<?= Security::e($search) ?>" placeholder="Search by Job ID, customer name, or mobile..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 outline-none shadow-sm">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            
            <select name="status" onchange="this.form.submit()" aria-label="Filter by status" class="px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 bg-white outline-none cursor-pointer shadow-sm">
                <option value="">All Statuses</option>
                <option value="pickup_request" <?= $status === 'pickup_request' ? 'selected' : '' ?>>Pickup Request</option>
                <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="picked_up" <?= $status === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                <option value="inspection_completed" <?= $status === 'inspection_completed' ? 'selected' : '' ?>>Inspection Completed</option>
                <option value="price_confirmed" <?= $status === 'price_confirmed' ? 'selected' : '' ?>>Price Confirmed</option>
                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
                <option value="out_for_delivery" <?= $status === 'out_for_delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <?php if (empty($isStaff)): ?>
                <select name="branch_id" onchange="this.form.submit()" aria-label="Filter by branch" class="px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 bg-white outline-none cursor-pointer shadow-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches ?? [] as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= ((int)($selectedBranchId ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                            <?= Security::e($b['name']) ?> (<?= Security::e($b['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Filter</button>
                <?php if (!empty($search) || !empty($status) || !empty($selectedBranchId) || $pickupToday || $deliveryToday || $paymentDue): ?>
                    <a href="/laundry-lms/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3.5 py-2 rounded-lg text-sm font-medium transition-colors border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-400">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Quick Filter Toggle Chips -->
        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100 text-xs">
            <span class="font-semibold text-slate-500 mr-1">Quick Filters:</span>
            
            <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['pickup_today' => $pickupToday ? null : '1', 'page' => 1])) ?>"
               class="px-3 py-1 rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 <?= $pickupToday ? 'bg-amber-100 border-amber-300 text-amber-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                <?= $pickupToday ? '✓ ' : '' ?>Today's Pickups
            </a>

            <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['delivery_today' => $deliveryToday ? null : '1', 'page' => 1])) ?>"
               class="px-3 py-1 rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 <?= $deliveryToday ? 'bg-purple-100 border-purple-300 text-purple-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                <?= $deliveryToday ? '✓ ' : '' ?>Today's Deliveries
            </a>

            <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['payment_due' => $paymentDue ? null : '1', 'page' => 1])) ?>"
               class="px-3 py-1 rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 <?= $paymentDue ? 'bg-rose-100 dark:bg-rose-900/60 border-rose-300 dark:border-rose-700 text-rose-900 dark:text-rose-100 font-bold' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                <?= $paymentDue ? '✓ ' : '' ?>Payment Due
            </a>

            <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['unassigned' => $unassigned ? null : '1', 'page' => 1])) ?>"
               class="px-3 py-1 rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 <?= $unassigned ? 'bg-amber-100 dark:bg-amber-900/60 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-100 font-bold' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                <?= $unassigned ? '✓ ' : '' ?>Unassigned Orders
            </a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2.5 min-w-[95px]">Order / Job ID</th>
                        <th class="px-2 py-2.5 min-w-[65px]">Branch</th>
                        <th class="px-3 py-2.5 min-w-[120px]">Customer</th>
                        <th class="px-3 py-2.5 min-w-[105px] whitespace-nowrap">Schedule</th>
                        <th class="px-3 py-2.5 min-w-[100px]">Status</th>
                        <th class="px-3 py-2.5 min-w-[75px]">Total</th>
                        <th class="px-3 py-2.5 min-w-[85px]">Outstanding</th>
                        <th class="px-3 py-2.5 min-w-[155px]">Assigned Staff</th>
                        <th class="px-3 py-2.5 min-w-[110px] text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3 border border-slate-200 dark:border-slate-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Orders Found</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">No orders match your active search or filter criteria. Try adjusting your filters.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): 
                            $outstanding = max(0, round((float)$o['total_amount'] - (float)$o['amount_paid'], 2));
                            $stBadge     = $getOrderStatusBadge((string)($o['status'] ?? ''));
                            $isUnassigned = empty($o['staff_id']);
                            $isMyOrder   = ((int)($o['staff_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0));
                        ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-3 py-2.5 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$o['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($o['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-2 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="<?= Security::e($o['branch_name'] ?? '') ?>">
                                        <?= Security::e($o['branch_code'] ?? ($o['branch_name'] ?? 'MAIN')) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[130px]" title="<?= Security::e($o['customer_name']) ?>"><?= Security::e($o['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= Security::e($o['customer_mobile']) ?></div>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <div class="text-[11px] font-medium text-slate-800 dark:text-slate-200">
                                        <span class="text-slate-400 dark:text-slate-500">Pick:</span> <?= Security::e(date('M d, Y', strtotime($o['pickup_date']))) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                        <span class="text-slate-400 dark:text-slate-500">Deliv:</span> <?= !empty($o['delivery_date']) ? Security::e(date('M d, Y', strtotime($o['delivery_date']))) : '<em class="text-slate-400">N/A</em>' ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <?= Security::e(str_replace('_', ' ', $o['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums whitespace-nowrap">
                                    ₹<?= number_format((float)$o['total_amount'], 2) ?>
                                </td>
                                <td class="px-3 py-2.5 font-mono font-semibold whitespace-nowrap tabular-nums <?= $outstanding > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                        <?php if ($o['status'] === 'cancelled'): ?>
                                            <span class="text-slate-400 text-xs italic">N/A (Cancelled)</span>
                                        <?php else: ?>
                                            <form action="/laundry-lms/orders/assign" method="POST" class="flex items-center gap-1.5">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="<?= Security::e($_SERVER['REQUEST_URI'] ?? '/orders') ?>">
                                                <select name="staff_id" aria-label="Assign staff to <?= Security::e($o['job_id']) ?>" class="text-xs py-1 px-1.5 rounded-lg border <?= $isUnassigned ? 'border-amber-300 dark:border-amber-700 bg-amber-50/60 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 font-semibold' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200' ?> shadow-2xs focus:ring-2 focus:ring-blue-500 cursor-pointer max-w-[125px]">
                                                    <option value="">-- Unassigned --</option>
                                                    <?php foreach (($staffList ?? []) as $st): ?>
                                                        <option value="<?= (int)$st['id'] ?>" <?= ((int)($o['staff_id'] ?? 0) === (int)$st['id']) ? 'selected' : '' ?>>
                                                            <?= Security::e($st['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer" title="Save staff assignment">
                                                    <?= $isUnassigned ? 'Assign' : 'Save' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: /* Staff */ ?>
                                        <?php if ($isUnassigned): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Unassigned</span>
                                        <?php elseif ($isMyOrder): ?>
                                            <span class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800 text-[10px] font-semibold" title="Assigned to you">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                You
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-800 dark:text-slate-200 font-medium text-xs max-w-[125px] truncate block" title="<?= Security::e($o['staff_name']) ?>"><?= Security::e($o['staff_name']) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5 text-right whitespace-nowrap space-x-1">
                                    <?php if (($_SESSION['user_role'] ?? '') === 'staff' && $isUnassigned && !in_array($o['status'], ['completed', 'delivered', 'cancelled'], true)): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="<?= Security::e($_SERVER['REQUEST_URI'] ?? '/orders') ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer" title="Self-claim this order">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                <span>Claim</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$o['id'] ?>" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="px-5 py-4 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span class="text-slate-500 font-medium">
                    Showing Page <strong class="font-mono text-slate-800"><?= $page ?></strong> of <strong class="font-mono text-slate-800"><?= $totalPages ?></strong> (Total <?= number_format($totalOrders) ?> orders)
                </span>
                
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['page' => $page - 1])) ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-100 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            &larr; Previous
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/laundry-lms/orders?<?= Security::e(buildQueryString(['page' => $page + 1])) ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-100 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
