<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers\Admin;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Branch;
use Faizal\LaundryLms\Helpers\Security;

class BranchController {
    public function index(): void {
        $search   = trim($_GET['search'] ?? '');
        $branches = Branch::all(!empty($search) ? $search : null);

        Router::view('admin/branches/index', [
            'title'    => 'Branch Management - Launder',
            'branches' => $branches,
            'search'   => $search
        ], 'admin');
    }

    public function store(): void {
        $name     = trim($_POST['name'] ?? '');
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $_SESSION['error'] = "Branch name is required.";
            Router::redirect('/admin/branches');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Branch name cannot exceed 100 characters.";
            Router::redirect('/admin/branches');
            return;
        }

        if (empty($code)) {
            $_SESSION['error'] = "Branch code is required.";
            Router::redirect('/admin/branches');
            return;
        }

        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            $_SESSION['error'] = "Branch code must be 2-20 alphanumeric characters (uppercase, digits, hyphens, underscores).";
            Router::redirect('/admin/branches');
            return;
        }

        if (Branch::findByCode($code)) {
            $_SESSION['error'] = "A branch with code '{$code}' already exists.";
            Router::redirect('/admin/branches');
            return;
        }

        try {
            Branch::create([
                'name'      => $name,
                'code'      => $code,
                'phone'     => !empty($phone) ? $phone : null,
                'address'   => !empty($address) ? $address : null,
                'is_active' => $isActive
            ]);
            $_SESSION['success'] = "Branch '{$name}' created successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create branch: " . $e->getMessage();
        }

        Router::redirect('/admin/branches');
    }

    public function update(): void {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0) {
            $_SESSION['error'] = "Invalid branch ID.";
            Router::redirect('/admin/branches');
            return;
        }

        $existing = Branch::find($id);
        if (!$existing) {
            $_SESSION['error'] = "Branch not found.";
            Router::redirect('/admin/branches');
            return;
        }

        if (empty($name)) {
            $_SESSION['error'] = "Branch name cannot be empty.";
            Router::redirect('/admin/branches');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Branch name cannot exceed 100 characters.";
            Router::redirect('/admin/branches');
            return;
        }

        if (empty($code)) {
            $_SESSION['error'] = "Branch code cannot be empty.";
            Router::redirect('/admin/branches');
            return;
        }

        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            $_SESSION['error'] = "Branch code must be 2-20 alphanumeric characters.";
            Router::redirect('/admin/branches');
            return;
        }

        $dup = Branch::findByCode($code, $id);
        if ($dup) {
            $_SESSION['error'] = "Branch code '{$code}' is already in use by another branch.";
            Router::redirect('/admin/branches');
            return;
        }

        try {
            Branch::update($id, [
                'name'      => $name,
                'code'      => $code,
                'phone'     => !empty($phone) ? $phone : null,
                'address'   => !empty($address) ? $address : null,
                'is_active' => $isActive
            ]);
            $_SESSION['success'] = "Branch '{$name}' updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update branch: " . $e->getMessage();
        }

        Router::redirect('/admin/branches');
    }

    public function toggleStatus(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid branch ID.";
            Router::redirect('/admin/branches');
            return;
        }

        $branch = Branch::find($id);
        if (!$branch) {
            $_SESSION['error'] = "Branch not found.";
            Router::redirect('/admin/branches');
            return;
        }

        try {
            Branch::toggleStatus($id);
            $action = ((int)$branch['is_active'] === 1) ? 'deactivated' : 'activated';
            $_SESSION['success'] = "Branch '{$branch['name']}' has been {$action}.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to toggle branch status: " . $e->getMessage();
        }

        Router::redirect('/admin/branches');
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid branch ID.";
            Router::redirect('/admin/branches');
            return;
        }

        try {
            Branch::delete($id);
            $_SESSION['success'] = "Branch deleted successfully.";
        } catch (\DomainException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to delete branch: " . $e->getMessage();
        }

        Router::redirect('/admin/branches');
    }
}
