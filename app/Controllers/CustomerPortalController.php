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
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\OrderMetadata;

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

        $extracted = OrderMetadata::extract($order['special_instructions']);
        $history   = Order::getStatusHistory($orderId);
        $payments  = Payment::getByOrderId($orderId);

        Router::view('customer/orders/show', [
            'title'        => 'Order ' . $order['job_id'] . ' - Launder',
            'order'        => $order,
            'history'      => $history,
            'payments'     => $payments,
            'customerNote' => $extracted['customer_note'],
            'metadata'     => $extracted['metadata']
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
        $itemId           = (int)($_POST['item_id'] ?? 0);
        $workTypeId       = (int)($_POST['work_type_id'] ?? 0);
        $rawQty           = trim($_POST['quantity'] ?? '');
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

        // 2. Item & Work Type Validation
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

        // 3. Pricing Matrix Validation
        $pricing = Pricing::find($itemId, $workTypeId);
        if (!$pricing) {
            $_SESSION['error'] = "No valid pricing configuration exists for the selected item and work type.";
            Router::redirect('/customer/orders/create');
            return;
        }

        // 4. Quantity / Weight Validation
        if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
            $_SESSION['error'] = "Quantity or weight must be a valid number greater than zero.";
            Router::redirect('/customer/orders/create');
            return;
        }
        $quantity = round((float)$rawQty, 2);

        // 5. Server-side total calculation
        $unitPrice    = (float)$pricing['price'];
        $pricingType  = $pricing['pricing_type'];
        $itemSubtotal = round($unitPrice * $quantity, 2);

        $settings        = Settings::get();
        $pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
        $deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
        $freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);

        $addedCharges = 0.00;
        if ($freePickupLimit <= 0 || $itemSubtotal < $freePickupLimit) {
            $addedCharges = $pickupCharge + $deliveryCharge;
        }

        $totalAmount = round($itemSubtotal + $addedCharges, 2);

        // Build service breakdown summary
        $itemObj   = LaundryItem::find($itemId);
        $wtObj     = WorkType::find($workTypeId);
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

        // 6. Transactional Order Creation
        try {
            $orderData = [
                'customer_id'          => $customerId, // Strictly authenticated customer session ID
                'status'               => 'pickup_request', // Strictly pickup_request initial state
                'total_amount'         => $totalAmount, // Strictly server-calculated total
                'amount_paid'          => 0.00,
                'payment_status'       => 'unpaid',
                'pickup_date'          => $pickupDate,
                'delivery_date'        => !empty($deliveryDate) ? $deliveryDate : null,
                'special_instructions' => $finalInstructions
            ];

            $orderId = Order::create($orderData, null);

            $_SESSION['success'] = "Pickup request submitted successfully! Total: ₹" . number_format($totalAmount, 2);
            Router::redirect('/customer/orders/show?id=' . $orderId);
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create pickup request: " . $e->getMessage();
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
}
