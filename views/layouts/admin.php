<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="/laundry-lms/public/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden font-sans">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <span class="font-display font-bold text-xl tracking-tight">Launder</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="/laundry-lms/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Dashboard
            </a>
            <a href="/laundry-lms/orders" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Orders
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
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="/laundry-lms/admin/settings" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors text-slate-300 hover:text-white">
                Business Settings
            </a>
            <?php endif; ?>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wider mt-1"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Guest') ?></div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-10">
            <h1 class="text-xl font-semibold text-slate-800 font-display"><?= htmlspecialchars($title ?? '') ?></h1>
            <a href="/laundry-lms/logout" class="text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">Logout</a>
        </header>

        <!-- Page Content Injection -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
            <div class="max-w-6xl mx-auto">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php require_once $content; ?>
            </div>
        </main>
    </div>

</body>
</html>