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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider <?= $order['status'] === 'cancelled' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' ?>">
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

    <?php if ($order['status'] === 'cancelled'): 
        $canc = $metadata['cancellation'] ?? [];
        $rej  = $metadata['rejection'] ?? [];
    ?>
        <!-- Order Cancelled Banner -->
        <div class="bg-rose-50 border-2 border-rose-300 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-rose-100 rounded-xl text-rose-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-rose-900">Order Cancelled</h3>
                    <p class="text-xs text-rose-700 mt-0.5">This order is cancelled and permanently terminal. No further transitions, inspections, or payments can be recorded.</p>
                    <div class="mt-3 bg-white p-3 rounded-xl border border-rose-200 text-xs space-y-1 text-slate-700">
                        <div><strong class="text-slate-900">Cancellation Reason:</strong> <?= htmlspecialchars($canc['reason'] ?? ($rej['reason'] ?? 'Order cancelled')) ?></div>
                        <div><strong class="text-slate-900">Cancelled At:</strong> <?= !empty($canc['cancelled_at']) ? date('F j, Y, g:i a', strtotime($canc['cancelled_at'])) : date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></div>
                        <div><strong class="text-slate-900">Cancelled By:</strong> <?= ucfirst(htmlspecialchars((string)($canc['cancelled_by_role'] ?? 'user'))) ?></div>
                        <?php if (!empty($canc['origin_status'])): ?>
                            <div><strong class="text-slate-900">Origin Status:</strong> <?= htmlspecialchars($canc['origin_status']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Transition Control Card -->
    <?php
    $canCancel = in_array($order['status'], ['pickup_request', 'accepted', 'inspection_completed'], true) 
        && ((float)$order['amount_paid'] == 0.00);
    $canReschedule = !in_array($order['status'], ['out_for_delivery', 'delivered', 'completed', 'cancelled'], true);
    ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Current Workflow Stage</span>
            <div class="font-bold text-slate-900 text-lg capitalize mt-0.5"><?= str_replace('_', ' ', htmlspecialchars($order['status'])) ?></div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <?php if ($order['status'] === 'cancelled'): ?>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-50 text-rose-700 text-sm font-semibold border border-rose-200">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Order Cancelled
                </span>
            <?php elseif ($order['status'] === 'picked_up'): ?>
                <button type="button" onclick="document.getElementById('inspectModal').classList.remove('hidden');" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Inspect &amp; Price Garments</span>
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

            <?php if ($canReschedule || $canCancel): ?>
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <?php if ($canReschedule): ?>
                        <button type="button" onclick="document.getElementById('rescheduleModal').classList.remove('hidden');" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-xl text-xs transition-colors border border-slate-300 flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Reschedule</span>
                        </button>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <button type="button" onclick="document.getElementById('cancelOrderModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-3 py-2 rounded-xl text-xs transition-colors border border-rose-200 flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span>Cancel</span>
                        </button>
                    <?php endif; ?>
                </div>
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

                <?php if (!empty($metadata['reschedules'])): ?>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Schedule Change History</span>
                        <div class="space-y-2">
                            <?php foreach ($metadata['reschedules'] as $rs): ?>
                                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1">
                                    <div class="flex justify-between font-medium">
                                        <span>Pickup: <strong><?= htmlspecialchars($rs['new_pickup_date']) ?></strong></span>
                                        <?php if (!empty($rs['new_delivery_date'])): ?>
                                            <span>Delivery: <strong><?= htmlspecialchars($rs['new_delivery_date']) ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 italic">"<?= htmlspecialchars($rs['reason']) ?>"</div>
                                    <div class="text-[10px] text-slate-400"><?= date('M j, Y g:ia', strtotime($rs['rescheduled_at'])) ?> &bull; by <?= htmlspecialchars($rs['rescheduled_by_role']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

        <!-- Record Payment Form (Shown if remaining balance > 0 and order not cancelled) -->
        <?php if ($remBal > 0 && $order['status'] !== 'cancelled'): ?>
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
        <?php elseif ($order['status'] === 'cancelled'): ?>
            <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-200 text-xs text-rose-700">
                This order is cancelled. Payment processing is blocked.
            </div>
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
        <!-- Order Items Breakdown Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Order Items &amp; Service Breakdown</h3>
                    <p class="text-xs text-slate-500 mt-0.5">List of garments and requested cleaning services.</p>
                </div>
                <?php if (!empty($orderItems)): ?>
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg">
                        <?= count($orderItems) ?> <?= count($orderItems) === 1 ? 'Line Item' : 'Line Items' ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($orderItems)): ?>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">#</th>
                                <th class="px-4 py-3">Item &amp; Service</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3 text-right">Unit Rate</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3">Line Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            $calcSubtotal = 0.00;
                            foreach ($orderItems as $item): 
                                $calcSubtotal += (float)$item['subtotal'];
                                $unitStr = ($item['pricing_type'] === 'per_kg') ? 'kg' : 'pcs';
                            ?>
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 text-center text-slate-400 font-mono"><?= (int)$item['display_order'] ?></td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <span class="ml-1 text-slate-500 font-normal">(<?= htmlspecialchars($item['work_type_name']) ?>)</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700">
                                        <?= number_format((float)$item['quantity'], $item['pricing_type'] === 'per_kg' ? 2 : 0) ?> <?= $unitStr ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-600">
                                        ₹<?= number_format((float)$item['unit_price'], 2) ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                                        ₹<?= number_format((float)$item['subtotal'], 2) ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-xs italic">
                                        <?= !empty($item['item_notes']) ? htmlspecialchars($item['item_notes']) : '<span class="text-slate-300">—</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-50 font-semibold text-slate-700 border-t border-slate-200">
                            <tr>
                                <td colspan="4" class="px-4 py-2.5 text-right uppercase text-xs tracking-wider">Items Subtotal:</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">₹<?= number_format($calcSubtotal, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs text-slate-500 italic">
                    Legacy Order: This order has no structured order items. Items were captured in the special instructions below or during workflow.
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Note Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Customer Special Instructions</h3>
            <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono whitespace-pre-wrap"><?= !empty($customerNote) ? htmlspecialchars($customerNote) : '<em class="text-slate-400">No special instructions provided by customer.</em>' ?></p>
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
                            Verified Inspection &amp; Pricing Details
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

                <?php if (!empty($insp['items']) && is_array($insp['items'])): ?>
                    <div class="overflow-x-auto border border-blue-100 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-blue-50 text-blue-800 font-semibold uppercase">
                                <tr>
                                    <th class="px-3 py-2">Item &amp; Service</th>
                                    <th class="px-3 py-2 text-right">Verified Qty</th>
                                    <th class="px-3 py-2 text-right">Unit Rate</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                    <th class="px-3 py-2">Line Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50 bg-white">
                                <?php foreach ($insp['items'] as $itLine): 
                                    $unitStr = ($itLine['pricing_type'] === 'per_kg') ? 'kg' : 'pcs';
                                ?>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">
                                            <?= htmlspecialchars($itLine['item_name']) ?> <span class="text-slate-500">(<?= htmlspecialchars($itLine['work_type_name']) ?>)</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-slate-700">
                                            <?= number_format((float)$itLine['quantity'], $itLine['pricing_type'] === 'per_kg' ? 2 : 0) ?> <?= $unitStr ?>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-slate-600">
                                            ₹<?= number_format((float)$itLine['unit_price'], 2) ?>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">
                                            ₹<?= number_format((float)$itLine['subtotal'], 2) ?>
                                        </td>
                                        <td class="px-3 py-2 text-slate-500 italic">
                                            <?= !empty($itLine['item_notes']) ? htmlspecialchars($itLine['item_notes']) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end items-end gap-2 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div>Items Subtotal: <strong>₹<?= number_format((float)($insp['items_subtotal'] ?? 0), 2) ?></strong></div>
                        <span class="hidden sm:inline">&bull;</span>
                        <div>Fees: <strong>₹<?= number_format((float)(($insp['pickup_fee'] ?? 0) + ($insp['delivery_fee'] ?? 0)), 2) ?></strong></div>
                        <span class="hidden sm:inline">&bull;</span>
                        <div class="text-sm font-bold text-blue-700">Final Inspected Total: ₹<?= number_format((float)$insp['calculated_total'], 2) ?></div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Service</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($insp['item_name'] ?? '') ?> (<?= htmlspecialchars($insp['work_type_name'] ?? '') ?>)</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Verified Quantity</span>
                            <span class="font-bold text-slate-800"><?= number_format((float)($insp['quantity'] ?? 0), 2) ?> <?= htmlspecialchars($insp['unit_label'] ?? '') ?></span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Unit Rate</span>
                            <span class="font-bold text-slate-800">₹<?= number_format((float)($insp['unit_price'] ?? 0), 2) ?> / <?= htmlspecialchars($insp['unit_label'] ?? '') ?></span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Calculated Total</span>
                            <span class="font-mono font-bold text-blue-700 text-base">₹<?= number_format((float)($insp['calculated_total'] ?? 0), 2) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

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

    <!-- Inspection Modal (Multi-Item Supported) -->
    <div id="inspectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Garment Inspection &amp; Final Pricing</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Weigh and verify garments per service line to calculate final order total.</p>
                </div>
                <button type="button" onclick="document.getElementById('inspectModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-2xl font-bold p-1">&times;</button>
            </div>

            <form action="/laundry-lms/orders/inspect" method="POST" id="inspectForm" class="p-6 space-y-5 overflow-y-auto flex-1">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">

                <!-- Inspection Lines Repeater -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Inspected Order Lines</label>
                        <button type="button" onclick="addInspectionRow()" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Service Line
                        </button>
                    </div>

                    <div id="inspectionRowsContainer" class="space-y-3">
                        <!-- Populated dynamically via JS -->
                    </div>
                    <div id="modalDupWarning" class="hidden mt-2 p-2.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-medium"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Garment Condition &amp; Defect Notes</label>
                        <textarea name="garment_notes" rows="2" placeholder="e.g. Minor stain on collar, fragile buttons, color bleed warning" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($metadata['inspection']['garment_notes'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reason for Price Adjustment</label>
                        <textarea name="adjustment_reason" rows="2" placeholder="e.g. Measured weight verified against estimate" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <!-- Live Price Preview Box -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-4 space-y-1.5 text-xs text-slate-700">
                    <div class="flex justify-between">
                        <span>Items Subtotal:</span>
                        <span class="font-semibold font-mono" id="modalPreviewSubtotal">₹0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pickup/Delivery Fee:</span>
                        <span class="font-semibold font-mono" id="modalPreviewFees">₹0.00</span>
                    </div>
                    <div class="flex justify-between border-t border-blue-200 pt-1.5 text-sm font-bold text-slate-900">
                        <span>Final Inspected Total:</span>
                        <span class="font-mono text-blue-700 text-base" id="modalPreviewTotal">₹0.00</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('inspectModal').classList.add('hidden');" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitBtn" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm">
                        Submit Inspection &amp; Calculate Price
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
    const pricingsCatalog = <?= json_encode($pricings ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const businessSettings = <?= json_encode($settings ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const initialOrderItems = <?= json_encode(!empty($orderItems) ? array_values($orderItems) : [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const initialInspectionItems = <?= json_encode(!empty($metadata['inspection']['items']) ? array_values($metadata['inspection']['items']) : [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const itemsCatalog = <?= json_encode($items ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const workTypesCatalog = <?= json_encode($workTypes ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    let inspectionRowIndex = 0;

    function createRowHtml(index, itemVal = '', workTypeVal = '', qtyVal = '1.00', noteVal = '') {
        let itemOpts = '<option value="">Select Item</option>';
        itemsCatalog.forEach(it => {
            const sel = (it.id == itemVal) ? 'selected' : '';
            itemOpts += `<option value="${it.id}" ${sel}>${it.name}</option>`;
        });

        let wtOpts = '<option value="">Select Work Type</option>';
        workTypesCatalog.forEach(wt => {
            const sel = (wt.id == workTypeVal) ? 'selected' : '';
            wtOpts += `<option value="${wt.id}" ${sel}>${wt.name}</option>`;
        });

        return `
            <div class="inspection-row bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2 relative" data-row="${index}">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <div class="sm:col-span-4">
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Item</label>
                        <select name="lines[${index}][item_id]" class="row-item w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-blue-500" required onchange="calculateModalTotals()">
                            ${itemOpts}
                        </select>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Work Type</label>
                        <select name="lines[${index}][work_type_id]" class="row-worktype w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-blue-500" required onchange="calculateModalTotals()">
                            ${wtOpts}
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[11px] font-medium text-slate-500 mb-1 row-qty-label">Verified Qty</label>
                        <input type="number" step="0.01" min="0.01" max="1000" name="lines[${index}][quantity]" value="${qtyVal}" class="row-qty w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:ring-2 focus:ring-blue-500" required oninput="calculateModalTotals()">
                    </div>
                    <div class="sm:col-span-1 flex justify-end pt-4 sm:pt-0">
                        <button type="button" onclick="removeInspectionRow(${index})" class="text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50" title="Remove line">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center pt-1 border-t border-slate-200/60">
                    <div class="sm:col-span-8">
                        <input type="text" name="lines[${index}][item_notes]" value="${noteVal.replace(/"/g, '&quot;')}" placeholder="Condition notes for this item (optional)" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-700 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-4 text-right">
                        <span class="text-xs text-slate-400">Rate: <span class="font-mono text-slate-600 row-rate">₹0.00</span> | Subtotal: <strong class="font-mono text-slate-900 row-subtotal">₹0.00</strong></span>
                    </div>
                </div>
            </div>
        `;
    }

    function addInspectionRow(itemVal = '', workTypeVal = '', qtyVal = '1.00', noteVal = '') {
        const container = document.getElementById('inspectionRowsContainer');
        const div = document.createElement('div');
        div.innerHTML = createRowHtml(inspectionRowIndex, itemVal, workTypeVal, qtyVal, noteVal);
        container.appendChild(div.firstElementChild);
        inspectionRowIndex++;
        calculateModalTotals();
    }

    function removeInspectionRow(index) {
        const rows = document.querySelectorAll('.inspection-row');
        if (rows.length <= 1) {
            alert('At least one item service line is required.');
            return;
        }
        const target = document.querySelector(`.inspection-row[data-row="${index}"]`);
        if (target) {
            target.remove();
            calculateModalTotals();
        }
    }

    function calculateModalTotals() {
        const rows = document.querySelectorAll('.inspection-row');
        let subtotal = 0.00;
        let hasDuplicate = false;
        const seenPairs = new Set();

        rows.forEach(row => {
            const itemSelect = row.querySelector('.row-item');
            const wtSelect = row.querySelector('.row-worktype');
            const qtyInput = row.querySelector('.row-qty');
            const rateSpan = row.querySelector('.row-rate');
            const subtotalSpan = row.querySelector('.row-subtotal');
            const qtyLabel = row.querySelector('.row-qty-label');

            const itemId = parseInt(itemSelect.value) || 0;
            const wtId = parseInt(wtSelect.value) || 0;
            const rawQty = parseFloat(qtyInput.value) || 0;

            if (itemId > 0 && wtId > 0) {
                const pairKey = `${itemId}_${wtId}`;
                if (seenPairs.has(pairKey)) {
                    hasDuplicate = true;
                }
                seenPairs.add(pairKey);
            }

            const p = pricingsCatalog.find(x => parseInt(x.item_id) === itemId && parseInt(x.work_type_id) === wtId);
            if (p) {
                const rate = parseFloat(p.price) || 0;
                if (p.pricing_type === 'per_item') {
                    qtyLabel.textContent = 'Quantity (items)';
                    qtyInput.step = '1';
                } else {
                    qtyLabel.textContent = 'Weight (kg)';
                    qtyInput.step = '0.01';
                }

                const lineSubtotal = Math.round(rate * rawQty * 100) / 100;
                subtotal += lineSubtotal;

                rateSpan.textContent = '₹' + rate.toFixed(2) + (p.pricing_type === 'per_kg' ? '/kg' : '/item');
                subtotalSpan.textContent = '₹' + lineSubtotal.toFixed(2);
            } else {
                rateSpan.textContent = '₹0.00';
                subtotalSpan.textContent = '₹0.00';
            }
        });

        const warnEl = document.getElementById('modalDupWarning');
        const submitBtn = document.getElementById('modalSubmitBtn');
        if (hasDuplicate) {
            warnEl.textContent = 'Duplicate item and work type combinations are not allowed.';
            warnEl.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            warnEl.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        const freeLimit = parseFloat(businessSettings.free_pickup_limit || 0);
        const pickupCharge = parseFloat(businessSettings.pickup_charge || 0);
        const deliveryCharge = parseFloat(businessSettings.delivery_charge || 0);

        let fees = 0.00;
        if (subtotal > 0 && (freeLimit <= 0 || subtotal < freeLimit)) {
            fees = pickupCharge + deliveryCharge;
        }
        const total = Math.round((subtotal + fees) * 100) / 100;

        document.getElementById('modalPreviewSubtotal').textContent = '₹' + subtotal.toFixed(2);
        document.getElementById('modalPreviewFees').textContent = '₹' + fees.toFixed(2);
        document.getElementById('modalPreviewTotal').textContent = '₹' + total.toFixed(2);
    }

    // Initialize inspection modal rows
    document.addEventListener('DOMContentLoaded', () => {
        const sourceLines = (initialInspectionItems.length > 0) 
            ? initialInspectionItems 
            : (initialOrderItems.length > 0 ? initialOrderItems : []);

        if (sourceLines.length > 0) {
            sourceLines.forEach(line => {
                addInspectionRow(line.item_id, line.work_type_id, line.quantity, line.item_notes || '');
            });
        } else {
            // Add one default blank row
            addInspectionRow();
        }
    });
    </script>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Reschedule Order #<?= htmlspecialchars($order['job_id']) ?></h3>
                <button type="button" onclick="document.getElementById('rescheduleModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <form action="/laundry-lms/orders/reschedule" method="POST" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pickup Date</label>
                        <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($order['pickup_date']) ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery Date</label>
                        <input type="date" name="delivery_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($order['delivery_date'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Rescheduling <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Operational or customer requested reason (min 5 characters)..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('rescheduleModal').classList.add('hidden');" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div id="cancelOrderModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Cancel Order #<?= htmlspecialchars($order['job_id']) ?></h3>
                <button type="button" onclick="document.getElementById('cancelOrderModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <p class="text-xs text-slate-600">Are you sure you want to cancel this order? It will be marked as cancelled permanently and removed from operational queues.</p>
            <form action="/laundry-lms/orders/cancel" method="POST" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="redirect_to" value="/orders/show?id=<?= (int)$order['id'] ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cancellation Reason <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Operational or customer requested reason (min 5 characters)..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('cancelOrderModal').classList.add('hidden');" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Back</button>
                    <button type="submit" onclick="return confirm('Confirm permanent cancellation of order #<?= htmlspecialchars($order['job_id']) ?>?');" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

