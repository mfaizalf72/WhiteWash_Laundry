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
use Faizal\LaundryLms\Models\OrderItem;
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\OrderMetadata;
use Faizal\LaundryLms\Services\Payment\PaymentGatewayFactory;

class CustomerPortalController {

    public function dashboard(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer = Customer::find($customerId);

        if (!$customer) {
            $_SESSION['error'] = "Customer profile not found.";
            Router::redirect('/logout');
            return;
        }

        $kpis = Order::getCustomerKpis($customerId);
        $recentOrders = Order::getCustomerRecentOrders($customerId, 5);

        Router::view('customer/dashboard', [
            'title'        => 'Customer Dashboard - Launder',
            'customer'     => $customer,
            'kpis'         => $kpis,
            'recentOrders' => $recentOrders
        ], 'customer');
    }

    public function orders(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $filter = trim($_GET['filter'] ?? 'all');
        if (!in_array($filter, ['all', 'active', 'completed', 'pending'], true)) {
            $filter = 'all';
        }

        $orders = Order::getCustomerOrders($customerId, $filter);

        Router::view('customer/orders/index', [
            'title'  => 'My Orders - Launder',
            'orders' => $orders,
            'filter' => $filter
        ], 'customer');
    }

    public function showOrder(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $orderId = (int)($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(403);
            $_SESSION['error'] = "Invalid order ID specified.";
            Router::redirect('/customer/orders');
            return;
        }

        // Strict Server-Side Ownership Check in Database Query
        $order = Order::findForCustomer($orderId, $customerId);

        if (!$order) {
            http_response_code(403);
            $_SESSION['error'] = "Access denied: Order not found or unauthorized access.";
            Router::redirect('/customer/orders');
            return;
        }

        $orderItems = Order::getItems($orderId);
        $extracted  = OrderMetadata::extract($order['special_instructions']);
        $history    = Order::getStatusHistory($orderId);
        $payments   = Payment::getByOrderId($orderId);

        Router::view('customer/orders/show', [
            'title'         => 'Order ' . $order['job_id'] . ' - Launder',
            'order'         => $order,
            'orderItems'    => $orderItems,
            'history'       => $history,
            'payments'      => $payments,
            'customerNote'  => $extracted['customer_note'],
            'metadata'      => $extracted['metadata'],
            'gatewayDriver' => (getenv('PAYMENT_GATEWAY_DRIVER') ?: 'mock')
        ], 'customer');
    }

    public function createOrderForm(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer   = Customer::find($customerId);
        $items      = LaundryItem::all();
        $workTypes  = WorkType::all();
        $pricings   = Pricing::all();
        $settings   = Settings::get();

        Router::view('customer/orders/create', [
            'title'     => 'Request Pickup - Launder',
            'customer'  => $customer,
            'items'     => $items,
            'workTypes' => $workTypes,
            'pricings'  => $pricings,
            'settings'  => $settings
        ], 'customer');
    }

    public function storeOrder(): void {
        $customerId       = (int)($_SESSION['user_id'] ?? 0);
        $pickupDate       = trim($_POST['pickup_date'] ?? '');
        $deliveryDate     = trim($_POST['delivery_date'] ?? '');
        $userInstructions = trim($_POST['special_instructions'] ?? '');

        // 1. Dates Validation
        if (empty($pickupDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
            $_SESSION['error'] = "Please provide a valid pickup date (YYYY-MM-DD).";
            Router::redirect('/customer/orders/create');
            return;
        }

        if (!empty($deliveryDate)) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
                $_SESSION['error'] = "Delivery date must be in YYYY-MM-DD format.";
                Router::redirect('/customer/orders/create');
                return;
            }
            if (strtotime($deliveryDate) < strtotime($pickupDate)) {
                $_SESSION['error'] = "Delivery date cannot be earlier than pickup date.";
                Router::redirect('/customer/orders/create');
                return;
            }
        }

        // 2. Multi-Item or Single-Item Validation & Normalization
        $validatedLines = [];
        $itemsSubtotal = 0.00;
        $isMulti = !empty($_POST['items']) && is_array($_POST['items']);

