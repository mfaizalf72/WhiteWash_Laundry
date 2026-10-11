<?php
use Faizal\LaundryLms\Helpers\Security;

// Helper for semantic order status styling
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pending_pickup'     => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'pending_inspection' => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'in_process'         => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'washing'            => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'drying'             => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'ironing'            => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
        'ready_for_pickup'   => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200'],
        'out_for_delivery'   => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200'],
        'completed'          => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
        'cancelled'          => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200']
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200'];
};
?>
<div class="space-y-6">

    <!-- Header & Date Filter Bar -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 font-display">Operational Dashboard</h2>
            <p class="text-sm text-slate-500 mt-1">Real-time overview of laundry operations, workload, and finances.</p>
        </div>
        <div class="w-full lg:w-auto">
            <form action="/laundry-lms/dashboard" method="GET" class="flex flex-wrap items-center gap-2">
                <select name="branch_id" onchange="this.form.submit()" aria-label="Filter by branch" class="px-3 py-2 rounded-lg border border-slate-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches ?? [] as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= ((int)($selectedBranchId ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                            <?= Security::e($b['name']) ?> (<?= Security::e($b['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter" id="dash_filter" onchange="toggleCustomDates('dash')" aria-label="Date range preset" class="px-3 py-2 rounded-lg border border-slate-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm">
                    <option value="today" <?= ($range['filter'] === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="last_7_days" <?= ($range['filter'] === 'last_7_days') ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30_days" <?= ($range['filter'] === 'last_30_days') ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="custom" <?= ($range['filter'] === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <div id="dash_custom_inputs" class="<?= ($range['filter'] === 'custom') ? 'flex' : 'hidden' ?> items-center gap-2">
                    <input type="date" name="start_date" aria-label="Start date" value="<?= Security::e($range['start_date']) ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" name="end_date" aria-label="End date" value="<?= Security::e($range['end_date']) ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Filter</button>
            </form>
        </div>
    </div>

    <!-- KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Orders</span>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-2 tabular-nums"><?= number_format($kpis['total_orders']) ?></div>
            <span class="text-xs text-slate-500 mt-1 block">In selected period</span>
        </div>

        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Order Value</span>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-slate-900 mt-2 tabular-nums">₹<?= number_format($kpis['total_order_value'], 2) ?></div>
            <span class="text-xs text-slate-500 mt-1 block">Combined order totals</span>
        </div>

        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Amount Collected</span>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-emerald-700 mt-2 tabular-nums">₹<?= number_format($kpis['amount_collected'], 2) ?></div>
            <span class="text-xs text-emerald-600 mt-1 block">Recorded payments</span>
        </div>

        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Outstanding Balance</span>
            <div class="text-2xl sm:text-3xl font-bold font-mono text-rose-700 mt-2 tabular-nums">₹<?= number_format($kpis['outstanding_balance'], 2) ?></div>
            <span class="text-xs text-rose-600 mt-1 block">Pending collection</span>
        </div>
    </div>

    <!-- Operational Workload Alerts ("What Needs Attention Now?") -->
    <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Operational Workload</h3>
                <p class="text-xs text-slate-500 mt-0.5">Active orders requiring operational action right now.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-700 font-semibold text-xs rounded-full border border-amber-200 w-fit">
                <?= number_format($kpis['active_orders_count']) ?> Active Orders
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($workload as $st => $item): ?>
                <a href="/laundry-lms/orders?status=<?= Security::e($st) ?>" class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-100/80 hover:border-slate-300 transition-all flex justify-between items-center group focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div>
                        <span class="text-xs font-medium text-slate-600 block group-hover:text-slate-900 transition-colors"><?= Security::e($item['label']) ?></span>
                        <span class="text-xl font-bold font-mono text-slate-900 block mt-1 tabular-nums"><?= number_format($item['count']) ?></span>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status & Payment Summary Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Payment Settlement Breakdown -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Payment Settlement Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 rounded-lg bg-rose-50/80 border border-rose-100">
                    <span class="text-xs font-semibold text-rose-800 uppercase">Unpaid Orders</span>
                    <span class="font-mono font-bold text-rose-900 text-sm tabular-nums"><?= number_format($kpis['unpaid_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3 rounded-lg bg-amber-50/80 border border-amber-100">
                    <span class="text-xs font-semibold text-amber-800 uppercase">Partially Paid Orders</span>
                    <span class="font-mono font-bold text-amber-900 text-sm tabular-nums"><?= number_format($kpis['partial_orders_count']) ?> orders</span>
                </div>
                <div class="flex justify-between items-center p-3 rounded-lg bg-emerald-50/80 border border-emerald-100">
                    <span class="text-xs font-semibold text-emerald-800 uppercase">Fully Paid Orders</span>
                    <span class="font-mono font-bold text-emerald-900 text-sm tabular-nums"><?= number_format($kpis['paid_orders_count']) ?> orders</span>
                </div>
            </div>
        </div>

        <!-- 10-Step Workflow Status Summary -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Workflow Stage Summary</h3>
            <div class="grid grid-cols-2 gap-2.5 text-xs">
                <?php foreach ($statusSum as $stKey => $stData): ?>
                    <div class="p-2.5 rounded-lg border border-slate-100 bg-slate-50/60 flex justify-between items-center">
                        <span class="font-medium text-slate-700"><?= Security::e($stData['name']) ?></span>
                        <span class="font-mono font-bold text-slate-900 tabular-nums"><?= number_format($stData['count']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Orders</h3>
                <p class="text-xs text-slate-500 mt-0.5">Top 10 latest order entries.</p>
            </div>
            <a href="/laundry-lms/orders" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
                <span>View All Orders</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Job ID</th>
                        <th class="px-5 py-3">Branch</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Created Date</th>
                        <th class="px-5 py-3">Workflow Status</th>
                        <th class="px-5 py-3">Total Amount</th>
                        <th class="px-5 py-3">Payment Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400 italic">No orders recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro):
                            $stBadge = $getOrderStatusBadge((string)($ro['status'] ?? ''));
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-3 font-mono font-bold text-blue-600">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ro['id'] ?>" class="hover:underline focus:outline-none focus:ring-1 focus:ring-blue-500 rounded"><?= Security::e($ro['job_id']) ?></a>
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200" title="<?= Security::e($ro['branch_name'] ?? '') ?>">
                                        <?= Security::e($ro['branch_code'] ?? ($ro['branch_name'] ?? 'MAIN')) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-800"><?= Security::e($ro['customer_name']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= Security::e($ro['customer_mobile']) ?></div>
                                </td>
                                <td class="px-5 py-3 text-slate-500 whitespace-nowrap"><?= Security::e(date('M d, Y', strtotime($ro['created_at']))) ?></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                                        <?= Security::e(str_replace('_', ' ', (string)$ro['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-mono font-bold text-slate-900 tabular-nums">₹<?= number_format((float)$ro['total_amount'], 2) ?></td>
                                <td class="px-5 py-3">
                                    <?php if ($ro['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Paid</span>
                                    <?php elseif ($ro['payment_status'] === 'partial'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Partial</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="/laundry-lms/orders/show?id=<?= (int)$ro['id'] ?>" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">View</a>
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