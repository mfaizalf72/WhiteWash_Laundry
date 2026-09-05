<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;

class DashboardController {
    public function index(): void {
        Router::view('admin/dashboard', ['title' => 'Dashboard - Launder'], 'admin');
    }
}