        if ($isMulti) {
            try {
                $validation = OrderItem::validateLines($_POST['items']);
                $validatedLines = $validation['lines'];
                $itemsSubtotal  = $validation['subtotal'];
            } catch (\InvalidArgumentException $e) {
                $_SESSION['error'] = $e->getMessage();
                Router::redirect('/customer/orders/create');
                return;
            }
        } else {
            // Single-item fallback
            $itemId     = (int)($_POST['item_id'] ?? 0);
            $workTypeId = (int)($_POST['work_type_id'] ?? 0);
            $rawQty     = trim($_POST['quantity'] ?? '');

            if ($itemId <= 0 || !LaundryItem::find($itemId)) {
                $_SESSION['error'] = "Selected laundry item is invalid.";
                Router::redirect('/customer/orders/create');
                return;
            }

            if ($workTypeId <= 0 || !WorkType::find($workTypeId)) {
                $_SESSION['error'] = "Selected work type is invalid.";
                Router::redirect('/customer/orders/create');
                return;
            }

            $pricing = Pricing::find($itemId, $workTypeId);
            if (!$pricing) {
                $_SESSION['error'] = "No valid pricing configuration exists for the selected item and work type.";
                Router::redirect('/customer/orders/create');
                return;
            }

            if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
                $_SESSION['error'] = "Quantity or weight must be a valid number greater than zero.";
                Router::redirect('/customer/orders/create');
                return;
            }
            if ((float)$rawQty > 1000) {
                $_SESSION['error'] = "Quantity or weight cannot exceed 1,000.";
                Router::redirect('/customer/orders/create');
                return;
            }

            $pricingType = $pricing['pricing_type'];
            if ($pricingType === 'per_item') {
                if (floor((float)$rawQty) != (float)$rawQty) {
                    $_SESSION['error'] = "Quantity for per-item services must be a whole positive integer.";
                    Router::redirect('/customer/orders/create');
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

        // 3. Strict Server-Side Total Amount Calculation
        $settings     = Settings::get();
        $addedCharges = Order::calculateDeliveryCharges($itemsSubtotal, $settings);
        $totalAmount  = round($itemsSubtotal + $addedCharges, 2);

        // Build service breakdown summary
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

        // 4. Transactional Order Creation
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

            $orderId = Order::create($orderData, null);

            $_SESSION['success'] = "Pickup requested successfully! Order #" . $orderId;
            Router::redirect('/customer/orders/show?id=' . $orderId);
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to request pickup: " . $e->getMessage();
            Router::redirect('/customer/orders/create');
        }
    }

    public function profile(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer   = Customer::find($customerId);

        if (!$customer) {
            $_SESSION['error'] = "Profile not found.";
            Router::redirect('/customer/dashboard');
            return;
        }

        Router::view('customer/profile', [
            'title'    => 'My Profile & Security - Launder',
            'customer' => $customer
        ], 'customer');
    }

    public function updateProfile(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $rawMobile  = $_POST['mobile'] ?? '';
        $mobile     = Security::sanitizeMobile($rawMobile);
        $address    = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $_SESSION['error'] = "Name cannot be empty.";
            Router::redirect('/customer/profile');
            return;
        }

        if (empty($mobile) || strlen($mobile) !== 10 || !ctype_digit($mobile)) {
            $_SESSION['error'] = "Mobile number must be a valid 10-digit number.";
            Router::redirect('/customer/profile');
            return;
        }

        // Check mobile uniqueness for other accounts
        $existing = Customer::findByMobile($mobile, $customerId);
        if ($existing) {
            $_SESSION['error'] = "Mobile number is already registered to another user account.";
            Router::redirect('/customer/profile');
            return;
        }

        try {
            Customer::updateProfile($customerId, [
                'name'    => $name,
                'mobile'  => $mobile,
                'address' => $address
            ]);

            $_SESSION['user_name'] = $name;
            $_SESSION['success'] = "Profile details updated successfully!";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update profile: " . $e->getMessage();
        }

