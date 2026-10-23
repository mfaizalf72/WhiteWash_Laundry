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
use Faizal\LaundryLms\Middleware\ForcePinChangeMiddleware;

$router = new Router();

// Public Routes
$router->get('/', 'Faizal\LaundryLms\Controllers\HomeController@index');

// Authentication Routes (Guest only for login & registration)
$router->get('/login', 'Faizal\LaundryLms\Controllers\AuthController@showLogin', [GuestMiddleware::class]);
$router->post('/login', 'Faizal\LaundryLms\Controllers\AuthController@login', [CsrfMiddleware::class]);
$router->get('/register', 'Faizal\LaundryLms\Controllers\AuthController@showRegister', [GuestMiddleware::class]);
$router->post('/register', 'Faizal\LaundryLms\Controllers\AuthController@register', [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/logout', 'Faizal\LaundryLms\Controllers\AuthController@logout', [AuthMiddleware::class]);

// Admin & Staff Operational Routes (Master Data, Orders, Reports)
$authRole     = [AuthMiddleware::class, RoleMiddleware::class . ':admin,staff'];
$authRoleCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':admin,staff', CsrfMiddleware::class];

// Staff Only Routes
$staffOnlyAuth     = [AuthMiddleware::class, RoleMiddleware::class . ':staff'];
$staffOnlyAuthCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':staff', CsrfMiddleware::class];

// Admin Only Settings Route Group
$adminOnlyAuth     = [AuthMiddleware::class, RoleMiddleware::class . ':admin'];
$adminOnlyAuthCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':admin', CsrfMiddleware::class];

// Customer Portal Routes (With ForcePinChangeMiddleware)
$customerAuth     = [AuthMiddleware::class, RoleMiddleware::class . ':customer', ForcePinChangeMiddleware::class];
$customerAuthCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':customer', ForcePinChangeMiddleware::class, CsrfMiddleware::class];

// Force PIN Update Route Group
$forcePinAuth     = [AuthMiddleware::class, RoleMiddleware::class . ':customer'];
$forcePinAuthCsrf = [AuthMiddleware::class, RoleMiddleware::class . ':customer', CsrfMiddleware::class];

// Protected Admin/Staff Dashboard Route
$router->get('/dashboard', 'Faizal\LaundryLms\Controllers\DashboardController@index', $authRole);

// Customer Portal Routes
$router->get('/customer/force-pin-update', 'Faizal\LaundryLms\Controllers\CustomerPortalController@forcePinUpdateForm', $forcePinAuth);
$router->post('/customer/force-pin-update', 'Faizal\LaundryLms\Controllers\CustomerPortalController@forcePinUpdate', $forcePinAuthCsrf);
$router->get('/customer/dashboard', 'Faizal\LaundryLms\Controllers\CustomerPortalController@dashboard', $customerAuth);
$router->get('/customer/orders', 'Faizal\LaundryLms\Controllers\CustomerPortalController@orders', $customerAuth);
$router->get('/customer/orders/show', 'Faizal\LaundryLms\Controllers\CustomerPortalController@showOrder', $customerAuth);
$router->get('/customer/orders/print', 'Faizal\LaundryLms\Controllers\CustomerPortalController@printReceipt', $customerAuth);
$router->get('/customer/orders/pdf', 'Faizal\LaundryLms\Controllers\CustomerPortalController@downloadPdf', $customerAuth);
$router->post('/customer/orders/approve-price', 'Faizal\LaundryLms\Controllers\CustomerPortalController@approvePrice', $customerAuthCsrf);
$router->post('/customer/orders/approve', 'Faizal\LaundryLms\Controllers\CustomerPortalController@approvePrice', $customerAuthCsrf);
$router->post('/customer/orders/reject-price', 'Faizal\LaundryLms\Controllers\CustomerPortalController@rejectPrice', $customerAuthCsrf);
$router->post('/customer/orders/cancel', 'Faizal\LaundryLms\Controllers\CustomerPortalController@cancelOrder', $customerAuthCsrf);
$router->post('/customer/orders/reschedule', 'Faizal\LaundryLms\Controllers\CustomerPortalController@reschedule', $customerAuthCsrf);
$router->post('/customer/orders/pay/initiate', 'Faizal\LaundryLms\Controllers\CustomerPortalController@initiateOnlinePayment', $customerAuthCsrf);
$router->post('/customer/orders/pay/verify', 'Faizal\LaundryLms\Controllers\CustomerPortalController@verifyOnlinePayment', $customerAuthCsrf);
$router->get('/customer/orders/create', 'Faizal\LaundryLms\Controllers\CustomerPortalController@createOrderForm', $customerAuth);
$router->post('/customer/orders/create', 'Faizal\LaundryLms\Controllers\CustomerPortalController@storeOrder', $customerAuthCsrf);
$router->get('/customer/profile', 'Faizal\LaundryLms\Controllers\CustomerPortalController@profile', $customerAuth);
$router->post('/customer/profile', 'Faizal\LaundryLms\Controllers\CustomerPortalController@updateProfile', $customerAuthCsrf);
$router->post('/customer/profile/pin', 'Faizal\LaundryLms\Controllers\CustomerPortalController@changePin', $customerAuthCsrf);
$router->get('/customer/notifications', 'Faizal\LaundryLms\Controllers\CustomerPortalController@notifications', $customerAuth);
$router->post('/customer/notifications/read', 'Faizal\LaundryLms\Controllers\CustomerPortalController@markNotificationRead', $customerAuthCsrf);
$router->post('/customer/notifications/read-all', 'Faizal\LaundryLms\Controllers\CustomerPortalController@markAllNotificationsRead', $customerAuthCsrf);

