<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Models\Order;

$pickupRequests   = $queues['pickup_requests'] ?? [];
$processingQueue  = $queues['processing'] ?? [];
$deliveryQueue    = $queues['delivery'] ?? [];
$paymentDueQueue  = $queues['payment_due'] ?? [];
$branches         = $branches ?? [];
$selectedBranchId = $selectedBranchId ?? null;
$staffBranchId    = $staffBranchId ?? 0;

// Helper for semantic order status badge styling matching Slice 1
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pickup_request'       => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',   'text' => 'text-amber-700 dark:text-amber-300',   'border' => 'border-amber-200 dark:border-amber-800'],
        'accepted'             => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',   'text' => 'text-amber-700 dark:text-amber-300',   'border' => 'border-amber-200 dark:border-amber-800'],
        'picked_up'            => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',   'text' => 'text-amber-700 dark:text-amber-300',   'border' => 'border-amber-200 dark:border-amber-800'],
        'inspection_completed' => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',       'text' => 'text-sky-700 dark:text-sky-300',       'border' => 'border-sky-200 dark:border-sky-800'],
        'price_confirmed'      => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',       'text' => 'text-sky-700 dark:text-sky-300',       'border' => 'border-sky-200 dark:border-sky-800'],
        'processing'           => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',       'text' => 'text-sky-700 dark:text-sky-300',       'border' => 'border-sky-200 dark:border-sky-800'],
        'ready'                => ['bg' => 'bg-violet-50 dark:bg-violet-950/40',  'text' => 'text-violet-700 dark:text-violet-300',  'border' => 'border-violet-200 dark:border-violet-800'],
        'out_for_delivery'     => ['bg' => 'bg-violet-50 dark:bg-violet-950/40',  'text' => 'text-violet-700 dark:text-violet-300',  'border' => 'border-violet-200 dark:border-violet-800'],
        'delivered'            => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-200 dark:border-emerald-800'],
        'completed'            => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-200 dark:border-emerald-800'],
        'cancelled'            => ['bg' => 'bg-rose-50 dark:bg-rose-950/40',       'text' => 'text-rose-700 dark:text-rose-300',       'border' => 'border-rose-200 dark:border-rose-800']
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-700 dark:text-slate-300', 'border' => 'border-slate-200 dark:border-slate-700'];
};
?>

<style>
/* High-Speed Horizontal Scroll & Virtualized Queue Tables */
.table-scroll-container {
    will-change: scroll-position;
    transform: translateZ(0);
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
}
.table-scroll-container.can-drag {
    cursor: grab;
}
.table-scroll-container.is-dragging {
    cursor: grabbing !important;
    user-select: none !important;
}
.table-scroll-container.is-dragging * {
    user-select: none !important;
    cursor: grabbing !important;
    pointer-events: none !important;
}
.table-scroll-container::-webkit-scrollbar,
.table-top-scrollbar::-webkit-scrollbar {
    height: 6px;
}
.table-scroll-container::-webkit-scrollbar-track,
.table-top-scrollbar::-webkit-scrollbar-track {
    background: rgba(148, 163, 184, 0.08);
}
.table-scroll-container::-webkit-scrollbar-thumb,
.table-top-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.35);
    border-radius: 9999px;
}
.table-scroll-container::-webkit-scrollbar-thumb:hover,
.table-top-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.65);
}
/* Virtualize table rows: renders only visible rows in viewport for maximum speed */
.pickup-row, .processing-row, .delivery-row, .payment-row {
    content-visibility: auto;
    contain-intrinsic-size: 45px;
}
</style>

