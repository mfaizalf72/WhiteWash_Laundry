<?php
use Faizal\LaundryLms\Helpers\Security;

// Check if customer is using default PIN 1234
$isDefaultPin = !empty($customer['pin_hash']) && password_verify('1234', $customer['pin_hash']);

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

// Payment status badge helper with dot indicator & outstanding balance display
$getPaymentStatusBadge = function(string $payStatus, float $outstanding): array {
    if ($payStatus === 'paid') {
        return [
            'bg'     => 'bg-emerald-50',
            'text'   => 'text-emerald-700',
            'border' => 'border-emerald-200',
            'dot'    => 'bg-emerald-500',
            'label'  => 'Paid'
        ];
    }
    if ($payStatus === 'partial') {
        return [
            'bg'     => 'bg-amber-50',
            'text'   => 'text-amber-700',
            'border' => 'border-amber-200',
            'dot'    => 'bg-amber-500',
            'label'  => 'Partial: Due ₹' . number_format($outstanding, 2)
        ];
    }
    return [
        'bg'     => 'bg-rose-50',
        'text'   => 'text-rose-700',
        'border' => 'border-rose-200',
        'dot'    => 'bg-rose-500',
        'label'  => ($outstanding > 0) ? 'Due: ₹' . number_format($outstanding, 2) : 'Unpaid'
    ];
};
?>

<div class="space-y-6">

    <!-- Top Personalized Header Card -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 font-display">
                Welcome back, <?= Security::e($customer['name'] ?? 'Customer') ?>!
            </h1>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-slate-500">
                <?php if (!empty($customer['mobile'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200/80">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="font-mono"><?= Security::e($customer['mobile']) ?></span>
                    </span>
                <?php endif; ?>
                <span>Track your laundry orders, account balance, and pickup requests.</span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/laundry-lms/customer/orders/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm inline-flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Request Pickup</span>
            </a>
            <a href="/laundry-lms/customer/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 inline-flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <span>View All Orders</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>

    <!-- Default PIN Warning Banner -->
    <?php if ($isDefaultPin): ?>
    <div class="bg-amber-50 border border-amber-200/90 p-4 sm:p-5 rounded-xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" role="alert">
        <div class="flex items-start gap-3">
            <div class="p-2 bg-amber-100 text-amber-700 rounded-lg shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-900">Security Recommendation: Default PIN Active</h4>
                <p class="text-xs sm:text-sm text-amber-800 mt-0.5">Your account is currently secured with the default 4-digit PIN (1234). We strongly recommend setting a personal PIN to protect your account and order history.</p>
            </div>
        </div>
        <a href="/laundry-lms/customer/profile" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors whitespace-nowrap shrink-0 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
            Change PIN &rarr;
        </a>
    </div>
    <?php endif; ?>

    <!-- Interactive KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Orders -->
        <a href="/laundry-lms/customer/orders?filter=all" aria-label="View all orders" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm hover:border-blue-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-2 tabular-nums"><?= number_format((int)($kpis['total'] ?? 0)) ?></div>
            <span class="text-xs text-blue-600 mt-1 block font-medium group-hover:underline">All order history &rarr;</span>
        </a>

        <!-- Active Orders -->
        <a href="/laundry-lms/customer/orders?filter=active" aria-label="View active orders" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm hover:border-amber-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-amber-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Orders</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg group-hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-amber-600 mt-2 tabular-nums"><?= number_format((int)($kpis['active'] ?? 0)) ?></div>
            <span class="text-xs text-amber-700 mt-1 block font-medium group-hover:underline">In-progress orders &rarr;</span>
        </a>

        <!-- Completed Orders -->
        <a href="/laundry-lms/customer/orders?filter=completed" aria-label="View completed orders" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm hover:border-emerald-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed Orders</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-emerald-600 mt-2 tabular-nums"><?= number_format((int)($kpis['completed'] ?? 0)) ?></div>
            <span class="text-xs text-emerald-700 mt-1 block font-medium group-hover:underline">Delivered orders &rarr;</span>
        </a>

        <!-- Outstanding Balance -->
        <a href="/laundry-lms/customer/orders?filter=pending" aria-label="View orders with pending payments" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm hover:border-rose-300 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-rose-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Outstanding Balance</span>
                <div class="p-2 bg-rose-50 text-rose-600 rounded-lg group-hover:bg-rose-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-rose-600 mt-2 tabular-nums">₹<?= number_format((float)($kpis['outstanding'] ?? 0), 2) ?></div>
            <span class="text-xs text-rose-700 mt-1 block font-medium group-hover:underline">Pending payments &rarr;</span>
        </a>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 font-display">Recent Orders</h2>
                <p class="text-xs text-slate-500 mt-0.5">Your latest laundry and dry cleaning requests.</p>
            </div>
            <a href="/laundry-lms/customer/orders" class="text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                <span>View All Orders</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="p-8 sm:p-12 text-center">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 font-display">No Orders Placed Yet</h3>
                <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-5">Welcome to Launder! Request your first laundry pickup to have your garments professionally cleaned and delivered.</p>
                <a href="/laundry-lms/customer/orders/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Request Your First Pickup</span>
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-semibold text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 min-w-[130px]">Job ID</th>
                            <th class="py-3 px-4 min-w-[110px]">Order Date</th>
                            <th class="py-3 px-4 min-w-[150px]">Status</th>
                            <th class="py-3 px-4 min-w-[120px] text-right">Total Amount</th>
                            <th class="py-3 px-4 min-w-[170px]">Payment</th>
                            <th class="py-3 px-4 min-w-[110px] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($recentOrders as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $stBadge = $getOrderStatusBadge((string)($ord['status'] ?? ''));
                            $payBadge = $getPaymentStatusBadge((string)($ord['payment_status'] ?? 'unpaid'), $outstanding);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="font-mono font-semibold text-blue-600 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    <?= date('M d, Y', strtotime($ord['created_at'])) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $stBadge['dot'] ?>" aria-hidden="true"></span>
                                        <span><?= Security::e(ucwords(str_replace('_', ' ', $ord['status']))) ?></span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-mono font-medium tabular-nums text-slate-900">₹<?= number_format((float)$ord['total_amount'], 2) ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $payBadge['bg'] ?> <?= $payBadge['text'] ?> <?= $payBadge['border'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $payBadge['dot'] ?>" aria-hidden="true"></span>
                                        <span><?= Security::e($payBadge['label']) ?></span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <span>Details</span>
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
