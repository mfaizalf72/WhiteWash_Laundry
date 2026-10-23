<?php
use Faizal\LaundryLms\Helpers\Security;

// Helper for semantic order status styling
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pending_pickup'     => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',   'text' => 'text-amber-700 dark:text-amber-400',   'border' => 'border-amber-200/80 dark:border-amber-800/60',   'dot' => 'bg-amber-500'],
        'pending_inspection' => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',   'text' => 'text-amber-700 dark:text-amber-400',   'border' => 'border-amber-200/80 dark:border-amber-800/60',   'dot' => 'bg-amber-500'],
        'in_process'         => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',     'text' => 'text-sky-700 dark:text-sky-400',     'border' => 'border-sky-200/80 dark:border-sky-800/60',     'dot' => 'bg-sky-500'],
        'washing'            => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',     'text' => 'text-sky-700 dark:text-sky-400',     'border' => 'border-sky-200/80 dark:border-sky-800/60',     'dot' => 'bg-sky-500'],
        'drying'             => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',     'text' => 'text-sky-700 dark:text-sky-400',     'border' => 'border-sky-200/80 dark:border-sky-800/60',     'dot' => 'bg-sky-500'],
        'ironing'            => ['bg' => 'bg-sky-50 dark:bg-sky-950/40',     'text' => 'text-sky-700 dark:text-sky-400',     'border' => 'border-sky-200/80 dark:border-sky-800/60',     'dot' => 'bg-sky-500'],
        'ready_for_pickup'   => ['bg' => 'bg-violet-50 dark:bg-violet-950/40',  'text' => 'text-violet-700 dark:text-violet-400',  'border' => 'border-violet-200/80 dark:border-violet-800/60',  'dot' => 'bg-violet-500'],
        'out_for_delivery'   => ['bg' => 'bg-violet-50 dark:bg-violet-950/40',  'text' => 'text-violet-700 dark:text-violet-400',  'border' => 'border-violet-200/80 dark:border-violet-800/60',  'dot' => 'bg-violet-500'],
        'completed'          => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-700 dark:text-emerald-400', 'border' => 'border-emerald-200/80 dark:border-emerald-800/60', 'dot' => 'bg-emerald-500'],
        'cancelled'          => ['bg' => 'bg-rose-50 dark:bg-rose-950/40',    'text' => 'text-rose-700 dark:text-rose-400',    'border' => 'border-rose-200/80 dark:border-rose-800/60',    'dot' => 'bg-rose-500']
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-700 dark:text-slate-300', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'];
};

