<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Customer;
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
        $pin      = trim($_POST['pin'] ?? '');
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

        if (!empty($pin) && (strlen($pin) !== 4 || !ctype_digit($pin))) {
            $_SESSION['error'] = "PIN must be exactly 4 digits.";
            Router::redirect('/customers');
            return;
        }

        if (Customer::findByMobile($mobile, $id)) {
            $_SESSION['error'] = "Another user or customer with mobile number '{$mobile}' already exists.";
            Router::redirect('/customers');
            return;
        }

        Customer::update($id, [
            'name'      => $name,
            'mobile'    => $mobile,
            'pin'       => $pin,
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
}
