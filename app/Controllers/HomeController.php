<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;

class HomeController {
    public function index(): void {
        Router::view('home', [
            'title' => 'Launder | Premium Care',
            'meta_desc' => 'Modern, frictionless laundry management system.'
        ]);
    }
}