<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;

$isDefaultPin = !empty($customer['pin_hash']) && password_verify('1234', $customer['pin_hash']);
?>

<div class="max-w-4xl mx-auto space-y-8">

    <!-- Page Title -->
    <div>
        <h1 class="text-2xl font-bold text-slate-800 font-display">Account Settings & Security</h1>
        <p class="text-sm text-slate-500">Manage your profile details and update your 4-digit security PIN.</p>
    </div>

    <?php if ($isDefaultPin): ?>
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r text-sm text-amber-800 flex items-start space-x-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div>
            <span class="font-bold">Attention: Default PIN Detected</span>
            <p class="text-xs text-amber-700 mt-0.5">You are currently using the default PIN <strong>1234</strong>. Please change your PIN below to secure your account.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Card 1: Profile Information -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 font-display">Profile Details</h2>
                <p class="text-xs text-slate-500">Update your name, registered mobile, and address.</p>
            </div>

            <form action="/laundry-lms/customer/profile" method="POST" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?= Security::e($customer['name'] ?? '') ?>" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div>
                    <label for="mobile" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mobile Number (10 Digits) <span class="text-rose-500">*</span></label>
                    <input type="text" id="mobile" name="mobile" maxlength="10" value="<?= Security::e($customer['mobile'] ?? '') ?>" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div>
                    <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Default Pickup & Delivery Address</label>
                    <textarea id="address" name="address" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"><?= Security::e($customer['address'] ?? '') ?></textarea>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm shadow-sm transition-colors">
                        Save Profile Details
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Change Security PIN -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 font-display">Change Security PIN</h2>
                <p class="text-xs text-slate-500">Update your 4-digit login PIN for secure access.</p>
            </div>

            <form action="/laundry-lms/customer/profile/pin" method="POST" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label for="current_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Current PIN <span class="text-rose-500">*</span></label>
                    <input type="password" id="current_pin" name="current_pin" maxlength="4" placeholder="••••" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono">
                </div>

                <div>
                    <label for="new_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">New 4-Digit PIN <span class="text-rose-500">*</span></label>
                    <input type="password" id="new_pin" name="new_pin" maxlength="4" placeholder="••••" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono">
                </div>

                <div>
                    <label for="confirm_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm New PIN <span class="text-rose-500">*</span></label>
                    <input type="password" id="confirm_pin" name="confirm_pin" maxlength="4" placeholder="••••" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono">
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg text-sm shadow-sm transition-colors">
                        Update PIN
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