// Calculate payment percentages for progress bar
$totalSettlementOrders = (int)$kpis['unpaid_orders_count'] + (int)$kpis['partial_orders_count'] + (int)$kpis['paid_orders_count'];
$paidPct = $totalSettlementOrders > 0 ? round(((int)$kpis['paid_orders_count'] / $totalSettlementOrders) * 100, 1) : 0;
$partialPct = $totalSettlementOrders > 0 ? round(((int)$kpis['partial_orders_count'] / $totalSettlementOrders) * 100, 1) : 0;
$unpaidPct = $totalSettlementOrders > 0 ? (100 - $paidPct - $partialPct) : 0;
?>
<div class="space-y-6 sm:space-y-8">

    <!-- Header & Date Filter Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-5 transition-colors">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400 inline-block"></span>
                    Live Operations
                </span>
                <?php if (!empty($selectedBranchId)): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700">
                        Filtered by Branch
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white font-display tracking-tight">Operational Dashboard</h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Real-time overview of laundry operations, workload, and financial performance.</p>
        </div>

        <div class="w-full xl:w-auto">
            <form action="/laundry-lms/dashboard" method="GET" class="flex flex-wrap items-center gap-2.5">
                <!-- Branch Dropdown -->
                <div class="relative">
                    <select name="branch_id" onchange="this.form.submit()" aria-label="Filter by branch" class="pl-3 pr-8 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 bg-white dark:bg-slate-800 shadow-2xs text-slate-700 dark:text-slate-200 transition-colors cursor-pointer">
                        <option value="">All Branches</option>
                        <?php foreach ($branches ?? [] as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= ((int)($selectedBranchId ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                                <?= Security::e($b['name']) ?> (<?= Security::e($b['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Filter Preset -->
                <div class="relative">
                    <select name="filter" id="dash_filter" onchange="toggleCustomDates('dash')" aria-label="Date range preset" class="pl-3 pr-8 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 bg-white dark:bg-slate-800 shadow-2xs text-slate-700 dark:text-slate-200 transition-colors cursor-pointer">
                        <option value="today" <?= ($range['filter'] === 'today') ? 'selected' : '' ?>>Today</option>
                        <option value="last_7_days" <?= ($range['filter'] === 'last_7_days') ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="last_30_days" <?= ($range['filter'] === 'last_30_days') ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="custom" <?= ($range['filter'] === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Custom Date Inputs -->
                <div id="dash_custom_inputs" class="<?= ($range['filter'] === 'custom') ? 'flex' : 'hidden' ?> items-center gap-2">
                    <input type="date" name="start_date" aria-label="Start date" value="<?= Security::e($range['start_date']) ?>" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-600 shadow-2xs text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800">
                    <span class="text-xs text-slate-400 font-medium">to</span>
                    <input type="date" name="end_date" aria-label="End date" value="<?= Security::e($range['end_date']) ?>" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-600 shadow-2xs text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800">
                </div>

                <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 active:bg-slate-950 dark:bg-indigo-600 dark:hover:bg-indigo-500 dark:active:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-300 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter</span>
                </button>
            </form>
        </div>
    </div>

    <!-- KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- 1. Total Orders -->
        <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Orders</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-display text-slate-900 dark:text-white mt-3 tabular-nums"><?= number_format($kpis['total_orders']) ?></div>
            <div class="flex items-center gap-1.5 mt-2">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">In selected period</span>
            </div>
        </div>

        <!-- 2. Total Order Value -->
        <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Order Value</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 8h6m-5 0a3 3 0 110 6h4a3 3 0 010 6H9m2-12v14" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-display text-slate-900 dark:text-white mt-3 tabular-nums">₹<?= number_format($kpis['total_order_value'], 2) ?></div>
            <div class="flex items-center gap-1.5 mt-2">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Combined order totals</span>
            </div>
        </div>

        <!-- 3. Amount Collected -->
        <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block">Amount Collected</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-display text-emerald-700 dark:text-emerald-400 mt-3 tabular-nums">₹<?= number_format($kpis['amount_collected'], 2) ?></div>
            <div class="flex items-center gap-1.5 mt-2">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Recorded payments</span>
            </div>
        </div>

        <!-- 4. Outstanding Balance -->
        <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider block">Outstanding Balance</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-800/60 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-display text-rose-700 dark:text-rose-400 mt-3 tabular-nums">₹<?= number_format($kpis['outstanding_balance'], 2) ?></div>
            <div class="flex items-center gap-1.5 mt-2">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span class="text-xs text-rose-600 dark:text-rose-400 font-medium">Pending collection</span>
            </div>
        </div>
    </div>

    <!-- Operational Workload Alerts ("What Needs Attention Now?") -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-5 transition-colors">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Operational Workload</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active orders requiring immediate staff or operational action.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 font-semibold text-xs rounded-full border border-amber-200/80 dark:border-amber-800/60 w-fit shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span><?= number_format($kpis['active_orders_count']) ?> Active Orders</span>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <?php foreach ($workload as $st => $item): ?>
                <a href="/laundry-lms/orders?status=<?= Security::e($st) ?>" class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/90 hover:border-indigo-300 dark:hover:border-indigo-500/60 hover:shadow-xs transition-all flex justify-between items-center group focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <div class="min-w-0 pr-2">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 block group-hover:text-slate-900 dark:group-hover:text-white transition-colors truncate"><?= Security::e($item['label']) ?></span>
                        <span class="text-xl font-bold font-display text-slate-900 dark:text-white block mt-1 tabular-nums"><?= number_format($item['count']) ?></span>
                    </div>
                    <div class="w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center shrink-0 text-slate-400 dark:text-slate-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:border-indigo-200 dark:group-hover:border-indigo-500/60 transition-all">
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status & Payment Summary Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Settlement Breakdown -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-5 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Payment Settlement Status</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Distribution across collection stages</p>
                </div>
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 font-mono"><?= number_format($totalSettlementOrders) ?> orders</span>
            </div>

            <!-- Proportional Progress Bar -->
            <div class="space-y-1.5">
                <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 flex overflow-hidden">
                    <?php if ($paidPct > 0): ?>
                        <div style="width: <?= $paidPct ?>%" class="bg-emerald-500 h-full transition-all" title="Paid: <?= $paidPct ?>%"></div>
                    <?php endif; ?>
                    <?php if ($partialPct > 0): ?>
                        <div style="width: <?= $partialPct ?>%" class="bg-amber-500 h-full transition-all" title="Partial: <?= $partialPct ?>%"></div>
                    <?php endif; ?>
                    <?php if ($unpaidPct > 0): ?>
                        <div style="width: <?= $unpaidPct ?>%" class="bg-rose-500 h-full transition-all" title="Unpaid: <?= $unpaidPct ?>%"></div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between text-[11px] font-medium">
                    <span class="text-emerald-700 dark:text-emerald-400"><?= $paidPct ?>% Paid</span>
                    <span class="text-amber-700 dark:text-amber-400"><?= $partialPct ?>% Partial</span>
                    <span class="text-rose-700 dark:text-rose-400"><?= $unpaidPct ?>% Unpaid</span>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center p-3.5 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-100/90 dark:border-rose-900/40 transition-all hover:bg-rose-50 dark:hover:bg-rose-950/50">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span class="text-xs font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider">Unpaid Orders</span>
                    </div>
                    <span class="font-display font-bold text-rose-900 dark:text-rose-200 text-sm tabular-nums"><?= number_format($kpis['unpaid_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-100/90 dark:border-amber-900/40 transition-all hover:bg-amber-50 dark:hover:bg-amber-950/50">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Partially Paid Orders</span>
                    </div>
                    <span class="font-display font-bold text-amber-900 dark:text-amber-200 text-sm tabular-nums"><?= number_format($kpis['partial_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-100/90 dark:border-emerald-900/40 transition-all hover:bg-emerald-50 dark:hover:bg-emerald-950/50">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Fully Paid Orders</span>
                    </div>
                    <span class="font-display font-bold text-emerald-900 dark:text-emerald-200 text-sm tabular-nums"><?= number_format($kpis['paid_orders_count']) ?> orders</span>
                </div>
            </div>
        </div>

        <!-- 10-Step Workflow Status Summary -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-5 transition-colors">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Workflow Stage Summary</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Order distribution across all 10 pipeline stages</p>
                </div>
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 font-mono">10 Stages</span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 text-xs">
                <?php foreach ($statusSum as $stKey => $stData):
                    $badgeStyle = $getOrderStatusBadge((string)$stKey);
                ?>
                    <div class="p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-slate-100/70 dark:hover:bg-slate-800 transition-colors flex justify-between items-center">
                        <div class="flex items-center gap-2 min-w-0 pr-1">
                            <span class="w-1.5 h-1.5 rounded-full <?= $badgeStyle['dot'] ?> shrink-0"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300 truncate"><?= Security::e($stData['name']) ?></span>
                        </div>
                        <span class="font-display font-bold text-slate-900 dark:text-white tabular-nums shrink-0"><?= number_format($stData['count']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Recent Orders</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Latest 10 order entries processed by the system.</p>
            </div>
            <a href="/laundry-lms/orders" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors inline-flex items-center gap-1.5 focus:outline-none focus:underline">
                <span>View All Orders</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/90 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-5 py-3.5">Job ID</th>
                        <th class="px-5 py-3.5">Branch</th>
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">Workflow Status</th>
                        <th class="px-5 py-3.5">Total Amount</th>
                        <th class="px-5 py-3.5">Payment</th>
                        <th class="px-5 py-3.5">Assigned Staff</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 italic">No orders recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro):
                            $stBadge = $getOrderStatusBadge((string)($ro['status'] ?? ''));
                        ?>
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ro['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded"><?= Security::e($ro['job_id']) ?></a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700" title="<?= Security::e($ro['branch_name'] ?? '') ?>">
                                        <?= Security::e($ro['branch_code'] ?? ($ro['branch_name'] ?? 'MAIN')) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-white"><?= Security::e($ro['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= Security::e($ro['customer_mobile']) ?></div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap"><?= Security::e(date('M d, Y', strtotime($ro['created_at']))) ?></td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $stBadge['dot'] ?>"></span>
                                        <span><?= Security::e(str_replace('_', ' ', (string)$ro['status'])) ?></span>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-display font-bold text-slate-900 dark:text-white tabular-nums">₹<?= number_format((float)$ro['total_amount'], 2) ?></td>
                                <td class="px-5 py-3.5">
                                    <?php if ($ro['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">Paid</span>
                                    <?php elseif ($ro['payment_status'] === 'partial'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">Partial</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <?php if (!empty($ro['staff_name'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60">
                                            <?= Security::e($ro['staff_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
                                            Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1.5">
                                    <?php if (empty($ro['staff_id']) && $ro['status'] !== 'cancelled'): ?>
                                        <a href="/laundry-lms/orders/show?id=<?= (int)$ro['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500">
                                            <span>Assign</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ro['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/80 dark:border-indigo-800/60 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <span>View</span>
                                        <svg class="w-3 h-3 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(prefix) {
    var filterEl = document.getElementById(prefix + '_filter');
    var customDiv = document.getElementById(prefix + '_custom_inputs');
    if (!filterEl || !customDiv) return;
    if (filterEl.value === 'custom') {
        customDiv.classList.remove('hidden');
        customDiv.classList.add('flex');
    } else {
        customDiv.classList.add('hidden');
        customDiv.classList.remove('flex');
    }
}
</script>