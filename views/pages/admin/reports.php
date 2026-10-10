<div class="space-y-6">
    <?php require_once __DIR__ . '/../../components/flash.php'; ?>

    <!-- Header & Date Filter Bar -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 font-display">Operational Reports</h2>
            <p class="text-sm text-slate-500 mt-1">Detailed performance metrics, workflow distribution, and settlement breakdown.</p>
        </div>
        <div>
            <form action="/laundry-lms/reports" method="GET" class="flex flex-wrap items-center gap-2">
                <?php if (($userRole ?? '') === 'admin'): ?>
                    <select name="branch_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-slate-900 bg-white">
                        <option value="">All Branches</option>
                        <?php foreach ($branches ?? [] as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= ((int)($selectedBranchId ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (($userRole ?? '') === 'staff' && !empty($currentBranch)): ?>
                    <div class="px-3.5 py-2 bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold rounded-xl flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Branch: <?= htmlspecialchars($currentBranch['name']) ?> (<?= htmlspecialchars($currentBranch['code']) ?>)</span>
                    </div>
                <?php endif; ?>

                <select name="filter" id="rep_filter" onchange="toggleCustomDates('rep')" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-slate-900">
                    <option value="today" <?= ($range['filter'] === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="last_7_days" <?= ($range['filter'] === 'last_7_days') ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30_days" <?= ($range['filter'] === 'last_30_days') ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="custom" <?= ($range['filter'] === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <div id="rep_custom_inputs" class="<?= ($range['filter'] === 'custom') ? 'flex' : 'hidden' ?> items-center gap-2">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($range['start_date']) ?>" class="px-3 py-1.5 rounded-xl border border-slate-300 text-sm">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($range['end_date']) ?>" class="px-3 py-1.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">Apply Filter</button>
            </form>
        </div>
    </div>

    <!-- Active Reporting Date Range Badge -->
    <div class="bg-slate-900 text-white p-4 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 shadow-sm">
        <div>
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Active Reporting Period</div>
            <div class="font-mono font-bold text-sm">
                <?= htmlspecialchars(date('M d, Y', strtotime($range['start_date']))) ?> — <?= htmlspecialchars(date('M d, Y', strtotime($range['end_date']))) ?>
            </div>
        </div>
        <div class="text-xs bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg text-slate-300 flex items-center gap-1.5">
            <span class="text-slate-400 font-medium">Scope:</span>
            <strong class="text-white font-semibold">
                <?php if (!empty($currentBranch)): ?>
                    <?= htmlspecialchars($currentBranch['name']) ?> (<?= htmlspecialchars($currentBranch['code']) ?>)
                <?php else: ?>
                    All Branches (Company-wide)
                <?php endif; ?>
            </strong>
        </div>
    </div>

    <!-- Operational Attention Snapshot (Overdue Indicators) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-5 rounded-2xl border <?= (($overdueCounts['overdue_pickups'] ?? 0) > 0) ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200' ?> shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider <?= (($overdueCounts['overdue_pickups'] ?? 0) > 0) ? 'text-rose-700' : 'text-slate-500' ?>">Overdue Pickups</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full <?= (($overdueCounts['overdue_pickups'] ?? 0) > 0) ? 'bg-rose-100 text-rose-800 font-bold' : 'bg-slate-100 text-slate-600' ?>">Current Snapshot</span>
                </div>
                <div class="text-2xl font-bold font-mono <?= (($overdueCounts['overdue_pickups'] ?? 0) > 0) ? 'text-rose-700' : 'text-slate-900' ?> mt-1">
                    <?= number_format($overdueCounts['overdue_pickups'] ?? 0) ?>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Orders in 'pickup_request' or 'accepted' with pickup date prior to today.</p>
            </div>
            <div class="p-3 rounded-xl <?= (($overdueCounts['overdue_pickups'] ?? 0) > 0) ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-400' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border <?= (($overdueCounts['overdue_deliveries'] ?? 0) > 0) ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200' ?> shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider <?= (($overdueCounts['overdue_deliveries'] ?? 0) > 0) ? 'text-amber-700' : 'text-slate-500' ?>">Overdue Deliveries</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full <?= (($overdueCounts['overdue_deliveries'] ?? 0) > 0) ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-100 text-slate-600' ?>">Current Snapshot</span>
                </div>
                <div class="text-2xl font-bold font-mono <?= (($overdueCounts['overdue_deliveries'] ?? 0) > 0) ? 'text-amber-700' : 'text-slate-900' ?> mt-1">
                    <?= number_format($overdueCounts['overdue_deliveries'] ?? 0) ?>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">In-flight active orders with delivery date prior to today.</p>
            </div>
            <div class="p-3 rounded-xl <?= (($overdueCounts['overdue_deliveries'] ?? 0) > 0) ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Section A: Order Summary Report Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Section A: Order Summary Report</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Orders Created</span>
                <span class="text-2xl font-bold font-mono text-slate-900 block mt-1"><?= number_format($kpis['total_orders']) ?></span>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Order Value</span>
                <span class="text-2xl font-bold font-mono text-slate-900 block mt-1">₹<?= number_format($kpis['total_order_value'], 2) ?></span>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-emerald-50/50">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Completed Orders</span>
                <span class="text-2xl font-bold font-mono text-emerald-700 block mt-1"><?= number_format($kpis['completed_orders_count']) ?></span>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-amber-50/50">
                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Active Orders In Progress</span>
                <span class="text-2xl font-bold font-mono text-amber-700 block mt-1"><?= number_format($kpis['active_orders_count']) ?></span>
            </div>
        </div>
    </div>

    <!-- Section B: Financial & Payment Settlement Report -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div>
                <h3 class="text-base font-bold text-slate-900">Section B: Financial & Payment Settlement Report</h3>
                <p class="text-xs text-slate-500 mt-0.5">Cash receipts and refund processing recorded during this period, alongside order cohort receivables.</p>
            </div>
            <div class="text-xs text-slate-400 font-mono">
                Order Value Booked: <strong class="text-slate-700 font-bold">₹<?= number_format($kpis['total_order_value'], 2) ?></strong>
            </div>
        </div>

        <!-- Cashflow Movement (Receipts, Refunds, Net) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Gross Payments Recorded -->
            <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/40 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Gross Payments Recorded</span>
                    <span class="text-xs font-mono font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">
                        <?= number_format($financials['gross_payments_count'] ?? 0) ?> txns
                    </span>
                </div>
                <span class="text-3xl font-bold font-mono text-emerald-800 block">
                    ₹<?= number_format($financials['gross_payments_recorded'] ?? 0, 2) ?>
                </span>
                <span class="text-xs text-emerald-600 block">Payments recorded during selected period</span>
            </div>

            <!-- Processed Refunds Recorded -->
            <div class="p-5 rounded-xl border border-amber-200 bg-amber-50/40 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">Processed Refunds Recorded</span>
                    <span class="text-xs font-mono font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                        <?= number_format($financials['processed_refunds_count'] ?? 0) ?> refunds
                    </span>
                </div>
                <span class="text-3xl font-bold font-mono text-amber-800 block">
                    ₹<?= number_format($financials['processed_refunds_recorded'] ?? 0, 2) ?>
                </span>
                <span class="text-xs text-amber-600 block">Refunds settled during selected period</span>
            </div>

            <!-- Net Recorded Collections -->
            <?php
                $netVal = (float)($financials['net_recorded_collections'] ?? 0);
                $netFormatted = ($netVal < 0)
                    ? '-₹' . number_format(abs($netVal), 2)
                    : '₹' . number_format($netVal, 2);
            ?>
            <div class="p-5 rounded-xl border border-slate-900 bg-slate-900 text-white space-y-1 shadow-sm">
                <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider block">Net Recorded Collections</span>
                <span class="text-3xl font-bold font-mono text-white block">
                    <?= $netFormatted ?>
                </span>
                <span class="text-xs text-slate-400 block">Gross recorded payments minus processed refunds. Covers recorded transactions only.</span>
            </div>
        </div>

        <!-- Receivables, Method Breakdown & Order Settlement Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-2 border-t border-slate-100">
            <!-- Outstanding Order Balance -->
            <div class="p-5 rounded-xl border border-rose-200 bg-rose-50/40 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Outstanding Order Balance</span>
                </div>
                <span class="text-3xl font-bold font-mono text-rose-800 block">
                    ₹<?= number_format($kpis['outstanding_balance'], 2) ?>
                </span>
                <span class="text-xs text-rose-600 block">Uncollected order balance for orders created in period</span>
            </div>

            <!-- Payment Method Breakdown -->
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 space-y-2 text-xs">
                <span class="font-bold text-slate-900 uppercase block border-b border-slate-200 pb-1">Payment Method Breakdown</span>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-slate-600 font-medium">Cash:</span>
                    <span class="font-mono font-bold text-slate-900">
                        ₹<?= number_format($financials['by_method']['cash']['amount'] ?? 0, 2) ?>
                        <span class="text-slate-400 font-normal">(<?= (int)($financials['by_method']['cash']['count'] ?? 0) ?>)</span>
                    </span>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-slate-600 font-medium">UPI:</span>
                    <span class="font-mono font-bold text-slate-900">
                        ₹<?= number_format($financials['by_method']['upi']['amount'] ?? 0, 2) ?>
                        <span class="text-slate-400 font-normal">(<?= (int)($financials['by_method']['upi']['count'] ?? 0) ?>)</span>
                    </span>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-slate-600 font-medium">Online Gateway:</span>
                    <span class="font-mono font-bold text-slate-900">
                        ₹<?= number_format($financials['by_method']['online']['amount'] ?? 0, 2) ?>
                        <span class="text-slate-400 font-normal">(<?= (int)($financials['by_method']['online']['count'] ?? 0) ?>)</span>
                    </span>
                </div>
            </div>

            <!-- Order Settlement Status -->
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 space-y-2 text-xs">
                <span class="font-bold text-slate-900 uppercase block border-b border-slate-200 pb-1">Order Settlement Status</span>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-rose-600 font-medium">Unpaid Orders:</span>
                    <span class="font-mono font-bold text-slate-900"><?= number_format($kpis['unpaid_orders_count']) ?></span>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-amber-600 font-medium">Partial Orders:</span>
                    <span class="font-mono font-bold text-slate-900"><?= number_format($kpis['partial_orders_count']) ?></span>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-emerald-600 font-medium">Fully Paid Orders:</span>
                    <span class="font-mono font-bold text-slate-900"><?= number_format($kpis['paid_orders_count']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section C: 10-Step Workflow Status Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Section C: Workflow Status Distribution Report</h3>
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                    <tr>
                        <th class="px-6 py-3">Workflow Stage</th>
                        <th class="px-6 py-3 text-center">Order Count</th>
                        <th class="px-6 py-3 text-right">Combined Order Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($statusSum as $stKey => $stData): ?>
                        <tr class="hover:bg-slate-50/50 <?= ($stKey === 'cancelled') ? 'bg-rose-50/30' : '' ?>">
                            <td class="px-6 py-3 font-semibold text-slate-800">
                                <?= htmlspecialchars($stData['name']) ?>
                                <?php if ($stKey === 'cancelled'): ?>
                                    <span class="ml-2 text-[10px] font-mono px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Terminal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-center font-mono font-bold <?= ($stKey === 'cancelled') ? 'text-rose-700' : 'text-slate-900' ?>"><?= number_format($stData['count']) ?></td>
                            <td class="px-6 py-3 text-right font-mono font-bold <?= ($stKey === 'cancelled') ? 'text-rose-700' : 'text-slate-900' ?>">₹<?= number_format($stData['val'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section D: Daily Performance Ledger -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div>
                <h3 class="text-base font-bold text-slate-900">Section D: Daily Performance Ledger</h3>
                <p class="text-xs text-slate-500 mt-0.5">Chronological day-by-day business volume and financial transaction ledger for the selected period.</p>
            </div>
            <div class="text-xs text-slate-400">
                <span class="font-medium text-slate-600"><?= count($dailyTrends ?? []) ?></span> calendar days in period
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-[500px] overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 bg-slate-50">Date</th>
                        <th class="px-4 py-3 text-center bg-slate-50">Orders Created</th>
                        <th class="px-4 py-3 text-right bg-slate-50">Booked Value</th>
                        <th class="px-4 py-3 text-right bg-slate-50">Gross Payments</th>
                        <th class="px-4 py-3 text-right bg-slate-50">Processed Refunds</th>
                        <th class="px-4 py-3 text-right bg-slate-50">Net Collections</th>
                        <th class="px-4 py-3 text-center bg-slate-50">Cohort Completed</th>
                        <th class="px-4 py-3 text-center bg-slate-50">Cohort Cancelled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                        $sumOrders = 0;
                        $sumBooked = 0.00;
                        $sumGross = 0.00;
                        $sumGrossCnt = 0;
                        $sumRef = 0.00;
                        $sumRefCnt = 0;
                        $sumNet = 0.00;
                        $sumCompCohort = 0;
                        $sumCancCohort = 0;
                    ?>
                    <?php if (empty($dailyTrends)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400 italic">No activity in selected period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dailyTrends as $row): ?>
                            <?php
                                $sumOrders += $row['orders_created'];
                                $sumBooked += $row['total_order_value'];
                                $sumGross += $row['gross_payments_recorded'];
                                $sumGrossCnt += $row['gross_payments_count'];
                                $sumRef += $row['processed_refunds_recorded'];
                                $sumRefCnt += $row['processed_refunds_count'];
                                $sumNet += $row['net_recorded_collections'];
                                $sumCompCohort += $row['completed_cohort_count'];
                                $sumCancCohort += $row['cancelled_cohort_count'];

                                $dayNet = (float)$row['net_recorded_collections'];
                                $dayNetFmt = ($dayNet < 0) ? '-₹' . number_format(abs($dayNet), 2) : '₹' . number_format($dayNet, 2);
                            ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2.5 font-mono font-medium text-slate-700 whitespace-nowrap">
                                    <?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono <?= ($row['orders_created'] > 0) ? 'font-bold text-slate-900' : 'text-slate-400' ?>">
                                    <?= number_format($row['orders_created']) ?>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono <?= ($row['total_order_value'] > 0) ? 'font-bold text-slate-900' : 'text-slate-400' ?>">
                                    ₹<?= number_format($row['total_order_value'], 2) ?>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono whitespace-nowrap <?= ($row['gross_payments_recorded'] > 0) ? 'text-emerald-700 font-bold' : 'text-slate-400' ?>">
                                    ₹<?= number_format($row['gross_payments_recorded'], 2) ?>
                                    <?php if ($row['gross_payments_count'] > 0): ?>
                                        <span class="text-[10px] font-normal text-emerald-600 block">(<?= $row['gross_payments_count'] ?> txns)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono whitespace-nowrap <?= ($row['processed_refunds_recorded'] > 0) ? 'text-amber-700 font-bold' : 'text-slate-400' ?>">
                                    ₹<?= number_format($row['processed_refunds_recorded'], 2) ?>
                                    <?php if ($row['processed_refunds_count'] > 0): ?>
                                        <span class="text-[10px] font-normal text-amber-600 block">(<?= $row['processed_refunds_count'] ?> ref)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold whitespace-nowrap <?= ($dayNet > 0) ? 'text-slate-900' : (($dayNet < 0) ? 'text-rose-600' : 'text-slate-400') ?>">
                                    <?= $dayNetFmt ?>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono <?= ($row['completed_cohort_count'] > 0) ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?>">
                                    <?= number_format($row['completed_cohort_count']) ?>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono <?= ($row['cancelled_cohort_count'] > 0) ? 'text-rose-600 font-semibold' : 'text-slate-400' ?>">
                                    <?= number_format($row['cancelled_cohort_count']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-100 border-t-2 border-slate-200 font-bold text-slate-900 sticky bottom-0 z-10">
                    <tr>
                        <td class="px-4 py-3 uppercase text-[11px] tracking-wider text-slate-600">Total / Summary</td>
                        <td class="px-4 py-3 text-center font-mono"><?= number_format($sumOrders) ?></td>
                        <td class="px-4 py-3 text-right font-mono">₹<?= number_format($sumBooked, 2) ?></td>
                        <td class="px-4 py-3 text-right font-mono text-emerald-800">
                            ₹<?= number_format($sumGross, 2) ?>
                            <span class="text-[10px] font-normal block">(<?= number_format($sumGrossCnt) ?> txns)</span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-amber-800">
                            ₹<?= number_format($sumRef, 2) ?>
                            <span class="text-[10px] font-normal block">(<?= number_format($sumRefCnt) ?> refunds)</span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            <?= ($sumNet < 0) ? '-₹' . number_format(abs($sumNet), 2) : '₹' . number_format($sumNet, 2) ?>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-emerald-700"><?= number_format($sumCompCohort) ?></td>
                        <td class="px-4 py-3 text-center font-mono text-rose-700"><?= number_format($sumCancCohort) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="text-[11px] text-slate-500 flex flex-wrap gap-x-6 gap-y-1 pt-1">
            <span>* <strong>Cohort Completed / Cancelled:</strong> Reflects current status of orders created on that calendar date.</span>
            <span>* <strong>Payments & Refunds:</strong> Attributed to the transaction date when recorded/processed, matching cash movements.</span>
        </div>
    </div>

    <!-- Section E: Order Economics & Value Distribution Report -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div>
                <h3 class="text-base font-bold text-slate-900">Section E: Order Economics & Ticket Size Distribution</h3>
                <p class="text-xs text-slate-500 mt-0.5">Basket size analytics and ticket tier breakdown across all orders created in this period.</p>
            </div>
            <div class="text-xs text-slate-400">
                Total Orders: <strong class="text-slate-800 font-bold"><?= number_format($orderEconomics['total_orders'] ?? 0) ?></strong>
            </div>
        </div>

        <!-- AOV Header Card -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl border border-indigo-100 bg-indigo-50/40">
                <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider block">Average Order Value (AOV)</span>
                <span class="text-3xl font-bold font-mono text-indigo-900 block mt-1">₹<?= number_format($orderEconomics['average_order_value'] ?? 0, 2) ?></span>
                <span class="text-xs text-indigo-600 block mt-1">Booked Value / Total Orders</span>
            </div>
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Booked Volume</span>
                <span class="text-3xl font-bold font-mono text-slate-900 block mt-1"><?= number_format($orderEconomics['total_orders'] ?? 0) ?></span>
                <span class="text-xs text-slate-500 block mt-1">Orders in selected cohort</span>
            </div>
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Combined Booked Value</span>
                <span class="text-3xl font-bold font-mono text-slate-900 block mt-1">₹<?= number_format($orderEconomics['total_order_value'] ?? 0, 2) ?></span>
                <span class="text-xs text-slate-500 block mt-1">Aggregate ticket value</span>
            </div>
        </div>

        <!-- Value Tiers Grid -->
        <div class="space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Ticket Size Tier Breakdown</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach (($orderEconomics['tiers'] ?? []) as $tierKey => $tier): ?>
                    <div class="p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-colors space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900"><?= htmlspecialchars($tier['label']) ?></span>
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                <?= number_format($tier['percentage'], 1) ?>%
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between pt-1">
                            <div>
                                <span class="text-2xl font-bold font-mono text-slate-900"><?= number_format($tier['count']) ?></span>
                                <span class="text-xs text-slate-400 ml-1">orders</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-mono font-semibold text-slate-700 block">₹<?= number_format($tier['value'], 2) ?></span>
                            </div>
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: <?= min(100, max(0, (float)$tier['percentage'])) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(prefix) {
    const filter = document.getElementById(prefix + '_filter').value;
    const customDiv = document.getElementById(prefix + '_custom_inputs');
    if (filter === 'custom') {
        customDiv.classList.remove('hidden');
        customDiv.classList.add('flex');
    } else {
        customDiv.classList.add('hidden');
        customDiv.classList.remove('flex');
    }
}
</script>
