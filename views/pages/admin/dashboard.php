<div class="space-y-6">
    <?php require_once __DIR__ . '/../../components/flash.php'; ?>

    <!-- Header & Date Filter Bar -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 font-display">Operational Dashboard</h2>
            <p class="text-sm text-slate-500 mt-1">Real-time overview of laundry operations, workload, and finances.</p>
        </div>
        <div>
            <form action="/laundry-lms/dashboard" method="GET" class="flex flex-wrap items-center gap-2">
                <select name="branch_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-slate-900 bg-white">
                    <option value="">All Branches</option>
                    <?php foreach ($branches ?? [] as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= ((int)($selectedBranchId ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter" id="dash_filter" onchange="toggleCustomDates('dash')" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-slate-900">
                    <option value="today" <?= ($range['filter'] === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="last_7_days" <?= ($range['filter'] === 'last_7_days') ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30_days" <?= ($range['filter'] === 'last_30_days') ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="custom" <?= ($range['filter'] === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <div id="dash_custom_inputs" class="<?= ($range['filter'] === 'custom') ? 'flex' : 'hidden' ?> items-center gap-2">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($range['start_date']) ?>" class="px-3 py-1.5 rounded-xl border border-slate-300 text-sm">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($range['end_date']) ?>" class="px-3 py-1.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">Filter</button>
            </form>
        </div>
    </div>

    <!-- KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Orders</span>
            <div class="text-3xl font-bold font-mono text-slate-900 mt-2"><?= number_format($kpis['total_orders']) ?></div>
            <span class="text-xs text-slate-500 mt-1 block">In selected period</span>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Order Value</span>
            <div class="text-3xl font-bold font-mono text-slate-900 mt-2">₹<?= number_format($kpis['total_order_value'], 2) ?></div>
            <span class="text-xs text-slate-500 mt-1 block">Combined order totals</span>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Amount Collected</span>
            <div class="text-3xl font-bold font-mono text-emerald-700 mt-2">₹<?= number_format($kpis['amount_collected'], 2) ?></div>
            <span class="text-xs text-emerald-600/80 mt-1 block">Recorded payments</span>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Outstanding Balance</span>
            <div class="text-3xl font-bold font-mono text-rose-700 mt-2">₹<?= number_format($kpis['outstanding_balance'], 2) ?></div>
            <span class="text-xs text-rose-600/80 mt-1 block">Pending collection</span>
        </div>
    </div>

    <!-- Operational Workload Alerts ("What Needs Attention Now?") -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Operational Workload</h3>
                <p class="text-xs text-slate-500 mt-0.5">Active orders requiring operational action right now.</p>
            </div>
            <span class="px-3 py-1 bg-amber-50 text-amber-700 font-semibold text-xs rounded-full border border-amber-200">
                <?= number_format($kpis['active_orders_count']) ?> Active Orders
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($workload as $st => $item): ?>
                <a href="/laundry-lms/orders?status=<?= $st ?>" class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors flex justify-between items-center">
                    <div>
                        <span class="text-xs font-medium text-slate-600 block"><?= htmlspecialchars($item['label']) ?></span>
                        <span class="text-xl font-bold font-mono text-slate-900 block mt-1"><?= number_format($item['count']) ?></span>
                    </div>
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status & Payment Summary Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Settlement Breakdown -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Payment Settlement Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 rounded-xl bg-rose-50 border border-rose-100">
                    <span class="text-xs font-semibold text-rose-700 uppercase">Unpaid Orders</span>
                    <span class="font-mono font-bold text-rose-900 text-sm"><?= number_format($kpis['unpaid_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3 rounded-xl bg-amber-50 border border-amber-100">
                    <span class="text-xs font-semibold text-amber-700 uppercase">Partially Paid Orders</span>
                    <span class="font-mono font-bold text-amber-900 text-sm"><?= number_format($kpis['partial_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                    <span class="text-xs font-semibold text-emerald-700 uppercase">Fully Paid Orders</span>
                    <span class="font-mono font-bold text-emerald-900 text-sm"><?= number_format($kpis['paid_orders_count']) ?> orders</span>
                </div>
            </div>
        </div>

        <!-- 10-Step Workflow Status Summary -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Workflow Stage Summary</h3>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <?php foreach ($statusSum as $stKey => $stData): ?>
                    <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <span class="font-medium text-slate-700"><?= htmlspecialchars($stData['name']) ?></span>
                        <span class="font-mono font-bold text-slate-900"><?= number_format($stData['count']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Orders</h3>
                <p class="text-xs text-slate-500 mt-0.5">Top 10 latest order entries.</p>
            </div>
            <a href="/laundry-lms/orders" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View All Orders &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                    <tr>
                        <th class="px-6 py-3">Job ID</th>
                        <th class="px-6 py-3">Branch</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Created Date</th>
                        <th class="px-6 py-3">Workflow Status</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3">Payment Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 italic">No orders recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 font-mono font-bold text-slate-900">
                                    <a href="/laundry-lms/orders/show?id=<?= $ro['id'] ?>" class="hover:text-indigo-600"><?= htmlspecialchars($ro['job_id']) ?></a>
                                </td>
                                <td class="px-6 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200" title="<?= htmlspecialchars($ro['branch_name'] ?? '') ?>">
                                        <?= htmlspecialchars($ro['branch_code'] ?? ($ro['branch_name'] ?? 'MAIN')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($ro['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($ro['customer_mobile']) ?></div>
                                </td>
                                <td class="px-6 py-3 text-slate-500"><?= htmlspecialchars(date('M d, Y', strtotime($ro['created_at']))) ?></td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= str_replace('_', ' ', htmlspecialchars($ro['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-mono font-bold text-slate-900">₹<?= number_format((float)$ro['total_amount'], 2) ?></td>
                                <td class="px-6 py-3">
                                    <?php if ($ro['payment_status'] === 'paid'): ?>
                                        <span class="text-emerald-600 font-semibold">Paid</span>
                                    <?php elseif ($ro['payment_status'] === 'partial'): ?>
                                        <span class="text-amber-600 font-semibold">Partial</span>
                                    <?php else: ?>
                                        <span class="text-rose-500 font-semibold">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="/laundry-lms/orders/show?id=<?= $ro['id'] ?>" class="text-indigo-600 hover:text-indigo-900 font-semibold bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg border border-indigo-100 transition-colors">View</a>
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