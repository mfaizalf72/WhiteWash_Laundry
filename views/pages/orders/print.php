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
 * Paper Formats:
 * - paper = 'a4' (Default A4 document layout)
 * - paper = '80' (80mm thermal POS layout, usable print width 72mm)
 * - paper = '58' (58mm thermal POS layout, usable print width 52mm)
 * 
 * Strictly Native Printing (window.print() + CSS @media print).
 * Read-only representation.
 */

$isJobCard = ($mode ?? 'job_card') === 'job_card';
$docHeading = $isJobCard ? 'JOB CARD' : 'CUSTOMER RECEIPT';
$docSubheading = $isJobCard ? 'Operational Laundry Service Ticket' : 'Official Customer Service Receipt';

// Paper parameter validation (strictly whitelist '58' and '80'; default to 'a4')
$paper = in_array($paper ?? 'a4', ['58', '80'], true) ? $paper : 'a4';
$isThermal58 = ($paper === '58');
$isThermal80 = ($paper === '80');
$isThermal   = $isThermal58 || $isThermal80;

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
$cancData = $metadata['cancellation'] ?? [];
$rejData  = $metadata['rejection'] ?? [];
$inspData = $metadata['inspection'] ?? [];

$branchName  = !empty($order['branch_name']) ? $order['branch_name'] : null;
$shopName    = !empty($settings['business_name']) ? $settings['business_name'] : 'Launder LMS';
$shopSubName = '';
if ($branchName && $branchName !== $shopName) {
    $shopSubName = $branchName . (!empty($order['branch_code']) ? ' (' . $order['branch_code'] . ')' : '');
} elseif (!empty($order['branch_code'])) {
    $shopSubName = 'Branch Code: ' . $order['branch_code'];
}
$shopAddress = !empty($order['branch_address']) ? $order['branch_address'] : (!empty($settings['address']) ? $settings['address'] : '');
$shopPhone   = !empty($order['branch_phone']) ? $order['branch_phone'] : (!empty($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '');
$shopHours   = !empty($order['branch_working_hours']) ? $order['branch_working_hours'] : (!empty($settings['working_hours']) ? $settings['working_hours'] : '');

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

$basePrintUrl = $isJobCard
    ? '/laundry-lms/orders/print?id=' . (int)$order['id']
    : '/laundry-lms/customer/orders/print?id=' . (int)$order['id'];
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
            overflow-wrap: break-word;
            word-break: break-word;
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
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            gap: 12px;
            flex-wrap: wrap;
        }

        .toolbar-left, .toolbar-center, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
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
            padding: 6px 12px;
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

        .btn-format {
            background-color: #1e293b;
            color: #cbd5e1;
            border: 1px solid #475569;
        }
        .btn-format:hover {
            background-color: #334155;
            color: #ffffff;
        }
        .btn-format-active {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 700;
            border: 1px solid #3b82f6;
        }

        .btn-print {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #059669;
        }

        /* Printable Paper Containers (Screen Previews) */
        /* A4 Layout Container */
        .paper-a4 .print-container {
            max-width: 800px;
            margin: 24px auto;
            background: #ffffff;
            padding: 32px 36px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* 80mm Thermal Container: Usable Print Width = 72mm */
        .paper-80mm .print-container {
            width: 72mm;
            max-width: 72mm;
            margin: 24px auto;
            background: #ffffff;
            padding: 3mm;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            font-size: 11px;
            line-height: 1.35;
        }

        /* 58mm Thermal Container: Usable Print Width = 52mm */
        .paper-58mm .print-container {
            width: 52mm;
            max-width: 52mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 2mm;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            font-size: 10px;
            line-height: 1.25;
        }

        /* Common Elements */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* A4 Specific Component Styles */
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

        .doc-meta { text-align: right; }

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

        .doc-meta-item { font-size: 11px; color: #475569; }

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

        .info-card-text { font-size: 11.5px; color: #334155; line-height: 1.4; }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 4px;
        }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-val { color: #0f172a; font-weight: 600; text-align: right; }

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

        .items-table tr:nth-child(even) td { background-color: #fafbfc; }

        .line-item-name { font-weight: 600; color: #0f172a; }
        .line-item-note { font-size: 10.5px; color: #64748b; font-style: italic; margin-top: 2px; }

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

        .note-card-body { font-size: 11.5px; color: #334155; white-space: pre-wrap; }

        .financial-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

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

        .doc-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
        }

        /* Thermal Layout Common Elements */
        .thermal-divider {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }
        .thermal-header {
            text-align: center;
            margin-bottom: 6px;
        }
        .thermal-shop-name {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .thermal-shop-sub {
            font-size: 0.9em;
            color: #333333;
            line-height: 1.3;
        }
        .thermal-doc-title {
            font-weight: 800;
            text-transform: uppercase;
            margin: 4px 0 2px 0;
        }
        .thermal-cancelled-box {
            border: 1px dashed #000000;
            padding: 4px 6px;
            margin: 6px 0;
            text-align: center;
        }
        .thermal-section-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.95em;
            margin: 4px 0 2px 0;
        }
        .thermal-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        /* 80mm Table Styling */
        .thermal-80-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin: 4px 0;
        }
        .thermal-80-table th {
            border-bottom: 1px solid #000000;
            border-top: 1px solid #000000;
            padding: 3px 2px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
        }
        .thermal-80-table td {
            padding: 3px 2px;
            vertical-align: top;
            border-bottom: 1px dotted #cccccc;
        }

        /* Single Page Sizing Rule Emission */
<?php if ($paper === '58'): ?>
        @page {
            size: 58mm auto;
            margin: 3mm;
        }
<?php elseif ($paper === '80'): ?>
        @page {
            size: 80mm auto;
            margin: 4mm;
        }
<?php else: ?>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
<?php endif; ?>

        /* Native Media Print Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .no-print-toolbar, .no-print {
                display: none !important;
            }

<?php if ($paper === '58'): ?>
            body {
                font-size: 9.5pt !important;
                line-height: 1.25 !important;
            }
            .print-container {
                width: 52mm !important;
                max-width: 52mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
<?php elseif ($paper === '80'): ?>
            body {
                font-size: 10pt !important;
                line-height: 1.3 !important;
            }
            .print-container {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
<?php else: ?>
            body {
                font-size: 11pt !important;
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
<?php endif; ?>

            .page-break-inside-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="paper-<?= $paper === '58' ? '58mm' : ($paper === '80' ? '80mm' : 'a4') ?>">

    <!-- Screen-Only Toolbar -->
    <div class="no-print-toolbar no-print">
        <div class="toolbar-left">
            <a href="<?= Security::e($backUrl ?? '/laundry-lms/orders') ?>" class="btn-toolbar btn-back">
                &larr; Back to Order
            </a>
            <span class="toolbar-title"><?= Security::e($docHeading) ?> Preview</span>
            <span class="toolbar-badge"><?= $isJobCard ? 'Staff / Admin' : 'Customer View' ?></span>
        </div>

        <!-- Format Switcher Controls -->
        <div class="toolbar-center">
            <span style="font-size: 11px; color: #94a3b8;">Format:</span>
            <a href="<?= $basePrintUrl ?>" class="btn-toolbar <?= $paper === 'a4' ? 'btn-format-active' : 'btn-format' ?>">
                A4 Document
            </a>
            <a href="<?= $basePrintUrl ?>&paper=80" class="btn-toolbar <?= $paper === '80' ? 'btn-format-active' : 'btn-format' ?>">
                80mm POS
            </a>
            <a href="<?= $basePrintUrl ?>&paper=58" class="btn-toolbar <?= $paper === '58' ? 'btn-format-active' : 'btn-format' ?>">
                58mm POS
            </a>
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

<?php if ($paper === '58'): ?>
        <!-- ====================================================================== -->
        <!-- 58mm THERMAL COMPACT STACKED LAYOUT (Usable Width = 52mm)               -->
        <!-- ====================================================================== -->

        <!-- Shop Header -->
        <div class="thermal-header">
            <div class="thermal-shop-name" style="font-size: 13px;"><?= Security::e($shopName) ?></div>
            <?php if (!empty($shopAddress)): ?>
                <div class="thermal-shop-sub"><?= Security::e($shopAddress) ?></div>
            <?php endif; ?>
            <?php if (!empty($shopPhone)): ?>
                <div class="thermal-shop-sub">Tel: <?= Security::e($shopPhone) ?></div>
            <?php endif; ?>
            <?php if (!empty($shopHours)): ?>
                <div class="thermal-shop-sub">Hours: <?= Security::e($shopHours) ?></div>
            <?php endif; ?>
            <div class="thermal-divider"></div>
            <div class="thermal-doc-title" style="font-size: 11px;"><?= Security::e($docHeading) ?></div>
            <div style="font-weight: 700; font-family: monospace; font-size: 12px;">#<?= Security::e($order['job_id'] ?? '') ?></div>
            <div style="font-size: 9px; color: #444;"><?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A' ?></div>
            <div style="font-size: 9.5px; font-weight: 700; margin-top: 2px;">Status: <?= Security::e($statusDisplay) ?></div>
        </div>

        <?php if ($isCancelled): ?>
            <!-- Prominent Cancelled Alert -->
            <div class="thermal-cancelled-box">
                <div style="font-weight: 800; font-size: 11px;">&#9888; ORDER CANCELLED</div>
                <div style="font-size: 9px; margin-top: 2px;">
                    <strong>Reason:</strong> <?= Security::e($cancData['reason'] ?? ($rejData['reason'] ?? 'Order cancelled')) ?><br>
                    <strong>On:</strong> <?= !empty($cancData['cancelled_at']) ? date('d M Y, h:i A', strtotime($cancData['cancelled_at'])) : date('d M Y, h:i A', strtotime($order['updated_at'] ?? 'now')) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Customer & Schedule Summary -->
        <div class="thermal-divider"></div>
        <div style="font-size: 9.5px;">
            <div><strong>Cust:</strong> <?= Security::e($order['customer_name'] ?? 'Walk-in') ?></div>
            <div><strong>Mob:</strong> <?= Security::e($order['customer_mobile'] ?? 'N/A') ?></div>
            <?php if (!empty($order['customer_address'])): ?>
                <div><strong>Addr:</strong> <?= Security::e($order['customer_address']) ?></div>
            <?php endif; ?>
            <div><strong>Pickup:</strong> <?= !empty($order['pickup_date']) ? date('d M Y', strtotime($order['pickup_date'])) : 'N/A' ?></div>
            <div><strong>Delivery:</strong> <?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : 'Pending' ?></div>
            <?php if (!empty($order['staff_name'])): ?>
                <div><strong>Staff:</strong> <?= Security::e($order['staff_name']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Items / Services Breakdown -->
        <div class="thermal-divider"></div>
        <div class="thermal-section-title">ITEMS / SERVICES</div>

        <?php if ($hasStructuredItems): ?>
            <!-- Tiered Stacked Items for 58mm -->
            <div style="margin-top: 4px;">
                <?php foreach ($orderItems as $idx => $item): 
                    $unitLabel = ($item['pricing_type'] ?? '') === 'per_kg' ? 'kg' : 'pc';
                    $qtyFormatted = ($item['pricing_type'] ?? '') === 'per_kg'
                        ? number_format((float)($item['quantity'] ?? 0), 2)
                        : number_format((float)($item['quantity'] ?? 0), 0);
                ?>
                    <div style="margin-bottom: 5px; padding-bottom: 3px; <?= $idx < count($orderItems) - 1 ? 'border-bottom: 1px dotted #ccc;' : '' ?>">
                        <!-- Tier 1: Item Name & Service -->
                        <div style="font-weight: 700; font-size: 10px;">
                            <?= Security::e($item['item_name'] ?? 'Item') ?>
                            <span style="font-weight: normal; font-size: 9px; color: #444;">(<?= Security::e($item['work_type_name'] ?? 'Standard') ?>)</span>
                        </div>
                        <?php if (!empty($item['item_notes'])): ?>
                            <div style="font-size: 8.5px; font-style: italic; color: #555;"><?= Security::e($item['item_notes']) ?></div>
                        <?php endif; ?>
                        <!-- Tier 2: Qty x Unit Rate on left, Line Subtotal on right -->
                        <div style="display: flex; justify-content: space-between; font-size: 9.5px; margin-top: 1px;">
                            <span><?= $qtyFormatted ?> <?= $unitLabel ?> &times; &#8377;<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></span>
                            <span style="font-weight: 700;">&#8377;<?= number_format((float)($item['subtotal'] ?? 0), 2) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Legacy Order Representation: Never fabricate structured items! -->
            <div style="padding: 4px 0; font-size: 9.5px; white-space: pre-wrap;">
                <?= Security::e(!empty($customerNote) ? $customerNote : (!empty($order['special_instructions']) ? $order['special_instructions'] : 'Standard laundry service request')) ?>
            </div>
        <?php endif; ?>

        <!-- Financial Summary -->
        <div class="thermal-divider"></div>
        <div style="font-size: 9.5px;">
            <?php if ($hasStructuredItems): ?>
                <div class="thermal-row">
                    <span>Subtotal:</span>
                    <span>&#8377;<?= number_format($itemsSubtotal, 2) ?></span>
                </div>
                <?php if ($pickupDeliveryCharge > 0): ?>
                    <div class="thermal-row">
                        <span>Delivery Fee:</span>
                        <span>&#8377;<?= number_format($pickupDeliveryCharge, 2) ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="thermal-row" style="font-weight: 800; font-size: 11px; border-top: 1px solid #000; margin-top: 3px; padding-top: 3px;">
                <span>TOTAL:</span>
                <span>&#8377;<?= number_format($totalAmount, 2) ?></span>
            </div>
            <?php if (!empty($totalRefunded) && $totalRefunded > 0): ?>
            <div class="thermal-row" style="font-weight: 600; color: #b91c1c;">
                <span>Refunded:</span>
                <span>-&#8377;<?= number_format($totalRefunded, 2) ?></span>
            </div>
            <div class="thermal-row" style="font-weight: 600; color: #15803d;">
                <span>Net Paid:</span>
                <span>&#8377;<?= number_format($amountPaid, 2) ?></span>
            </div>
            <?php else: ?>
            <div class="thermal-row" style="font-weight: 600; color: #15803d;">
                <span>Paid:</span>
                <span>&#8377;<?= number_format($amountPaid, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="thermal-row" style="font-weight: 800; border-top: 1px dashed #ccc; margin-top: 2px; padding-top: 2px; <?= $balanceDue > 0 ? 'color: #b91c1c;' : 'color: #15803d;' ?>">
                <span>Balance:</span>
                <span><?= $balanceDue <= 0.001 ? 'PAID IN FULL' : '&#8377;' . number_format($balanceDue, 2) ?></span>
            </div>
        </div>

        <?php if (!empty($payments) && is_array($payments)): ?>
            <!-- Compact Payment Records -->
            <div class="thermal-divider"></div>
            <div class="thermal-section-title">PAYMENTS</div>
            <?php foreach ($payments as $p): ?>
                <div class="thermal-row" style="font-size: 8.5px; margin-bottom: 2px;">
                    <span><?= !empty($p['created_at']) ? date('d M', strtotime($p['created_at'])) : '' ?> (<?= strtoupper(Security::e($p['payment_method'] ?? 'cash')) ?>)</span>
                    <span style="font-weight: 700;">&#8377;<?= number_format((float)($p['amount'] ?? 0), 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($refunds) && is_array($refunds)): ?>
            <!-- Compact Refund Records -->
            <?php $hasProcessedRefunds = false; foreach ($refunds as $rf) { if ($rf['status'] === 'processed') { $hasProcessedRefunds = true; break; } } ?>
            <?php if ($hasProcessedRefunds): ?>
                <div class="thermal-divider"></div>
                <div class="thermal-section-title" style="color: #b91c1c;">REFUNDS</div>
                <?php foreach ($refunds as $rf): ?>
                    <?php if ($rf['status'] === 'processed'): ?>
                        <div class="thermal-row" style="font-size: 8.5px; margin-bottom: 2px; color: #b91c1c;">
                            <span><?= !empty($rf['created_at']) ? date('d M', strtotime($rf['created_at'])) : '' ?> (REFUND)</span>
                            <span style="font-weight: 700;">-&#8377;<?= number_format((float)($rf['amount'] ?? 0), 2) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isJobCard): ?>
            <!-- Handover Signature Line for Staff Job Card -->
            <div style="margin-top: 18px; border-top: 1px solid #000; padding-top: 3px; text-align: center; font-size: 8.5px;">
                Customer Handover Signature
            </div>
        <?php else: ?>
            <!-- Customer Receipt Compact Note -->
            <div style="margin-top: 12px; text-align: center; font-size: 8.5px; color: #444;">
                Thank you for your business!
            </div>
        <?php endif; ?>

        <!-- Compact Footer -->
        <div class="thermal-divider"></div>
        <div style="text-align: center; font-size: 8px; color: #666;">
            <?= Security::e($shopName) ?> &bull; <?= date('d M Y, h:i A') ?>
        </div>

<?php elseif ($paper === '80'): ?>
        <!-- ====================================================================== -->
        <!-- 80mm THERMAL COMPACT TABLE LAYOUT (Usable Width = 72mm)                 -->
        <!-- ====================================================================== -->

        <!-- Shop Header -->
        <div class="thermal-header">
            <div class="thermal-shop-name" style="font-size: 14px;"><?= Security::e($shopName) ?></div>
            <?php if (!empty($shopAddress)): ?>
                <div class="thermal-shop-sub"><?= Security::e($shopAddress) ?></div>
            <?php endif; ?>
            <?php if (!empty($shopPhone)): ?>
                <div class="thermal-shop-sub">Phone: <?= Security::e($shopPhone) ?><?= !empty($shopHours) ? ' &bull; Hours: ' . Security::e($shopHours) : '' ?></div>
            <?php endif; ?>
            <div class="thermal-divider"></div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div class="thermal-doc-title" style="font-size: 12px;"><?= Security::e($docHeading) ?></div>
                <div style="font-weight: 700; font-family: monospace; font-size: 13px;">#<?= Security::e($order['job_id'] ?? '') ?></div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 9.5px; color: #444; margin-top: 2px;">
                <span>Date: <?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A' ?></span>
                <span style="font-weight: 700;">Status: <?= Security::e($statusDisplay) ?></span>
            </div>
        </div>

        <?php if ($isCancelled): ?>
            <!-- Prominent Cancelled Alert -->
            <div class="thermal-cancelled-box">
                <div style="font-weight: 800; font-size: 12px;">&#9888; ORDER CANCELLED</div>
                <div style="font-size: 10px; margin-top: 2px;">
                    <strong>Reason:</strong> <?= Security::e($cancData['reason'] ?? ($rejData['reason'] ?? 'Order cancelled')) ?><br>
                    <strong>Cancelled On:</strong> <?= !empty($cancData['cancelled_at']) ? date('d M Y, h:i A', strtotime($cancData['cancelled_at'])) : date('d M Y, h:i A', strtotime($order['updated_at'] ?? 'now')) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Customer & Operations Details -->
        <div class="thermal-divider"></div>
        <div style="font-size: 10px; line-height: 1.4;">
            <div class="thermal-row">
                <span><strong>Customer:</strong> <?= Security::e($order['customer_name'] ?? 'Walk-in') ?></span>
                <span><strong>Mob:</strong> <?= Security::e($order['customer_mobile'] ?? 'N/A') ?></span>
            </div>
            <?php if (!empty($order['customer_address'])): ?>
                <div><strong>Address:</strong> <?= Security::e($order['customer_address']) ?></div>
            <?php endif; ?>
            <div class="thermal-row" style="margin-top: 2px;">
                <span><strong>Pickup:</strong> <?= !empty($order['pickup_date']) ? date('d M Y', strtotime($order['pickup_date'])) : 'N/A' ?></span>
                <span><strong>Delivery:</strong> <?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : 'Pending' ?></span>
            </div>
            <?php if (!empty($order['staff_name'])): ?>
                <div class="thermal-row">
                    <span><strong>Staff:</strong> <?= Security::e($order['staff_name']) ?></span>
                    <?php if (!empty($inspData['inspected_by_name'])): ?>
                        <span><strong>Inspected:</strong> <?= Security::e($inspData['inspected_by_name']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items / Services Breakdown (Compact 4-Column Table) -->
        <div class="thermal-divider"></div>
        <div class="thermal-section-title">SERVICE DETAILS & ITEMS</div>

        <?php if ($hasStructuredItems): ?>
            <table class="thermal-80-table">
                <thead>
                    <tr>
                        <th style="width: 46%; text-align: left;">Item & Service</th>
                        <th style="width: 14%; text-align: center;">Qty</th>
                        <th style="width: 20%; text-align: right;">Rate</th>
                        <th style="width: 20%; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): 
                        $unitLabel = ($item['pricing_type'] ?? '') === 'per_kg' ? 'kg' : 'pc';
                        $qtyFormatted = ($item['pricing_type'] ?? '') === 'per_kg'
                            ? number_format((float)($item['quantity'] ?? 0), 2)
                            : number_format((float)($item['quantity'] ?? 0), 0);
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= Security::e($item['item_name'] ?? 'Item') ?></div>
                                <div style="font-size: 8.5px; color: #555;"><?= Security::e($item['work_type_name'] ?? 'Standard') ?></div>
                                <?php if (!empty($item['item_notes'])): ?>
                                    <div style="font-size: 8.5px; font-style: italic; color: #666;"><?= Security::e($item['item_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $qtyFormatted ?> <?= $unitLabel ?></td>
                            <td class="text-right">&#8377;<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                            <td class="text-right" style="font-weight: 700;">&#8377;<?= number_format((float)($item['subtotal'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Legacy Order Representation: Never fabricate structured items! -->
            <div style="padding: 6px 0; font-size: 10px; white-space: pre-wrap;">
                <strong>Service Description:</strong><br>
                <?= Security::e(!empty($customerNote) ? $customerNote : (!empty($order['special_instructions']) ? $order['special_instructions'] : 'Standard laundry service request')) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($customerNote) && $hasStructuredItems || !empty($inspData['garment_notes'])): ?>
            <!-- Optional Inspection / Special Instructions -->
            <div style="font-size: 9.5px; margin: 4px 0;">
                <?php if (!empty($customerNote) && $hasStructuredItems): ?>
                    <div><strong>Instructions:</strong> <?= Security::e($customerNote) ?></div>
                <?php endif; ?>
                <?php if (!empty($inspData['garment_notes'])): ?>
                    <div><strong>Inspection Notes:</strong> <?= Security::e($inspData['garment_notes']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Financial Summary -->
        <div class="thermal-divider"></div>
        <div style="font-size: 10px;">
            <?php if ($hasStructuredItems): ?>
                <div class="thermal-row">
                    <span>Items Subtotal:</span>
                    <span>&#8377;<?= number_format($itemsSubtotal, 2) ?></span>
                </div>
                <?php if ($pickupDeliveryCharge > 0): ?>
                    <div class="thermal-row">
                        <span>Pickup & Delivery Charge:</span>
                        <span>&#8377;<?= number_format($pickupDeliveryCharge, 2) ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="thermal-row" style="font-weight: 800; font-size: 12px; border-top: 1px solid #000; margin-top: 4px; padding-top: 4px;">
                <span>TOTAL AMOUNT:</span>
                <span>&#8377;<?= number_format($totalAmount, 2) ?></span>
            </div>
            <?php if (!empty($totalRefunded) && $totalRefunded > 0): ?>
            <div class="thermal-row" style="font-weight: 600; color: #b91c1c; margin-top: 2px;">
                <span>Total Refunded:</span>
                <span>-&#8377;<?= number_format($totalRefunded, 2) ?></span>
            </div>
            <div class="thermal-row" style="font-weight: 600; color: #15803d; margin-top: 2px;">
                <span>Net Paid:</span>
                <span>&#8377;<?= number_format($amountPaid, 2) ?></span>
            </div>
            <?php else: ?>
            <div class="thermal-row" style="font-weight: 600; color: #15803d; margin-top: 2px;">
                <span>Amount Paid:</span>
                <span>&#8377;<?= number_format($amountPaid, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="thermal-row" style="font-weight: 800; font-size: 11px; border-top: 1px dashed #ccc; margin-top: 3px; padding-top: 3px; <?= $balanceDue > 0 ? 'color: #b91c1c;' : 'color: #15803d;' ?>">
                <span>Balance Due:</span>
                <span><?= $balanceDue <= 0.001 ? 'PAID IN FULL' : '&#8377;' . number_format($balanceDue, 2) ?></span>
            </div>
        </div>

        <?php if (!empty($payments) && is_array($payments)): ?>
            <!-- Payment History Ledger -->
            <div class="thermal-divider"></div>
            <div class="thermal-section-title">PAYMENT HISTORY</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 2px;">
                <thead>
                    <tr style="border-bottom: 1px solid #000;">
                        <th style="text-align: left; padding: 2px 0;">Date</th>
                        <th style="text-align: left; padding: 2px 0;">Method</th>
                        <th style="text-align: right; padding: 2px 0;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr style="border-bottom: 1px dotted #ddd;">
                            <td style="padding: 2px 0;"><?= !empty($p['created_at']) ? date('d M, h:i A', strtotime($p['created_at'])) : 'N/A' ?></td>
                            <td style="padding: 2px 0; text-transform: uppercase;">
                                <?= Security::e($p['payment_method'] ?? 'cash') ?>
                                <?= !empty($p['upi_reference']) ? '(' . Security::e($p['upi_reference']) . ')' : '' ?>
                            </td>
                            <td style="text-align: right; padding: 2px 0; font-weight: 700;">&#8377;<?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($refunds) && is_array($refunds)): ?>
            <!-- Refund History Ledger -->
            <?php $hasProcessed80 = false; foreach ($refunds as $rf) { if ($rf['status'] === 'processed') { $hasProcessed80 = true; break; } } ?>
            <?php if ($hasProcessed80): ?>
                <div class="thermal-divider"></div>
                <div class="thermal-section-title" style="color: #b91c1c;">REFUNDS</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 2px;">
                    <tbody>
                        <?php foreach ($refunds as $rf): ?>
                            <?php if ($rf['status'] === 'processed'): ?>
                                <tr style="border-bottom: 1px dotted #ddd; color: #b91c1c;">
                                    <td style="padding: 2px 0;"><?= !empty($rf['created_at']) ? date('d M, h:i A', strtotime($rf['created_at'])) : 'N/A' ?></td>
                                    <td style="padding: 2px 0;"><?= htmlspecialchars($rf['reason']) ?></td>
                                    <td style="text-align: right; padding: 2px 0; font-weight: 700;">-&#8377;<?= number_format((float)($rf['amount'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isJobCard): ?>
            <!-- Signature Block for 80mm Job Card -->
            <div style="display: flex; justify-content: space-between; gap: 16px; margin-top: 24px;">
                <div style="flex: 1; border-top: 1px solid #000; padding-top: 4px; text-align: center; font-size: 9px;">
                    Customer Signature
                </div>
                <div style="flex: 1; border-top: 1px solid #000; padding-top: 4px; text-align: center; font-size: 9px;">
                    Staff Signature
                </div>
            </div>
        <?php else: ?>
            <!-- Customer Receipt Terms Note -->
            <div style="margin-top: 14px; font-size: 9px; color: #555; text-align: center; line-height: 1.3;">
                Please report any garment discrepancies within 24 hours of delivery. Thank you for your business!
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="thermal-divider"></div>
        <div style="display: flex; justify-content: space-between; font-size: 8.5px; color: #666;">
            <span><?= Security::e($shopName) ?></span>
            <span>Generated <?= date('d M Y, h:i A') ?></span>
        </div>

<?php else: ?>
        <!-- ====================================================================== -->
        <!-- STANDARD A4 DOCUMENT LAYOUT (100% Unchanged Existing Implementation)    -->
        <!-- ====================================================================== -->

        <!-- Document Header: Shop & Order Meta -->
        <div class="doc-header">
            <div class="shop-info">
                <h1><?= Security::e($shopName) ?></h1>
                <?php if (!empty($shopSubName)): ?>
                    <p style="font-weight: bold; color: #475569;"><?= Security::e($shopSubName) ?></p>
                <?php endif; ?>
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
                    <span class="info-label">Branch:</span>
                    <span class="info-val"><?= Security::e(!empty($order['branch_name']) ? $order['branch_name'] . (!empty($order['branch_code']) ? ' (' . $order['branch_code'] . ')' : '') : 'Main Branch') ?></span>
                </div>
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

                <?php if (!empty($refunds) && is_array($refunds)): ?>
                    <?php $hasProcessedA4 = false; foreach ($refunds as $rf) { if ($rf['status'] === 'processed') { $hasProcessedA4 = true; break; } } ?>
                    <?php if ($hasProcessedA4): ?>
                        <div class="section-title" style="margin-top: 14px; color: #b91c1c;">Payment Refunds</div>
                        <table class="receipt-table" style="font-size: 11px;">
                            <thead>
                                <tr style="background-color: #fff1f2;">
                                    <th>Date & Time</th>
                                    <th>Gateway Refund ID</th>
                                    <th>Reason</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($refunds as $rf): ?>
                                    <?php if ($rf['status'] === 'processed'): ?>
                                        <tr style="color: #b91c1c;">
                                            <td><?= !empty($rf['created_at']) ? date('d M Y, h:i A', strtotime($rf['created_at'])) : 'N/A' ?></td>
                                            <td style="font-family: monospace;"><?= Security::e($rf['gateway_refund_id'] ?? 'N/A') ?></td>
                                            <td><?= Security::e($rf['reason']) ?></td>
                                            <td class="text-right" style="font-weight: 700;">-&#8377;<?= number_format((float)($rf['amount'] ?? 0), 2) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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

                    <?php if (!empty($totalRefunded) && $totalRefunded > 0): ?>
                    <div class="summary-row" style="margin-top: 4px;">
                        <span style="color: #b91c1c;">Total Refunded:</span>
                        <span style="font-weight: 600; color: #b91c1c;">-&#8377;<?= number_format($totalRefunded, 2) ?></span>
                    </div>
                    <div class="summary-row" style="margin-top: 4px;">
                        <span>Net Paid:</span>
                        <span style="font-weight: 600; color: #15803d;">&#8377;<?= number_format($amountPaid, 2) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="summary-row" style="margin-top: 4px;">
                        <span>Amount Paid:</span>
                        <span style="font-weight: 600; color: #15803d;">&#8377;<?= number_format($amountPaid, 2) ?></span>
                    </div>
                    <?php endif; ?>

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

<?php endif; ?>

    </div>

</body>
</html>