// Laundry Items (Admin & Staff)
$router->get('/items', 'Faizal\LaundryLms\Controllers\LaundryItemController@index', $authRole);
$router->post('/items', 'Faizal\LaundryLms\Controllers\LaundryItemController@store', $authRoleCsrf);
$router->post('/items/update', 'Faizal\LaundryLms\Controllers\LaundryItemController@update', $authRoleCsrf);
$router->post('/items/delete', 'Faizal\LaundryLms\Controllers\LaundryItemController@delete', $authRoleCsrf);

// Work Types (Admin & Staff)
$router->get('/work-types', 'Faizal\LaundryLms\Controllers\WorkTypeController@index', $authRole);
$router->post('/work-types', 'Faizal\LaundryLms\Controllers\WorkTypeController@store', $authRoleCsrf);
$router->post('/work-types/update', 'Faizal\LaundryLms\Controllers\WorkTypeController@update', $authRoleCsrf);
$router->post('/work-types/delete', 'Faizal\LaundryLms\Controllers\WorkTypeController@delete', $authRoleCsrf);

// Pricing (Admin & Staff)
$router->get('/pricing', 'Faizal\LaundryLms\Controllers\PricingController@index', $authRole);
$router->post('/pricing', 'Faizal\LaundryLms\Controllers\PricingController@store', $authRoleCsrf);
$router->post('/pricing/update', 'Faizal\LaundryLms\Controllers\PricingController@update', $authRoleCsrf);
$router->post('/pricing/delete', 'Faizal\LaundryLms\Controllers\PricingController@delete', $authRoleCsrf);

// Customers (Admin & Staff)
$router->get('/customers', 'Faizal\LaundryLms\Controllers\CustomerController@index', $authRole);
$router->get('/customers/show', 'Faizal\LaundryLms\Controllers\CustomerController@show', $authRole);
$router->post('/customers', 'Faizal\LaundryLms\Controllers\CustomerController@store', $authRoleCsrf);
$router->post('/customers/update', 'Faizal\LaundryLms\Controllers\CustomerController@update', $authRoleCsrf);
$router->post('/customers/delete', 'Faizal\LaundryLms\Controllers\CustomerController@delete', $authRoleCsrf);
$router->post('/admin/customers/reset-pin', 'Faizal\LaundryLms\Controllers\CustomerController@resetPin', $adminOnlyAuthCsrf);

// Staff Operational Routes
$router->get('/staff/dashboard', 'Faizal\LaundryLms\Controllers\StaffPortalController@dashboard', $staffOnlyAuth);

