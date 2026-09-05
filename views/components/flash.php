<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
