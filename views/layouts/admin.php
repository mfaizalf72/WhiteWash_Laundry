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
$userName = (string)($_SESSION['user_name'] ?? 'User');
// Generate user initials for avatar
$nameParts = explode(' ', trim($userName));
$userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
if (empty($userInitials)) $userInitials = 'U';

// Sanitize page title and branding
$pageTitle = $title ?? (($role === 'staff') ? 'Staff Operations - Laundry' : 'Admin - Laundry');
$pageTitle = str_replace('Launder', 'Laundry', $pageTitle);

// Centralize navigation items with dedicated inline SVGs
$navItems = [
    [
        'label' => 'Dashboard',
        'href'  => ($role === 'staff') ? '/laundry-lms/staff/dashboard' : '/laundry-lms/dashboard',
        'route' => ($role === 'staff') ? '/staff/dashboard' : '/dashboard',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>'
    ],
    [
        'label' => 'Orders',
        'href'  => '/laundry-lms/orders',
        'route' => '/orders',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>'
    ],
    [
        'label' => 'Customers',
        'href'  => '/laundry-lms/customers',
        'route' => '/customers',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'
    ],
    [
        'label' => 'Laundry Items',
        'href'  => '/laundry-lms/items',
        'route' => '/items',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'
    ],
    [
        'label' => 'Work Types',
        'href'  => '/laundry-lms/work-types',
        'route' => '/work-types',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'
    ],
    [
        'label' => 'Pricing',
        'href'  => '/laundry-lms/pricing',
        'route' => '/pricing',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6h4a3 3 0 010 6H9m2-12v14"/>'
    ],
    [
        'label' => 'Reports',
        'href'  => '/laundry-lms/reports',
        'route' => '/reports',
        'roles' => ['admin', 'staff'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'
    ],
    [
        'label' => 'Staff',
        'href'  => '/laundry-lms/admin/staff',
        'route' => '/admin/staff',
        'roles' => ['admin'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'
    ],
    [
        'label' => 'Branches',
        'href'  => '/laundry-lms/admin/branches',
        'route' => '/admin/branches',
        'roles' => ['admin'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'
    ],
    [
        'label' => 'Business Settings',
        'href'  => '/laundry-lms/admin/settings',
        'route' => '/admin/settings',
        'roles' => ['admin'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($pageTitle) ?></title>
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('lms_theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch(e) {}
        })();

        function updateToggleIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            var toggles = document.querySelectorAll('[data-theme-toggle]');
            toggles.forEach(function(btn) {
                var moonIcon = btn.querySelector('[data-theme-icon-moon]');
                var sunIcon = btn.querySelector('[data-theme-icon-sun]');
                if (moonIcon && sunIcon) {
                    if (isDark) {
                        moonIcon.classList.add('hidden');
                        sunIcon.classList.remove('hidden');
                        btn.setAttribute('aria-label', 'Switch to light mode');
                        btn.setAttribute('title', 'Switch to light mode');
                    } else {
                        moonIcon.classList.remove('hidden');
                        sunIcon.classList.add('hidden');
                        btn.setAttribute('aria-label', 'Switch to dark mode');
                        btn.setAttribute('title', 'Switch to dark mode');
                    }
                }
            });
        }

        window.toggleTheme = function(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            var isDark = document.documentElement.classList.contains('dark');
            var next = isDark ? 'light' : 'dark';
            try {
                localStorage.setItem('lms_theme', next);
            } catch(e) {}
            if (next === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            updateToggleIcons();
        };

        window.addEventListener('storage', function(e) {
            if (e.key === 'lms_theme') {
                if (e.newValue === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                updateToggleIcons();
            }
        });

        if (document.readyState !== 'loading') {
            updateToggleIcons();
        } else {
            document.addEventListener('DOMContentLoaded', updateToggleIcons);
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="/laundry-lms/public/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex h-full overflow-hidden font-sans antialiased">

    <!-- Desktop Sidebar -->
    <aside class="w-64 bg-slate-950 text-white flex-col hidden lg:flex shrink-0 border-r border-slate-800/80 shadow-xl">
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800/80">
            <a href="<?= ($role === 'staff') ? '/laundry-lms/staff/dashboard' : '/laundry-lms/dashboard' ?>" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-md shadow-indigo-900/30 group-hover:from-indigo-600 group-hover:to-indigo-800 transition-all">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="18" height="20" x="3" y="2" rx="3"></rect>
                        <circle cx="12" cy="13" r="5"></circle>
                        <path d="M12 10a3 3 0 0 0-3 3"></path>
                        <circle cx="7" cy="6" r="1" fill="currentColor"></circle>
                        <circle cx="11" cy="6" r="1" fill="currentColor"></circle>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="font-display font-bold text-lg tracking-tight text-white group-hover:text-indigo-200 transition-colors leading-none">Laundry</span>
                    <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase mt-0.5">Management</span>
                </div>
            </a>
            <span data-role-badge class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase <?= ($role === 'staff') ? 'bg-blue-500/15 text-blue-400 border border-blue-500/30' : 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30' ?>">
                <?= Security::e($role === 'staff' ? 'Staff' : 'Admin') ?>
            </span>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 scrollbar-thin scrollbar-thumb-slate-800" aria-label="Admin Navigation">
            <?php foreach ($navItems as $item): ?>
                <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                <?php $active = $isActiveRoute($item['route']); ?>
                <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs sm:text-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= $active ? 'bg-gradient-to-r from-indigo-600/20 to-indigo-600/5 text-white font-semibold border-l-2 border-indigo-500 shadow-2xs' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                    <svg class="w-4 h-4 shrink-0 transition-colors <?= $active ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <?= $item['icon'] ?>
                    </svg>
                    <span class="truncate"><?= Security::e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User Profile & Sign Out Footer -->
        <div class="p-3.5 border-t border-slate-800/80 bg-slate-950/60">
            <div class="flex items-center justify-between gap-3 p-2 rounded-xl bg-slate-900/60 border border-slate-800/60">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                        <?= Security::e($userInitials) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold truncate text-white"><?= Security::e($userName) ?></div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider"><?= Security::e($role) ?></div>
                    </div>
                </div>
                <a href="/laundry-lms/logout" aria-label="Sign Out" title="Sign Out" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-drawer-container" class="fixed inset-0 z-50 lg:hidden hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation">
        <!-- Backdrop -->
        <div id="mobile-drawer-backdrop" class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-300 opacity-0"></div>
        <!-- Drawer Panel -->
        <div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-950 text-white shadow-2xl flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-slate-800">
            <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800">
                <a href="<?= ($role === 'staff') ? '/laundry-lms/staff/dashboard' : '/laundry-lms/dashboard' ?>" class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-xs">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="20" x="3" y="2" rx="3"></rect>
                            <circle cx="12" cy="13" r="5"></circle>
                            <path d="M12 10a3 3 0 0 0-3 3"></path>
                            <circle cx="7" cy="6" r="1" fill="currentColor"></circle>
                            <circle cx="11" cy="6" r="1" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <span class="font-display font-bold text-lg tracking-tight text-white">Laundry</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= ($role === 'staff') ? 'bg-blue-500/15 text-blue-400 border border-blue-500/30' : 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30' ?>"><?= Security::e($role === 'staff' ? 'Staff' : 'Admin') ?></span>
                </a>
                <button type="button" id="mobile-menu-close" aria-label="Close mobile navigation" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                    <?php $active = $isActiveRoute($item['route']); ?>
                    <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 <?= $active ? 'bg-gradient-to-r from-indigo-600/20 to-indigo-600/5 text-white font-semibold border-l-2 border-indigo-500 shadow-2xs' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <svg class="w-4 h-4 shrink-0 <?= $active ? 'text-indigo-400' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <?= $item['icon'] ?>
                        </svg>
                        <span><?= Security::e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/80">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                        <?= Security::e($userInitials) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold truncate text-white"><?= Security::e($userName) ?></div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider"><?= Security::e($role) ?></div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-800/80">
                    <a href="/laundry-lms/logout" class="flex items-center gap-2 text-sm font-semibold text-rose-400 hover:text-rose-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden min-w-0">
        <!-- Topbar -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 shrink-0 shadow-2xs z-10 transition-colors">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="mobile-menu-open" aria-controls="mobile-drawer" aria-expanded="false" aria-label="Open mobile navigation" class="lg:hidden inline-flex items-center justify-center p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="flex items-center gap-2 text-sm min-w-0">
                    <span class="text-slate-400 dark:text-slate-500 hidden sm:inline">Laundry</span>
                    <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">/</span>
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white font-display truncate"><?= Security::e($pageTitle) ?></h1>
                </div>
            </div>
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                    <span>System Online</span>
                </div>

                <!-- Theme Toggle Button -->
                <button type="button" onclick="window.toggleTheme && window.toggleTheme(event)" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                    <svg data-theme-icon-moon class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg data-theme-icon-sun class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <div class="h-4 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-900 dark:bg-slate-800 text-white font-semibold text-xs flex items-center justify-center shadow-xs border dark:border-slate-700">
                        <?= Security::e($userInitials) ?>
                    </div>
                    <span class="text-xs text-slate-600 dark:text-slate-300 font-medium hidden sm:inline"><?= Security::e($userName) ?></span>
                </div>
                <a href="/laundry-lms/logout" class="text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 border border-transparent hover:border-rose-100 dark:hover:border-rose-900/50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg px-2.5 py-1.5">
                    Logout
                </a>
            </div>
        </header>

        <!-- Page Content Injection -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50/70 dark:bg-slate-950 transition-colors">
            <div class="max-w-7xl mx-auto">
                <?php require __DIR__ . '/../components/flash.php'; ?>
                <?php require_once $content; ?>
            </div>
        </main>
    </div>

    <script src="/laundry-lms/public/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>"></script>
</body>
</html>