// Orders Management (Admin & Staff)
$router->get('/orders', 'Faizal\LaundryLms\Controllers\OrderController@index', $authRole);
$router->get('/orders/create', 'Faizal\LaundryLms\Controllers\OrderController@create', $authRole);
$router->post('/orders', 'Faizal\LaundryLms\Controllers\OrderController@store', $authRoleCsrf);
$router->get('/orders/show', 'Faizal\LaundryLms\Controllers\OrderController@show', $authRole);
$router->get('/orders/print', 'Faizal\LaundryLms\Controllers\OrderController@printJobCard', $authRole);
$router->get('/orders/pdf', 'Faizal\LaundryLms\Controllers\OrderController@downloadPdf', $authRole);
$router->post('/orders/status', 'Faizal\LaundryLms\Controllers\OrderController@updateStatus', $authRoleCsrf);
$router->post('/orders/payment', 'Faizal\LaundryLms\Controllers\OrderController@recordPayment', $authRoleCsrf);
$router->post('/orders/refund', 'Faizal\LaundryLms\Controllers\OrderController@processRefund', $authRoleCsrf);
$router->post('/orders/claim', 'Faizal\LaundryLms\Controllers\OrderController@claimOrder', $staffOnlyAuthCsrf);
$router->post('/orders/assign', 'Faizal\LaundryLms\Controllers\OrderController@assignStaff', $adminOnlyAuthCsrf);
$router->post('/orders/inspect', 'Faizal\LaundryLms\Controllers\OrderController@submitInspection', $authRoleCsrf);
$router->post('/orders/override-price-approval', 'Faizal\LaundryLms\Controllers\OrderController@overridePriceApproval', $adminOnlyAuthCsrf);
$router->post('/orders/cancel', 'Faizal\LaundryLms\Controllers\OrderController@cancel', $authRoleCsrf);
$router->post('/orders/reschedule', 'Faizal\LaundryLms\Controllers\OrderController@reschedule', $authRoleCsrf);

// Reports Route (Admin & Staff)
$router->get('/reports', 'Faizal\LaundryLms\Controllers\ReportController@index', $authRole);

// Admin Staff Management Routes (Admin Only)
$router->get('/admin/staff', 'Faizal\LaundryLms\Controllers\Admin\StaffController@index', $adminOnlyAuth);
$router->post('/admin/staff', 'Faizal\LaundryLms\Controllers\Admin\StaffController@store', $adminOnlyAuthCsrf);
$router->post('/admin/staff/update', 'Faizal\LaundryLms\Controllers\Admin\StaffController@update', $adminOnlyAuthCsrf);
$router->post('/admin/staff/toggle-status', 'Faizal\LaundryLms\Controllers\Admin\StaffController@toggleStatus', $adminOnlyAuthCsrf);

// Admin Branch Management Routes (Admin Only)
$router->get('/admin/branches', 'Faizal\LaundryLms\Controllers\Admin\BranchController@index', $adminOnlyAuth);
$router->post('/admin/branches', 'Faizal\LaundryLms\Controllers\Admin\BranchController@store', $adminOnlyAuthCsrf);
$router->post('/admin/branches/update', 'Faizal\LaundryLms\Controllers\Admin\BranchController@update', $adminOnlyAuthCsrf);
$router->post('/admin/branches/toggle-status', 'Faizal\LaundryLms\Controllers\Admin\BranchController@toggleStatus', $adminOnlyAuthCsrf);
$router->post('/admin/branches/delete', 'Faizal\LaundryLms\Controllers\Admin\BranchController@delete', $adminOnlyAuthCsrf);

// Admin Settings Route (Admin Only)
$router->get('/admin/settings', 'Faizal\LaundryLms\Controllers\Admin\SettingsController@index', $adminOnlyAuth);
$router->post('/admin/settings', 'Faizal\LaundryLms\Controllers\Admin\SettingsController@update', $adminOnlyAuthCsrf);

// Payment Webhook Route (Server-to-Server, cryptographically verified)
$router->post('/api/payment/webhook', 'Faizal\LaundryLms\Controllers\PaymentWebhookController@handle');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);