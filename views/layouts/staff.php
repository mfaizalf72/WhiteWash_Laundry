<?php
use Faizal\LaundryLms\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($title ?? 'Staff Operations - Launder') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="/laundry-lms/public/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden font-sans">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-slate-800 space-x-2">
            <span class="font-display font-bold text-xl tracking-tight">Launder</span>
            <span class="bg-blue-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Staff</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="/laundry-lms/staff/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Operational Dashboard
            </a>
            <a href="/laundry-lms/orders" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Orders Directory
            </a>
            <a href="/laundry-lms/customers" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Customers
            </a>
            <a href="/laundry-lms/items" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Laundry Items
            </a>
            <a href="/laundry-lms/work-types" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Work Types
            </a>
            <a href="/laundry-lms/pricing" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Pricing
            </a>
            <a href="/laundry-lms/reports" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Reports
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <div class="text-sm font-medium truncate"><?= Security::e($_SESSION['user_name'] ?? 'Staff User') ?></div>
            <div class="text-xs text-blue-400 uppercase tracking-wider mt-1">Staff Member</div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-10">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-semibold text-slate-800 font-display"><?= Security::e($title ?? '') ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs text-slate-500 hidden sm:inline">LoggedIn: <strong class="text-slate-800"><?= Security::e($_SESSION['user_name'] ?? 'Staff') ?></strong></span>
                <a href="/laundry-lms/logout" class="text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">Logout</a>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
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

            <?php require $content; ?>
        </main>
    </div>

</body>
</html>
