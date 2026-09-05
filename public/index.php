<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Middleware\AuthMiddleware;
use Faizal\LaundryLms\Middleware\GuestMiddleware;
use Faizal\LaundryLms\Middleware\RoleMiddleware;
use Faizal\LaundryLms\Middleware\CsrfMiddleware;

$router = new Router();

// Public Routes
$router->get('/', 'Faizal\LaundryLms\Controllers\HomeController@index');

// Authentication Routes (Guest only for login)
$router->get('/login', 'Faizal\LaundryLms\Controllers\AuthController@showLogin', [GuestMiddleware::class]);
$router->post('/login', 'Faizal\LaundryLms\Controllers\AuthController@login', [CsrfMiddleware::class]);
$router->get('/logout', 'Faizal\LaundryLms\Controllers\AuthController@logout', [AuthMiddleware::class]);

// Protected Routes (Dashboard) - Any authenticated user (admin, staff, customer)
$router->get('/dashboard', 'Faizal\LaundryLms\Controllers\DashboardController@index', [AuthMiddleware::class]);

// Admin & Staff Master Data Routes
$authRole = [AuthMiddleware::class, RoleMiddleware::class . ':admin,staff'];
$authRoleCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':admin,staff', CsrfMiddleware::class];

// Laundry Items
$router->get('/items', 'Faizal\LaundryLms\Controllers\LaundryItemController@index', $authRole);
$router->post('/items', 'Faizal\LaundryLms\Controllers\LaundryItemController@store', $authRoleCsrf);
$router->post('/items/update', 'Faizal\LaundryLms\Controllers\LaundryItemController@update', $authRoleCsrf);
$router->post('/items/delete', 'Faizal\LaundryLms\Controllers\LaundryItemController@delete', $authRoleCsrf);

// Work Types
$router->get('/work-types', 'Faizal\LaundryLms\Controllers\WorkTypeController@index', $authRole);
$router->post('/work-types', 'Faizal\LaundryLms\Controllers\WorkTypeController@store', $authRoleCsrf);
$router->post('/work-types/update', 'Faizal\LaundryLms\Controllers\WorkTypeController@update', $authRoleCsrf);
$router->post('/work-types/delete', 'Faizal\LaundryLms\Controllers\WorkTypeController@delete', $authRoleCsrf);

// Pricing
$router->get('/pricing', 'Faizal\LaundryLms\Controllers\PricingController@index', $authRole);
$router->post('/pricing', 'Faizal\LaundryLms\Controllers\PricingController@store', $authRoleCsrf);
$router->post('/pricing/update', 'Faizal\LaundryLms\Controllers\PricingController@update', $authRoleCsrf);
$router->post('/pricing/delete', 'Faizal\LaundryLms\Controllers\PricingController@delete', $authRoleCsrf);

// Customers
$router->get('/customers', 'Faizal\LaundryLms\Controllers\CustomerController@index', $authRole);
$router->get('/customers/show', 'Faizal\LaundryLms\Controllers\CustomerController@show', $authRole);
$router->post('/customers', 'Faizal\LaundryLms\Controllers\CustomerController@store', $authRoleCsrf);
$router->post('/customers/update', 'Faizal\LaundryLms\Controllers\CustomerController@update', $authRoleCsrf);
$router->post('/customers/delete', 'Faizal\LaundryLms\Controllers\CustomerController@delete', $authRoleCsrf);

// Orders Management
$router->get('/orders', 'Faizal\LaundryLms\Controllers\OrderController@index', $authRole);
$router->get('/orders/create', 'Faizal\LaundryLms\Controllers\OrderController@create', $authRole);
$router->post('/orders', 'Faizal\LaundryLms\Controllers\OrderController@store', $authRoleCsrf);
$router->get('/orders/show', 'Faizal\LaundryLms\Controllers\OrderController@show', $authRole);
$router->post('/orders/status', 'Faizal\LaundryLms\Controllers\OrderController@updateStatus', $authRoleCsrf);
$router->post('/orders/payment', 'Faizal\LaundryLms\Controllers\OrderController@recordPayment', $authRoleCsrf);

// Admin Settings Route
$router->get('/admin/settings', 'Faizal\LaundryLms\Controllers\Admin\SettingsController@index', [AuthMiddleware::class, RoleMiddleware::class . ':admin']);
$router->post('/admin/settings', 'Faizal\LaundryLms\Controllers\Admin\SettingsController@update', [AuthMiddleware::class, RoleMiddleware::class . ':admin', CsrfMiddleware::class]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);