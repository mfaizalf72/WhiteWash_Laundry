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
use Faizal\LaundryLms\Helpers\OrderMetadata;

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
            'currentPage'  => $paginatedData['page'],
            'totalPages'   => $paginatedData['total_pages'],
            'currentFilter'=> $status,
            'searchTerm'   => $search,
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

        // 3. Item & Work Type Validation
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

        // 4. Pricing Matrix Validation
        $pricing = Pricing::find($itemId, $workTypeId);
        if (!$pricing) {
            $_SESSION['error'] = "No valid pricing configuration exists for the selected item and work type combination.";
            Router::redirect('/orders/create');
            return;
        }

        // 5. Quantity / Weight Validation
        if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
            $_SESSION['error'] = "Quantity or weight must be a valid number greater than zero.";
            Router::redirect('/orders/create');
            return;
        }
        $quantity = round((float)$rawQty, 2);

        // 6. Strict Server-Side Total Amount Calculation
        $unitPrice   = (float)$pricing['price'];
        $pricingType = $pricing['pricing_type'];
        $itemSubtotal = round($unitPrice * $quantity, 2);

        $settings = Settings::get();
        $pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
        $deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
        $freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);

        $addedCharges = 0.00;
        if ($freePickupLimit <= 0 || $itemSubtotal < $freePickupLimit) {
            $addedCharges = $pickupCharge + $deliveryCharge;
        }

        $totalAmount = round($itemSubtotal + $addedCharges, 2);

        // Formatted Item Details Summary
        $itemObj = LaundryItem::find($itemId);
        $wtObj   = WorkType::find($workTypeId);
        $typeLabel = ($pricingType === 'per_kg') ? 'KG' : 'Item(s)';

        $breakdownSummary = sprintf(
            "Service Breakdown: %s (%s) — %s %s @ ₹%.2f/%s = ₹%.2f",
            $itemObj['name'],
            $wtObj['name'],
            $quantity,
            $typeLabel,
            $unitPrice,
            $pricingType === 'per_kg' ? 'kg' : 'pc',
            $itemSubtotal
        );

        if ($addedCharges > 0) {
            $breakdownSummary .= sprintf(" (+ ₹%.2f Pickup/Delivery Fee)", $addedCharges);
        }

        $finalInstructions = $breakdownSummary;
        if (!empty($userInstructions)) {
            $finalInstructions .= "\n\nSpecial Instructions: " . $userInstructions;
        }

        // 7. Transactional Order Creation
        try {
            $orderData = [
                'customer_id'          => $customerId,
                'status'               => 'pickup_request',
                'total_amount'         => $totalAmount,
                'amount_paid'          => 0.00,
                'payment_status'       => 'unpaid',
                'pickup_date'          => $pickupDate,
                'delivery_date'        => !empty($deliveryDate) ? $deliveryDate : null,
                'special_instructions' => $finalInstructions
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

        $extracted = OrderMetadata::extract($order['special_instructions']);
        $history   = Order::getStatusHistory($id);
        $payments  = Payment::getByOrderId($id);
        $items     = LaundryItem::all();
        $workTypes = WorkType::all();
        $pricings  = Pricing::all();
        $settings  = Settings::get();
        $layout    = (($_SESSION['user_role'] ?? '') === 'staff') ? 'staff' : 'admin';

        Router::view('admin/orders/show', [
            'title'        => 'Order ' . $order['job_id'] . ' - Launder',
            'order'        => $order,
            'history'      => $history,
            'payments'     => $payments,
            'customerNote' => $extracted['customer_note'],
            'metadata'     => $extracted['metadata'],
            'items'        => $items,
            'workTypes'    => $workTypes,
            'pricings'     => $pricings,
            'settings'     => $settings
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
}
