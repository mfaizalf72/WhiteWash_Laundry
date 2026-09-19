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
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                Status: <?= Security::e(str_replace('_', ' ', $order['status'])) ?>
            </span>
        </div>
    </div>

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
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Initial Estimate</span>
                        <span class="font-bold text-slate-700 text-sm">₹<?= number_format((float)$prevTotal, 2) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Verified Service</span>
                        <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($insp['item_name']) ?> (<?= htmlspecialchars($insp['work_type_name']) ?>)</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Measured Quantity</span>
                        <span class="font-bold text-slate-800 text-sm"><?= number_format((float)$insp['quantity'], 2) ?> <?= htmlspecialchars($insp['unit_label']) ?> @ ₹<?= number_format((float)$insp['unit_price'], 2) ?>/<?= htmlspecialchars($insp['unit_label']) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Delivery Fees</span>
                        <span class="font-bold text-slate-800 text-sm">₹<?= number_format((float)$insp['pickup_fee'] + (float)$insp['delivery_fee'], 2) ?></span>
                    </div>
                </div>

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

                    <form action="/laundry-lms/customer/orders/approve-price" method="POST" class="inline-block">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                        <input type="hidden" name="expected_revision" value="<?= htmlspecialchars((string)($metadata['inspection_revision'] ?? '')) ?>">
                        <button type="submit" onclick="return confirm('Confirm and approve final order price of ₹<?= number_format((float)$order['total_amount'], 2) ?>?');" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl text-sm shadow-md transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Approve Final Price (₹<?= number_format((float)$order['total_amount'], 2) ?>)</span>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <p class="text-xs text-amber-800 italic">
                Note: If you disagree with this verified price, please call or contact shop support before approving. Garments will not be washed until your confirmation is received.
            </p>
        </div>
    <?php endif; ?>

    <!-- Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Order Information & Financials -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Dates & Address Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">Schedule & Delivery</h3>
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
                        <span class="text-xs text-slate-400 block font-medium">Pickup & Delivery Address</span>
                        <span class="text-slate-700"><?= Security::e($order['customer_address']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Service Breakdown & Instructions -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">Order Details & Instructions</h3>
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

</div>
