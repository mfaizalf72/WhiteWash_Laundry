<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Order;
use Faizal\LaundryLms\Models\Customer;
use Faizal\LaundryLms\Models\LaundryItem;
use Faizal\LaundryLms\Models\WorkType;
use Faizal\LaundryLms\Models\Pricing;
use Faizal\LaundryLms\Models\Settings;
use Faizal\LaundryLms\Models\Payment;
use Faizal\LaundryLms\Models\Refund;
use Faizal\LaundryLms\Models\OrderItem;
use Faizal\LaundryLms\Helpers\OrderMetadata;
use Faizal\LaundryLms\Services\Payment\PaymentGatewayFactory;
use Faizal\LaundryLms\Services\Pdf\InvoicePdfService;

class OrderController {
    public function index(): void {
        $status        = trim($_GET['status'] ?? '');
        $search        = trim($_GET['search'] ?? '');
        $pickupToday   = !empty($_GET['pickup_today']);
        $deliveryToday = !empty($_GET['delivery_today']);
        $paymentDue    = !empty($_GET['payment_due']);
        $page          = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'status'         => $status,
            'search'         => $search,
            'pickup_today'   => $pickupToday,
            'delivery_today' => $deliveryToday,
            'payment_due'    => $paymentDue
        ];

        $paginatedData = Order::allPaginated($filters, $page, 20);
        $layout        = (($_SESSION['user_role'] ?? '') === 'staff') ? 'staff' : 'admin';

