<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;
?>

<div class="max-w-md mx-auto my-12 bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
    <div class="text-center space-y-2">
        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold font-display text-slate-800">Establish Secure PIN</h2>
        <p class="text-sm text-slate-500">A PIN update is required for your account before proceeding. Please choose a new secure 4-digit PIN.</p>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium rounded-lg">
            <?= Security::e($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['warning'])): ?>
        <div class="p-3 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-medium rounded-lg">
            <?= Security::e($_SESSION['warning']) ?>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <form action="/laundry-lms/customer/force-pin-update" method="POST" class="space-y-4">
        <?= Csrf::field() ?>

        <div>
            <label for="new_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                New 4-Digit PIN <span class="text-rose-500">*</span>
            </label>
            <input type="password" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="••••"
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm text-center font-mono tracking-widest focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div>
            <label for="confirm_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                Confirm New PIN <span class="text-rose-500">*</span>
            </label>
            <input type="password" id="confirm_pin" name="confirm_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="••••"
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm text-center font-mono tracking-widest focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <button type="submit" class="w-full py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
            Update PIN & Access Account
        </button>
    </form>

    <div class="text-center pt-2">
        <a href="/laundry-lms/logout" class="text-xs text-slate-400 hover:text-rose-600 transition-colors">Sign Out</a>
    </div>
</div>
