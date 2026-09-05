<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers\Admin;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Settings;

class SettingsController {
    public function index(): void {
        $settings = Settings::get();
       Router::view('admin/settings', [
    'title' => 'Business Settings',
    'settings' => $settings
], 'admin');
    }

    public function update(): void {
        Settings::update($_POST);
        $_SESSION['success'] = "Business settings updated successfully.";
        Router::redirect('/admin/settings');
    }
}