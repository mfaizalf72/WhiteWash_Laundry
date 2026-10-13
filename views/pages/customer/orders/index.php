<?php
use Faizal\LaundryLms\Helpers\Security;

// Semantic status badge styling helper matching Phase 19 standard palette
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pickup_request'       => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'accepted'             => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'picked_up'            => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'inspection_completed' => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'price_confirmed'      => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'processing'           => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'ready'                => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200',  'dot' => 'bg-violet-500'],
        'out_for_delivery'     => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200',  'dot' => 'bg-violet-500'],
        'delivered'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        'completed'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        'cancelled'            => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500'],
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'dot' => 'bg-slate-500'];
};

// Payment status badge helper with dot indicator
$getPaymentStatusBadge = function(string $payStatus): array {
    $map = [
        'paid'    => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Paid'],
        'partial' => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500',   'label' => 'Partial'],
        'unpaid'  => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500',    'label' => 'Unpaid'],
    ];
    return $map[$payStatus] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'dot' => 'bg-slate-500', 'label' => ucfirst($payStatus)];
};
?>

<div class="space-y-6">

    <!-- Page Header & Filter Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 font-display">My Orders</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">View and track the real-time status of all your laundry service orders.</p>
        </div>
        <a href="/laundry-lms/customer/orders/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Request Pickup</span>
        </a>
    </div>

    <!-- Filter Pills Navigation -->
    <div class="flex flex-wrap items-center gap-2 bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-sm" role="navigation" aria-label="Order Status Filters">
        <a href="/laundry-lms/customer/orders?filter=all"
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $filter === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>"
           <?= $filter === 'all' ? 'aria-current="true"' : '' ?>>
            All Orders
        </a>
        <a href="/laundry-lms/customer/orders?filter=active"
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $filter === 'active' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-blue-700 hover:bg-blue-50' ?>"
           <?= $filter === 'active' ? 'aria-current="true"' : '' ?>>
            Active
        </a>
        <a href="/laundry-lms/customer/orders?filter=completed"
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $filter === 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-emerald-700 hover:bg-emerald-50' ?>"
           <?= $filter === 'completed' ? 'aria-current="true"' : '' ?>>
            Completed
        </a>
        <a href="/laundry-lms/customer/orders?filter=pending"
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors <?= $filter === 'pending' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:text-rose-700 hover:bg-rose-50' ?>"
           <?= $filter === 'pending' ? 'aria-current="true"' : '' ?>>
            Payment Pending
        </a>
    </div>

    <!-- Orders Table Container -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <?php if (empty($orders)): ?>
            <div class="p-12 text-center text-slate-500">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 border border-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No orders found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">No orders match the "<?= Security::e($filter) ?>" filter. Try selecting another filter or create a new pickup request.</p>
                <div class="mt-4">
                    <a href="/laundry-lms/customer/orders/create" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                        + Request Pickup
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 min-w-[120px]">Job ID</th>
                            <th class="py-3 px-4 min-w-[110px] whitespace-nowrap">Order Date</th>
                            <th class="py-3 px-4 min-w-[110px] whitespace-nowrap">Pickup Date</th>
                            <th class="py-3 px-4 min-w-[140px]">Status</th>
                            <th class="py-3 px-4 min-w-[95px] text-right whitespace-nowrap">Total</th>
                            <th class="py-3 px-4 min-w-[95px] text-right whitespace-nowrap">Paid</th>
                            <th class="py-3 px-4 min-w-[105px] text-right whitespace-nowrap">Outstanding</th>
                            <th class="py-3 px-4 min-w-[120px]">Payment Status</th>
                            <th class="py-3 px-4 min-w-[110px] text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($orders as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $stBadge     = $getOrderStatusBadge((string)($ord['status'] ?? ''));
                            $payBadge    = $getPaymentStatusBadge((string)($ord['payment_status'] ?? 'unpaid'));
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-blue-600 whitespace-nowrap">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap"><?= !empty($ord['pickup_date']) ? date('M d, Y', strtotime($ord['pickup_date'])) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $stBadge['dot'] ?>"></span>
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 tabular-nums text-right whitespace-nowrap">
                                    ₹<?= number_format((float)$ord['total_amount'], 2) ?>
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-slate-700 tabular-nums text-right whitespace-nowrap">
                                    ₹<?= number_format((float)$ord['amount_paid'], 2) ?>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold tabular-nums text-right whitespace-nowrap <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-600' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider border <?= $payBadge['bg'] ?> <?= $payBadge['text'] ?> <?= $payBadge['border'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $payBadge['dot'] ?>"></span>
                                        <?= Security::e($payBadge['label']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
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
