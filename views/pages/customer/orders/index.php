<?php
use Faizal\LaundryLms\Helpers\Security;
?>

<div class="space-y-6">

    <!-- Page Header & Filter Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 font-display">My Orders</h1>
            <p class="text-sm text-slate-500">View and track the status of all your laundry service orders.</p>
        </div>

        <div class="flex items-center space-x-2 bg-slate-200/60 p-1 rounded-lg self-start">
            <a href="/laundry-lms/customer/orders?filter=all" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors <?= $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                All
            </a>
            <a href="/laundry-lms/customer/orders?filter=active" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors <?= $filter === 'active' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                Active
            </a>
            <a href="/laundry-lms/customer/orders?filter=completed" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors <?= $filter === 'completed' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                Completed
            </a>
            <a href="/laundry-lms/customer/orders?filter=pending" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors <?= $filter === 'pending' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                Payment Pending
            </a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($orders)): ?>
            <div class="p-12 text-center text-slate-500">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-base font-semibold text-slate-700">No orders found matching filter "<?= Security::e($filter) ?>".</p>
                <p class="text-xs text-slate-400 mt-1">Try selecting a different filter or submit a new pickup request.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-medium text-xs uppercase tracking-wider">
                            <th class="py-3.5 px-4">Job ID</th>
                            <th class="py-3.5 px-4">Order Date</th>
                            <th class="py-3.5 px-4">Pickup Date</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Total</th>
                            <th class="py-3.5 px-4">Paid</th>
                            <th class="py-3.5 px-4">Outstanding</th>
                            <th class="py-3.5 px-4">Payment Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($orders as $ord): 
                            $outstanding = round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900"><?= Security::e($ord['job_id']) ?></td>
                                <td class="py-3.5 px-4 text-slate-600"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                <td class="py-3.5 px-4 text-slate-600"><?= !empty($ord['pickup_date']) ? date('M d, Y', strtotime($ord['pickup_date'])) : '-' ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 capitalize">
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="py-3.5 px-4 text-slate-600">₹<?= number_format((float)$ord['amount_paid'], 2) ?></td>
                                <td class="py-3.5 px-4 font-semibold <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Paid</span>
                                    <?php elseif ($ord['payment_status'] === 'partial'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Partial</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded font-medium text-xs transition-colors">
                                        View Details
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
