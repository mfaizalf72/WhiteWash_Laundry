<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;

$outstanding = max(0, round((float)$order['total_amount'] - (float)$order['amount_paid'], 2));

// Semantic status badge styling helper matching Phase 19 standard palette
$getOrderStatusBadge = function(string $status): array {
    $map = [
        'pickup_request'       => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'accepted'             => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'picked_up'            => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500'],
        'inspection_completed' => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'price_confirmed'      => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'processing'           => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200',     'dot' => 'bg-sky-500'],
        'ready'                => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200',  'dot' => 'bg-violet-500'],
        'out_for_delivery'     => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'border' => 'border-violet-200',  'dot' => 'bg-violet-500'],
        'delivered'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        'completed'            => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        'cancelled'            => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500'],
    ];
    return $map[$status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'dot' => 'bg-slate-500'];
};

// Payment status badge helper with dot indicator
$getPaymentStatusBadge = function(string $payStatus): array {
    $map = [
        'paid'    => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Paid'],
        'partial' => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500',   'label' => 'Partial'],
        'unpaid'  => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500',    'label' => 'Unpaid'],
    ];
    return $map[$payStatus] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'dot' => 'bg-slate-500', 'label' => ucfirst($payStatus)];
};

$stBadge  = $getOrderStatusBadge((string)($order['status'] ?? ''));
$payBadge = $getPaymentStatusBadge((string)($order['payment_status'] ?? 'unpaid'));
?>

