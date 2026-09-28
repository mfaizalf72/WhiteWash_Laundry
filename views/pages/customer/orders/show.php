<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;

$outstanding = round((float)$order['total_amount'] - (float)$order['amount_paid'], 2);
?>

<div class="space-y-6">

    <!-- Top Navigation & Order Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="/laundry-lms/customer/orders" class="text-xs font-semibold text-blue-600 hover:underline inline-flex items-center mb-1">
                &larr; Back to My Orders
            </a>
            <h1 class="text-2xl font-bold text-slate-800 font-display">Order #<?= Security::e($order['job_id']) ?></h1>
            <p class="text-xs text-slate-500">Placed on <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/laundry-lms/customer/orders/print?id=<?= (int)$order['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 rounded-lg border border-slate-300 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Receipt
            </a>
            <?php if ($order['status'] === 'cancelled'): ?>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">
                    Status: Cancelled
                </span>
            <?php else: ?>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                    Status: <?= Security::e(str_replace('_', ' ', $order['status'])) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($order['status'] === 'cancelled'): 
        $canc = $metadata['cancellation'] ?? [];
        $rej  = $metadata['rejection'] ?? [];
    ?>
        <!-- Order Cancelled Banner -->
        <div class="bg-rose-50 border-2 border-rose-300 rounded-2xl p-6 shadow-sm space-y-3">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-rose-100 rounded-xl text-rose-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-rose-900">Order Cancelled</h2>
                    <p class="text-sm text-rose-700 mt-1">This order is cancelled and permanently terminal. No further services or transactions will take place.</p>
                    <div class="mt-3 bg-white p-3 rounded-xl border border-rose-200 text-xs space-y-1.5 text-slate-700">
                        <div><strong class="text-slate-900">Reason:</strong> <?= htmlspecialchars($canc['reason'] ?? ($rej['reason'] ?? 'Order cancelled')) ?></div>
                        <div><strong class="text-slate-900">Cancelled On:</strong> <?= !empty($canc['cancelled_at']) ? date('F d, Y \a\t h:i A', strtotime($canc['cancelled_at'])) : date('F d, Y', strtotime($order['updated_at'])) ?></div>
                        <div><strong class="text-slate-900">Initiated By:</strong> <?= ucfirst(htmlspecialchars((string)($canc['cancelled_by_role'] ?? 'user'))) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($order['status'] === 'inspection_completed'): ?>
        <!-- Price Approval Required Card -->
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-amber-100 rounded-xl text-amber-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-amber-900">Action Required: Review &amp; Approve Your Final Price</h2>
                    <p class="text-sm text-amber-700 mt-1">Our shop staff has inspected and weighed your garments. Please review the verified price details below to authorize processing.</p>
                </div>
            </div>

            <?php if (!empty($metadata['inspection'])): 
                $insp = $metadata['inspection'];
                $revs = $metadata['revisions'] ?? [];
                $latestRev = !empty($revs) ? end($revs) : null;
                $prevTotal = $latestRev['previous_total'] ?? $order['total_amount'];
            ?>
            <div class="bg-white rounded-xl border border-amber-200 p-4 space-y-3">
                <?php if (!empty($insp['items']) && is_array($insp['items'])): ?>
                    <div class="overflow-x-auto border border-amber-200/70 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-amber-50 text-amber-900 font-semibold uppercase">
                                <tr>
                                    <th class="px-3 py-2">Item &amp; Service</th>
                                    <th class="px-3 py-2 text-right">Verified Qty</th>
                                    <th class="px-3 py-2 text-right">Unit Rate</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                    <th class="px-3 py-2">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100 bg-white">
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
                    <div class="flex flex-col sm:flex-row justify-end items-end gap-3 text-xs text-slate-600 bg-amber-50/50 p-2.5 rounded-lg border border-amber-100">
                        <div>Items Subtotal: <strong>₹<?= number_format((float)($insp['items_subtotal'] ?? 0), 2) ?></strong></div>
                        <span class="hidden sm:inline">&bull;</span>
                        <div>Pickup/Delivery Fee: <strong>₹<?= number_format((float)(($insp['pickup_fee'] ?? 0) + ($insp['delivery_fee'] ?? 0)), 2) ?></strong></div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 block font-medium">Initial Estimate</span>
                            <span class="font-bold text-slate-700 text-sm">₹<?= number_format((float)$prevTotal, 2) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Verified Service</span>
                            <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($insp['item_name'] ?? '') ?> (<?= htmlspecialchars($insp['work_type_name'] ?? '') ?>)</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Measured Quantity</span>
                            <span class="font-bold text-slate-800 text-sm"><?= number_format((float)($insp['quantity'] ?? 0), 2) ?> <?= htmlspecialchars($insp['unit_label'] ?? '') ?> @ ₹<?= number_format((float)($insp['unit_price'] ?? 0), 2) ?>/<?= htmlspecialchars($insp['unit_label'] ?? '') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Delivery Fees</span>
                            <span class="font-bold text-slate-800 text-sm">₹<?= number_format((float)(($insp['pickup_fee'] ?? 0) + ($insp['delivery_fee'] ?? 0)), 2) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($insp['garment_notes'])): ?>
                    <div class="text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-slate-700">
                        <strong>Garment Condition Notes:</strong> <?= htmlspecialchars($insp['garment_notes']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($latestRev['reason']) && $latestRev['reason'] !== 'Initial inspection completed'): ?>
                    <div class="text-xs bg-blue-50 p-2.5 rounded-lg border border-blue-200 text-blue-800">
                        <strong>Price Adjustment Reason:</strong> <?= htmlspecialchars($latestRev['reason']) ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-2 border-t border-slate-100">
                    <div>
                        <span class="text-xs text-slate-500 block">Final Inspected Total:</span>
                        <span class="text-2xl font-bold font-mono text-slate-900">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <form action="/laundry-lms/customer/orders/approve-price" method="POST" class="inline-block">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <input type="hidden" name="expected_revision" value="<?= htmlspecialchars((string)($metadata['inspection_revision'] ?? '')) ?>">
                            <button type="submit" onclick="return confirm('Confirm and approve final order price of ₹<?= number_format((float)$order['total_amount'], 2) ?>?');" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Approve Final Price (₹<?= number_format((float)$order['total_amount'], 2) ?>)</span>
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('rejectPriceModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-4 py-2.5 rounded-xl text-xs border border-rose-200 transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span>Reject &amp; Cancel</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <p class="text-xs text-amber-800 italic">
                Note: If you disagree with this verified price, you can reject it above to cancel the order, or contact shop support before approving. Garments will not be washed until your confirmation is received.
            </p>
        </div>
    <?php endif; ?>

    <!-- Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Order Information & Financials -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Dates & Address Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Schedule &amp; Delivery</h3>
                    <?php 
                    $canCustomerReschedule = in_array($order['status'], ['pickup_request', 'accepted'], true);
                    $canCustomerCancel = ($order['status'] === 'pickup_request') && ((float)$order['amount_paid'] == 0.00);
                    ?>
                    <?php if ($canCustomerReschedule || $canCustomerCancel): ?>
                        <div class="flex items-center gap-2">
                            <?php if ($canCustomerReschedule): ?>
                                <button type="button" onclick="document.getElementById('reschedulePickupModal').classList.remove('hidden');" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-2.5 py-1 rounded-lg text-xs border border-slate-300 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span>Reschedule</span>
                                </button>
                            <?php endif; ?>
                            <?php if ($canCustomerCancel): ?>
                                <button type="button" onclick="document.getElementById('cancelOrderModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-2.5 py-1 rounded-lg text-xs border border-rose-200 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    <span>Cancel</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Scheduled Pickup Date</span>
                        <span class="font-semibold text-slate-800"><?= !empty($order['pickup_date']) ? date('F d, Y', strtotime($order['pickup_date'])) : 'Not specified' ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Target Delivery Date</span>
                        <span class="font-semibold text-slate-800"><?= !empty($order['delivery_date']) ? date('F d, Y', strtotime($order['delivery_date'])) : 'To be confirmed' ?></span>
                    </div>
                    <?php if (!empty($order['customer_address'])): ?>
                    <div class="sm:col-span-2">
                        <span class="text-xs text-slate-400 block font-medium">Pickup &amp; Delivery Address</span>
                        <span class="text-slate-700"><?= Security::e($order['customer_address']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($metadata['reschedules'])): ?>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-2">Schedule Change History</span>
                        <div class="space-y-2">
                            <?php foreach ($metadata['reschedules'] as $rs): ?>
                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-xs text-slate-700 space-y-1">
                                    <div class="flex justify-between font-medium">
                                        <span>Pickup: <strong><?= htmlspecialchars($rs['new_pickup_date']) ?></strong></span>
                                        <?php if (!empty($rs['new_delivery_date'])): ?>
                                            <span>Delivery: <strong><?= htmlspecialchars($rs['new_delivery_date']) ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 italic">"<?= htmlspecialchars($rs['reason']) ?>"</div>
                                    <div class="text-[10px] text-slate-400"><?= date('M d, Y h:i A', strtotime($rs['rescheduled_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Items Breakdown Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Order Items &amp; Services</h3>
                    <?php if (!empty($orderItems)): ?>
                        <span class="text-xs text-slate-500 font-medium"><?= count($orderItems) ?> item(s)</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($orderItems)): ?>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-2">Item &amp; Service</th>
                                    <th class="px-3 py-2 text-right">Quantity</th>
                                    <th class="px-3 py-2 text-right">Rate</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php 
                                $cSubtotal = 0.00;
                                foreach ($orderItems as $item): 
                                    $cSubtotal += (float)$item['subtotal'];
                                    $unitStr = ($item['pricing_type'] === 'per_kg') ? 'kg' : 'pcs';
                                ?>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">
                                            <?= htmlspecialchars($item['item_name']) ?>
                                            <span class="text-slate-500 text-[11px] block sm:inline">(<?= htmlspecialchars($item['work_type_name']) ?>)</span>
                                            <?php if (!empty($item['item_notes'])): ?>
                                                <span class="text-slate-400 text-[11px] block italic"><?= htmlspecialchars($item['item_notes']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-right text-slate-700">
                                            <?= number_format((float)$item['quantity'], $item['pricing_type'] === 'per_kg' ? 2 : 0) ?> <?= $unitStr ?>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-slate-600">
                                            ₹<?= number_format((float)$item['unit_price'], 2) ?>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">
                                            ₹<?= number_format((float)$item['subtotal'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-50 font-semibold text-slate-700 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right uppercase text-[11px]">Items Subtotal:</td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">₹<?= number_format($cSubtotal, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-slate-500 italic bg-slate-50 p-3 rounded-lg border border-slate-200">
                        Legacy order: Service details were recorded in the instructions note below.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Customer Special Instructions -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">Special Instructions</h3>
                <div class="bg-slate-50 p-4 rounded-lg text-sm font-mono text-slate-800 whitespace-pre-line border border-slate-200">
                    <?= !empty($customerNote) ? Security::e($customerNote) : '<em class="text-slate-400 font-sans">No special instructions provided.</em>' ?>
                </div>
            </div>

            <!-- Payment Summary Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">Payment Breakdown</h3>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1">
                        <span class="text-slate-600">Total Order Amount:</span>
                        <span class="font-bold text-slate-900">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-slate-100">
                        <span class="text-slate-600">Amount Paid:</span>
                        <span class="font-semibold text-emerald-600">₹<?= number_format((float)$order['amount_paid'], 2) ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-slate-100 text-base">
                        <span class="font-bold text-slate-800">Outstanding Balance:</span>
                        <span class="font-bold <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-800' ?>">₹<?= number_format($outstanding, 2) ?></span>
                    </div>
                </div>

                <!-- Payments Log -->
                <?php if (!empty($payments)): ?>
                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Recorded Payments</h4>
                        <div class="space-y-2">
                            <?php foreach ($payments as $pmt): ?>
                                <div class="flex items-center justify-between text-xs bg-slate-50 p-2.5 rounded border border-slate-100">
                                    <div>
                                        <span class="font-semibold text-slate-800">₹<?= number_format((float)$pmt['amount'], 2) ?></span>
                                        <span class="text-slate-500 uppercase ml-1">(<?= Security::e($pmt['payment_method']) ?>)</span>
                                        <?php if (!empty($pmt['upi_reference'])): ?>
                                            <span class="text-slate-400 block font-mono">Ref: <?= Security::e($pmt['upi_reference']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-slate-400"><?= date('M d, Y h:i A', strtotime($pmt['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Column 3: Order Workflow Timeline -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">Status Timeline</h3>
                
                <?php if (empty($history)): ?>
                    <p class="text-xs text-slate-400">No status history recorded yet.</p>
                <?php else: ?>
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        <?php foreach ($history as $h): ?>
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-3 h-3 rounded-full bg-blue-600 border-2 border-white ring-2 ring-blue-100"></div>
                                <div>
                                    <span class="font-semibold text-slate-800 text-sm capitalize block"><?= Security::e(str_replace('_', ' ', $h['new_status'])) ?></span>
                                    <span class="text-xs text-slate-400 block"><?= date('M d, Y h:i A', strtotime($h['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Reject Inspected Price Modal -->
    <div id="rejectPriceModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Reject Inspected Price</h3>
                <button type="button" onclick="document.getElementById('rejectPriceModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <p class="text-xs text-slate-600">Rejecting the verified price will cancel this order. Our shop will not wash your garments and will arrange to return them.</p>
            <form action="/laundry-lms/customer/orders/reject-price" method="POST" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="expected_revision" value="<?= htmlspecialchars((string)($metadata['inspection_revision'] ?? '')) ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Rejection <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Please explain why you disagree with this price (min 5 characters)..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('rejectPriceModal').classList.add('hidden');" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Back</button>
                    <button type="submit" onclick="return confirm('Are you sure you want to reject this price and cancel this order?');" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm">Reject &amp; Cancel Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div id="cancelOrderModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Cancel Order Request</h3>
                <button type="button" onclick="document.getElementById('cancelOrderModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <p class="text-xs text-slate-600">Are you sure you want to cancel this order? This action cannot be undone.</p>
            <form action="/laundry-lms/customer/orders/cancel" method="POST" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Cancellation <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Why would you like to cancel this order (min 5 characters)..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('cancelOrderModal').classList.add('hidden');" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Back</button>
                    <button type="submit" onclick="return confirm('Confirm cancellation of order #<?= htmlspecialchars($order['job_id']) ?>?');" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reschedule Pickup Modal -->
    <div id="reschedulePickupModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Reschedule Pickup</h3>
                <button type="button" onclick="document.getElementById('reschedulePickupModal').classList.add('hidden');" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <form action="/laundry-lms/customer/orders/reschedule" method="POST" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New Pickup Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($order['pickup_date']) ?>" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Rescheduling <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="2" required minlength="5" placeholder="e.g. Out of town on original pickup date (min 5 characters)..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('reschedulePickupModal').classList.add('hidden');" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm">Save New Pickup Date</button>
                </div>
            </form>
        </div>
    </div>

</div>
