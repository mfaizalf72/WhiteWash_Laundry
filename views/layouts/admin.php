<?php
use Faizal\LaundryLms\Helpers\Security;

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$baseAppPath = '/laundry-lms';
if (str_starts_with($currentUri, $baseAppPath)) {
    $currentUri = substr($currentUri, strlen($baseAppPath));
}
$currentUri = rtrim($currentUri, '/') ?: '/';

$isActiveRoute = function(string $route) use ($currentUri): bool {
    if ($route === '/dashboard') {
        return $currentUri === '/dashboard';
    }
    if ($route === '/staff/dashboard') {
        return $currentUri === '/staff/dashboard';
    }
    return $currentUri === $route || str_starts_with($currentUri, $route . '/');
};

$role = (string)($_SESSION['user_role'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($title ?? 'Admin - Laundry') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="/laundry-lms/public/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden font-sans">

    <!-- Desktop Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex-col hidden md:flex shrink-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-800 space-x-2">
            <span class="font-display font-bold text-xl tracking-tight text-white">Laundry</span>
            <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <?php if ($role === 'staff'): ?>
            <a href="/laundry-lms/staff/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/staff/dashboard') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/staff/dashboard') ? 'aria-current="page"' : '' ?>>
                Dashboard
            </a>
            <?php else: ?>
            <a href="/laundry-lms/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/dashboard') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/dashboard') ? 'aria-current="page"' : '' ?>>
                Dashboard
            </a>
            <?php endif; ?>
            <a href="/laundry-lms/orders" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/orders') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/orders') ? 'aria-current="page"' : '' ?>>
                Orders
            </a>
            <a href="/laundry-lms/customers" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/customers') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customers') ? 'aria-current="page"' : '' ?>>
                Customers
            </a>
            <a href="/laundry-lms/items" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/items') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/items') ? 'aria-current="page"' : '' ?>>
                Laundry Items
            </a>
            <a href="/laundry-lms/work-types" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/work-types') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/work-types') ? 'aria-current="page"' : '' ?>>
                Work Types
            </a>
            <a href="/laundry-lms/pricing" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/pricing') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/pricing') ? 'aria-current="page"' : '' ?>>
                Pricing
            </a>
            <a href="/laundry-lms/reports" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/reports') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/reports') ? 'aria-current="page"' : '' ?>>
                Reports
            </a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="/laundry-lms/admin/staff" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/staff') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/staff') ? 'aria-current="page"' : '' ?>>
                Staff
            </a>
            <a href="/laundry-lms/admin/branches" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/branches') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/branches') ? 'aria-current="page"' : '' ?>>
                Branches
            </a>
            <a href="/laundry-lms/admin/settings" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/settings') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/settings') ? 'aria-current="page"' : '' ?>>
                Business Settings
            </a>
            <?php endif; ?>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <div class="text-sm font-medium truncate text-white"><?= Security::e($_SESSION['user_name'] ?? 'User') ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wider mt-1"><?= Security::e($_SESSION['user_role'] ?? 'Guest') ?></div>
        </div>
    </aside>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-drawer-container" class="fixed inset-0 z-50 md:hidden hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation">
        <!-- Backdrop -->
        <div id="mobile-drawer-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 opacity-0"></div>
        <!-- Drawer Panel -->
        <div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-900 text-white shadow-2xl flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out">
            <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="font-display font-bold text-xl tracking-tight text-white">Laundry</span>
                    <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Admin</span>
                </div>
                <button type="button" id="mobile-menu-close" aria-label="Close mobile navigation" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <?php if ($role === 'staff'): ?>
                <a href="/laundry-lms/staff/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/staff/dashboard') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/staff/dashboard') ? 'aria-current="page"' : '' ?>>
                    Dashboard
                </a>
                <?php else: ?>
                <a href="/laundry-lms/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/dashboard') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/dashboard') ? 'aria-current="page"' : '' ?>>
                    Dashboard
                </a>
                <?php endif; ?>
                <a href="/laundry-lms/orders" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/orders') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/orders') ? 'aria-current="page"' : '' ?>>
                    Orders
                </a>
                <a href="/laundry-lms/customers" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/customers') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/customers') ? 'aria-current="page"' : '' ?>>
                    Customers
                </a>
                <a href="/laundry-lms/items" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/items') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/items') ? 'aria-current="page"' : '' ?>>
                    Laundry Items
                </a>
                <a href="/laundry-lms/work-types" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/work-types') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/work-types') ? 'aria-current="page"' : '' ?>>
                    Work Types
                </a>
                <a href="/laundry-lms/pricing" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/pricing') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/pricing') ? 'aria-current="page"' : '' ?>>
                    Pricing
                </a>
                <a href="/laundry-lms/reports" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/reports') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/reports') ? 'aria-current="page"' : '' ?>>
                    Reports
                </a>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="/laundry-lms/admin/staff" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/staff') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/staff') ? 'aria-current="page"' : '' ?>>
                    Staff
                </a>
                <a href="/laundry-lms/admin/branches" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/branches') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/branches') ? 'aria-current="page"' : '' ?>>
                    Branches
                </a>
                <a href="/laundry-lms/admin/settings" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $isActiveRoute('/admin/settings') ? 'bg-blue-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>" <?= $isActiveRoute('/admin/settings') ? 'aria-current="page"' : '' ?>>
                    Business Settings
                </a>
                <?php endif; ?>
            </nav>
            <div class="p-4 border-t border-slate-800">
                <div class="text-sm font-medium truncate text-white"><?= Security::e($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wider mt-0.5"><?= Security::e($_SESSION['user_role'] ?? 'Guest') ?></div>
                <div class="mt-3 pt-3 border-t border-slate-800/80">
                    <a href="/laundry-lms/logout" class="flex items-center text-sm font-medium text-rose-400 hover:text-rose-300 transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 shrink-0 shadow-sm z-10">
            <div class="flex items-center min-w-0">
                <button type="button" id="mobile-menu-open" aria-controls="mobile-drawer" aria-expanded="false" aria-label="Open mobile navigation" class="md:hidden inline-flex items-center justify-center p-2 mr-3 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-800 font-display truncate"><?= Security::e($title ?? '') ?></h1>
            </div>
            <div class="flex items-center space-x-4 shrink-0">
                <span class="text-xs text-slate-500 hidden sm:inline">Signed in as <strong class="text-slate-800 font-medium"><?= Security::e($_SESSION['user_name'] ?? 'Admin') ?></strong></span>
                <a href="/laundry-lms/logout" class="text-sm font-medium text-slate-600 hover:text-red-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-1.5 py-1">Logout</a>
            </div>
        </header>

        <!-- Page Content Injection -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-50/50">
            <div class="max-w-6xl mx-auto">
                <?php require __DIR__ . '/../components/flash.php'; ?>
                <?php require_once $content; ?>
            </div>
        </main>
    </div>

    <script src="/laundry-lms/public/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>"></script>
</body>
</html>