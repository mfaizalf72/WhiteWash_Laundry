<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Models\Order;

$nextStatus = Order::getNextStatus($order['status']);
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <!-- Header Card -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <a href="/laundry-lms/orders" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                &larr; Back to Orders Directory
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold font-mono text-slate-900"><?= htmlspecialchars($order['job_id']) ?></h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <?= str_replace('_', ' ', htmlspecialchars($order['status'])) ?>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Created on <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($order['created_at']))) ?></p>
        </div>
        <div class="text-right">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Amount</span>
            <span class="text-2xl font-bold font-mono text-slate-900">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 mt-1 uppercase">
                <?= htmlspecialchars($order['payment_status']) ?>
            </span>
        </div>
    </div>

    <!-- Status Transition Control Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Current Workflow Stage</span>
            <div class="font-bold text-slate-900 text-lg capitalize mt-0.5"><?= str_replace('_', ' ', htmlspecialchars($order['status'])) ?></div>
        </div>
        <div>
            <?php if ($nextStatus): ?>
                <form action="/laundry-lms/orders/status" method="POST" class="flex items-center gap-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($nextStatus) ?>">
                    <button type="submit" onclick="return confirm('Advance order status to \'<?= str_replace('_', ' ', $nextStatus) ?>\'?');" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2">
                        <span>Advance to: <strong><?= str_replace('_', ' ', htmlspecialchars($nextStatus)) ?></strong></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </form>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold border border-emerald-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Order Workflow Completed
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order & Customer Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer Info Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Customer Profile</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Name</span>
                    <span class="font-bold text-slate-900 block"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Mobile</span>
                    <span class="font-mono text-slate-700 block"><?= htmlspecialchars($order['customer_mobile']) ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Address</span>
                    <span class="text-slate-700 block text-xs bg-slate-50 p-3 rounded-xl border border-slate-200 mt-1">
                        <?= !empty($order['customer_address']) ? nl2br(htmlspecialchars($order['customer_address'])) : 'No address provided' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Schedule & Staff Info Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Schedule & Assignment</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Pickup Date</span>
                    <span class="font-medium text-slate-800 block"><?= htmlspecialchars(date('F j, Y', strtotime($order['pickup_date']))) ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Expected Delivery Date</span>
                    <span class="font-medium text-slate-800 block"><?= !empty($order['delivery_date']) ? htmlspecialchars(date('F j, Y', strtotime($order['delivery_date']))) : 'Not specified' ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Recorded By</span>
                    <span class="font-medium text-slate-800 block"><?= htmlspecialchars($order['staff_name'] ?? 'System Admin') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php
    $totalAmt  = round((float)$order['total_amount'], 2);
    $paidAmt   = round((float)$order['amount_paid'], 2);
    $remBal    = round($totalAmt - $paidAmt, 2);
    if ($remBal < 0) { $remBal = 0.00; }
    ?>

    <!-- Financial & Payment Settlement Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Payment Settlement</h3>
                <p class="text-xs text-slate-500 mt-0.5">Track payments and record new cash or UPI settlements.</p>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($order['payment_status'] === 'paid'): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Fully Paid
                    </span>
                <?php elseif ($order['payment_status'] === 'partial'): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                        Partial Payment
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                        Unpaid
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Order Total</span>
                <span class="text-xl font-bold font-mono text-slate-900 block mt-1">₹<?= number_format($totalAmt, 2) ?></span>
            </div>
            <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Amount Paid</span>
                <span class="text-xl font-bold font-mono text-emerald-700 block mt-1">₹<?= number_format($paidAmt, 2) ?></span>
            </div>
            <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-100">
                <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Remaining Balance</span>
                <span class="text-xl font-bold font-mono text-rose-700 block mt-1">₹<?= number_format($remBal, 2) ?></span>
            </div>
        </div>

        <!-- Record Payment Form (Shown if remaining balance > 0) -->
        <?php if ($remBal > 0): ?>
            <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 space-y-4">
                <h4 class="text-sm font-bold text-slate-900">Record New Payment</h4>
                <form action="/laundry-lms/orders/payment" method="POST" class="space-y-4">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Payment Method</label>
                            <select id="payment_method" name="payment_method" required onchange="toggleUpiField()" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" max="<?= sprintf('%.2f', $remBal) ?>" name="amount" value="<?= sprintf('%.2f', $remBal) ?>" required placeholder="e.g. <?= sprintf('%.2f', $remBal) ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 font-mono">
                        </div>
                        <div id="upi_ref_container" class="hidden">
                            <label class="block text-xs font-medium text-slate-700 mb-1">UPI Reference / Txn ID</label>
                            <input type="text" id="upi_reference" name="upi_reference" placeholder="e.g. 123456789012" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 font-mono">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors shadow-sm">
                            Submit Payment
                        </button>
                    </div>
                </form>
            </div>
            <script>
            function toggleUpiField() {
                const method = document.getElementById('payment_method').value;
                const container = document.getElementById('upi_ref_container');
                const refInput = document.getElementById('upi_reference');
                if (method === 'upi') {
                    container.classList.remove('hidden');
                    refInput.setAttribute('required', 'required');
                } else {
                    container.classList.add('hidden');
                    refInput.removeAttribute('required');
                    refInput.value = '';
                }
            }
            </script>
        <?php endif; ?>

        <!-- Payment History Audit Table -->
        <div>
            <h4 class="text-sm font-bold text-slate-900 mb-3">Payment History</h4>
            <?php if (empty($payments)): ?>
                <p class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-xl border border-slate-200">No payment records found for this order.</p>
            <?php else: ?>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Date & Time</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">UPI Ref</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(date('M d, Y, g:i a', strtotime($p['created_at']))) ?></td>
                                    <td class="px-4 py-3 font-semibold uppercase text-slate-800"><?= htmlspecialchars($p['payment_method']) ?></td>
                                    <td class="px-4 py-3 font-mono text-slate-600"><?= !empty($p['upi_reference']) ? htmlspecialchars($p['upi_reference']) : '<em class="text-slate-400">N/A</em>' ?></td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-900">₹<?= number_format((float)$p['amount'], 2) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($p['recorded_by_name'] ?? 'System User') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Special Instructions & Service Breakdown -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Order Details & Service Breakdown</h3>
        <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono whitespace-pre-wrap">
            <?= htmlspecialchars($order['special_instructions'] ?? 'No special instructions provided.') ?>
        </p>
    </div>

    <!-- Status History Timeline -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Status Audit History</h3>
        
        <div class="relative pl-6 border-l-2 border-slate-200 space-y-6">
            <?php foreach ($history as $h): ?>
                <div class="relative">
                    <div class="absolute -left-[31px] top-1.5 w-4 h-4 rounded-full bg-slate-900 ring-4 ring-white"></div>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <div>
                            <span class="font-bold text-sm text-slate-900 uppercase tracking-wide">
                                <?= str_replace('_', ' ', htmlspecialchars($h['new_status'])) ?>
                            </span>
                            <?php if (!empty($h['old_status'])): ?>
                                <span class="text-xs text-slate-400 block">From <?= str_replace('_', ' ', htmlspecialchars($h['old_status'])) ?></span>
                            <?php else: ?>
                                <span class="text-xs text-emerald-600 font-medium block">Initial Order Creation</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-400 text-right mt-1 sm:mt-0">
                            <div><?= htmlspecialchars(date('M d, Y, g:i a', strtotime($h['updated_at']))) ?></div>
                            <div>By <?= htmlspecialchars($h['updated_by_name'] ?? 'System User') ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
