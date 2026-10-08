<?php
declare(strict_types=1);

use Faizal\LaundryLms\Helpers\Security;

/**
 * Dedicated A4 PDF Template for Invoices & Job Cards.
 *
 * Rendered server-side by Dompdf.
 * Uses CSS 2.1 table and box-model layouts for maximum Dompdf compatibility.
 */

$isJobCard = ($mode ?? 'receipt') === 'job_card';
$docTitle  = $isJobCard ? 'JOB CARD' : 'TAX INVOICE / RECEIPT';
$docSub    = $isJobCard ? 'Operational Laundry Service Ticket' : 'Official Customer Service Invoice';

$totalAmount   = (float)($order['total_amount'] ?? 0);
$amountPaid    = (float)($order['amount_paid'] ?? 0);
$totalRefunded = (float)($totalRefunded ?? (\Faizal\LaundryLms\Models\Refund::getTotalRefundedByOrderId((int)$order['id'])));
$refunds       = $refunds ?? (\Faizal\LaundryLms\Models\Refund::getByOrderId((int)$order['id']));
$balanceDue    = max(0.0, round($totalAmount - $amountPaid, 2));

$hasStructuredItems = !empty($orderItems) && is_array($orderItems);
$itemsSubtotal = 0.0;
if ($hasStructuredItems) {
    foreach ($orderItems as $item) {
        $itemsSubtotal += (float)($item['subtotal'] ?? 0);
    }
    $pickupDeliveryCharge = max(0.0, round($totalAmount - $itemsSubtotal, 2));
} else {
    $pickupDeliveryCharge = 0.0;
}

$isCancelled = ($order['status'] ?? '') === 'cancelled';
$cancData    = $metadata['cancellation'] ?? [];
$rejData     = $metadata['rejection'] ?? [];
$inspData    = $metadata['inspection'] ?? [];