<div class="space-y-6">

    <!-- Dashboard Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 font-display tracking-tight">Staff Operations Dashboard</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">Manage daily pickup requests, processing workflows, deliveries, and payment settlements.</p>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0 flex-nowrap self-start md:self-auto overflow-x-auto max-w-full py-0.5">
            <a href="/laundry-lms/orders/create" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap cursor-pointer border border-transparent" title="Create a new counter drop-off or walk-in order">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Walk-In Order</span>
            </a>
            <a href="/laundry-lms/orders" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 shadow-2xs hover:shadow-xs transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 whitespace-nowrap cursor-pointer" title="Search full orders directory and past tickets">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Orders Directory</span>
            </a>
        </div>
    </div>

    <!-- Branch Filter Selector Bar -->
    <?php if (!empty($branches) && count($branches) > 1): ?>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </span>
            <div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider block">Branch View</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    <?= $selectedBranchId === null ? 'Displaying operational orders across all locations' : 'Filtered to selected operational location' ?>
                </span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="/laundry-lms/staff/dashboard?branch_id=all"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $selectedBranchId === null ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                All Branches
            </a>
            <?php foreach ($branches as $br):
                $isActiveBranch = ($selectedBranchId !== null && (int)$selectedBranchId === (int)$br['id']);
                $isMyAssigned   = ((int)$br['id'] === (int)$staffBranchId);
            ?>
                <a href="/laundry-lms/staff/dashboard?branch_id=<?= (int)$br['id'] ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5 <?= $isActiveBranch ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                    <span><?= Security::e($br['name']) ?></span>
                    <span class="text-[10px] px-1 py-0.2 rounded font-mono <?= $isActiveBranch ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400' ?>"><?= Security::e($br['code']) ?></span>
                    <?php if ($isMyAssigned): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" title="Assigned Branch"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Queue Metric Summary Strip & Navigation Tabs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <a href="#queue-pickups" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-amber-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pickups</span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-1.5 tabular-nums"><?= number_format(count($pickupRequests)) ?></div>
            <span class="text-xs text-amber-700 dark:text-amber-400 mt-1 block font-medium group-hover:underline">Pending Requests &darr;</span>
        </a>

        <a href="#queue-processing" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-sky-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">In-Shop</span>
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-1.5 tabular-nums"><?= number_format(count($processingQueue)) ?></div>
            <span class="text-xs text-sky-700 dark:text-sky-400 mt-1 block font-medium group-hover:underline">Active Operations &darr;</span>
        </a>

        <a href="#queue-delivery" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:border-violet-300 dark:hover:border-violet-700 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-violet-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dispatch</span>
                <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-1.5 tabular-nums"><?= number_format(count($deliveryQueue)) ?></div>
            <span class="text-xs text-violet-700 dark:text-violet-400 mt-1 block font-medium group-hover:underline">Ready / Delivery &darr;</span>
        </a>

        <a href="#queue-payment-due" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:border-rose-300 dark:hover:border-rose-700 hover:shadow-md transition-all group focus:outline-none focus:ring-2 focus:ring-rose-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Payment Due</span>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-1.5 tabular-nums"><?= number_format(count($paymentDueQueue)) ?></div>
            <span class="text-xs text-rose-700 dark:text-rose-400 mt-1 block font-medium group-hover:underline">Delivered Unsettled &darr;</span>
        </a>
    </div>

    <!-- Section 1: Pickup Requests Queue -->
    <div id="queue-pickups" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-amber-50/40 dark:bg-amber-950/20 flex flex-col md:flex-row justify-between md:items-center gap-3">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-display">1. Pickup Requests Queue</h2>
                <span id="pickup-badge-total" class="bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 text-xs font-bold px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800"><?= count($pickupRequests) ?></span>
            </div>

            <!-- Sort & Quick Filter Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Search Filter Input -->
                <div class="relative min-w-[200px] flex-1 sm:flex-initial">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" id="pickup-filter-input" placeholder="Search job, customer, mobile..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 shadow-xs">
                </div>

                <!-- Sort Dropdown -->
                <div class="flex items-center gap-1.5 bg-white dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 shadow-xs">
                    <label for="pickup-sort-select" class="text-xs font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                        <span>Sort:</span>
                    </label>
                    <select id="pickup-sort-select" class="text-xs font-medium bg-transparent text-slate-800 dark:text-slate-200 outline-none cursor-pointer py-0.5">
                        <option value="id-desc">Job ID: Newest First &darr;</option>
                        <option value="id-asc">Job ID: Oldest First &uarr;</option>
                        <option value="date-asc">Pickup Date: Earliest First &uarr;</option>
                        <option value="date-desc">Pickup Date: Latest First &darr;</option>
                        <option value="customer-asc">Customer: A &rarr; Z</option>
                        <option value="customer-desc">Customer: Z &rarr; A</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (empty($pickupRequests)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 mb-3 border border-amber-100 dark:border-amber-900/60">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Pending Pickup Requests</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">All pickup requests for this branch have been accepted and dispatched.</p>
            </div>
        <?php else: ?>
            <!-- Top Horizontal Scrollbar (Synchronized) -->
            <div id="pickup-top-scrollbar" class="table-top-scrollbar overflow-x-auto overflow-y-hidden border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 h-2">
                <div class="h-2"></div>
            </div>
            <div id="pickup-scroll-container" class="table-scroll-container overflow-x-auto">
                <table id="pickup-table" class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2.5 min-w-[85px] cursor-pointer hover:text-amber-600 dark:hover:text-amber-400 select-none group" data-sort-key="id" title="Click to sort by Job ID">
                                <div class="flex items-center gap-1.5">
                                    <span>Job ID</span>
                                    <span class="sort-icon text-amber-600 dark:text-amber-400 font-bold text-[11px]">▼</span>
                                </div>
                            </th>
                            <th class="px-3 py-2.5 min-w-[110px] cursor-pointer hover:text-amber-600 dark:hover:text-amber-400 select-none group" data-sort-key="customer" title="Click to sort by Customer Name">
                                <div class="flex items-center gap-1.5">
                                    <span>Customer &amp; Mobile</span>
                                    <span class="sort-icon text-slate-300 dark:text-slate-600 group-hover:text-amber-500 text-[11px]">↕</span>
                                </div>
                            </th>
                            <th class="px-3 py-2.5 min-w-[85px] whitespace-nowrap cursor-pointer hover:text-amber-600 dark:hover:text-amber-400 select-none group" data-sort-key="date" title="Click to sort by Pickup Date">
                                <div class="flex items-center gap-1.5">
                                    <span>Pickup Date</span>
                                    <span class="sort-icon text-slate-300 dark:text-slate-600 group-hover:text-amber-500 text-[11px]">↕</span>
                                </div>
                            </th>
                            <th class="px-3 py-2.5 min-w-[95px]">Address</th>
                            <th class="px-3 py-2.5 min-w-[95px]">Instructions</th>
                            <th class="px-3 py-2.5 min-w-[90px]">Assignment</th>
                            <th class="px-3 py-2.5 min-w-[110px] text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($pickupRequests as $ord): 
                            $isAssignedToMe = ((int)($ord['staff_id'] ?? 0) === (int)$_SESSION['user_id']);
                            $isUnassigned   = empty($ord['staff_id']);
                            $nextStatus     = Order::getNextStatus($ord['status']);
                            $cleanInstructions = preg_replace('/<!--.*?-->/s', '', (string)($ord['special_instructions'] ?? ''));
                            $cleanInstructions = trim($cleanInstructions);
                        ?>
                            <tr class="pickup-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                data-order-id="<?= (int)$ord['id'] ?>"
                                data-job-id="<?= Security::e($ord['job_id']) ?>"
                                data-customer="<?= Security::e(strtolower($ord['customer_name'])) ?>"
                                data-date="<?= Security::e($ord['pickup_date']) ?>"
                                data-search="<?= Security::e(strtolower($ord['job_id'] . ' ' . $ord['customer_name'] . ' ' . $ord['customer_mobile'] . ' ' . ($ord['customer_address'] ?? '') . ' ' . ($ord['branch_name'] ?? ''))) ?>">
                                <td class="px-3 py-2.5 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                    <?php if (!empty($ord['branch_name']) && (empty($selectedBranchId) || count($branches) > 1)): ?>
                                        <div class="text-[10px] font-sans font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5" title="Branch">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span><?= Security::e($ord['branch_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[120px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-3 py-2.5 text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-3 py-2.5 text-slate-600 dark:text-slate-400">
                                    <div class="max-w-[105px] truncate" title="<?= Security::e($ord['customer_address'] ?? '') ?>">
                                        <?= Security::e($ord['customer_address'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-slate-500 dark:text-slate-400">
                                    <div class="max-w-[105px] truncate" title="<?= Security::e($cleanInstructions ?: '-') ?>">
                                        <?= Security::e($cleanInstructions ?: '-') ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <?php if ($isUnassigned): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
                                            Unassigned
                                        </span>
                                    <?php elseif ($isAssignedToMe): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="Assigned to you">
                                            <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Assigned to You
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 max-w-[105px] truncate" title="<?= Security::e($ord['staff_name']) ?>">
                                            <?= Security::e($ord['staff_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5 text-right whitespace-nowrap space-x-1">
                                    <?php if ($isUnassigned): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer" title="Self-claim this order">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                <span>Claim Order</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if ($nextStatus): ?>
                                            <form action="/laundry-lms/orders/status" method="POST" class="inline-block">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                                                <input type="hidden" name="status" value="<?= Security::e($nextStatus) ?>">
                                                <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer" title="Accept customer pickup request">
                                                    <span>Mark <?= Security::e(str_replace('_', ' ', $nextStatus)) ?></span>
                                                    <span aria-hidden="true">&rarr;</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-2 py-1 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors inline-block" title="View Order Details">
                                            View
                                        </a>
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
    <div id="queue-processing" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-sky-50/40 dark:bg-sky-950/20 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-display">2. In-Shop Processing Queue</h2>
                <span class="bg-sky-100 dark:bg-sky-900/60 text-sky-800 dark:text-sky-200 text-xs font-bold px-2 py-0.5 rounded-full border border-sky-200 dark:border-sky-800"><?= count($processingQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Active Laundry Operations</span>
        </div>

        <?php if (empty($processingQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 mb-3 border border-sky-100 dark:border-sky-900/60">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Orders in Processing</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">No orders are currently undergoing active washing, drying, or ironing.</p>
            </div>
        <?php else: ?>
            <!-- Top Horizontal Scrollbar (Synchronized) -->
            <div id="processing-top-scrollbar" class="table-top-scrollbar overflow-x-auto overflow-y-hidden border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 h-2">
                <div class="h-2"></div>
            </div>
            <div id="processing-scroll-container" class="table-scroll-container overflow-x-auto">
                <table id="processing-table" class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[95px] cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none group" data-sort-table="processing" data-sort-key="id" title="Sort by Job ID">
                                <div class="flex items-center gap-1"><span>Job ID</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[130px] cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none group" data-sort-table="processing" data-sort-key="customer" title="Sort by Customer">
                                <div class="flex items-center gap-1"><span>Customer</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[120px]">Current Status</th>
                            <th class="px-4 py-3 min-w-[95px] whitespace-nowrap cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none group" data-sort-table="processing" data-sort-key="pickup_date" title="Sort by Pickup Date">
                                <div class="flex items-center gap-1"><span>Pickup Date</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[95px] whitespace-nowrap">Delivery Date</th>
                            <th class="px-4 py-3 min-w-[85px] cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none group" data-sort-table="processing" data-sort-key="total" title="Sort by Total Amount">
                                <div class="flex items-center gap-1"><span>Total</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[85px] cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none group" data-sort-table="processing" data-sort-key="outstanding" title="Sort by Outstanding">
                                <div class="flex items-center gap-1"><span>Outstanding</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[180px] text-right whitespace-nowrap">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($processingQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                            $stBadge     = $getOrderStatusBadge((string)($ord['status'] ?? ''));
                        ?>
                            <tr class="processing-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                data-order-id="<?= (int)$ord['id'] ?>"
                                data-customer="<?= Security::e(strtolower($ord['customer_name'])) ?>"
                                data-pickup-date="<?= Security::e($ord['pickup_date']) ?>"
                                data-total="<?= (float)$ord['total_amount'] ?>"
                                data-outstanding="<?= (float)$outstanding ?>">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                    <?php if (!empty($ord['branch_name']) && (empty($selectedBranchId) || count($branches) > 1)): ?>
                                        <div class="text-[10px] font-sans font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span><?= Security::e($ord['branch_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[140px]" title="<?= Security::e($ord['customer_name']) ?>">
                                        <?= Security::e($ord['customer_name']) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <?= Security::e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= date('M d, Y', strtotime($ord['pickup_date'])) ?></td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums whitespace-nowrap">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3 font-mono font-semibold whitespace-nowrap tabular-nums <?= $outstanding > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if (empty($ord['staff_id'])): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block mr-1">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer" title="Self-claim this order">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                <span>Claim</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($ord['status'] === 'picked_up'): ?>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                            <span>Inspect &amp; Price</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    <?php elseif ($ord['status'] === 'inspection_completed'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 mr-1.5">
                                            Awaiting Approval
                                        </span>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-medium transition-colors border border-slate-200 dark:border-slate-700 inline-flex items-center focus:outline-none focus:ring-2 focus:ring-slate-400">
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
    <div id="queue-delivery" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-violet-50/40 dark:bg-violet-950/20 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-violet-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-display">3. Ready / Delivery Queue</h2>
                <span class="bg-violet-100 dark:bg-violet-900/60 text-violet-800 dark:text-violet-200 text-xs font-bold px-2 py-0.5 rounded-full border border-violet-200 dark:border-violet-800"><?= count($deliveryQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Ready for Dispatch or Out for Delivery</span>
        </div>

        <?php if (empty($deliveryQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 mb-3 border border-violet-100 dark:border-violet-900/60">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002 2V8m-9 4h4"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Orders Ready for Delivery</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Orders will appear here once laundry processing is marked ready.</p>
            </div>
        <?php else: ?>
            <!-- Top Horizontal Scrollbar (Synchronized) -->
            <div id="delivery-top-scrollbar" class="table-top-scrollbar overflow-x-auto overflow-y-hidden border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 h-2">
                <div class="h-2"></div>
            </div>
            <div id="delivery-scroll-container" class="table-scroll-container overflow-x-auto">
                <table id="delivery-table" class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[100px] cursor-pointer hover:text-violet-600 dark:hover:text-violet-400 select-none group" data-sort-table="delivery" data-sort-key="id" title="Sort by Job ID">
                                <div class="flex items-center gap-1"><span>Job ID</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[140px] cursor-pointer hover:text-violet-600 dark:hover:text-violet-400 select-none group" data-sort-table="delivery" data-sort-key="customer" title="Sort by Customer">
                                <div class="flex items-center gap-1"><span>Customer &amp; Mobile</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[130px]">Delivery Address</th>
                            <th class="px-4 py-3 min-w-[100px] whitespace-nowrap cursor-pointer hover:text-violet-600 dark:hover:text-violet-400 select-none group" data-sort-table="delivery" data-sort-key="delivery_date" title="Sort by Delivery Date">
                                <div class="flex items-center gap-1"><span>Delivery Date</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[90px]">Payment</th>
                            <th class="px-4 py-3 min-w-[90px] cursor-pointer hover:text-violet-600 dark:hover:text-violet-400 select-none group" data-sort-table="delivery" data-sort-key="outstanding" title="Sort by Outstanding">
                                <div class="flex items-center gap-1"><span>Outstanding</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[160px] text-right whitespace-nowrap">Next Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($deliveryQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                            $nextStatus  = Order::getNextStatus($ord['status']);
                        ?>
                            <tr class="delivery-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                data-order-id="<?= (int)$ord['id'] ?>"
                                data-customer="<?= Security::e(strtolower($ord['customer_name'])) ?>"
                                data-delivery-date="<?= Security::e($ord['delivery_date'] ?? '') ?>"
                                data-outstanding="<?= (float)$outstanding ?>">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                    <?php if (!empty($ord['branch_name']) && (empty($selectedBranchId) || count($branches) > 1)): ?>
                                        <div class="text-[10px] font-sans font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span><?= Security::e($ord['branch_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[140px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                    <div class="max-w-[140px] truncate" title="<?= Security::e($ord['customer_address'] ?? '') ?>">
                                        <?= Security::e($ord['customer_address'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= !empty($ord['delivery_date']) ? date('M d, Y', strtotime($ord['delivery_date'])) : '<em class="text-slate-400">N/A</em>' ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Paid</span>
                                    <?php elseif ($ord['payment_status'] === 'partial'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Partial</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Due</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold whitespace-nowrap tabular-nums <?= $outstanding > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400' ?>">
                                    ₹<?= number_format($outstanding, 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if (empty($ord['staff_id'])): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block mr-1">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer" title="Self-claim this order">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                <span>Claim</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
    <div id="queue-payment-due" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden scroll-mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-rose-50/40 dark:bg-rose-950/20 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-display">4. Payment Due Queue</h2>
                <span class="bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200 text-xs font-bold px-2 py-0.5 rounded-full border border-rose-200 dark:border-rose-800"><?= count($paymentDueQueue) ?></span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Delivered Orders Pending Payment Settlement</span>
        </div>

        <?php if (empty($paymentDueQueue)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 mb-3 border border-rose-100 dark:border-rose-900/60">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">All Delivered Orders Settled</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">There are no delivered orders with outstanding balances.</p>
            </div>
        <?php else: ?>
            <!-- Top Horizontal Scrollbar (Synchronized) -->
            <div id="payment-top-scrollbar" class="table-top-scrollbar overflow-x-auto overflow-y-hidden border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 h-2">
                <div class="h-2"></div>
            </div>
            <div id="payment-scroll-container" class="table-scroll-container overflow-x-auto">
                <table id="payment-table" class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 min-w-[100px] cursor-pointer hover:text-rose-600 dark:hover:text-rose-400 select-none group" data-sort-table="payment" data-sort-key="id" title="Sort by Job ID">
                                <div class="flex items-center gap-1"><span>Job ID</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[150px] cursor-pointer hover:text-rose-600 dark:hover:text-rose-400 select-none group" data-sort-table="payment" data-sort-key="customer" title="Sort by Customer">
                                <div class="flex items-center gap-1"><span>Customer &amp; Mobile</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[95px] cursor-pointer hover:text-rose-600 dark:hover:text-rose-400 select-none group" data-sort-table="payment" data-sort-key="total" title="Sort by Total Amount">
                                <div class="flex items-center gap-1"><span>Total</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[95px]">Amount Paid</th>
                            <th class="px-4 py-3 min-w-[110px] cursor-pointer hover:text-rose-600 dark:hover:text-rose-400 select-none group" data-sort-table="payment" data-sort-key="outstanding" title="Sort by Outstanding Balance">
                                <div class="flex items-center gap-1"><span>Outstanding</span><span class="sort-icon text-slate-300 dark:text-slate-600 text-[11px]">↕</span></div>
                            </th>
                            <th class="px-4 py-3 min-w-[90px]">Status</th>
                            <th class="px-4 py-3 min-w-[150px] text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($paymentDueQueue as $ord): 
                            $outstanding = max(0, round((float)$ord['total_amount'] - (float)$ord['amount_paid'], 2));
                        ?>
                            <tr class="payment-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                data-order-id="<?= (int)$ord['id'] ?>"
                                data-customer="<?= Security::e(strtolower($ord['customer_name'])) ?>"
                                data-total="<?= (float)$ord['total_amount'] ?>"
                                data-outstanding="<?= (float)$outstanding ?>">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ord['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded">
                                        <?= Security::e($ord['job_id']) ?>
                                    </a>
                                    <?php if (!empty($ord['branch_name']) && (empty($selectedBranchId) || count($branches) > 1)): ?>
                                        <div class="text-[10px] font-sans font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span><?= Security::e($ord['branch_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[160px]" title="<?= Security::e($ord['customer_name']) ?>"><?= Security::e($ord['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= Security::e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap tabular-nums">₹<?= number_format((float)$ord['total_amount'], 2) ?></td>
                                <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap tabular-nums">₹<?= number_format((float)$ord['amount_paid'], 2) ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap tabular-nums">₹<?= number_format($outstanding, 2) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 uppercase">
                                        <?= Security::e($ord['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <?php if (empty($ord['staff_id'])): ?>
                                        <form action="/laundry-lms/orders/claim" method="POST" class="inline-block mr-1">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/staff/dashboard">
                                            <button type="submit" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-medium shadow-sm transition-colors whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer" title="Self-claim this order">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                <span>Claim</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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

<!-- Interactive Client-side Table Sorting & Filtering Scripts -->
<script>
(function() {
    // 1. Pickup Requests Queue Table Sorting & Live Filtering
    var pickupTable = document.getElementById('pickup-table');
    if (pickupTable) {
        var pickupTbody = pickupTable.querySelector('tbody');
        var pickupFilterInput = document.getElementById('pickup-filter-input');
        var pickupSortSelect = document.getElementById('pickup-sort-select');
        var pickupBadgeTotal = document.getElementById('pickup-badge-total');
        var pickupHeaders = pickupTable.querySelectorAll('th[data-sort-key]');

        var currentPickupKey = 'id';
        var currentPickupDir = 'desc';

        function getPickupRows() {
            return Array.from(pickupTbody.querySelectorAll('tr.pickup-row'));
        }

        function sortPickupRows(key, dir) {
            currentPickupKey = key;
            currentPickupDir = dir;
            var rows = getPickupRows();

            rows.sort(function(a, b) {
                if (key === 'id') {
                    var valA = parseInt(a.dataset.orderId || '0', 10);
                    var valB = parseInt(b.dataset.orderId || '0', 10);
                    return dir === 'asc' ? (valA - valB) : (valB - valA);
                } else if (key === 'customer') {
                    var nameA = (a.dataset.customer || '').toLowerCase();
                    var nameB = (b.dataset.customer || '').toLowerCase();
                    return dir === 'asc' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                } else if (key === 'date') {
                    var dateA = a.dataset.date || '';
                    var dateB = b.dataset.date || '';
                    return dir === 'asc' ? dateA.localeCompare(dateB) : dateB.localeCompare(dateA);
                }
                return 0;
            });

            rows.forEach(function(row) {
                pickupTbody.appendChild(row);
            });

            // Update UI indicators on headers
            pickupHeaders.forEach(function(th) {
                var thKey = th.dataset.sortKey;
                var icon = th.querySelector('.sort-icon');
                if (!icon) return;
                if (thKey === key) {
                    icon.textContent = dir === 'asc' ? '▲' : '▼';
                    icon.className = 'sort-icon text-amber-600 dark:text-amber-400 font-bold text-[11px]';
                } else {
                    icon.textContent = '↕';
                    icon.className = 'sort-icon text-slate-300 dark:text-slate-600 text-[11px]';
                }
            });

            // Update sort dropdown selection
            if (pickupSortSelect) {
                var selectVal = key + '-' + dir;
                if (pickupSortSelect.value !== selectVal) {
                    var opt = pickupSortSelect.querySelector('option[value="' + selectVal + '"]');
                    if (opt) pickupSortSelect.value = selectVal;
                }
            }
        }

        function filterPickupRows() {
            var query = (pickupFilterInput ? pickupFilterInput.value : '').trim().toLowerCase();
            var rows = getPickupRows();
            var visibleCount = 0;

            rows.forEach(function(row) {
                var searchContent = (row.dataset.search || '').toLowerCase();
                var matches = !query || searchContent.indexOf(query) !== -1;
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            if (pickupBadgeTotal) {
                pickupBadgeTotal.textContent = visibleCount;
            }

            var noMatchRow = document.getElementById('pickup-no-match-row');
            if (visibleCount === 0 && rows.length > 0) {
                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.id = 'pickup-no-match-row';
                    noMatchRow.innerHTML = '<td colspan="7" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 text-xs font-medium">No pickup requests match your search criteria.</td>';
                    pickupTbody.appendChild(noMatchRow);
                }
                noMatchRow.style.display = '';
            } else if (noMatchRow) {
                noMatchRow.style.display = 'none';
            }
        }

        // Header click sort handler
        pickupHeaders.forEach(function(th) {
            th.addEventListener('click', function() {
                var key = th.dataset.sortKey;
                var dir = (currentPickupKey === key && currentPickupDir === 'asc') ? 'desc' : 'asc';
                if (key === 'id' && currentPickupKey !== 'id') {
                    dir = 'desc';
                }
                sortPickupRows(key, dir);
            });
        });

        // Dropdown sort handler
        if (pickupSortSelect) {
            pickupSortSelect.addEventListener('change', function() {
                var parts = this.value.split('-');
                if (parts.length === 2) {
                    sortPickupRows(parts[0], parts[1]);
                }
            });
        }

        // Filter input handler
        if (pickupFilterInput) {
            pickupFilterInput.addEventListener('input', filterPickupRows);
        }
    }

    // 2. Generic Sort Handler for Processing, Delivery, and Payment tables
    function setupGenericTableSort(tableId, rowClass) {
        var tbl = document.getElementById(tableId);
        if (!tbl) return;
        var tb = tbl.querySelector('tbody');
        if (!tb) return;
        var headers = tbl.querySelectorAll('th[data-sort-key]');
        var activeKey = null;
        var activeDir = 'desc';

        headers.forEach(function(th) {
            th.addEventListener('click', function() {
                var key = th.dataset.sortKey;
                activeDir = (activeKey === key && activeDir === 'asc') ? 'desc' : 'asc';
                activeKey = key;
                var rows = Array.from(tb.querySelectorAll('tr.' + rowClass));

                rows.sort(function(a, b) {
                    if (key === 'id') {
                        var aId = parseInt(a.dataset.orderId || '0', 10);
                        var bId = parseInt(b.dataset.orderId || '0', 10);
                        return activeDir === 'asc' ? (aId - bId) : (bId - aId);
                    } else if (key === 'customer') {
                        var aName = (a.dataset.customer || '').toLowerCase();
                        var bName = (b.dataset.customer || '').toLowerCase();
                        return activeDir === 'asc' ? aName.localeCompare(bName) : bName.localeCompare(aName);
                    } else if (key === 'pickup_date') {
                        var aDate = a.dataset.pickupDate || '';
                        var bDate = b.dataset.pickupDate || '';
                        return activeDir === 'asc' ? aDate.localeCompare(bDate) : bDate.localeCompare(aDate);
                    } else if (key === 'delivery_date') {
                        var aDel = a.dataset.deliveryDate || '';
                        var bDel = b.dataset.deliveryDate || '';
                        return activeDir === 'asc' ? aDel.localeCompare(bDel) : bDel.localeCompare(aDel);
                    } else if (key === 'total') {
                        var aTot = parseFloat(a.dataset.total || '0');
                        var bTot = parseFloat(b.dataset.total || '0');
                        return activeDir === 'asc' ? (aTot - bTot) : (bTot - aTot);
                    } else if (key === 'outstanding') {
                        var aOut = parseFloat(a.dataset.outstanding || '0');
                        var bOut = parseFloat(b.dataset.outstanding || '0');
                        return activeDir === 'asc' ? (aOut - bOut) : (bOut - aOut);
                    }
                    return 0;
                });

                rows.forEach(function(row) {
                    tb.appendChild(row);
                });

                headers.forEach(function(h) {
                    var icon = h.querySelector('.sort-icon');
                    if (!icon) return;
                    if (h.dataset.sortKey === key) {
                        icon.textContent = activeDir === 'asc' ? '▲' : '▼';
                        icon.className = 'sort-icon text-blue-600 dark:text-blue-400 font-bold text-[11px]';
                    } else {
                        icon.textContent = '↕';
                        icon.className = 'sort-icon text-slate-300 dark:text-slate-600 text-[11px]';
                    }
                });
            });
        });
    }

    setupGenericTableSort('processing-table', 'processing-row');
    setupGenericTableSort('delivery-table', 'delivery-row');
    setupGenericTableSort('payment-table', 'payment-row');

    // 3. Ultra-Fast, Zero-Lag Horizontal Scroll Engine (Works Anywhere on the List)
    function setupHighSpeedHorizontalScroll(containerId, topScrollbarId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var topScrollbar = topScrollbarId ? document.getElementById(topScrollbarId) : null;
        var topInner = topScrollbar ? topScrollbar.firstElementChild : null;

        function updateDimensions() {
            var scrollW = container.scrollWidth;
            var clientW = container.clientWidth;
            var isOverflowing = scrollW > clientW + 2;

            if (isOverflowing) {
                container.classList.add('can-drag');
                if (topScrollbar && topInner) {
                    topScrollbar.style.display = 'block';
                    topInner.style.width = scrollW + 'px';
                }
            } else {
                container.classList.remove('can-drag');
                if (topScrollbar) {
                    topScrollbar.style.display = 'none';
                }
            }
        }

        updateDimensions();
        window.addEventListener('resize', updateDimensions, { passive: true });

        // Synchronize top scrollbar with RAF and re-entrancy flags
        if (topScrollbar) {
            var isSyncingTop = false;
            var isSyncingContainer = false;

            topScrollbar.addEventListener('scroll', function() {
                if (isSyncingTop) return;
                isSyncingContainer = true;
                container.scrollLeft = topScrollbar.scrollLeft;
                requestAnimationFrame(function() {
                    isSyncingContainer = false;
                });
            }, { passive: true });

            container.addEventListener('scroll', function() {
                if (isSyncingContainer) return;
                isSyncingTop = true;
                topScrollbar.scrollLeft = container.scrollLeft;
                requestAnimationFrame(function() {
                    isSyncingTop = false;
                });
            }, { passive: true });
        }

        // Blisteringly Fast Mouse Drag-to-Scroll from anywhere on the table
        var isDown = false;
        var startX = 0;
        var startScrollLeft = 0;
        var hasMoved = false;
        var targetScrollLeft = 0;
        var rafPending = false;

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return; // Left mouse button only
            // Don't intercept clicks on interactive controls
            if (e.target.closest('a, button, input, select, textarea, label, form, [role="button"]')) {
                return;
            }

            isDown = true;
            hasMoved = false;
            startX = e.clientX;
            startScrollLeft = container.scrollLeft;
            targetScrollLeft = startScrollLeft;
        });

        window.addEventListener('mousemove', function(e) {
            if (!isDown) return;

            var deltaX = e.clientX - startX;

            if (!hasMoved) {
                if (Math.abs(deltaX) > 3) {
                    hasMoved = true;
                    container.classList.add('is-dragging');
                } else {
                    return;
                }
            }

            targetScrollLeft = startScrollLeft - deltaX;

            if (!rafPending) {
                rafPending = true;
                requestAnimationFrame(function() {
                    container.scrollLeft = targetScrollLeft;
                    rafPending = false;
                });
            }
        });

        function finishDrag() {
            if (!isDown) return;
            isDown = false;
            container.classList.remove('is-dragging');
            if (rafPending) {
                container.scrollLeft = targetScrollLeft;
                rafPending = false;
            }
            if (hasMoved) {
                // Reset hasMoved after a short delay so stale state never lingers
                setTimeout(function() {
                    hasMoved = false;
                }, 100);
            }
        }

        window.addEventListener('mouseup', finishDrag);

        // Cancel accidental click event on drag release
        container.addEventListener('click', function(e) {
            if (hasMoved) {
                e.preventDefault();
                e.stopPropagation();
                hasMoved = false;
            }
        }, true);

        // Convenient Alt + Wheel horizontal scrolling
        container.addEventListener('wheel', function(e) {
            if (e.altKey && e.deltaY !== 0) {
                e.preventDefault();
                container.scrollLeft += e.deltaY;
            }
        }, { passive: false });
    }

    setupHighSpeedHorizontalScroll('pickup-scroll-container', 'pickup-top-scrollbar');
    setupHighSpeedHorizontalScroll('processing-scroll-container', 'processing-top-scrollbar');
    setupHighSpeedHorizontalScroll('delivery-scroll-container', 'delivery-top-scrollbar');
    setupHighSpeedHorizontalScroll('payment-scroll-container', 'payment-top-scrollbar');
})();
</script>
