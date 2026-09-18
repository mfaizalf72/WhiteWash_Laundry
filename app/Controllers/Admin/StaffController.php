<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers\Admin;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Staff;
use Faizal\LaundryLms\Helpers\Security;

class StaffController {
    public function index(): void {
        $search = trim($_GET['search'] ?? '');
        $staffMembers = Staff::all($search);

        Router::view('admin/staff/index', [
            'title'        => 'Staff Management - Launder',
            'staffMembers' => $staffMembers,
            'search'       => $search
        ], 'admin');
    }

    public function store(): void {
        $name     = trim($_POST['name'] ?? '');
        $mobile   = Security::sanitizeMobile($_POST['mobile'] ?? '');
        $pin      = trim($_POST['pin'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($name)) {
            $_SESSION['error'] = "Staff name is required.";
            Router::redirect('/admin/staff');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Staff name cannot exceed 100 characters.";
            Router::redirect('/admin/staff');
            return;
        }

        if (empty($mobile)) {
            $_SESSION['error'] = "Mobile number is required.";
            Router::redirect('/admin/staff');
            return;
        }

        if (strlen($mobile) < 10 || strlen($mobile) > 15) {
            $_SESSION['error'] = "Please enter a valid mobile number (10 to 15 digits).";
            Router::redirect('/admin/staff');
            return;
        }

        if (empty($pin) || strlen($pin) !== 4 || !ctype_digit($pin)) {
            $_SESSION['error'] = "A valid 4-digit numeric PIN is required for staff account creation.";
            Router::redirect('/admin/staff');
            return;
        }

        if (Staff::findByMobile($mobile)) {
            $_SESSION['error'] = "A user with mobile number '{$mobile}' already exists.";
            Router::redirect('/admin/staff');
            return;
        }

        try {
            Staff::create([
                'name'      => $name,
                'mobile'    => $mobile,
                'pin'       => $pin,
                'is_active' => $isActive
            ]);
            $_SESSION['success'] = "Staff account '{$name}' created successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create staff account: " . $e->getMessage();
        }

        Router::redirect('/admin/staff');
    }

    public function update(): void {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $mobile   = Security::sanitizeMobile($_POST['mobile'] ?? '');
        $pin      = trim($_POST['pin'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0) {
            $_SESSION['error'] = "Invalid staff account ID.";
            Router::redirect('/admin/staff');
            return;
        }

        $existing = Staff::find($id);
        if (!$existing) {
            $_SESSION['error'] = "Staff account not found.";
            Router::redirect('/admin/staff');
            return;
        }

        if (empty($name)) {
            $_SESSION['error'] = "Staff name cannot be empty.";
            Router::redirect('/admin/staff');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Staff name cannot exceed 100 characters.";
            Router::redirect('/admin/staff');
            return;
        }

        if (empty($mobile)) {
            $_SESSION['error'] = "Mobile number cannot be empty.";
            Router::redirect('/admin/staff');
            return;
        }

        if (strlen($mobile) < 10 || strlen($mobile) > 15) {
            $_SESSION['error'] = "Please enter a valid mobile number (10 to 15 digits).";
            Router::redirect('/admin/staff');
            return;
        }

        if (!empty($pin)) {
            if (strlen($pin) !== 4 || !ctype_digit($pin)) {
                $_SESSION['error'] = "PIN must be exactly 4 numeric digits.";
                Router::redirect('/admin/staff');
                return;
            }
        }

        $duplicate = Staff::findByMobile($mobile, $id);
        if ($duplicate) {
            $_SESSION['error'] = "Mobile number '{$mobile}' is already in use by another account.";
            Router::redirect('/admin/staff');
            return;
        }

        try {
            $data = [
                'name'      => $name,
                'mobile'    => $mobile,
                'is_active' => $isActive
            ];
            if (!empty($pin)) {
                $data['pin'] = $pin;
            }

            Staff::update($id, $data);
            $_SESSION['success'] = "Staff account '{$name}' updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update staff account: " . $e->getMessage();
        }

        Router::redirect('/admin/staff');
    }

    public function toggleStatus(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid staff account ID.";
            Router::redirect('/admin/staff');
            return;
        }

        $staff = Staff::find($id);
        if (!$staff) {
            $_SESSION['error'] = "Staff account not found.";
            Router::redirect('/admin/staff');
            return;
        }

        try {
            Staff::toggleStatus($id);
            $action = ((int)$staff['is_active'] === 1) ? 'deactivated' : 'activated';
            $_SESSION['success'] = "Staff account '{$staff['name']}' has been {$action}.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to change staff status: " . $e->getMessage();
        }

        Router::redirect('/admin/staff');
    }
}