        Router::redirect('/customer/profile');
    }

    public function changePin(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer   = Customer::find($customerId);

        $currentPin = trim($_POST['current_pin'] ?? '');
        $newPin     = trim($_POST['new_pin'] ?? '');
        $confirmPin = trim($_POST['confirm_pin'] ?? '');

        if (!$customer || empty($customer['pin_hash'])) {
            $_SESSION['error'] = "User account not found.";
            Router::redirect('/customer/profile');
            return;
        }

        // Verify current PIN
        if (empty($currentPin) || !password_verify($currentPin, $customer['pin_hash'])) {
            $_SESSION['error'] = "Current PIN is incorrect.";
            Router::redirect('/customer/profile');
            return;
        }

        // Validate new PIN
        if (strlen($newPin) !== 4 || !ctype_digit($newPin)) {
            $_SESSION['error'] = "New PIN must be a 4-digit number.";
            Router::redirect('/customer/profile');
            return;
        }

        if ($newPin !== $confirmPin) {
            $_SESSION['error'] = "New PIN and confirmation PIN do not match.";
            Router::redirect('/customer/profile');
            return;
        }

        try {
            Customer::updatePin($customerId, $newPin);
            unset($_SESSION['requires_pin_change']);
            $_SESSION['success'] = "Your PIN has been updated successfully!";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update PIN: " . $e->getMessage();
        }

        Router::redirect('/customer/profile');
    }

    public function forcePinUpdateForm(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer   = Customer::find($customerId);

        if (!$customer) {
            $_SESSION['error'] = "Account not found.";
            Router::redirect('/logout');
            return;
        }

        // If customer does not require pin change, proceed to dashboard
        if (empty($_SESSION['requires_pin_change'])) {
            Router::redirect('/customer/dashboard');
            return;
        }

        Router::view('customer/force_pin_update', [
            'title'    => 'Change Default PIN - Launder',
            'customer' => $customer
        ], 'customer');
    }

    public function forcePinUpdate(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $customer   = Customer::find($customerId);

        if (!$customer) {
            $_SESSION['error'] = "Account not found.";
            Router::redirect('/logout');
            return;
        }

        $newPin     = trim($_POST['new_pin'] ?? '');
        $confirmPin = trim($_POST['confirm_pin'] ?? '');

        if (strlen($newPin) !== 4 || !ctype_digit($newPin)) {
            $_SESSION['error'] = "New PIN must be a 4-digit number.";
            Router::redirect('/customer/force-pin-update');
            return;
        }

        if ($newPin === '1234') {
            $_SESSION['error'] = "You cannot use the default PIN (1234). Please choose a different 4-digit PIN.";
            Router::redirect('/customer/force-pin-update');
            return;
        }

        if ($newPin !== $confirmPin) {
            $_SESSION['error'] = "New PIN and confirmation PIN do not match.";
            Router::redirect('/customer/force-pin-update');
            return;
        }

        try {
            Customer::updatePin($customerId, $newPin);
            unset($_SESSION['requires_pin_change']);
            $_SESSION['success'] = "PIN updated successfully! You can now access your dashboard.";
            Router::redirect('/customer/dashboard');
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update PIN: " . $e->getMessage();
            Router::redirect('/customer/force-pin-update');
        }
    }

    public function approvePrice(): void {
        $customerId       = (int)($_SESSION['user_id'] ?? 0);
        $orderId          = (int)($_POST['order_id'] ?? 0);
        $expectedRevision = trim((string)($_POST['expected_revision'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for price approval.";
            Router::redirect('/customer/orders');
            return;
        }

        try {
            Order::confirmPriceForCustomer($orderId, $customerId, $expectedRevision);
            $_SESSION['success'] = "Order price approved successfully! Your order is now confirmed for processing.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        Router::redirect('/customer/orders/show?id=' . $orderId);
    }

    public function cancelOrder(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $orderId    = (int)($_POST['order_id'] ?? 0);
        $reason     = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for cancellation.";
            Router::redirect('/customer/orders');
            return;
        }

        try {
            Order::cancel($orderId, $customerId, 'customer', $reason);
            $_SESSION['success'] = "Order #{$orderId} has been cancelled successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        Router::redirect('/customer/orders/show?id=' . $orderId);
    }

    public function rejectPrice(): void {
        $customerId       = (int)($_SESSION['user_id'] ?? 0);
        $orderId          = (int)($_POST['order_id'] ?? 0);
        $expectedRevision = trim((string)($_POST['expected_revision'] ?? ''));
        $reason           = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for price rejection.";
            Router::redirect('/customer/orders');
            return;
        }

        try {
            Order::rejectInspectedPrice($orderId, $customerId, $expectedRevision, $reason);
            $_SESSION['success'] = "Inspected price rejected. Order #{$orderId} has been cancelled.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        Router::redirect('/customer/orders/show?id=' . $orderId);
    }

    public function reschedule(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $orderId    = (int)($_POST['order_id'] ?? 0);
        $pickupDate = !empty($_POST['pickup_date']) ? trim((string)$_POST['pickup_date']) : null;
        $reason     = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['error'] = "Invalid order specified for rescheduling.";
            Router::redirect('/customer/orders');
            return;
        }

        try {
            Order::reschedule($orderId, $customerId, 'customer', $pickupDate, null, $reason);
            $_SESSION['success'] = "Pickup date for order #{$orderId} has been rescheduled successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        Router::redirect('/customer/orders/show?id=' . $orderId);
    }

    public function printReceipt(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $orderId    = (int)($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(403);
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-width: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>403 Forbidden</h1>" .
                 "<p style='color: #475569;'>Invalid order ID specified.</p>" .
                 "<p><a href='/laundry-lms/customer/orders' style='color: #2563eb; text-decoration: underline;'>Return to My Orders</a></p>" .
                 "</div>";
            exit;
        }

        // Strict Server-Side Customer Ownership Check via Database
        $order = Order::findForCustomer($orderId, $customerId);

        if (!$order) {
            http_response_code(403);
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-width: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>403 Forbidden</h1>" .
                 "<p style='color: #475569;'>Access denied: You are not authorized to view or print this receipt.</p>" .
                 "<p><a href='/laundry-lms/customer/orders' style='color: #2563eb; text-decoration: underline;'>Return to My Orders</a></p>" .
                 "</div>";
            exit;
        }

        $orderItems = Order::getItems($orderId);
        $extracted  = OrderMetadata::extract($order['special_instructions']);
        $history    = Order::getStatusHistory($orderId);
        $payments   = Payment::getByOrderId($orderId);
        $settings   = Settings::get();

        $rawPaper = (string)($_GET['paper'] ?? 'a4');
        $paper    = in_array($rawPaper, ['58', '80'], true) ? $rawPaper : 'a4';

        Router::view('orders/print', [
            'title'        => 'Receipt - ' . $order['job_id'],
            'mode'         => 'receipt',
            'paper'        => $paper,
            'order'        => $order,
            'orderItems'   => $orderItems,
            'history'      => $history,
            'payments'     => $payments,
            'settings'     => $settings,
            'customerNote' => $extracted['customer_note'],
            'metadata'     => $extracted['metadata'],
            'backUrl'      => '/laundry-lms/customer/orders/show?id=' . $order['id']
        ], 'print');
    }

    public function initiateOnlinePayment(): void {
        $customerId = (int)($_SESSION['user_id'] ?? 0);
        $orderId    = (int)($_POST['order_id'] ?? 0);

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
               || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
               || !empty($_POST['is_ajax']);

        if ($orderId <= 0) {
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid order ID specified.']);
                exit;
            }
            $_SESSION['error'] = "Invalid order ID specified.";
            Router::redirect('/customer/orders');
            return;
        }

        // Strict Server-Side Customer Ownership Check via Database
        $order = Order::findForCustomer($orderId, $customerId);
        if (!$order) {
            if ($isJson) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied: You do not own this order.']);
                exit;
            }
            $_SESSION['error'] = "Access denied: You do not own this order.";
            Router::redirect('/customer/orders');
            return;
        }

        if ($order['status'] === 'cancelled') {
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment cannot be initiated for a cancelled order.']);
                exit;
            }
            $_SESSION['error'] = "Payment cannot be initiated for a cancelled order.";
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        $totalAmount  = round((float)$order['total_amount'], 2);
        $amountPaid   = round((float)$order['amount_paid'], 2);
        $outstanding  = round($totalAmount - $amountPaid, 2);

        if ($outstanding <= 0.001) {
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'This order is already fully paid.']);
                exit;
            }
            $_SESSION['error'] = "This order is already fully paid.";
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        // Validate monetary amount requested
        $rawAmount = trim((string)($_POST['amount'] ?? $outstanding));
        if (!is_numeric($rawAmount) || (float)$rawAmount < 1.00) {
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Minimum online payment amount is ₹1.00.']);
                exit;
            }
            $_SESSION['error'] = "Minimum online payment amount is ₹1.00.";
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        $amountToPay = round((float)$rawAmount, 2);
        if ($amountToPay > $outstanding + 0.001) {
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Payment amount (₹" . number_format($amountToPay, 2) . ") exceeds outstanding balance (₹" . number_format($outstanding, 2) . ")."]);
                exit;
            }
            $_SESSION['error'] = "Payment amount exceeds outstanding balance.";
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        try {
            $gateway = PaymentGatewayFactory::create();
            $gatewayOrder = $gateway->createOrder($orderId, $amountToPay, 'INR', [
                'order_id'    => (string)$orderId,
                'customer_id' => (string)$customerId
            ]);

            // Save pending session context
            if (!isset($_SESSION['pending_online_payment'])) {
                $_SESSION['pending_online_payment'] = [];
            }
            $_SESSION['pending_online_payment'][$orderId] = [
                'gateway'          => $gateway->getDriverName(),
                'gateway_order_id' => $gatewayOrder['gateway_order_id'],
                'amount'           => $amountToPay,
                'order_id'         => $orderId,
                'expires_at'       => time() + 1800 // 30-minute TTL (SEC-P2-02)
            ];

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'            => true,
                    'driver'             => $gateway->getDriverName(),
                    'gateway_order_id'   => $gatewayOrder['gateway_order_id'],
                    'gateway_payment_id' => $gatewayOrder['gateway_payment_id'] ?? null,
                    'gateway_signature'  => $gatewayOrder['gateway_signature'] ?? null,
                    'amount'             => $amountToPay,
                    'currency'           => $gatewayOrder['currency'] ?? 'INR',
                    'key_id'             => $gateway->getPublicKey(),
                    'order_id'           => $orderId
                ]);
                exit;
            }

            Router::redirect('/customer/orders/show?id=' . $orderId);
        } catch (\Throwable $e) {
            if ($isJson) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = "Payment gateway error: " . $e->getMessage();
            Router::redirect('/customer/orders/show?id=' . $orderId);
        }
    }

    public function verifyOnlinePayment(): void {
        $customerId       = (int)($_SESSION['user_id'] ?? 0);
        $orderId          = (int)($_POST['order_id'] ?? 0);
        $gatewayOrderId   = trim((string)($_POST['gateway_order_id'] ?? ''));
        $gatewayPaymentId = trim((string)($_POST['gateway_payment_id'] ?? ''));
        $gatewaySignature = trim((string)($_POST['gateway_signature'] ?? ''));
        $mockStatus       = trim((string)($_POST['mock_status'] ?? ''));

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
               || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
               || !empty($_POST['is_ajax']);

        if ($orderId <= 0 || empty($gatewayOrderId) || empty($gatewayPaymentId)) {
            $msg = "Incomplete payment verification payload.";
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            Router::redirect('/customer/orders');
            return;
        }

        // Strict Server-Side Customer Ownership Check via Database
        $order = Order::findForCustomer($orderId, $customerId);
        if (!$order) {
            $msg = "Access denied: You do not own this order.";
            if ($isJson) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            Router::redirect('/customer/orders');
            return;
        }

        if ($order['status'] === 'cancelled') {
            $msg = "Payment verification rejected: Order is cancelled.";
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        // Cross-check pending session context
        $pending = $_SESSION['pending_online_payment'][$orderId] ?? null;
        if (!$pending || $pending['gateway_order_id'] !== $gatewayOrderId) {
            $msg = "Invalid or expired payment session context.";
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        // Check explicit 30-minute session TTL (SEC-P2-02)
        if (!isset($pending['expires_at']) || (int)$pending['expires_at'] < time()) {
            unset($_SESSION['pending_online_payment'][$orderId]);
            $msg = "Payment session has expired. Please initiate a new payment.";
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            Router::redirect('/customer/orders/show?id=' . $orderId);
            return;
        }

        // Amount must be the server-calculated pending amount, NOT a browser-supplied amount
        $amount = (float)$pending['amount'];
        $driver = (string)$pending['gateway'];

        try {
            $gateway = PaymentGatewayFactory::create($driver);
            $isValid = $gateway->verifySignature([
                'gateway_order_id'   => $gatewayOrderId,
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_signature'  => $gatewaySignature,
                'mock_status'        => $mockStatus
            ]);

            if (!$isValid) {
                throw new \DomainException("Payment signature verification failed. Untrusted or tampered transaction.");
            }

            // Record payment through standard financial logic
            $paymentResult = Payment::create([
                'order_id'           => $orderId,
                'payment_method'     => 'online',
                'amount'             => $amount,
                'gateway'            => $driver,
                'gateway_order_id'   => $gatewayOrderId,
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_signature'  => $gatewaySignature
            ], $customerId);

            // Clear session pending context
            unset($_SESSION['pending_online_payment'][$orderId]);

            $successMsg = "Online payment of ₹" . number_format($amount, 2) . " processed successfully!";
            $_SESSION['success'] = $successMsg;

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'      => true,
                    'message'      => $successMsg,
                    'redirect_url' => '/laundry-lms/customer/orders/show?id=' . $orderId
                ]);
                exit;
            }

            Router::redirect('/customer/orders/show?id=' . $orderId);
        } catch (\Throwable $e) {
            $errMsg = "Payment verification error: " . $e->getMessage();
            if ($isJson) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $errMsg]);
                exit;
            }
            $_SESSION['error'] = $errMsg;
            Router::redirect('/customer/orders/show?id=' . $orderId);
        }
    }
}