<div class="space-y-6">

    <!-- Top Navigation & Order Title Header Card -->
    <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <a href="/laundry-lms/customer/orders" class="text-xs font-medium text-slate-500 hover:text-blue-600 inline-flex items-center gap-1.5 mb-2.5 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                <span aria-hidden="true">&larr;</span> <span>Back to My Orders</span>
            </a>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold font-mono text-slate-900 tracking-tight">Order #<?= Security::e($order['job_id']) ?></h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border <?= $stBadge['bg'] ?> <?= $stBadge['text'] ?> <?= $stBadge['border'] ?>">
                    <span class="w-1.5 h-1.5 rounded-full <?= $stBadge['dot'] ?>"></span>
                    <?= Security::e(str_replace('_', ' ', $order['status'])) ?>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border <?= $payBadge['bg'] ?> <?= $payBadge['text'] ?> <?= $payBadge['border'] ?>">
                    <span class="w-1.5 h-1.5 rounded-full <?= $payBadge['dot'] ?>"></span>
                    <?= Security::e($payBadge['label']) ?>
                </span>
                <?php if (!empty($order['branch_name'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200" title="<?= Security::e($order['branch_name']) ?>">
                        <?= Security::e($order['branch_code'] ?? ($order['branch_name'] ?? 'Branch')) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-400 mt-1">Placed on <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end flex-wrap">
            <a href="/laundry-lms/customer/orders/pdf?id=<?= (int)$order['id'] ?>&download=1" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 rounded-lg border border-slate-300 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Download PDF</span>
            </a>
            <a href="/laundry-lms/customer/orders/print?id=<?= (int)$order['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 rounded-lg border border-slate-300 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Print Receipt</span>
            </a>
        </div>
    </div>

    <?php if ($order['status'] === 'cancelled'): 
        $canc = $metadata['cancellation'] ?? [];
        $rej  = $metadata['rejection'] ?? [];
    ?>
        <!-- Order Cancelled Banner -->
        <div class="bg-rose-50 border-2 border-rose-300 rounded-xl p-5 sm:p-6 shadow-sm space-y-3">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-rose-100 rounded-xl text-rose-700 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base sm:text-lg font-bold text-rose-900">Order Cancelled</h2>
                    <p class="text-xs sm:text-sm text-rose-700 mt-0.5">This order is cancelled and permanently terminal. No further services or transactions will take place.</p>
                    <div class="mt-3 bg-white p-3.5 rounded-xl border border-rose-200 text-xs space-y-1 text-slate-700">
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
        <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-amber-100 rounded-xl text-amber-700 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base sm:text-lg font-bold text-amber-900">Action Required: Review &amp; Approve Your Final Price</h2>
                    <p class="text-xs sm:text-sm text-amber-700 mt-0.5">Our shop staff has inspected and weighed your garments. Please review the verified price details below to authorize processing.</p>
                </div>
            </div>

            <?php if (!empty($metadata['inspection'])): 
                $insp = $metadata['inspection'];
                $revs = $metadata['revisions'] ?? [];
                $latestRev = !empty($revs) ? end($revs) : null;
                $prevTotal = $latestRev['previous_total'] ?? $order['total_amount'];
            ?>
            <div class="bg-white rounded-xl border border-amber-200/80 p-4 sm:p-5 space-y-4 shadow-sm">
                <?php if (!empty($insp['items']) && is_array($insp['items'])): ?>
                    <div class="overflow-x-auto border border-amber-200/70 rounded-xl">
                        <table class="w-full text-left text-xs min-w-[560px]">
                            <thead class="bg-amber-50 text-amber-900 font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3.5 py-2.5">Item &amp; Service</th>
                                    <th class="px-3.5 py-2.5 text-right">Verified Qty</th>
                                    <th class="px-3.5 py-2.5 text-right">Unit Rate</th>
                                    <th class="px-3.5 py-2.5 text-right">Subtotal</th>
                                    <th class="px-3.5 py-2.5">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100 bg-white text-slate-700">
                                <?php foreach ($insp['items'] as $itLine): 
                                    $unitStr = ($itLine['pricing_type'] === 'per_kg') ? 'kg' : 'pcs';
                                ?>
                                    <tr>
                                        <td class="px-3.5 py-2.5 font-medium text-slate-800">
                                            <?= htmlspecialchars($itLine['item_name']) ?> <span class="text-slate-500">(<?= htmlspecialchars($itLine['work_type_name']) ?>)</span>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right text-slate-700 font-medium">
                                            <?= number_format((float)$itLine['quantity'], $itLine['pricing_type'] === 'per_kg' ? 2 : 0) ?> <?= $unitStr ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right font-mono text-slate-600 tabular-nums">
                                            ₹<?= number_format((float)$itLine['unit_price'], 2) ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right font-mono font-bold text-slate-900 tabular-nums">
                                            ₹<?= number_format((float)$itLine['subtotal'], 2) ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-slate-500 italic">
                                            <?= !empty($itLine['item_notes']) ? htmlspecialchars($itLine['item_notes']) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end items-end gap-3 text-xs text-slate-600 bg-amber-50/50 p-2.5 rounded-lg border border-amber-100">
                        <div>Items Subtotal: <strong class="font-mono tabular-nums">₹<?= number_format((float)($insp['items_subtotal'] ?? 0), 2) ?></strong></div>
                        <span class="hidden sm:inline">&bull;</span>
                        <div>Pickup/Delivery Fee: <strong class="font-mono tabular-nums">₹<?= number_format((float)(($insp['pickup_fee'] ?? 0) + ($insp['delivery_fee'] ?? 0)), 2) ?></strong></div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 block font-medium">Initial Estimate</span>
                            <span class="font-bold text-slate-700 text-sm font-mono tabular-nums">₹<?= number_format((float)$prevTotal, 2) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Verified Service</span>
                            <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($insp['item_name'] ?? '') ?> (<?= htmlspecialchars($insp['work_type_name'] ?? '') ?>)</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Measured Quantity</span>
                            <span class="font-bold text-slate-800 text-sm font-mono tabular-nums"><?= number_format((float)($insp['quantity'] ?? 0), 2) ?> <?= htmlspecialchars($insp['unit_label'] ?? '') ?> @ ₹<?= number_format((float)($insp['unit_price'] ?? 0), 2) ?>/<?= htmlspecialchars($insp['unit_label'] ?? '') ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Delivery Fees</span>
                            <span class="font-bold text-slate-800 text-sm font-mono tabular-nums">₹<?= number_format((float)(($insp['pickup_fee'] ?? 0) + ($insp['delivery_fee'] ?? 0)), 2) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($insp['garment_notes'])): ?>
                    <div class="text-xs bg-slate-50 p-3 rounded-lg border border-slate-200 text-slate-700">
                        <strong>Garment Condition Notes:</strong> <?= htmlspecialchars($insp['garment_notes']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($latestRev['reason']) && $latestRev['reason'] !== 'Initial inspection completed'): ?>
                    <div class="text-xs bg-blue-50 p-3 rounded-lg border border-blue-200 text-blue-800">
                        <strong>Price Adjustment Reason:</strong> <?= htmlspecialchars($latestRev['reason']) ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-3 border-t border-slate-100">
                    <div>
                        <span class="text-xs text-slate-500 block">Final Inspected Total:</span>
                        <span class="text-2xl font-bold font-mono text-slate-900 tabular-nums">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <form action="/laundry-lms/customer/orders/approve-price" method="POST" class="inline-block">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <input type="hidden" name="expected_revision" value="<?= htmlspecialchars((string)($metadata['inspection_revision'] ?? '')) ?>">
                            <button type="submit" onclick="return confirm('Confirm and approve final order price of ₹<?= number_format((float)$order['total_amount'], 2) ?>?');" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Approve Final Price (₹<?= number_format((float)$order['total_amount'], 2) ?>)</span>
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('rejectPriceModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-4 py-2.5 rounded-xl text-xs border border-rose-200 transition-colors flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-rose-500">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Order Information & Financials -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Dates & Address Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Schedule &amp; Delivery</h3>
                    <?php 
                    $canCustomerReschedule = in_array($order['status'], ['pickup_request', 'accepted'], true);
                    $canCustomerCancel = ($order['status'] === 'pickup_request') && ((float)$order['amount_paid'] == 0.00);
                    ?>
                    <?php if ($canCustomerReschedule || $canCustomerCancel): ?>
                        <div class="flex items-center gap-2">
                            <?php if ($canCustomerReschedule): ?>
                                <button type="button" onclick="document.getElementById('reschedulePickupModal').classList.remove('hidden');" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-2.5 py-1 rounded-lg text-xs border border-slate-300 transition-colors flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span>Reschedule</span>
                                </button>
                            <?php endif; ?>
                            <?php if ($canCustomerCancel): ?>
                                <button type="button" onclick="document.getElementById('cancelOrderModal').classList.remove('hidden');" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-2.5 py-1 rounded-lg text-xs border border-rose-200 transition-colors flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-rose-500">
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
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Order Items &amp; Services</h3>
                    <?php if (!empty($orderItems)): ?>
                        <span class="text-xs text-slate-500 font-medium"><?= count($orderItems) ?> item(s)</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($orderItems)): ?>
                    <div class="overflow-x-auto border border-slate-200/80 rounded-lg">
                        <table class="w-full text-left text-xs min-w-[480px]">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="px-3.5 py-2.5">Item &amp; Service</th>
                                    <th class="px-3.5 py-2.5 text-right">Quantity</th>
                                    <th class="px-3.5 py-2.5 text-right">Rate</th>
                                    <th class="px-3.5 py-2.5 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <?php 
                                $cSubtotal = 0.00;
                                foreach ($orderItems as $item): 
                                    $cSubtotal += (float)$item['subtotal'];
                                    $unitStr = ($item['pricing_type'] === 'per_kg') ? 'kg' : 'pcs';
                                ?>
                                    <tr>
                                        <td class="px-3.5 py-2.5 font-medium text-slate-800">
                                            <?= htmlspecialchars($item['item_name']) ?>
                                            <span class="text-slate-500 text-[11px] block sm:inline">(<?= htmlspecialchars($item['work_type_name']) ?>)</span>
                                            <?php if (!empty($item['item_notes'])): ?>
                                                <span class="text-slate-400 text-[11px] block italic"><?= htmlspecialchars($item['item_notes']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right text-slate-700 font-medium">
                                            <?= number_format((float)$item['quantity'], $item['pricing_type'] === 'per_kg' ? 2 : 0) ?> <?= $unitStr ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right font-mono text-slate-600 tabular-nums">
                                            ₹<?= number_format((float)$item['unit_price'], 2) ?>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right font-mono font-bold text-slate-900 tabular-nums">
                                            ₹<?= number_format((float)$item['subtotal'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-50 font-semibold text-slate-700 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="px-3.5 py-2.5 text-right uppercase text-[11px]">Items Subtotal:</td>
                                    <td class="px-3.5 py-2.5 text-right font-mono font-bold text-slate-900 tabular-nums">₹<?= number_format($cSubtotal, 2) ?></td>
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
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">Special Instructions</h3>
                <div class="bg-slate-50 p-4 rounded-lg text-sm font-mono text-slate-800 whitespace-pre-line border border-slate-200">
                    <?= !empty($customerNote) ? Security::e($customerNote) : '<em class="text-slate-400 font-sans">No special instructions provided.</em>' ?>
                </div>
            </div>

        </div>

        <!-- Column 3: Payment Breakdown & Status Timeline -->
        <div class="space-y-6">

            <!-- Payment Summary Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">Payment Breakdown</h3>
                
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between py-1">
                        <span class="text-slate-600">Total Order Amount:</span>
                        <span class="font-bold font-mono text-slate-900 tabular-nums">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>
                    <?php if (!empty($totalRefunded) && (float)$totalRefunded > 0): ?>
                    <div class="flex justify-between py-1 border-t border-slate-100">
                        <span class="text-slate-600">Total Refunded:</span>
                        <span class="font-semibold font-mono text-rose-600 tabular-nums">-₹<?= number_format((float)$totalRefunded, 2) ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Net Paid:</span>
                        <span class="font-semibold font-mono text-emerald-700 tabular-nums">₹<?= number_format((float)$order['amount_paid'], 2) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-between py-1 border-t border-slate-100">
                        <span class="text-slate-600">Amount Paid:</span>
                        <span class="font-semibold font-mono text-emerald-600 tabular-nums">₹<?= number_format((float)$order['amount_paid'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between py-2 border-t border-slate-200 text-base">
                        <span class="font-bold text-slate-900">Outstanding Balance:</span>
                        <span class="font-bold font-mono tabular-nums <?= $outstanding > 0 ? 'text-rose-600' : 'text-slate-900' ?>">₹<?= number_format($outstanding, 2) ?></span>
                    </div>
                </div>

                <?php if ($outstanding > 0 && $order['status'] !== 'cancelled'): ?>
                    <div class="pt-2">
                        <button type="button" id="payOnlineBtn" onclick="document.getElementById('payOnlineModal').classList.remove('hidden');" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span>Pay Online (₹<?= number_format($outstanding, 2) ?>)</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Payments Log -->
                <?php if (!empty($payments)): ?>
                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Recorded Payments</h4>
                        <div class="space-y-2">
                            <?php foreach ($payments as $pmt): ?>
                                <div class="flex items-center justify-between text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-200/80">
                                    <div>
                                        <span class="font-bold font-mono text-slate-900 tabular-nums">₹<?= number_format((float)$pmt['amount'], 2) ?></span>
                                        <span class="text-slate-500 uppercase ml-1">(<?= Security::e($pmt['payment_method']) ?>)</span>
                                        <?php if (!empty($pmt['upi_reference'])): ?>
                                            <span class="text-slate-400 block font-mono text-[10px]">Ref: <?= Security::e($pmt['upi_reference']) ?></span>
                                        <?php elseif (!empty($pmt['gateway_payment_id'])): ?>
                                            <span class="text-slate-400 block font-mono text-[10px]">Ref: <?= Security::e($pmt['gateway_payment_id']) ?> (<?= Security::e(strtoupper($pmt['gateway'] ?? 'online')) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-slate-400 text-[11px]"><?= date('M d, Y h:i A', strtotime($pmt['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Refunds Log (Read-Only) -->
                <?php if (!empty($refunds)): ?>
                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-xs font-bold text-rose-600 uppercase tracking-wider">Refund History</h4>
                            <span class="text-[11px] font-bold font-mono text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 tabular-nums">
                                Total: -₹<?= number_format((float)($totalRefunded ?? 0), 2) ?>
                            </span>
                        </div>
                        <div class="space-y-2">
                            <?php foreach ($refunds as $rf): ?>
                                <div class="text-xs bg-rose-50/50 p-2.5 rounded-lg border border-rose-200/80 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold font-mono text-rose-700 tabular-nums">₹<?= number_format((float)$rf['amount'], 2) ?></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $rf['status'] === 'processed' ? 'bg-emerald-100 text-emerald-800' : ($rf['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') ?>">
                                            <?= htmlspecialchars($rf['status']) ?>
                                        </span>
                                    </div>
                                    <div class="text-slate-600 text-[11px]">
                                        <strong>Reason:</strong> <?= htmlspecialchars($rf['reason']) ?>
                                    </div>
                                    <?php if (!empty($rf['gateway_refund_id'])): ?>
                                        <div class="text-slate-400 font-mono text-[10px]">
                                            Ref: <?= htmlspecialchars($rf['gateway_refund_id']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-slate-400 text-[10px]">
                                        <?= date('M d, Y h:i A', strtotime($rf['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Status Timeline Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">Status Timeline</h3>
                
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

    <?php if ($outstanding > 0 && $order['status'] !== 'cancelled'): ?>
    <!-- Online Payment Modal -->
    <div id="payOnlineModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Pay Outstanding Balance</h3>
                        <p class="text-[11px] text-slate-500">Order #<?= htmlspecialchars($order['job_id']) ?></p>
                    </div>
                </div>
                <button type="button" onclick="closePayModal()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <!-- Error Banner -->
            <div id="payModalError" class="hidden bg-rose-50 border border-rose-200 text-rose-700 text-xs p-3 rounded-xl"></div>

            <!-- Financial Summary Box -->
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5">
                <div class="flex justify-between text-slate-600">
                    <span>Order Total:</span>
                    <span class="font-mono font-medium text-slate-800">₹<?= number_format((float)$order['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Amount Paid:</span>
                    <span class="font-mono font-medium text-emerald-600">₹<?= number_format((float)$order['amount_paid'], 2) ?></span>
                </div>
                <div class="flex justify-between text-slate-800 font-bold border-t border-slate-200 pt-1.5 text-sm">
                    <span>Outstanding Balance:</span>
                    <span class="font-mono text-rose-600">₹<?= number_format($outstanding, 2) ?></span>
                </div>
            </div>

            <!-- Amount Options -->
            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-700">Select Amount to Pay</label>

                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="pay_amount_type" id="pay_full_radio" value="full" checked onchange="toggleCustomAmount(false)" class="text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block">Pay Full Balance</span>
                            <span class="text-slate-500 font-mono">₹<?= number_format($outstanding, 2) ?></span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="pay_amount_type" id="pay_custom_radio" value="custom" onchange="toggleCustomAmount(true)" class="text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs flex-1">
                            <span class="font-bold text-slate-800 block">Pay Custom Amount</span>
                            <span class="text-slate-500">Min ₹1.00 &bull; Max ₹<?= number_format($outstanding, 2) ?></span>
                        </div>
                    </label>

                    <div id="custom_amount_container" class="hidden pl-7 pr-2">
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-mono text-xs">
                                ₹
                            </div>
                            <input type="number" id="custom_amount_input" step="0.01" min="1.00" max="<?= sprintf('%.2f', $outstanding) ?>" value="<?= sprintf('%.2f', $outstanding) ?>" class="w-full pl-7 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono" placeholder="Enter amount">
                        </div>
                    </div>
                </div>
            </div>

            <?php if (($gatewayDriver ?? '') === 'mock'): ?>
            <!-- Mock Gateway Sandbox Badge -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-amber-800">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>MOCK PAYMENT GATEWAY (Local Sandbox)</span>
                </div>
                <p class="text-[11px] text-amber-700">
                    No real bank or card is charged. Tests the exact server-side HMAC verification, balance recalculation, and idempotency logic.
                </p>
            </div>

            <!-- Mock Actions -->
            <div class="space-y-2 pt-1">
                <button type="button" id="mockSuccessBtn" onclick="processMockPayment('success')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simulate Payment Success</span>
                </button>

                <button type="button" id="mockFailBtn" onclick="processMockPayment('failure')" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold py-2 px-4 rounded-xl text-xs border border-rose-200 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span>Simulate Payment Failure</span>
                </button>

                <button type="button" onclick="closePayModal()" class="w-full py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
                    Cancel
                </button>
            </div>

            <?php else: ?>
            <!-- Live Gateway (Razorpay) Action -->
            <div class="space-y-2 pt-1">
                <button type="button" id="razorpayBtn" onclick="processRazorpayPayment()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span>Proceed to Secure Checkout</span>
                </button>

                <button type="button" onclick="closePayModal()" class="w-full py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
                    Cancel
                </button>
            </div>
            <?php endif; ?>

            <!-- Loading Spinner -->
            <div id="payModalLoading" class="hidden text-center py-4 space-y-2">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-emerald-600"></div>
                <p class="text-xs text-slate-600 font-medium" id="payModalLoadingText">Connecting to secure gateway...</p>
            </div>
        </div>
    </div>

    <!-- Hidden form for CSRF and submission reference -->
    <form id="hiddenPaymentForm" class="hidden">
        <?= Csrf::field() ?>
        <input type="hidden" id="pay_order_id" value="<?= (int)$order['id'] ?>">
        <input type="hidden" id="pay_max_outstanding" value="<?= sprintf('%.2f', $outstanding) ?>">
    </form>

    <script>
    function toggleCustomAmount(isCustom) {
        const container = document.getElementById('custom_amount_container');
        if (isCustom) {
            container.classList.remove('hidden');
            document.getElementById('custom_amount_input').focus();
        } else {
            container.classList.add('hidden');
        }
    }

    function closePayModal() {
        document.getElementById('payOnlineModal').classList.add('hidden');
        document.getElementById('payModalError').classList.add('hidden');
        document.getElementById('payModalLoading').classList.add('hidden');
    }

    function getSelectedAmount() {
        const isCustom = document.getElementById('pay_custom_radio').checked;
        const maxOut = parseFloat(document.getElementById('pay_max_outstanding').value);
        if (isCustom) {
            const raw = parseFloat(document.getElementById('custom_amount_input').value);
            if (isNaN(raw) || raw < 1.00) {
                throw new Error("Minimum payment amount is ₹1.00");
            }
            if (raw > maxOut + 0.001) {
                throw new Error("Amount cannot exceed outstanding balance of ₹" + maxOut.toFixed(2));
            }
            return raw.toFixed(2);
        }
        return maxOut.toFixed(2);
    }

    function showPayError(msg) {
        const el = document.getElementById('payModalError');
        el.textContent = msg;
        el.classList.remove('hidden');
        document.getElementById('payModalLoading').classList.add('hidden');
    }

    function setPayLoading(loading, text = 'Processing payment...') {
        const loadingEl = document.getElementById('payModalLoading');
        const textEl = document.getElementById('payModalLoadingText');
        textEl.textContent = text;
        if (loading) {
            loadingEl.classList.remove('hidden');
            document.getElementById('payModalError').classList.add('hidden');
        } else {
            loadingEl.classList.add('hidden');
        }
    }

    async function processMockPayment(mode) {
        try {
            const amount = getSelectedAmount();
            const orderId = document.getElementById('pay_order_id').value;
            const csrfInput = document.querySelector('#hiddenPaymentForm input[name="_csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            setPayLoading(true, "Creating payment session...");

            // Step 1: Initiate
            const initFormData = new URLSearchParams();
            initFormData.append('_csrf_token', csrfToken);
            initFormData.append('order_id', orderId);
            initFormData.append('amount', amount);
            initFormData.append('is_ajax', '1');

            const initRes = await fetch('/laundry-lms/customer/orders/pay/initiate', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: initFormData
            });

            const initData = await initRes.json();
            if (!initRes.ok || !initData.success) {
                throw new Error(initData.message || 'Failed to initiate payment session.');
            }

            setPayLoading(true, "Verifying transaction signature...");

            // Step 2: Verify
            const verifyFormData = new URLSearchParams();
            verifyFormData.append('_csrf_token', csrfToken);
            verifyFormData.append('order_id', orderId);
            verifyFormData.append('gateway_order_id', initData.gateway_order_id);
            verifyFormData.append('gateway_payment_id', initData.gateway_payment_id);
            verifyFormData.append('is_ajax', '1');

            if (mode === 'failure') {
                verifyFormData.append('mock_status', 'failed');
                verifyFormData.append('gateway_signature', 'tampered_mock_signature');
            } else {
                verifyFormData.append('mock_status', 'success');
                verifyFormData.append('gateway_signature', initData.gateway_signature);
            }

            const verifyRes = await fetch('/laundry-lms/customer/orders/pay/verify', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: verifyFormData
            });

            const verifyData = await verifyRes.json();
            if (!verifyRes.ok || !verifyData.success) {
                throw new Error(verifyData.message || 'Payment verification failed.');
            }

            // Success -> redirect
            window.location.href = verifyData.redirect_url || ('/laundry-lms/customer/orders/show?id=' + orderId);
        } catch (err) {
            showPayError(err.message || 'An unexpected error occurred during payment processing.');
        }
    }

    async function processRazorpayPayment() {
        try {
            const amount = getSelectedAmount();
            const orderId = document.getElementById('pay_order_id').value;
            const csrfInput = document.querySelector('#hiddenPaymentForm input[name="_csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            setPayLoading(true, "Initiating Razorpay order...");

            const initFormData = new URLSearchParams();
            initFormData.append('_csrf_token', csrfToken);
            initFormData.append('order_id', orderId);
            initFormData.append('amount', amount);
            initFormData.append('is_ajax', '1');

            const initRes = await fetch('/laundry-lms/customer/orders/pay/initiate', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: initFormData
            });

            const initData = await initRes.json();
            if (!initRes.ok || !initData.success) {
                throw new Error(initData.message || 'Failed to initiate Razorpay order.');
            }

            if (!window.Razorpay) {
                // Dynamically load script
                const script = document.createElement('script');
                script.src = 'https://checkout.razorpay.com/v1/checkout.js';
                script.onload = () => openRazorpayModal(initData, csrfToken, orderId);
                script.onerror = () => showPayError("Failed to load Razorpay SDK. Please check your Internet connection.");
                document.body.appendChild(script);
            } else {
                openRazorpayModal(initData, csrfToken, orderId);
            }
        } catch (err) {
            showPayError(err.message || 'Error connecting to Razorpay.');
        }
    }

    function openRazorpayModal(initData, csrfToken, orderId) {
        setPayLoading(false);
        const options = {
            key: initData.key_id,
            amount: Math.round(parseFloat(initData.amount) * 100),
            currency: initData.currency || 'INR',
            name: 'Laundry LMS',
            description: 'Order Payment #' + orderId,
            order_id: initData.gateway_order_id,
            handler: async function (response) {
                setPayLoading(true, "Verifying payment with bank...");
                try {
                    const verifyFormData = new URLSearchParams();
                    verifyFormData.append('_csrf_token', csrfToken);
                    verifyFormData.append('order_id', orderId);
                    verifyFormData.append('gateway_order_id', response.razorpay_order_id);
                    verifyFormData.append('gateway_payment_id', response.razorpay_payment_id);
                    verifyFormData.append('gateway_signature', response.razorpay_signature);
                    verifyFormData.append('is_ajax', '1');

                    const verifyRes = await fetch('/laundry-lms/customer/orders/pay/verify', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: verifyFormData
                    });

                    const verifyData = await verifyRes.json();
                    if (!verifyRes.ok || !verifyData.success) {
                        throw new Error(verifyData.message || 'Verification failed.');
                    }
                    window.location.href = verifyData.redirect_url || ('/laundry-lms/customer/orders/show?id=' + orderId);
                } catch (vErr) {
                    showPayError(vErr.message);
                }
            },
            modal: {
                ondismiss: function () {
                    setPayLoading(false);
                }
            }
        };
        const rzp = new Razorpay(options);
        rzp.open();
    }
    </script>
    <?php endif; ?>

</div>
