<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_desc ?? '') ?>">
    <title><?= htmlspecialchars($title ?? 'Laundry LMS') ?></title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Compiled Tailwind (Absolute path for XAMPP root resolution) -->
    <link href="/laundry-lms/public/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="flex flex-col min-h-screen">
    
    <!-- Glassmorphic Nav -->
    <nav class="fixed w-full z-50 top-0 border-b border-slate-200/50 bg-white/70 backdrop-blur-xl shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="font-display font-bold text-xl tracking-tight text-slate-900">Launder</span>
                </div>
                <div class="flex items-center">
                    <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full border border-indigo-100 shadow-sm">
                        Phase 1 Live
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Injection -->
    <main class="pt-16 flex-grow flex flex-col justify-center">
        <?php require_once $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center mt-auto">
        <p class="text-sm text-slate-500 font-medium">&copy; <?= date('Y') ?> Faizal LMS. Premium Architecture.</p>
    </footer>

    <script src="/laundry-lms/public/assets/js/app.js"></script>
</body>
</html>