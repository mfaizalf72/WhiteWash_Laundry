<?php
use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Models\Notification;

$navCustomerId = (int)($_SESSION['user_id'] ?? 0);
$unreadNavCount = $navCustomerId > 0 ? Notification::getUnreadCount($navCustomerId) : 0;
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
                    <a href="/laundry-lms/customer/dashboard" class="flex items-center space-x-2">
                        <span class="font-display font-bold text-xl tracking-tight text-white">Launder</span>
                        <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Customer</span>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center space-x-4">
                    <a href="/laundry-lms/customer/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        Dashboard
                    </a>
                    <a href="/laundry-lms/customer/orders" class="px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        My Orders
                    </a>
                    <a href="/laundry-lms/customer/orders/create" class="px-3 py-2 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors shadow-sm">
                        + Request Pickup
                    </a>
                    <a href="/laundry-lms/customer/notifications" class="relative px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors inline-flex items-center space-x-1.5">
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
                    <a href="/laundry-lms/customer/profile" class="px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        Profile
                    </a>
                    <a href="/laundry-lms/logout" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:text-red-300 hover:bg-slate-800 transition-colors">
                        Logout
                    </a>
                </nav>

                <!-- User Info Header -->
                <div class="flex items-center space-x-3 text-sm">
                    <span class="hidden sm:inline-block text-slate-400">Welcome,</span>
                    <span class="font-semibold text-white"><?= Security::e($_SESSION['user_name'] ?? 'Customer') ?></span>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links (Mobile sub-bar) -->
        <div class="md:hidden border-t border-slate-800 bg-slate-900/95 px-4 py-2 flex overflow-x-auto space-x-2 text-sm">
            <a href="/laundry-lms/customer/dashboard" class="px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 hover:text-white whitespace-nowrap">Dashboard</a>
            <a href="/laundry-lms/customer/orders" class="px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 hover:text-white whitespace-nowrap">My Orders</a>
            <a href="/laundry-lms/customer/orders/create" class="px-3 py-1.5 rounded-md text-xs font-medium bg-blue-600 text-white whitespace-nowrap">+ Pickup Request</a>
            <a href="/laundry-lms/customer/notifications" class="px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 hover:text-white whitespace-nowrap inline-flex items-center space-x-1">
                <span>Notifications</span>
                <?php if ($unreadNavCount > 0): ?>
                    <span class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-rose-600 rounded-full">
                        <?= $unreadNavCount > 99 ? '99+' : $unreadNavCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="/laundry-lms/customer/profile" class="px-3 py-1.5 rounded-md text-xs font-medium text-slate-300 hover:text-white whitespace-nowrap">Profile</a>
            <a href="/laundry-lms/logout" class="px-3 py-1.5 rounded-md text-xs font-medium text-red-400 whitespace-nowrap">Logout</a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-sm text-emerald-800 font-medium"><?= Security::e($_SESSION['success']) ?></p>
                </div>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <p class="text-sm text-rose-800 font-medium"><?= Security::e($_SESSION['error']) ?></p>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Page View Content -->
        <?php require $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 mt-auto">
        <p>&copy; <?= date('Y') ?> Launder Laundry LMS. Customer Self-Service Portal.</p>
    </footer>

</body>
</html>