$shopName    = !empty($settings['business_name']) ? $settings['business_name'] : 'Launder LMS';
$shopAddress = !empty($settings['address']) ? $settings['address'] : '';
$shopPhone   = !empty($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '';
$shopHours   = !empty($settings['working_hours']) ? $settings['working_hours'] : '';

$statusLabels = [
    'pickup_request'       => 'Pickup Requested',
    'accepted'             => 'Order Accepted',
    'picked_up'            => 'Garments Picked Up',
    'inspection_completed' => 'Inspection Completed',
    'price_confirmed'      => 'Price Confirmed',
    'processing'           => 'In Processing',
    'ready'                => 'Ready for Delivery',
    'out_for_delivery'     => 'Out for Delivery',
    'delivered'            => 'Delivered',
    'completed'            => 'Completed',
    'cancelled'            => 'Cancelled'
];
$statusDisplay = $statusLabels[$order['status'] ?? ''] ?? ucwords(str_replace('_', ' ', (string)($order['status'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= Security::e($docTitle) ?> - #<?= Security::e($order['job_id'] ?? '') ?></title>
    <style>
        @page {
            margin: 28px 32px 32px 32px;
            size: a4 portrait;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background-color: #ffffff;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: top;
        }

        .shop-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .shop-meta {
            font-size: 10px;
            color: #475569;
            line-height: 1.4;
        }

        .doc-title-cell {
            text-align: right;
        }

        .doc-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .job-id {
            font-size: 15px;
            font-weight: bold;
            font-family: monospace;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .order-meta-line {
            font-size: 10px;
            color: #475569;
        }

        .status-pill {
            display: inline-block;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 9999px;
            background-color: #e2e8f0;
            color: #1e293b;
            margin-top: 3px;
        }

        .status-pill-cancelled {
            background-color: #ffe4e6;
            color: #9f1239;
        }

        /* Cancellation Banner */
        .cancel-banner {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            color: #881337;
        }

        .cancel-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .cancel-text {
            font-size: 9.5px;
            line-height: 1.35;
        }

        /* Two-Column Info Cards */
        .info-table {
            width: 100%;
            margin-bottom: 14px;
            border-spacing: 0;
        }

        .info-card {
            width: 48%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            vertical-align: top;
        }

        .info-card-title {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }

        .info-card-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .info-card-text {
            font-size: 10px;
            color: #334155;
            line-height: 1.4;
        }

        /* Items Table */
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: top;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .item-name {
            font-weight: bold;
            color: #0f172a;
        }

        .item-note {
            font-size: 8.5px;
            color: #64748b;
            font-style: italic;
            margin-top: 1px;
        }

        /* Legacy Service Box */
        .legacy-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 14px;
            font-size: 10px;
            color: #334155;
        }

        /* Bottom Section: Payment Ledger & Billing Summary */
        .summary-split-table {
            width: 100%;
            border-spacing: 0;
            margin-bottom: 14px;
        }

        .summary-split-left {
            width: 58%;
            vertical-align: top;
            padding-right: 12px;
        }

        .summary-split-right {
            width: 42%;
            vertical-align: top;
        }

        .billing-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
        }

        .billing-row {
            width: 100%;
            font-size: 10px;
            padding: 2px 0;
        }

        .billing-row-total {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            margin-top: 4px;
            padding-top: 4px;
        }

        .billing-row-balance {
            font-size: 12px;
            font-weight: bold;
            border-top: 1px dashed #94a3b8;
            margin-top: 4px;
            padding-top: 4px;
        }

        .paid-badge {
            color: #15803d;
            font-weight: bold;
        }

        .due-badge {
            color: #b91c1c;
            font-weight: bold;
        }

        /* Terms / Signatures */
        .terms-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.35;
            margin-top: 10px;
        }

        .footer-table {
            width: 100%;
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
        }

        .page-avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 58%;">
                <div class="shop-name"><?= Security::e($shopName) ?></div>
                <div class="shop-meta">
                    <?php if (!empty($shopAddress)): ?>
                        <div><?= Security::e($shopAddress) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($shopPhone)): ?>
                        <div>Phone / WhatsApp: <?= Security::e($shopPhone) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($shopHours)): ?>
                        <div>Operating Hours: <?= Security::e($shopHours) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="doc-title-cell" style="width: 42%;">
                <div class="doc-badge"><?= Security::e($docTitle) ?></div>
                <div class="job-id">#<?= Security::e($order['job_id'] ?? '') ?></div>
                <div class="order-meta-line">
                    Order Date: <?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A' ?>
                </div>
                <div>
                    <span class="status-pill <?= $isCancelled ? 'status-pill-cancelled' : '' ?>">
                        Status: <?= Security::e($statusDisplay) ?>
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <?php if ($isCancelled): ?>
        <!-- Cancellation Notice -->
        <div class="cancel-banner">
            <div class="cancel-title">&#9888; ORDER CANCELLED</div>
            <div class="cancel-text">
                <strong>Reason:</strong> <?= Security::e($cancData['reason'] ?? ($rejData['reason'] ?? 'Order cancelled')) ?><br>
                <strong>Cancelled On:</strong> <?= !empty($cancData['cancelled_at']) ? date('d M Y, h:i A', strtotime($cancData['cancelled_at'])) : date('d M Y, h:i A', strtotime($order['updated_at'] ?? 'now')) ?>
                <?php if (!empty($cancData['cancelled_by_role'])): ?>
                    &bull; <strong>By:</strong> <?= ucfirst(Security::e($cancData['cancelled_by_role'])) ?>
                    <?php if (!empty($cancData['cancelled_by_name'])): ?>
                        (<?= Security::e($cancData['cancelled_by_name']) ?>)
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Customer & Operations Schedule Cards -->
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-card">
                <div class="info-card-title">Customer Details</div>
                <div class="info-card-name"><?= Security::e($order['customer_name'] ?? 'Walk-in Customer') ?></div>
                <div class="info-card-text">
                    <strong>Mobile:</strong> <?= Security::e($order['customer_mobile'] ?? 'N/A') ?><br>
                    <strong>Address:</strong> <?= Security::e(!empty($order['customer_address']) ? $order['customer_address'] : 'No address specified') ?>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="info-card">
                <div class="info-card-title">Operations & Schedule</div>
                <div class="info-card-text">
                    <strong>Pickup Date:</strong> <?= !empty($order['pickup_date']) ? date('d M Y', strtotime($order['pickup_date'])) : 'N/A' ?><br>
                    <strong>Delivery Date:</strong> <?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : 'Pending Schedule' ?><br>
                    <strong>Staff Operator:</strong> <?= Security::e(!empty($order['staff_name']) ? $order['staff_name'] : 'Unassigned') ?>
                    <?php if (!empty($inspData['inspected_by_name'])): ?>
                        <br><strong>Inspected By:</strong> <?= Security::e($inspData['inspected_by_name']) ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <div class="section-heading">Service Breakdown & Items</div>
    <?php if ($hasStructuredItems): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 40%;">Item Description</th>
                    <th style="width: 20%;">Service Type</th>
                    <th style="width: 11%;" class="text-center">Qty / Wt</th>
                    <th style="width: 11%;" class="text-right">Unit Rate</th>
                    <th style="width: 13%;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 1;
                foreach ($orderItems as $item):
                    $unitLabel = ($item['pricing_type'] ?? '') === 'per_kg' ? 'kg' : 'pc';
                    $qtyFormatted = ($item['pricing_type'] ?? '') === 'per_kg'
                        ? number_format((float)($item['quantity'] ?? 0), 2)
                        : number_format((float)($item['quantity'] ?? 0), 0);
                ?>
                    <tr>
                        <td class="text-center"><?= $count++ ?></td>
                        <td>
                            <div class="item-name"><?= Security::e($item['item_name'] ?? 'Item') ?></div>
                            <?php if (!empty($item['item_notes'])): ?>
                                <div class="item-note"><?= Security::e($item['item_notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= Security::e($item['work_type_name'] ?? 'Standard') ?></td>
                        <td class="text-center"><?= $qtyFormatted ?> <?= $unitLabel ?></td>
                        <td class="text-right">&#8377;<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                        <td class="text-right" style="font-weight: bold;">&#8377;<?= number_format((float)($item['subtotal'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="legacy-box">
            <strong>Legacy Service Request:</strong><br>
            <?= nl2br(Security::e(!empty($customerNote) ? $customerNote : (!empty($order['special_instructions']) ? $order['special_instructions'] : 'Standard laundry service package'))) ?>
        </div>
    <?php endif; ?>

    <!-- Special Notes if Present -->
    <?php if ((!empty($customerNote) && $hasStructuredItems) || !empty($inspData['garment_notes'])): ?>
        <table class="info-table" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
            <tr>
                <?php if (!empty($customerNote) && $hasStructuredItems): ?>
                    <td class="info-card" style="width: 48%;">
                        <div class="info-card-title">Customer Instructions</div>
                        <div class="info-card-text"><?= nl2br(Security::e($customerNote)) ?></div>
                    </td>
                <?php endif; ?>
                <?php if (!empty($customerNote) && $hasStructuredItems && !empty($inspData['garment_notes'])): ?>
                    <td style="width: 4%;"></td>
                <?php endif; ?>
                <?php if (!empty($inspData['garment_notes'])): ?>
                    <td class="info-card" style="width: <?= (!empty($customerNote) && $hasStructuredItems) ? '48%' : '100%' ?>;">
                        <div class="info-card-title">Inspection / Garment Notes</div>
                        <div class="info-card-text"><?= nl2br(Security::e($inspData['garment_notes'])) ?></div>
                    </td>
                <?php endif; ?>
            </tr>
        </table>
    <?php endif; ?>

    <!-- Financial Breakdown & Payment History Split -->
    <div class="page-avoid-break">
        <table class="summary-split-table" cellpadding="0" cellspacing="0">
            <tr>
                <!-- Left: Payment & Refund Ledgers -->
                <td class="summary-split-left">
                    <div class="section-heading">Payment History</div>
                    <?php if (!empty($payments) && is_array($payments)): ?>
                        <table class="data-table" style="margin-bottom: 10px;">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Date & Time</th>
                                    <th style="width: 20%;">Method</th>
                                    <th style="width: 26%;">Reference</th>
                                    <th style="width: 22%;" class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><?= !empty($p['created_at']) ? date('d M Y, h:i A', strtotime($p['created_at'])) : 'N/A' ?></td>
                                        <td style="text-transform: uppercase; font-weight: bold;"><?= Security::e($p['payment_method'] ?? 'cash') ?></td>
                                        <td><?= !empty($p['upi_reference']) ? Security::e($p['upi_reference']) : '-' ?></td>
                                        <td class="text-right" style="font-weight: bold;">&#8377;<?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="font-size: 9.5px; color: #64748b; font-style: italic; margin-bottom: 10px;">
                            No payments recorded yet for this order.
                        </div>
                    <?php endif; ?>

                    <?php
                    $processedRefunds = [];
                    if (!empty($refunds) && is_array($refunds)) {
                        foreach ($refunds as $rf) {
                            if (($rf['status'] ?? '') === 'processed') {
                                $processedRefunds[] = $rf;
                            }
                        }
                    }
                    ?>
                    <?php if (!empty($processedRefunds)): ?>
                        <div class="section-heading" style="color: #b91c1c; border-bottom-color: #fecdd3;">Processed Refunds</div>
                        <table class="data-table">
                            <thead>
                                <tr style="background-color: #fff1f2;">
                                    <th style="width: 30%;">Date</th>
                                    <th style="width: 26%;">Gateway Ref</th>
                                    <th style="width: 24%;">Reason</th>
                                    <th style="width: 20%;" class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processedRefunds as $rf): ?>
                                    <tr style="color: #9f1239;">
                                        <td><?= !empty($rf['created_at']) ? date('d M Y, h:i A', strtotime($rf['created_at'])) : 'N/A' ?></td>
                                        <td style="font-family: monospace; font-size: 8.5px;"><?= Security::e($rf['gateway_refund_id'] ?? 'N/A') ?></td>
                                        <td><?= Security::e($rf['reason']) ?></td>
                                        <td class="text-right" style="font-weight: bold;">-&#8377;<?= number_format((float)($rf['amount'] ?? 0), 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </td>

                <!-- Right: Billing Calculation Box -->
                <td class="summary-split-right">
                    <div class="section-heading">Billing Summary</div>
                    <div class="billing-box">
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php if ($hasStructuredItems): ?>
                                <tr>
                                    <td class="billing-row" style="color: #475569;">Items Subtotal:</td>
                                    <td class="billing-row text-right">&#8377;<?= number_format($itemsSubtotal, 2) ?></td>
                                </tr>
                                <?php if ($pickupDeliveryCharge > 0): ?>
                                    <tr>
                                        <td class="billing-row" style="color: #475569;">Pickup &amp; Delivery Fee:</td>
                                        <td class="billing-row text-right">&#8377;<?= number_format($pickupDeliveryCharge, 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>

                            <tr>
                                <td class="billing-row-total">Total Amount:</td>
                                <td class="billing-row-total text-right">&#8377;<?= number_format($totalAmount, 2) ?></td>
                            </tr>

                            <?php if (!empty($totalRefunded) && $totalRefunded > 0): ?>
                                <tr>
                                    <td class="billing-row" style="color: #b91c1c; padding-top: 4px;">Total Refunded:</td>
                                    <td class="billing-row text-right" style="color: #b91c1c; font-weight: bold; padding-top: 4px;">-&#8377;<?= number_format($totalRefunded, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="billing-row" style="color: #15803d; font-weight: bold;">Net Paid:</td>
                                    <td class="billing-row text-right" style="color: #15803d; font-weight: bold;">&#8377;<?= number_format($amountPaid, 2) ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td class="billing-row" style="color: #15803d; padding-top: 4px;">Amount Paid:</td>
                                    <td class="billing-row text-right" style="color: #15803d; font-weight: bold; padding-top: 4px;">&#8377;<?= number_format($amountPaid, 2) ?></td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td class="billing-row-balance">Balance Due:</td>
                                <td class="billing-row-balance text-right">
                                    <?php if ($balanceDue <= 0.001): ?>
                                        <span class="paid-badge">PAID IN FULL</span>
                                    <?php else: ?>
                                        <span class="due-badge">&#8377;<?= number_format($balanceDue, 2) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top: 8px; font-size: 9.5px; text-align: right; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px;">
                            Payment Status: <strong><?= strtoupper(Security::e($order['payment_status'] ?? 'unpaid')) ?></strong>
                        </div>
                    </div>

                    <?php if ($isJobCard): ?>
                        <!-- Staff / Customer Signatures on Job Card -->
                        <table style="width: 100%; margin-top: 14px;">
                            <tr>
                                <td style="width: 48%; border-top: 1px dashed #94a3b8; font-size: 8px; color: #64748b; padding-top: 3px; text-align: center;">
                                    Customer Signature
                                </td>
                                <td style="width: 4%;"></td>
                                <td style="width: 48%; border-top: 1px dashed #94a3b8; font-size: 8px; color: #64748b; padding-top: 3px; text-align: center;">
                                    Staff Operator Signature
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <!-- Customer Receipt Terms -->
                        <div class="terms-box">
                            <strong>Terms &amp; Conditions:</strong> Please inspect garments upon delivery. Any discrepancies must be reported within 24 hours. Present this receipt or your registered mobile number for handover. Thank you for your business!
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Document Footer -->
    <table class="footer-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="text-align: left;">
                <?= Security::e($shopName) ?> &bull; Generated <?= date('d M Y, h:i A') ?>
            </td>
            <td style="text-align: right;">
                Job ID: #<?= Security::e($order['job_id'] ?? '') ?>
            </td>
        </tr>
    </table>

</body>
</html>
