<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Models\Notification;

$navCustomerId = (int)($_SESSION['user_id'] ?? 0);
$unreadNavCount = $navCustomerId > 0 ? Notification::getUnreadCount($navCustomerId) : 0;

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$baseAppPath = '/laundry-lms';
if (str_starts_with($currentUri, $baseAppPath)) {
    $currentUri = substr($currentUri, strlen($baseAppPath));
}
$currentUri = rtrim($currentUri, '/') ?: '/';

$isActiveRoute = function(string $route) use ($currentUri): bool {
    if ($route === '/customer/dashboard') {
        return $currentUri === '/customer/dashboard';
    }
    if ($route === '/customer/orders') {
        return ($currentUri === '/customer/orders' || str_starts_with($currentUri, '/customer/orders/show')) && !str_starts_with($currentUri, '/customer/orders/create');
    }
    if ($route === '/customer/orders/create') {
        return str_starts_with($currentUri, '/customer/orders/create');
    }
    if ($route === '/customer/notifications') {
        return str_starts_with($currentUri, '/customer/notifications');
    }
    if ($route === '/customer/profile') {
        return str_starts_with($currentUri, '/customer/profile');
    }
    return $currentUri === $route || str_starts_with($currentUri, $route . '/');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($title ?? 'Customer Portal - Launder') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="/laundry-lms/public/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 flex flex-col min-h-screen font-sans">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 text-white border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="/laundry-lms/customer/dashboard" class="flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                        <span class="font-display font-bold text-xl tracking-tight text-white">Launder</span>
                        <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Customer</span>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav aria-label="Customer Navigation" class="hidden lg:flex items-center space-x-2">
                    <a href="/laundry-lms/customer/dashboard" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/customer/dashboard') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/dashboard') ? 'aria-current="page"' : '' ?>>
                        Dashboard
                    </a>
                    <a href="/laundry-lms/customer/orders" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/customer/orders') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/orders') ? 'aria-current="page"' : '' ?>>
                        My Orders
                    </a>
                    <a href="/laundry-lms/customer/orders/create" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm flex items-center gap-1.5 <?= $isActiveRoute('/customer/orders/create') ? 'bg-blue-700 ring-2 ring-blue-400 text-white font-semibold' : 'bg-blue-600 hover:bg-blue-700 text-white' ?>" <?= $isActiveRoute('/customer/orders/create') ? 'aria-current="page"' : '' ?>>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Request Pickup</span>
                    </a>
                    <a href="/laundry-lms/customer/notifications" class="relative px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 inline-flex items-center space-x-1.5 <?= $isActiveRoute('/customer/notifications') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/notifications') ? 'aria-current="page"' : '' ?>>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span>Notifications</span>
                        <?php if ($unreadNavCount > 0): ?>
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-rose-600 rounded-full">
                                <?= $unreadNavCount > 99 ? '99+' : $unreadNavCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="/laundry-lms/customer/profile" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/customer/profile') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/profile') ? 'aria-current="page"' : '' ?>>
                        Profile
                    </a>
                    <a href="/laundry-lms/logout" class="px-3 py-2 rounded-lg text-sm font-medium text-rose-400 hover:text-rose-300 hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">
                        Logout
                    </a>
                </nav>

                <!-- User Info Header -->
                <div class="flex items-center space-x-3 text-sm">
                    <span class="hidden sm:inline-block text-slate-400">Welcome,</span>
                    <span class="font-semibold text-white truncate max-w-[180px] sm:max-w-none"><?= Security::e($_SESSION['user_name'] ?? 'Customer') ?></span>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links (Mobile/Tablet sub-bar) -->
        <nav aria-label="Customer Mobile Navigation" class="lg:hidden border-t border-slate-800 bg-slate-900/95 px-3 py-2 flex items-center overflow-x-auto space-x-1.5 text-xs">
            <a href="/laundry-lms/customer/dashboard" class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors <?= $isActiveRoute('/customer/dashboard') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/dashboard') ? 'aria-current="page"' : '' ?>>
                Dashboard
            </a>
            <a href="/laundry-lms/customer/orders" class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors <?= $isActiveRoute('/customer/orders') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/orders') ? 'aria-current="page"' : '' ?>>
                My Orders
            </a>
            <a href="/laundry-lms/customer/orders/create" class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors <?= $isActiveRoute('/customer/orders/create') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'bg-blue-600/90 text-white hover:bg-blue-600' ?>" <?= $isActiveRoute('/customer/orders/create') ? 'aria-current="page"' : '' ?>>
                + Pickup Request
            </a>
            <a href="/laundry-lms/customer/notifications" class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors inline-flex items-center space-x-1 <?= $isActiveRoute('/customer/notifications') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/notifications') ? 'aria-current="page"' : '' ?>>
                <span>Notifications</span>
                <?php if ($unreadNavCount > 0): ?>
                    <span class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-rose-600 rounded-full">
                        <?= $unreadNavCount > 99 ? '99+' : $unreadNavCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="/laundry-lms/customer/profile" class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors <?= $isActiveRoute('/customer/profile') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customer/profile') ? 'aria-current="page"' : '' ?>>
                Profile
            </a>
            <a href="/laundry-lms/logout" class="px-3 py-1.5 rounded-lg text-xs font-medium text-rose-400 hover:text-rose-300 whitespace-nowrap transition-colors">
                Logout
            </a>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Flash Messages -->
        <?php require __DIR__ . '/../components/flash.php'; ?>

        <!-- Page View Content -->
        <?php require $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 mt-auto">
        <p>&copy; <?= date('Y') ?> Launder Laundry LMS. Customer Self-Service Portal.</p>
    </footer>

    <script src="/laundry-lms/public/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>"></script>
</body>
</html>