        Router::view('admin/orders/index', [
            'title'        => 'Orders Directory - Launder',
            'orders'       => $paginatedData['orders'],
            'totalOrders'  => $paginatedData['total_orders'],
            'page'         => $paginatedData['page'],
            'totalPages'   => $paginatedData['total_pages'],
            'status'       => $status,
            'search'       => $search,
            'pickupToday'  => $pickupToday,
            'deliveryToday'=> $deliveryToday,
            'paymentDue'   => $paymentDue
        ], $layout);
    }

    public function create(): void {
        $customers = Customer::all();
        $items     = LaundryItem::all();
        $workTypes = WorkType::all();
        $pricings  = Pricing::all();

        Router::view('admin/orders/create', [
            'title'     => 'Create New Order - Launder',
            'customers' => $customers,
            'items'     => $items,
            'workTypes' => $workTypes,
            'pricings'  => $pricings
        ], 'admin');
    }

    public function store(): void {
        $customerId          = (int)($_POST['customer_id'] ?? 0);
        $pickupDate          = trim($_POST['pickup_date'] ?? '');
        $deliveryDate        = trim($_POST['delivery_date'] ?? '');
        $itemId              = (int)($_POST['item_id'] ?? 0);
        $workTypeId          = (int)($_POST['work_type_id'] ?? 0);
        $rawQty              = trim($_POST['quantity'] ?? '');
        $userInstructions    = trim($_POST['special_instructions'] ?? '');

        // 1. Customer Validation
        if ($customerId <= 0) {
            $_SESSION['error'] = "Please select a valid customer.";
            Router::redirect('/orders/create');
            return;
        }

        $customer = Customer::find($customerId);
        if (!$customer || (int)$customer['is_active'] !== 1) {
            $_SESSION['error'] = "Selected customer is invalid or inactive.";
            Router::redirect('/orders/create');
            return;
        }

        // 2. Dates Validation
        if (empty($pickupDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
            $_SESSION['error'] = "Please provide a valid pickup date (YYYY-MM-DD).";
            Router::redirect('/orders/create');
            return;
        }

        if (!empty($deliveryDate)) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
                $_SESSION['error'] = "Delivery date must be in YYYY-MM-DD format.";
                Router::redirect('/orders/create');
                return;
            }
            if (strtotime($deliveryDate) < strtotime($pickupDate)) {
                $_SESSION['error'] = "Delivery date cannot be earlier than the pickup date.";
                Router::redirect('/orders/create');
                return;
            }
        }

        // 3. Multi-Item or Single-Item Validation & Normalization
        $validatedLines = [];
        $itemsSubtotal = 0.00;
        $isMulti = !empty($_POST['items']) && is_array($_POST['items']);

        if ($isMulti) {
            try {
                $validated = OrderItem::validateLines($_POST['items']);
                $validatedLines = $validated['lines'];
                $itemsSubtotal  = $validated['subtotal'];
            } catch (\InvalidArgumentException $e) {
                $_SESSION['error'] = $e->getMessage();
                Router::redirect('/orders/create');
                return;
            }
        } else {
            // Single-item fallback
            $itemId     = (int)($_POST['item_id'] ?? 0);
            $workTypeId = (int)($_POST['work_type_id'] ?? 0);
            $rawQty     = trim($_POST['quantity'] ?? '');

            if ($itemId <= 0 || !LaundryItem::find($itemId)) {
                $_SESSION['error'] = "Selected laundry item is invalid or soft-deleted.";
                Router::redirect('/orders/create');
                return;
            }

            if ($workTypeId <= 0 || !WorkType::find($workTypeId)) {
                $_SESSION['error'] = "Selected work type is invalid or soft-deleted.";
                Router::redirect('/orders/create');
                return;
            }

            $pricing = Pricing::find($itemId, $workTypeId);
            if (!$pricing) {
                $_SESSION['error'] = "No valid pricing configuration exists for the selected item and work type combination.";
                Router::redirect('/orders/create');
                return;
            }

            if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
                $_SESSION['error'] = "Quantity or weight must be a valid number greater than zero.";
                Router::redirect('/orders/create');
                return;
            }
            if ((float)$rawQty > 1000) {
                $_SESSION['error'] = "Quantity or weight cannot exceed 1,000.";
                Router::redirect('/orders/create');
                return;
            }

            $pricingType = $pricing['pricing_type'];
            if ($pricingType === 'per_item') {
                if (floor((float)$rawQty) != (float)$rawQty) {
                    $_SESSION['error'] = "Quantity for per-item services must be a whole positive integer.";
                    Router::redirect('/orders/create');
                    return;
                }
                $quantity = (float)floor((float)$rawQty);
            } else {
                $quantity = round((float)$rawQty, 2);
            }

            $itemObj = LaundryItem::find($itemId);
            $workTypeObj = WorkType::find($workTypeId);
            $unitPrice = (float)$pricing['price'];
            $itemsSubtotal = round($unitPrice * $quantity, 2);

            $validatedLines[] = [
                'item_id'        => $itemId,
                'item_name'      => $itemObj['name'],
                'work_type_id'   => $workTypeId,
                'work_type_name' => $workTypeObj['name'],
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'pricing_type'   => $pricingType,
                'subtotal'       => $itemsSubtotal,
                'item_notes'     => null,
                'display_order'  => 1
            ];
        }

        $itemsSubtotal = round($itemsSubtotal, 2);

        // 4. Strict Server-Side Total Amount Calculation
        $settings = Settings::get();
        $addedCharges = Order::calculateDeliveryCharges($itemsSubtotal, $settings);
        $totalAmount = round($itemsSubtotal + $addedCharges, 2);

        // Formatted Item Details Summary
        $lineSummaries = [];
        foreach ($validatedLines as $vl) {
            $label = ($vl['pricing_type'] === 'per_kg') ? 'kg' : 'pc';
            $lineSummaries[] = sprintf("%s (%s) — %s %s @ ₹%.2f/%s = ₹%.2f",
                $vl['item_name'], $vl['work_type_name'], $vl['quantity'], $label, $vl['unit_price'], $label, $vl['subtotal']);
        }
        $breakdownSummary = "Service Breakdown:\n• " . implode("\n• ", $lineSummaries);

        if ($addedCharges > 0) {
            $breakdownSummary .= sprintf("\n(+ ₹%.2f Pickup/Delivery Fee)", $addedCharges);
        }

        $finalInstructions = $breakdownSummary;
        if (!empty($userInstructions)) {
            $finalInstructions .= "\n\nSpecial Instructions: " . $userInstructions;
        }

        // 5. Transactional Order Creation
        try {
            $orderData = [
                'customer_id'          => $customerId,
                'status'               => 'pickup_request',
                'total_amount'         => $totalAmount,
                'amount_paid'          => 0.00,
                'payment_status'       => 'unpaid',
                'pickup_date'          => $pickupDate,
                'delivery_date'        => !empty($deliveryDate) ? $deliveryDate : null,
                'special_instructions' => $finalInstructions,
                'items'                => $validatedLines
            ];

            $staffUserId = (int)($_SESSION['user_id'] ?? 1);
            $orderId = Order::create($orderData, $staffUserId);

            $_SESSION['success'] = "Order created successfully! Total: ₹" . number_format($totalAmount, 2);
            Router::redirect('/orders/show?id=' . $orderId);
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create order: " . $e->getMessage();
            Router::redirect('/orders/create');
        }
    }

    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $order = $id > 0 ? Order::find($id) : null;

        if (!$order) {
            $_SESSION['error'] = "Order not found.";
            Router::redirect('/orders');
            return;
        }

        $orderItems = Order::getItems($id);
        $extracted  = OrderMetadata::extract($order['special_instructions']);
        $history    = Order::getStatusHistory($id);
        $payments   = Payment::getByOrderId($id);
        foreach ($payments as &$p) {
            $p['refundable_amount'] = ($p['payment_method'] === 'online')
                ? Refund::getRefundableAmount((int)$p['id'])
                : 0.00;
        }
        unset($p);
        $refunds       = Refund::getByOrderId($id);
        $totalRefunded = Refund::getTotalRefundedByOrderId($id);
        $items         = LaundryItem::all();
        $workTypes     = WorkType::all();
        $pricings      = Pricing::all();
        $settings      = Settings::get();
        $layout        = (($_SESSION['user_role'] ?? '') === 'staff') ? 'staff' : 'admin';

        Router::view('admin/orders/show', [
            'title'         => 'Order ' . $order['job_id'] . ' - Launder',
            'order'         => $order,
            'orderItems'    => $orderItems,
            'history'       => $history,
            'payments'      => $payments,
            'refunds'       => $refunds,
            'totalRefunded' => $totalRefunded,
            'customerNote'  => $extracted['customer_note'],
            'metadata'      => $extracted['metadata'],
            'items'         => $items,
            'workTypes'     => $workTypes,
            'pricings'      => $pricings,
            'settings'      => $settings
        ], $layout);
    }

    public function updateStatus(): void {
        $orderId   = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRole  = $_SESSION['user_role'] ?? '';

        if ($orderId <= 0 || empty($newStatus)) {
            $_SESSION['error'] = "Invalid status update request.";
            Router::redirect('/orders');
            return;
        }

        try {
            Order::updateStatus($orderId, $newStatus, $userId, $userRole);
            $_SESSION['success'] = "Order status updated successfully to '" . str_replace('_', ' ', $newStatus) . "'.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function claimOrder(): void {
        $userRole = $_SESSION['user_role'] ?? '';
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        if ($userRole !== 'staff') {
            $_SESSION['error'] = "Unauthorized: Only staff members may claim unassigned orders.";
            Router::redirect('/staff/dashboard');
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for claiming.";
            Router::redirect('/staff/dashboard');
            return;
        }

        try {
            Order::claimOrder($orderId, $userId);
            $_SESSION['success'] = "Order #{$orderId} claimed successfully!";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? '/staff/dashboard');
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/staff/dashboard';
        }
        Router::redirect($redirect);
    }

    public function recordPayment(): void {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $userId  = (int)($_SESSION['user_id'] ?? 0);

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for payment.";
            Router::redirect('/orders');
            return;
        }

        try {
            $res = Payment::create($_POST, $userId);
            $_SESSION['success'] = "Payment of ₹" . number_format((float)($_POST['amount'] ?? 0), 2) . " recorded successfully!";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function processRefund(): void {
        $orderId   = (int)($_POST['order_id'] ?? 0);
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRole  = (string)($_SESSION['user_role'] ?? '');

        if (!in_array($userRole, ['admin', 'staff'], true) || $userId <= 0) {
            $_SESSION['error'] = "Unauthorized access to refund processing.";
            Router::redirect('/orders');
            return;
        }

        if ($orderId <= 0 || $paymentId <= 0) {
            $_SESSION['error'] = "Invalid order or payment specified for refund.";
            Router::redirect('/orders');
            return;
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }

        try {
            // Phase 1: Atomically validate, reserve balance, and create/retrieve PENDING refund intent
            $intentResult = Refund::createIntent($_POST, $userId);
            $refund       = $intentResult['refund'];
            $isReplay     = $intentResult['already_exists'];

            if ($isReplay && $refund['status'] === 'processed') {
                $_SESSION['info'] = "This refund has already been successfully processed.";
                Router::redirect($redirect);
                return;
            }

            if ($isReplay && $refund['status'] === 'failed') {
                $_SESSION['error'] = "A previous refund attempt with this request key failed. Please submit a new refund request.";
                Router::redirect($redirect);
                return;
            }

            $refundId         = (int)$refund['id'];
            $gatewayDriver    = $refund['gateway'] ?: 'mock';
            $gatewayPaymentId = (string)$refund['gateway_payment_id'];
            $amount           = (float)$refund['amount'];

            // Phase 2: Call payment gateway WITHOUT any database locks
            $gateway = PaymentGatewayFactory::create($gatewayDriver);
            $res = $gateway->createRefund($gatewayPaymentId, $amount, [
                'refund_id'       => $refundId,
                'order_id'        => $orderId,
                'payment_id'      => $paymentId,
                'reason'          => (string)$refund['reason'],
                'idempotency_key' => (string)$refund['idempotency_key'],
                'mock_status'     => $_POST['mock_status'] ?? null
            ]);

            // Phase 3: Finalize settlement state
            if ($res['status'] === 'processed' || ($res['success'] && !empty($res['gateway_refund_id']) && $res['status'] !== 'pending')) {
                Refund::settleSuccess($refundId, (string)$res['gateway_refund_id']);
                $_SESSION['success'] = "Refund of ₹" . number_format($amount, 2) . " processed successfully! (Ref: " . htmlspecialchars((string)$res['gateway_refund_id']) . ")";
            } elseif ($res['status'] === 'pending') {
                $_SESSION['info'] = "Refund of ₹" . number_format($amount, 2) . " submitted and is pending gateway confirmation.";
            } else {
                Refund::settleFailure($refundId, $res['error'] ?? 'Gateway rejected refund.');
                $_SESSION['error'] = "Gateway refund failed: " . ($res['error'] ?? 'Unknown gateway rejection.');
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Refund error: " . $e->getMessage();
        }

        Router::redirect($redirect);
    }

    public function submitInspection(): void {
        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $userRole = (string)($_SESSION['user_role'] ?? '');
        $orderId  = (int)($_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for inspection.";
            Router::redirect('/orders');
            return;
        }

        try {
            Order::submitInspection($orderId, $_POST, $userId, $userRole);
            $_SESSION['success'] = "Inspection submitted and final price calculated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function overridePriceApproval(): void {
        $userRole = (string)($_SESSION['user_role'] ?? '');
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        if ($userRole !== 'admin') {
            $_SESSION['error'] = "Unauthorized: Only administrators may execute emergency price approval overrides.";
            Router::redirect('/orders');
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason  = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified.";
            Router::redirect('/orders');
            return;
        }

        try {
            Order::overridePriceApprovalByAdmin($orderId, $userId, $reason);
            $_SESSION['success'] = "Emergency price approval override executed successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function cancel(): void {
        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $userRole = (string)($_SESSION['user_role'] ?? '');
        $orderId  = (int)($_POST['order_id'] ?? 0);
        $reason   = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for cancellation.";
            Router::redirect('/orders');
            return;
        }

        try {
            Order::cancel($orderId, $userId, $userRole, $reason);
            $_SESSION['success'] = "Order #{$orderId} cancelled successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function reschedule(): void {
        $userId       = (int)($_SESSION['user_id'] ?? 0);
        $userRole     = (string)($_SESSION['user_role'] ?? '');
        $orderId      = (int)($_POST['order_id'] ?? 0);
        $pickupDate   = !empty($_POST['pickup_date']) ? trim((string)$_POST['pickup_date']) : null;
        $deliveryDate = !empty($_POST['delivery_date']) ? trim((string)$_POST['delivery_date']) : null;
        $reason       = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for rescheduling.";
            Router::redirect('/orders');
            return;
        }

        try {
            Order::reschedule($orderId, $userId, $userRole, $pickupDate, $deliveryDate, $reason);
            $_SESSION['success'] = "Schedule for order #{$orderId} updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $redirect = trim($_POST['redirect_to'] ?? ('/orders/show?id=' . $orderId));
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/orders/show?id=' . $orderId;
        }
        Router::redirect($redirect);
    }

    public function printJobCard(): void {
        $id = (int)($_GET['id'] ?? 0);
        $order = $id > 0 ? Order::find($id) : null;

        if (!$order) {
            http_response_code(404);
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-width: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>404 Not Found</h1>" .
                 "<p style='color: #475569;'>Order not found or invalid order ID.</p>" .
                 "<p><a href='/laundry-lms/orders' style='color: #2563eb; text-decoration: underline;'>Return to Orders Directory</a></p>" .
                 "</div>";
            exit;
        }

        $orderItems = Order::getItems($id);
        $extracted  = OrderMetadata::extract($order['special_instructions']);
        $history    = Order::getStatusHistory($id);
        $payments      = Payment::getByOrderId($id);
        $refunds       = Refund::getByOrderId($id);
        $totalRefunded = Refund::getTotalRefundedByOrderId($id);
        $settings      = Settings::get();

        $rawPaper = (string)($_GET['paper'] ?? 'a4');
        $paper    = in_array($rawPaper, ['58', '80'], true) ? $rawPaper : 'a4';

        Router::view('orders/print', [
            'title'         => 'Job Card - ' . $order['job_id'],
            'mode'          => 'job_card',
            'paper'         => $paper,
            'order'         => $order,
            'orderItems'    => $orderItems,
            'history'       => $history,
            'payments'      => $payments,
            'refunds'       => $refunds,
            'totalRefunded' => $totalRefunded,
            'settings'      => $settings,
            'customerNote'  => $extracted['customer_note'],
            'metadata'      => $extracted['metadata'],
            'backUrl'       => '/laundry-lms/orders/show?id=' . $order['id']
        ], 'print');
    }

    /**
     * Generate and stream A4 PDF operational job card/invoice for staff & admin.
     */
    public function downloadPdf(): void {
        $id = (int)($_GET['id'] ?? 0);
        $order = $id > 0 ? Order::find($id) : null;

        if (!$order) {
            http_response_code(404);
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-width: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>404 Not Found</h1>" .
                 "<p style='color: #475569;'>Order not found or invalid order ID.</p>" .
                 "<p><a href='/laundry-lms/orders' style='color: #2563eb; text-decoration: underline;'>Return to Orders Directory</a></p>" .
                 "</div>";
            exit;
        }

        $orderItems    = Order::getItems($id);
        $extracted     = OrderMetadata::extract($order['special_instructions']);
        $history       = Order::getStatusHistory($id);
        $payments      = Payment::getByOrderId($id);
        $refunds       = Refund::getByOrderId($id);
        $totalRefunded = Refund::getTotalRefundedByOrderId($id);
        $settings      = Settings::get();

        $download = (isset($_GET['download']) && (string)$_GET['download'] === '1');

        ob_start();
        $mode         = 'job_card';
        $customerNote = $extracted['customer_note'];
        $metadata     = $extracted['metadata'];
        require __DIR__ . '/../../views/pages/orders/invoice_pdf.php';
        $html = (string)ob_get_clean();

        InvoicePdfService::stream($html, (string)($order['job_id'] ?? 'Order'), $download);
    }
}
