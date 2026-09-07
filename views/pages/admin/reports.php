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
    <div class="bg-slate-900 text-white p-4 rounded-xl flex items-center justify-between shadow-sm">
        <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Active Reporting Period</div>
        <div class="font-mono font-bold text-sm">
            <?= htmlspecialchars(date('M d, Y', strtotime($range['start_date']))) ?> — <?= htmlspecialchars(date('M d, Y', strtotime($range['end_date']))) ?>
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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Section B: Payment Settlement Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/40 space-y-1">
                <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Amount Collected</span>
                <span class="text-3xl font-bold font-mono text-emerald-800 block">₹<?= number_format($kpis['amount_collected'], 2) ?></span>
                <span class="text-xs text-emerald-600 block">Total recorded payments</span>
            </div>
            <div class="p-5 rounded-xl border border-rose-200 bg-rose-50/40 space-y-1">
                <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Outstanding Amount</span>
                <span class="text-3xl font-bold font-mono text-rose-800 block">₹<?= number_format($kpis['outstanding_balance'], 2) ?></span>
                <span class="text-xs text-rose-600 block">Uncollected order balance</span>
            </div>
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 space-y-2 text-xs">
                <span class="font-bold text-slate-900 uppercase block border-b border-slate-200 pb-1">Payment Status Distribution</span>
                <div class="flex justify-between">
                    <span class="text-rose-600 font-medium">Unpaid Orders:</span>
                    <span class="font-mono font-bold"><?= number_format($kpis['unpaid_orders_count']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-amber-600 font-medium">Partial Orders:</span>
                    <span class="font-mono font-bold"><?= number_format($kpis['partial_orders_count']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-emerald-600 font-medium">Fully Paid Orders:</span>
                    <span class="font-mono font-bold"><?= number_format($kpis['paid_orders_count']) ?></span>
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
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-3 font-semibold text-slate-800"><?= htmlspecialchars($stData['name']) ?></td>
                            <td class="px-6 py-3 text-center font-mono font-bold text-slate-900"><?= number_format($stData['count']) ?></td>
                            <td class="px-6 py-3 text-right font-mono font-bold text-slate-900">₹<?= number_format($stData['val'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
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
