<?php
use Faizal\LaundryLms\Helpers\Security;

// Check if customer is using default PIN 1234
$isDefaultPin = !empty($customer['pin_hash']) && password_verify('1234', $customer['pin_hash']);
?>

<div class="space-y-6">

    <!-- Top Greeting & Warning Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 font-display">Dashboard Overview</h1>
            <p class="text-sm text-slate-500">Track your laundry orders, account balance, and pickup requests.</p>
        </div>
        <div>
            <a href="/laundry-lms/customer/orders/create" class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Request New Pickup
            </a>
        </div>
    </div>

    <?php if ($isDefaultPin): ?>
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r shadow-sm flex items-start justify-between">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h4 class="text-sm font-semibold text-amber-800">Security Recommendation: Default PIN Active</h4>
                <p class="text-xs text-amber-700 mt-1">Your account is currently secured with the default 4-digit PIN (1234). We strongly recommend changing your PIN from your profile page.</p>
            </div>
        </div>
        <a href="/laundry-lms/customer/profile" class="text-xs font-semibold text-amber-800 underline hover:text-amber-900 shrink-0 ml-4">
            Change PIN
        </a>
    </div>
    <?php endif; ?>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Orders -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Orders</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-slate-800"><?= (int)($kpis['total'] ?? 0) ?></span>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Orders</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-amber-600"><?= (int)($kpis['active'] ?? 0) ?></span>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Completed Orders</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-emerald-600"><?= (int)($kpis['completed'] ?? 0) ?></span>
            </div>
        </div>

        <!-- Outstanding Balance -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Outstanding Balance</span>
                <div class="p-2 bg-rose-50 text-rose-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-rose-600">₹<?= number_format((float)($kpis['outstanding'] ?? 0), 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800 font-display">Recent Orders</h2>
            <a href="/laundry-lms/customer/orders" class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">View All Orders &rarr;</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                <p>No orders found. Click "Request New Pickup" to start your first laundry order!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-medium text-xs uppercase tracking-wider">
                            <th class="py-3 px-4">Job ID</th>
                            <th class="py-3 px-4">Order Date</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Total Amount</th>
                            <th class="py-3 px-4">Payment</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($recentOrders as $ord): 
                            $outstanding = round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900"><?= Security::e($ord['job_id']) ?></td>
                                <td class="py-3 px-4 text-slate-600"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 capitalize">
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Paid</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Due: ₹<?= number_format($outstanding, 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/laundry-lms/customer/orders/show?id=<?= (int)$ord['id'] ?>" class="text-blue-600 hover:underline font-medium text-xs">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
