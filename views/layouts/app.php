<?php
use Faizal\LaundryLms\Helpers\Security;

// Sanitize title and ensure consistent branding
$pageTitle = $title ?? 'Laundry LMS';
$pageTitle = str_replace('Launder', 'Laundry', $pageTitle);

// Determine route for header action link
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isRegisterPage = str_contains($currentPath, '/register');
$baseUri = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/laundry-lms') ? '/laundry-lms' : '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_desc ?? '') ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Compiled Tailwind (Adaptive path for local XAMPP and production domain) -->
    <link href="<?= $baseUri ?>/public/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="flex flex-col min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased font-sans">
    
    <!-- Glassmorphic Nav -->
    <nav class="fixed w-full z-50 top-0 border-b border-slate-200/80 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl shadow-xs transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/laundry-lms/" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg group">
                    <div class="w-9 h-9 bg-slate-900 dark:bg-slate-800 border dark:border-slate-700 rounded-xl flex items-center justify-center shadow-xs group-hover:bg-slate-800 dark:group-hover:bg-slate-700 transition-colors">
                        <svg class="w-5 h-5 text-indigo-400" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="20" x="3" y="2" rx="3"></rect>
                            <circle cx="12" cy="13" r="5"></circle>
                            <path d="M12 10a3 3 0 0 0-3 3"></path>
                            <circle cx="7" cy="6" r="1" fill="currentColor"></circle>
                            <circle cx="11" cy="6" r="1" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <span class="font-display font-bold text-xl tracking-tight text-slate-900 dark:text-white">Laundry</span>
                </a>
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Theme Toggle Button -->
                    <button type="button" onclick="window.toggleTheme(event)" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        <svg data-theme-icon-moon class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg data-theme-icon-sun class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <?php if ($isRegisterPage): ?>
                        <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:inline-block">Already have an account?</span>
                        <a href="/laundry-lms/login" class="text-xs sm:text-sm font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/60 dark:border-indigo-800/60 px-3.5 py-1.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Sign In
                        </a>
                    <?php else: ?>
                        <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:inline-block">New customer?</span>
                        <a href="/laundry-lms/register" class="text-xs sm:text-sm font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/60 dark:border-indigo-800/60 px-3.5 py-1.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Create Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Injection -->
    <main class="pt-16 flex-grow flex flex-col justify-center">
        <?php require_once $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-6 text-center mt-auto transition-colors">
        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">&copy; <?= date('Y') ?> Laundry LMS. Professional garment care management.</p>
    </footer>

    <script src="<?= $baseUri ?>/public/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>"></script>
</body>
</html>