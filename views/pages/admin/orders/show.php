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
            <?php if ($order['status'] === 'picked_up'): ?>
                <button type="button" onclick="document.getElementById('inspectModal').classList.remove('hidden');" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Inspect & Price Garments</span>
                </button>
            <?php elseif ($order['status'] === 'inspection_completed'): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                        <svg class="w-3.5 h-3.5 mr-1 text-amber-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Awaiting Customer Price Approval
                    </span>
                    <button type="button" onclick="document.getElementById('inspectModal').classList.remove('hidden');" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-xl text-xs transition-colors border border-slate-200">
                        Edit / Re-inspect
                    </button>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <button type="button" onclick="document.getElementById('adminOverrideModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-4 py-2 rounded-xl text-xs transition-colors border border-rose-200">
                            Emergency Override
                        </button>
                    <?php endif; ?>
                </div>
            <?php elseif ($nextStatus): ?>
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
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Assigned Staff</span>
                    <?php if (!empty($order['staff_name'])): ?>
                        <span class="font-medium text-slate-800 block"><?= htmlspecialchars($order['staff_name']) ?></span>
                    <?php else: ?>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Unassigned</span>
                            <?php if (($_SESSION['user_role'] ?? '') === 'staff'): ?>
                                <form action="/laundry-lms/orders/claim" method="POST" class="inline-block">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">
                                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-3 py-1 rounded shadow-sm transition-colors">
                                        Claim Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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

    <!-- Customer Instructions & Inspection Breakdown -->
    <div class="space-y-6">
        <!-- Customer Note Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Order Details & Customer Special Instructions</h3>
            <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono whitespace-pre-wrap">
                <?= !empty($customerNote) ? htmlspecialchars($customerNote) : '<em class="text-slate-400">No special instructions provided by customer.</em>' ?>
            </p>
        </div>

        <?php if (!empty($metadata['inspection'])): 
            $insp = $metadata['inspection'];
            $appr = $metadata['approval'] ?? [];
            $revs = $metadata['revisions'] ?? [];
        ?>
            <!-- Inspected Service & Pricing Card -->
            <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-6 space-y-4">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Verified Inspection & Pricing Details
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Inspected by <strong><?= htmlspecialchars($insp['inspected_by_name']) ?></strong> on <?= htmlspecialchars(date('M d, Y g:i A', strtotime($insp['inspected_at']))) ?>
                        </p>
                    </div>
                    <?php if (($appr['status'] ?? '') === 'approved'): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Price Approved (<?= htmlspecialchars($appr['method'] === 'admin_emergency_override' ? 'Admin Override' : 'Customer Confirmed') ?>)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                            Awaiting Customer Approval
                        </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Service</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($insp['item_name']) ?> (<?= htmlspecialchars($insp['work_type_name']) ?>)</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Verified Quantity</span>
                        <span class="font-bold text-slate-800"><?= number_format((float)$insp['quantity'], 2) ?> <?= htmlspecialchars($insp['unit_label']) ?></span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Unit Rate</span>
                        <span class="font-bold text-slate-800">₹<?= number_format((float)$insp['unit_price'], 2) ?> / <?= htmlspecialchars($insp['unit_label']) ?></span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Calculated Total</span>
                        <span class="font-mono font-bold text-blue-700 text-base">₹<?= number_format((float)$insp['calculated_total'], 2) ?></span>
                    </div>
                </div>

                <?php if (!empty($insp['garment_notes'])): ?>
                    <div class="text-xs bg-amber-50/70 border border-amber-200 p-3 rounded-xl text-amber-900">
                        <strong>Garment Condition Notes:</strong> <?= htmlspecialchars($insp['garment_notes']) ?>
                    </div>
                <?php endif; ?>

                <?php if (($appr['status'] ?? '') === 'approved' && !empty($appr['override_reason'])): ?>
                    <div class="text-xs bg-rose-50 border border-rose-200 p-3 rounded-xl text-rose-900">
                        <strong>Admin Emergency Override Reason:</strong> <?= htmlspecialchars($appr['override_reason']) ?> (by User #<?= (int)$appr['approved_by'] ?> at <?= htmlspecialchars($appr['approved_at']) ?>)
                    </div>
                <?php endif; ?>

                <?php if (count($revs) > 1): ?>
                    <div class="pt-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Price Revision History</h4>
                        <div class="space-y-1.5 text-xs">
                            <?php foreach ($revs as $idx => $r): ?>
                                <div class="flex items-center justify-between bg-white border border-slate-200 p-2.5 rounded-lg">
                                    <div>
                                        <span class="font-semibold text-slate-800">Rev #<?= $idx + 1 ?>: ₹<?= number_format((float)$r['previous_total'], 2) ?> &rarr; ₹<?= number_format((float)$r['new_total'], 2) ?></span>
                                        <span class="text-slate-500 ml-2">(<?= htmlspecialchars($r['reason'] ?? '') ?>)</span>
                                    </div>
                                    <span class="text-slate-400"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($r['revised_at']))) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

    <!-- Inspection Modal -->
    <div id="inspectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Garment Inspection & Final Pricing</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Weigh and verify garments to calculate final order total.</p>
                </div>
                <button type="button" onclick="document.getElementById('inspectModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
            </div>

            <form action="/laundry-lms/orders/inspect" method="POST" class="p-6 space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Laundry Item</label>
                    <select name="item_id" id="modalItemId" required onchange="calculateModalTotal()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select an Item</option>
                        <?php foreach ($items as $it): 
                            $sel = (!empty($metadata['inspection']['item_id']) && (int)$metadata['inspection']['item_id'] === (int)$it['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= (int)$it['id'] ?>" <?= $sel ?>><?= htmlspecialchars($it['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Work / Service Type</label>
                    <select name="work_type_id" id="modalWorkTypeId" required onchange="calculateModalTotal()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a Work Type</option>
                        <?php foreach ($workTypes as $wt): 
                            $sel = (!empty($metadata['inspection']['work_type_id']) && (int)$metadata['inspection']['work_type_id'] === (int)$wt['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= (int)$wt['id'] ?>" <?= $sel ?>><?= htmlspecialchars($wt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1" id="qtyLabel">Verified Quantity / Weight</label>
                    <input type="number" name="quantity" id="modalQuantity" step="0.01" min="0.01" required value="<?= htmlspecialchars((string)($metadata['inspection']['quantity'] ?? '1.00')) ?>" oninput="calculateModalTotal()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Garment Condition & Defect Notes</label>
                    <textarea name="garment_notes" rows="2" placeholder="e.g. Minor stain on collar, fragile buttons, color bleed warning" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($metadata['inspection']['garment_notes'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reason for Price Adjustment</label>
                    <input type="text" name="adjustment_reason" placeholder="e.g. Measured 4.8kg vs customer estimated 3.0kg" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Live Price Preview Box -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-4 space-y-1.5 text-xs text-slate-700">
                    <div class="flex justify-between">
                        <span>Unit Rate:</span>
                        <span class="font-semibold" id="previewRate">₹0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Item Subtotal:</span>
                        <span class="font-semibold" id="previewSubtotal">₹0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pickup/Delivery Fee:</span>
                        <span class="font-semibold" id="previewFees">₹0.00</span>
                    </div>
                    <div class="flex justify-between border-t border-blue-200 pt-1.5 text-sm font-bold text-slate-900">
                        <span>Final Total:</span>
                        <span class="font-mono text-blue-700 text-base" id="previewTotal">₹0.00</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('inspectModal').classList.add('hidden');" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm">
                        Submit Inspection & Calculate Price
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <!-- Admin Emergency Override Modal -->
    <div id="adminOverrideModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-rose-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-rose-900">Emergency Price Approval Override</h3>
                    <p class="text-xs text-rose-600 mt-0.5">Admin-only action to bypass customer portal approval.</p>
                </div>
                <button type="button" onclick="document.getElementById('adminOverrideModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
            </div>

            <form action="/laundry-lms/orders/override-price-approval" method="POST" class="p-6 space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Override Rationale (Required, Min 10 chars)</label>
                    <textarea name="reason" rows="3" required minlength="10" placeholder="e.g. Customer called by telephone and verbally approved the revised total of ₹..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                </div>

                <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <strong>Audit Notice:</strong> Your Administrator User ID and timestamp will be permanently written to the order status history and metadata block.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('adminOverrideModal').classList.add('hidden');" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm">
                        Confirm Emergency Override
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    const pricingsCatalog = <?= json_encode($pricings ?? []) ?>;
    const businessSettings = <?= json_encode($settings ?? []) ?>;

    function calculateModalTotal() {
        const itemId = parseInt(document.getElementById('modalItemId').value) || 0;
        const workTypeId = parseInt(document.getElementById('modalWorkTypeId').value) || 0;
        const qty = parseFloat(document.getElementById('modalQuantity').value) || 0;

        const p = pricingsCatalog.find(x => parseInt(x.item_id) === itemId && parseInt(x.work_type_id) === workTypeId);
        if (!p || qty <= 0) {
            document.getElementById('previewRate').textContent = '₹0.00';
            document.getElementById('previewSubtotal').textContent = '₹0.00';
            document.getElementById('previewFees').textContent = '₹0.00';
            document.getElementById('previewTotal').textContent = '₹0.00';
            return;
        }

        const rate = parseFloat(p.price);
        const subtotal = Math.round(rate * qty * 100) / 100;
        const freeLimit = parseFloat(businessSettings.free_pickup_limit || 0);
        const pickupCharge = parseFloat(businessSettings.pickup_charge || 0);
        const deliveryCharge = parseFloat(businessSettings.delivery_charge || 0);

        let fees = 0.00;
        if (freeLimit <= 0 || subtotal < freeLimit) {
            fees = pickupCharge + deliveryCharge;
        }
        const total = Math.round((subtotal + fees) * 100) / 100;

        const unitSuffix = p.pricing_type === 'per_kg' ? '/kg' : '/item';
        document.getElementById('previewRate').textContent = '₹' + rate.toFixed(2) + ' ' + unitSuffix;
        document.getElementById('previewSubtotal').textContent = '₹' + subtotal.toFixed(2);
        document.getElementById('previewFees').textContent = '₹' + fees.toFixed(2);
        document.getElementById('previewTotal').textContent = '₹' + total.toFixed(2);
    }
    calculateModalTotal();
    </script>
</div>
