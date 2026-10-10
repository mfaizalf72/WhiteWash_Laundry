<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Models\Customer;
use Faizal\LaundryLms\Models\LoginAttempt;
use Faizal\LaundryLms\Services\Notification\NotificationService;
use Faizal\LaundryLms\Helpers\Security;

class CustomerController {
    public function index(): void {
        $search = trim($_GET['search'] ?? '');
        $customers = Customer::all($search);

        Router::view('admin/customers/index', [
            'title'     => 'Customers - Launder',
            'customers' => $customers,
            'search'    => $search
        ], 'admin');
    }

    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $customer = $id > 0 ? Customer::find($id) : null;

        if (!$customer) {
            $_SESSION['error'] = "Customer not found.";
            Router::redirect('/customers');
            return;
        }

        Router::view('admin/customers/show', [
            'title'    => 'Customer Details - Launder',
            'customer' => $customer
        ], 'admin');
    }

    public function store(): void {
        $name    = trim($_POST['name'] ?? '');
        $mobile  = Security::sanitizeMobile($_POST['mobile'] ?? '');
        $pin     = trim($_POST['pin'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name) || empty($mobile)) {
            $_SESSION['error'] = "Customer name and mobile number are required.";
            Router::redirect('/customers');
            return;
        }

        if (strlen($mobile) < 10 || strlen($mobile) > 15) {
            $_SESSION['error'] = "Please enter a valid mobile number (10 to 15 digits).";
            Router::redirect('/customers');
            return;
        }

        if (!empty($pin) && (strlen($pin) !== 4 || !ctype_digit($pin))) {
            $_SESSION['error'] = "PIN must be exactly 4 digits.";
            Router::redirect('/customers');
            return;
        }

        if (Customer::findByMobile($mobile)) {
            $_SESSION['error'] = "A user or customer with mobile number '{$mobile}' already exists.";
            Router::redirect('/customers');
            return;
        }

        Customer::create([
            'name'      => $name,
            'mobile'    => $mobile,
            'pin'       => !empty($pin) ? $pin : '1234',
            'address'   => $address,
            'is_active' => 1
        ]);

        $_SESSION['success'] = "Customer '{$name}' created successfully.";
        Router::redirect('/customers');
    }

    public function update(): void {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $mobile   = Security::sanitizeMobile($_POST['mobile'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || !Customer::find($id)) {
            $_SESSION['error'] = "Customer not found.";
            Router::redirect('/customers');
            return;
        }

        if (empty($name) || empty($mobile)) {
            $_SESSION['error'] = "Customer name and mobile number cannot be empty.";
            Router::redirect('/customers');
            return;
        }

        if (strlen($mobile) < 10 || strlen($mobile) > 15) {
            $_SESSION['error'] = "Please enter a valid mobile number (10 to 15 digits).";
            Router::redirect('/customers');
            return;
        }

        if (Customer::findByMobile($mobile, $id)) {
            $_SESSION['error'] = "Another user or customer with mobile number '{$mobile}' already exists.";
            Router::redirect('/customers');
            return;
        }

        // Generic profile update strictly does not accept or persist PIN changes
        Customer::update($id, [
            'name'      => $name,
            'mobile'    => $mobile,
            'address'   => $address,
            'is_active' => $isActive
        ]);

        $_SESSION['success'] = "Customer details updated successfully.";
        Router::redirect('/customers');
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !Customer::find($id)) {
            $_SESSION['error'] = "Customer not found.";
            Router::redirect('/customers');
            return;
        }

        try {
            Customer::delete($id);
            $_SESSION['success'] = "Customer deleted successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        Router::redirect('/customers');
    }

    /**
     * Dedicated Administrator Customer PIN Reset Action.
     * Generates a secure random 4-digit temporary PIN, relieves lockouts across all IPs,
     * invalidates active sessions, and dispatches an in-app security notification.
     */
    public function resetPin(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid customer specified.";
            Router::redirect('/customers');
            return;
        }

        $customer = Customer::find($id);
        if (!$customer) {
            $_SESSION['error'] = "Customer not found.";
            Router::redirect('/customers');
            return;
        }

        // Verify target is strictly a customer, not staff or admin
        if (($customer['role'] ?? '') !== 'customer') {
            $_SESSION['error'] = "Target account is not an eligible customer account.";
            Router::redirect('/customers');
            return;
        }

        if ((int)($customer['is_active'] ?? 1) !== 1) {
            $_SESSION['error'] = "Cannot reset PIN for an inactive customer account.";
            Router::redirect('/customers/show?id=' . $id);
            return;
        }

        // Generate cryptographically secure random 4-digit temporary PIN
        do {
            $tempPin = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while ($tempPin === '1234' || $tempPin === '4321' || preg_match('/^(\d)\1{3}$/', $tempPin));

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Update PIN, set must_change_pin = 1, increment session_version to invalidate existing sessions
            Customer::setTemporaryPin($id, $tempPin);

            // Relieve lockout for this mobile across all IPs
            LoginAttempt::clearAllAttemptsForMobile($customer['mobile']);

            // Create in-app security notification for customer
            NotificationService::dispatch(
                (int)$customer['id'],
                null,
                'security_pin_reset',
                'Security Alert: PIN Reset by Administration',
                'Your login PIN has been reset by store administration. Upon your next sign-in using your temporary PIN, you will be required to establish a new 4-digit PIN. If you did not request this reset, please contact store support immediately.',
                'notif_pin_reset_' . $id . '_' . time(),
                'in_app'
            );

            $db->commit();

            $_SESSION['temp_pin_modal'] = [
                'customer_id'     => $id,
                'customer_name'   => $customer['name'],
                'customer_mobile' => $customer['mobile'],
                'temp_pin'        => $tempPin
            ];
            $_SESSION['success'] = "PIN reset successfully for {$customer['name']}.";

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log("Admin PIN reset failed for customer #{$id}: " . $e->getMessage());
            $_SESSION['error'] = "Failed to reset PIN: " . $e->getMessage();
        }

        Router::redirect('/customers/show?id=' . $id);
    }
}
