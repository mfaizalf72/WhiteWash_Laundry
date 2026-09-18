<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;

$page         = (int)($page ?? 1);
$totalPages   = (int)($totalPages ?? 1);
$totalOrders  = (int)($totalOrders ?? 0);
$status       = $status ?? '';
$search       = $search ?? '';
$pickupToday  = !empty($pickupToday);
$deliveryToday= !empty($deliveryToday);
$paymentDue   = !empty($paymentDue);

// Build filter query string for pagination links
function buildQueryString(array $override = []): string {
    $params = $_GET;
    foreach ($override as $k => $v) {
        if ($v === null || $v === false || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return http_build_query($params);
}
?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <!-- Directory Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Orders Directory</h2>
            <p class="text-sm text-slate-500 mt-1">Total matching orders: <strong><?= $totalOrders ?></strong></p>
        </div>
        <a href="/laundry-lms/orders/create" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Order
        </a>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
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
                <input type="text" name="search" value="<?= Security::e($search) ?>" placeholder="Search by Job ID, customer name, or mobile..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            
            <select name="status" onchange="this.form.submit()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 bg-white outline-none cursor-pointer">
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
            </select>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors shadow-sm">Filter</button>
            
            <?php if (!empty($search) || !empty($status) || $pickupToday || $deliveryToday || $paymentDue): ?>
                <a href="/laundry-lms/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Quick Filter Toggle Chips -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs">
            <span class="font-semibold text-slate-500 mr-1">Quick Filters:</span>
            
            <a href="/laundry-lms/orders?<?= buildQueryString(['pickup_today' => $pickupToday ? null : '1', 'page' => 1]) ?>"
               class="px-3 py-1 rounded-full border transition-colors <?= $pickupToday ? 'bg-amber-100 border-amber-300 text-amber-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                <?= $pickupToday ? '✓ ' : '' ?>Today's Pickups
            </a>

            <a href="/laundry-lms/orders?<?= buildQueryString(['delivery_today' => $deliveryToday ? null : '1', 'page' => 1]) ?>"
               class="px-3 py-1 rounded-full border transition-colors <?= $deliveryToday ? 'bg-purple-100 border-purple-300 text-purple-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                <?= $deliveryToday ? '✓ ' : '' ?>Today's Deliveries
            </a>

            <a href="/laundry-lms/orders?<?= buildQueryString(['payment_due' => $paymentDue ? null : '1', 'page' => 1]) ?>"
               class="px-3 py-1 rounded-full border transition-colors <?= $paymentDue ? 'bg-rose-100 border-rose-300 text-rose-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                <?= $paymentDue ? '✓ ' : '' ?>Payment Due
            </a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Job ID</th>
                        <th class="px-5 py-3.5">Customer & Mobile</th>
                        <th class="px-5 py-3.5">Pickup Date</th>
                        <th class="px-5 py-3.5">Delivery Date</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Total</th>
                        <th class="px-5 py-3.5">Outstanding</th>
                        <th class="px-5 py-3.5">Assigned Staff</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                No orders found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): 
                            $outstanding = max(0, round((float)$o['total_amount'] - (float)$o['amount_paid'], 2));
                        ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5 font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$o['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= Security::e($o['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900"><?= Security::e($o['customer_name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= Security::e($o['customer_mobile']) ?></div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 text-xs"><?= Security::e(date('M d, Y', strtotime($o['pickup_date']))) ?></td>
                                <td class="px-5 py-3.5 text-slate-600 text-xs"><?= !empty($o['delivery_date']) ? Security::e(date('M d, Y', strtotime($o['delivery_date']))) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= Security::e(str_replace('_', ' ', $o['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-slate-900">
                                    ₹<?= number_format((float)$o['total_amount'], 2) ?>
                                </td>
                                <td class="px-5 py-3.5 font-semibold <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <?php if (!empty($o['staff_name'])): ?>
                                        <span class="text-slate-800 font-medium"><?= Security::e($o['staff_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-medium">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-right space-x-1">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$o['id'] ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-xs bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg border border-blue-100 transition-colors">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <span class="text-slate-500 font-medium">
                    Showing Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (Total <?= $totalOrders ?> orders)
                </span>
                
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/laundry-lms/orders?<?= buildQueryString(['page' => $page - 1]) ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg font-semibold hover:bg-slate-100 transition-colors">
                            &larr; Previous
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/laundry-lms/orders?<?= buildQueryString(['page' => $page + 1]) ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg font-semibold hover:bg-slate-100 transition-colors">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
