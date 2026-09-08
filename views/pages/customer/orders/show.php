<?php
use Faizal\LaundryLms\Helpers\Security;

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
                    <?= Security::e($order['special_instructions'] ?? 'No special instructions provided.') ?>
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
