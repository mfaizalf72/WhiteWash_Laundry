<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Orders Directory</h2>
            <p class="text-sm text-slate-500 mt-1">Track and manage laundry service orders.</p>
        </div>
        <a href="/laundry-lms/orders/create" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Order
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="/laundry-lms/orders" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by Job ID, customer name, or mobile..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-slate-900">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <select name="status" class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-slate-900">
                <option value="">All Statuses</option>
                <option value="pickup_request" <?= ($status ?? '') === 'pickup_request' ? 'selected' : '' ?>>Pickup Request</option>
                <option value="accepted" <?= ($status ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="picked_up" <?= ($status ?? '') === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                <option value="inspection_completed" <?= ($status ?? '') === 'inspection_completed' ? 'selected' : '' ?>>Inspection Completed</option>
                <option value="price_confirmed" <?= ($status ?? '') === 'price_confirmed' ? 'selected' : '' ?>>Price Confirmed</option>
                <option value="processing" <?= ($status ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="ready" <?= ($status ?? '') === 'ready' ? 'selected' : '' ?>>Ready</option>
                <option value="out_for_delivery" <?= ($status ?? '') === 'out_for_delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="completed" <?= ($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">Filter</button>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="/laundry-lms/orders" class="bg-slate-50 hover:bg-slate-100 text-slate-500 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Job ID</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Pickup Date</th>
                        <th class="px-6 py-4">Delivery Date</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Total Amount</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No orders found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="/laundry-lms/orders/show?id=<?= $o['id'] ?>" class="font-mono font-bold text-slate-900 hover:text-indigo-600">
                                        <?= htmlspecialchars($o['job_id']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($o['customer_name']) ?></div>
                                    <div class="text-xs font-mono text-slate-500"><?= htmlspecialchars($o['customer_mobile']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-xs"><?= htmlspecialchars(date('M d, Y', strtotime($o['pickup_date']))) ?></td>
                                <td class="px-6 py-4 text-slate-600 text-xs"><?= !empty($o['delivery_date']) ? htmlspecialchars(date('M d, Y', strtotime($o['delivery_date']))) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= str_replace('_', ' ', htmlspecialchars($o['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-900">
                                    <div class="font-bold">₹<?= number_format((float)$o['total_amount'], 2) ?></div>
                                    <div class="text-xs font-sans mt-0.5">
                                        <?php if ($o['payment_status'] === 'paid'): ?>
                                            <span class="text-emerald-600 font-semibold">Paid</span>
                                        <?php elseif ($o['payment_status'] === 'partial'): ?>
                                            <span class="text-amber-600 font-semibold">Partial (₹<?= number_format((float)$o['amount_paid'], 2) ?>)</span>
                                        <?php else: ?>
                                            <span class="text-rose-500 font-semibold">Unpaid</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/laundry-lms/orders/show?id=<?= $o['id'] ?>" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-100 transition-colors">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
