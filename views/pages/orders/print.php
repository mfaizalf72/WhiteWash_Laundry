<?php
declare(strict_types=1);

use Faizal\LaundryLms\Helpers\Security;

/**
 * Unified Print Template: Job Card & Customer Receipt
 * 
 * Supports:
 * - mode = 'job_card' (Staff/Admin operational ticket)
 * - mode = 'receipt' (Customer payment/acknowledgement receipt)
 * 
 * Strictly Native Printing (window.print() + CSS @media print).
 * Read-only representation.
 */

$isJobCard = ($mode ?? 'job_card') === 'job_card';
$docHeading = $isJobCard ? 'JOB CARD' : 'CUSTOMER RECEIPT';
$docSubheading = $isJobCard ? 'Operational Laundry Service Ticket' : 'Official Customer Service Receipt';

$totalAmount = (float)($order['total_amount'] ?? 0);
$amountPaid  = (float)($order['amount_paid'] ?? 0);
$balanceDue  = max(0.0, round($totalAmount - $amountPaid, 2));

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
$cancData = $metadata['cancellation'] ?? [];
$rejData  = $metadata['rejection'] ?? [];
$inspData = $metadata['inspection'] ?? [];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($docHeading) ?> - #<?= Security::e($order['job_id'] ?? '') ?> - <?= Security::e($shopName) ?></title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            line-height: 1.45;
            font-size: 13px;
        }

        /* Screen-Only Control Toolbar */
        .no-print-toolbar {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toolbar-title {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .toolbar-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: <?= $isJobCard ? '#3b82f6' : '#10b981' ?>;
            color: #ffffff;
        }

        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background-color 0.15s ease-in-out;
        }

        .btn-back {
            background-color: #334155;
            color: #f8fafc;
        }
        .btn-back:hover {
            background-color: #475569;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        /* Printable Paper Container */
        .print-container {
            max-width: 800px;
            margin: 24px auto;
            background: #ffffff;
            padding: 32px 36px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* Header Layout */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        .shop-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }

        .shop-info p {
            font-size: 11px;
            color: #475569;
            line-height: 1.35;
        }

        .doc-meta {
            text-align: right;
        }

        .doc-title-badge {
            display: inline-block;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .doc-job-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .doc-meta-item {
            font-size: 11px;
            color: #475569;
        }

        .status-pill {
            display: inline-block;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #334155;
        }

        .status-pill-cancelled {
            border-color: #f87171;
            background-color: #fef2f2;
            color: #991b1b;
        }

        /* Cancelled Alert Box */
        .cancelled-banner {
            border: 2px dashed #dc2626;
            background-color: #fef2f2;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .cancelled-banner-title {
            color: #991b1b;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cancelled-banner-text {
            font-size: 11.5px;
            color: #7f1d1d;
            line-height: 1.4;
        }

        .cancelled-banner-details {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #fecaca;
            font-size: 11px;
            color: #991b1b;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* 2-Column Section: Customer & Schedule */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            background-color: #f8fafc;
        }

        .info-card-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-card-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .info-card-text {
            font-size: 11.5px;
            color: #334155;
            line-height: 1.4;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 4px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-val {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        /* Section Headings */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #0f172a;
            margin-bottom: 8px;
            border-left: 3px solid #0f172a;
            padding-left: 8px;
        }

        /* Order Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: 0.04em;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .line-item-name {
            font-weight: 600;
            color: #0f172a;
        }

        .line-item-note {
            font-size: 10.5px;
            color: #64748b;
            font-style: italic;
            margin-top: 2px;
        }

        /* Legacy Order Section */
        .legacy-order-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #f8fafc;
            padding: 14px 16px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .legacy-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #475569;
            margin-bottom: 6px;
        }

        .legacy-instructions-text {
            font-size: 12px;
            color: #1e293b;
            white-space: pre-wrap;
            line-height: 1.45;
        }

        /* Special Notes & Inspection Details */
        .notes-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .note-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            background-color: #ffffff;
        }

        .note-card-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .note-card-body {
            font-size: 11.5px;
            color: #334155;
            white-space: pre-wrap;
        }

        /* Financial & Payments Summary Grid */
        .financial-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        /* Payment Ledger Table */
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .payments-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .payments-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        /* Financial Summary Box */
        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 16px;
            background-color: #f8fafc;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 6px;
            color: #334155;
        }

        .summary-row-total {
            border-top: 1.5px solid #0f172a;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        .summary-row-balance {
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 13px;
            font-weight: 700;
            color: <?= $balanceDue > 0 ? '#b91c1c' : '#15803d' ?>;
        }

        /* Signature Blocks for Job Card */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            text-align: center;
            font-size: 11px;
            color: #475569;
            font-weight: 600;
        }

        /* Document Footer */
        .doc-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
        }

        /* Native Media Print Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11pt;
            }

            .no-print-toolbar, .no-print {
                display: none !important;
            }

            .print-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .info-card, .summary-box, .legacy-order-box, .note-card {
                background-color: #ffffff !important;
                border: 1px solid #94a3b8 !important;
            }

            .items-table th, .payments-table th {
                background-color: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #64748b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table td, .payments-table td {
                border: 1px solid #94a3b8 !important;
            }

            .cancelled-banner {
                background-color: #ffffff !important;
                border: 2px dashed #000000 !important;
            }

            .cancelled-banner-title, .cancelled-banner-text, .cancelled-banner-details {
                color: #000000 !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }

            .page-break-inside-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Screen-Only Toolbar -->
    <div class="no-print-toolbar no-print">
        <div class="toolbar-left">
            <a href="<?= Security::e($backUrl ?? '/laundry-lms/orders') ?>" class="btn-toolbar btn-back">
                &larr; Back to Order
            </a>
            <span class="toolbar-title"><?= Security::e($docHeading) ?> Preview</span>
            <span class="toolbar-badge"><?= $isJobCard ? 'Staff / Admin' : 'Customer View' ?></span>
        </div>
        <div class="toolbar-right">
            <button type="button" onclick="window.print()" class="btn-toolbar btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Paper Area -->
    <div class="print-container">

        <!-- Document Header: Shop & Order Meta -->
        <div class="doc-header">
            <div class="shop-info">
                <h1><?= Security::e($shopName) ?></h1>
                <?php if (!empty($shopAddress)): ?>
                    <p><?= Security::e($shopAddress) ?></p>
                <?php endif; ?>
                <?php if (!empty($shopPhone)): ?>
                    <p>Phone / WhatsApp: <?= Security::e($shopPhone) ?></p>
                <?php endif; ?>
                <?php if (!empty($shopHours)): ?>
                    <p>Hours: <?= Security::e($shopHours) ?></p>
                <?php endif; ?>
            </div>
            <div class="doc-meta">
                <div class="doc-title-badge"><?= Security::e($docHeading) ?></div>
                <div class="doc-job-id">#<?= Security::e($order['job_id'] ?? '') ?></div>
                <div class="doc-meta-item">Order Date: <?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A' ?></div>
                <div class="doc-meta-item">
                    <span class="status-pill <?= $isCancelled ? 'status-pill-cancelled' : '' ?>">
                        Status: <?= Security::e($statusDisplay) ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($isCancelled): ?>
            <!-- Prominent Cancelled Watermark Banner -->
            <div class="cancelled-banner">
                <div class="cancelled-banner-title">
                    &#9888; ORDER CANCELLED
                </div>
                <div class="cancelled-banner-text">
                    This order has been cancelled and is terminal. No active processing, collection, or delivery is permitted.
                </div>
                <div class="cancelled-banner-details">
                    <div>
                        <strong>Reason:</strong>
                        <?= Security::e($cancData['reason'] ?? ($rejData['reason'] ?? 'Order cancelled')) ?>
                    </div>
                    <div>
                        <strong>Cancelled On:</strong>
                        <?= !empty($cancData['cancelled_at']) ? date('d M Y, h:i A', strtotime($cancData['cancelled_at'])) : date('d M Y, h:i A', strtotime($order['updated_at'] ?? 'now')) ?>
                    </div>
                    <div>
                        <strong>Initiator:</strong>
                        <?= ucfirst(Security::e((string)($cancData['cancelled_by_role'] ?? 'user'))) ?>
                        <?php if (!empty($cancData['cancelled_by_name'])): ?>
                            (<?= Security::e($cancData['cancelled_by_name']) ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Customer & Schedule Information Grid -->
        <div class="info-grid">
            <!-- Customer Card -->
            <div class="info-card">
                <div class="info-card-title">Customer Information</div>
                <div class="info-card-name"><?= Security::e($order['customer_name'] ?? 'Walk-in Customer') ?></div>
                <div class="info-card-text">
                    <strong>Mobile:</strong> <?= Security::e($order['customer_mobile'] ?? 'N/A') ?><br>
                    <strong>Address:</strong> <?= Security::e($order['customer_address'] ?? 'No address provided') ?>
                </div>
            </div>

            <!-- Operations / Schedule Card -->
            <div class="info-card">
                <div class="info-card-title">Operations & Schedule</div>
                <div class="info-row">
                    <span class="info-label">Pickup Date:</span>
                    <span class="info-val"><?= !empty($order['pickup_date']) ? date('d M Y', strtotime($order['pickup_date'])) : 'N/A' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Delivery Date:</span>
                    <span class="info-val"><?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : 'Pending Schedule' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Assigned Staff:</span>
                    <span class="info-val"><?= Security::e($order['staff_name'] ?? 'Unassigned') ?></span>
                </div>
                <?php if (!empty($inspData['inspected_by_name'])): ?>
                    <div class="info-row">
                        <span class="info-label">Inspected By:</span>
                        <span class="info-val"><?= Security::e($inspData['inspected_by_name']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($inspData['inspected_at'])): ?>
                    <div class="info-row">
                        <span class="info-label">Inspected On:</span>
                        <span class="info-val"><?= date('d M Y, h:i A', strtotime($inspData['inspected_at'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items / Service Breakdown -->
        <div class="page-break-inside-avoid">
            <div class="section-title">Service Details & Items</div>

            <?php if ($hasStructuredItems): ?>
                <!-- Structured Phase 10C Multi-Item Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 38%;">Item & Line Notes</th>
                            <th style="width: 20%;">Service</th>
                            <th style="width: 12%;" class="text-center">Qty / Wt</th>
                            <th style="width: 12%;" class="text-right">Rate</th>
                            <th style="width: 13%;" class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($orderItems as $item): 
                            $unitLabel = ($item['pricing_type'] ?? '') === 'per_kg' ? 'kg' : 'pc';
                            $qtyFormatted = ($item['pricing_type'] ?? '') === 'per_kg'
                                ? number_format((float)($item['quantity'] ?? 0), 2)
                                : number_format((float)($item['quantity'] ?? 0), 0);
                        ?>
                            <tr>
                                <td class="text-center"><?= $counter++ ?></td>
                                <td>
                                    <div class="line-item-name"><?= Security::e($item['item_name'] ?? 'Item') ?></div>
                                    <?php if (!empty($item['item_notes'])): ?>
                                        <div class="line-item-note"><?= Security::e($item['item_notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= Security::e($item['work_type_name'] ?? 'Standard') ?></td>
                                <td class="text-center"><?= $qtyFormatted ?> <?= $unitLabel ?></td>
                                <td class="text-right">&#8377;<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                                <td class="text-right">&#8377;<?= number_format((float)($item['subtotal'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Legacy Order Representation: Never invent fake structured items! -->
                <div class="legacy-order-box">
                    <span class="legacy-tag">Legacy Order (Single-Package Service)</span>
                    <div class="legacy-instructions-text"><?= Security::e(!empty($customerNote) ? $customerNote : (!empty($order['special_instructions']) ? $order['special_instructions'] : 'Standard laundry service request')) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Optional Notes Section (Customer Instructions / Inspection Notes) -->
        <?php if (!empty($customerNote) && $hasStructuredItems || !empty($inspData['garment_notes'])): ?>
            <div class="notes-grid">
                <?php if (!empty($customerNote) && $hasStructuredItems): ?>
                    <div class="note-card">
                        <div class="note-card-title">Customer Special Instructions</div>
                        <div class="note-card-body"><?= Security::e($customerNote) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($inspData['garment_notes'])): ?>
                    <div class="note-card">
                        <div class="note-card-title">Inspection & Garment Notes</div>
                        <div class="note-card-body"><?= Security::e($inspData['garment_notes']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Financial Summary & Payment History Grid -->
        <div class="financial-grid">
            <!-- Left: Payment Ledger -->
            <div>
                <div class="section-title">Payment History</div>
                <?php if (!empty($payments) && is_array($payments)): ?>
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Received By</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= !empty($p['created_at']) ? date('d M Y, h:i A', strtotime($p['created_at'])) : 'N/A' ?></td>
                                    <td style="text-transform: uppercase; font-weight: 600;"><?= Security::e($p['payment_method'] ?? 'cash') ?></td>
                                    <td><?= !empty($p['upi_reference']) ? Security::e($p['upi_reference']) : '-' ?></td>
                                    <td><?= Security::e($p['recorded_by_name'] ?? 'Staff') ?></td>
                                    <td class="text-right" style="font-weight: 600;">&#8377;<?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="font-size: 11.5px; color: #64748b; font-style: italic; padding: 6px 0;">
                        No payments recorded yet for this order.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Right: Financial Calculation Box -->
            <div>
                <div class="section-title">Billing Summary</div>
                <div class="summary-box">
                    <?php if ($hasStructuredItems): ?>
                        <div class="summary-row">
                            <span>Items Subtotal:</span>
                            <span>&#8377;<?= number_format($itemsSubtotal, 2) ?></span>
                        </div>
                        <?php if ($pickupDeliveryCharge > 0): ?>
                            <div class="summary-row">
                                <span>Pickup & Delivery Charge:</span>
                                <span>&#8377;<?= number_format($pickupDeliveryCharge, 2) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="summary-row summary-row-total">
                        <span>Total Amount:</span>
                        <span>&#8377;<?= number_format($totalAmount, 2) ?></span>
                    </div>

                    <div class="summary-row" style="margin-top: 4px;">
                        <span>Amount Paid:</span>
                        <span style="font-weight: 600; color: #15803d;">&#8377;<?= number_format($amountPaid, 2) ?></span>
                    </div>

                    <div class="summary-row summary-row-balance">
                        <span>Balance Due:</span>
                        <span><?= $balanceDue <= 0.001 ? 'PAID IN FULL' : '&#8377;' . number_format($balanceDue, 2) ?></span>
                    </div>

                    <div style="margin-top: 6px; font-size: 11px; text-align: right; color: #64748b;">
                        Payment Status: <strong><?= strtoupper(Security::e($order['payment_status'] ?? 'unpaid')) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($isJobCard): ?>
            <!-- Handover / Signatures Block for Staff Job Card -->
            <div class="signatures-grid">
                <div>
                    <div class="signature-line">Customer Signature (Dropoff / Pickup Handover)</div>
                </div>
                <div>
                    <div class="signature-line">Staff Operator Signature</div>
                </div>
            </div>
        <?php else: ?>
            <!-- Customer Receipt Terms & Acknowledgment -->
            <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; background-color: #fafbfc; margin-top: 20px; font-size: 11px; color: #475569; page-break-inside: avoid;">
                <strong>Terms & Conditions:</strong>
                Please inspect garments at the time of delivery. Any discrepancies must be reported within 24 hours of delivery. Present this receipt or your registered mobile number for garment handover. Thank you for your business!
            </div>
        <?php endif; ?>

        <!-- Document Footer -->
        <div class="doc-footer">
            <div><?= Security::e($shopName) ?> &bull; Generated <?= date('d M Y, h:i A') ?></div>
            <div>Job ID: #<?= Security::e($order['job_id'] ?? '') ?></div>
        </div>

    </div>

</body>
</html>
