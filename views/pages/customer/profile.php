<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;

$isDefaultPin = !empty($customer['pin_hash']) && password_verify('1234', $customer['pin_hash']);
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Page Title & Subtitle -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 font-display tracking-tight">Account Profile & Security</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your personal details, registered address for pickup & delivery, and 4-digit security PIN.</p>
        </div>
        <div class="shrink-0 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Active Account</span>
            </span>
        </div>
    </div>

    <!-- Default PIN Warning Banner (Rendered only when server detects default PIN 1234) -->
    <?php if ($isDefaultPin): ?>
    <div class="bg-amber-50/90 border border-amber-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex items-start gap-3.5">
        <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700 border border-amber-200 shrink-0 mt-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <div class="space-y-0.5">
            <span class="font-bold text-amber-950 font-display text-sm">Attention: Default PIN Detected</span>
            <p class="text-xs text-amber-800 leading-relaxed">
                Your account is currently using the default PIN <strong>1234</strong>. Please choose a new, private 4-digit PIN in the Security section below to safeguard your orders and account data.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <!-- Card 1: Profile Details Form -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-5">
            <div class="flex items-start gap-3 border-b border-slate-100 pb-4">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-800 font-display">Profile Details</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Update your full name, registered mobile, and default laundry address.</p>
                </div>
            </div>

            <form action="/laundry-lms/customer/profile" method="POST" id="customerProfileForm" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Full Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="<?= Security::e($customer['name'] ?? '') ?>" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs placeholder:text-slate-400">
                </div>

                <div>
                    <label for="mobile" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Mobile Number (10 Digits) <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" id="mobile" name="mobile" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" value="<?= Security::e($customer['mobile'] ?? '') ?>" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs placeholder:text-slate-400">
                    <p class="text-[11px] text-slate-500 mt-1">10-digit primary number used for account sign-in and pickup coordination.</p>
                </div>

                <div>
                    <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Default Pickup & Delivery Address
                    </label>
                    <textarea id="address" name="address" rows="3" placeholder="Flat/House No, Building name, Street, Landmark, City, PIN code"
                              class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs placeholder:text-slate-400"><?= Security::e($customer['address'] ?? '') ?></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">Prefilled automatically when scheduling laundry pickup requests.</p>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" id="profileSubmitBtn" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                        <span>Save Profile Details</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Change Security PIN Form -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-5">
            <div class="flex items-start gap-3 border-b border-slate-100 pb-4">
                <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-800 font-display">Change Security PIN</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Update your 4-digit numeric login PIN for secure access.</p>
                </div>
            </div>

            <!-- PIN Policy Guidance Note -->
            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3 text-xs text-slate-600 space-y-1">
                <div class="font-semibold text-slate-700 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>PIN Rules & Security Guidelines:</span>
                </div>
                <p class="pl-5 text-slate-500">Must be exactly 4 numeric digits. Common PINs (1234, 4321, 0000, 1111) and your current PIN are blocked.</p>
            </div>

            <form action="/laundry-lms/customer/profile/pin" method="POST" id="customerPinForm" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label for="current_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Current PIN <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="current_pin" name="current_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="current-password" placeholder="••••" required
                               class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono tracking-widest font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                        <button type="button" onclick="togglePinField('current_pin', this)" aria-label="Toggle Current PIN visibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                            <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="new_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        New 4-Digit PIN <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" placeholder="••••" required
                               class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono tracking-widest font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                        <button type="button" onclick="togglePinField('new_pin', this)" aria-label="Toggle New PIN visibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                            <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirm_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Confirm New PIN <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="confirm_pin" name="confirm_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" placeholder="••••" required
                               class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono tracking-widest font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                        <button type="button" onclick="togglePinField('confirm_pin', this)" aria-label="Toggle Confirm PIN visibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                            <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                        </button>
                    </div>
                    <!-- Live PIN Match Indicator -->
                    <div id="pinMatchStatus" class="hidden mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 transition-all"></div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" id="pinSubmitBtn" class="w-full sm:w-auto px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 flex items-center justify-center gap-2">
                        <span>Update Security PIN</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<script>
function togglePinField(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const eyeIcon = btn.querySelector('.eye-icon');
    const eyeOffIcon = btn.querySelector('.eye-off-icon');
    if (eyeIcon && eyeOffIcon) {
        if (isPassword) {
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
            btn.setAttribute('aria-label', `Hide ${input.name.replace('_', ' ')}`);
        } else {
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
            btn.setAttribute('aria-label', `Show ${input.name.replace('_', ' ')}`);
        }
    }
}

// Live PIN match validation check for voluntary PIN change
const newPinInp = document.getElementById('new_pin');
const confirmPinInp = document.getElementById('confirm_pin');
const matchStatus = document.getElementById('pinMatchStatus');

function checkPinMatch() {
    if (!newPinInp || !confirmPinInp || !matchStatus) return;
    const nVal = newPinInp.value.trim();
    const cVal = confirmPinInp.value.trim();

    if (!cVal && !nVal) {
        matchStatus.className = 'hidden';
        matchStatus.innerHTML = '';
        return;
    }

    if (cVal.length > 0 || nVal.length > 0) {
        if (nVal === cVal && nVal.length === 4) {
            matchStatus.className = 'mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200';
            matchStatus.innerHTML = `
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>New PIN and confirmation match.</span>
            `;
        } else if (cVal.length >= nVal.length && nVal !== cVal) {
            matchStatus.className = 'mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 bg-rose-50 text-rose-800 border border-rose-200';
            matchStatus.innerHTML = `
                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>Confirmation PIN does not match.</span>
            `;
        } else {
            matchStatus.className = 'hidden';
            matchStatus.innerHTML = '';
        }
    }
}

if (newPinInp && confirmPinInp) {
    newPinInp.addEventListener('input', checkPinMatch);
    confirmPinInp.addEventListener('input', checkPinMatch);
}

// Double submit prevention for Profile Details Form
const profileForm = document.getElementById('customerProfileForm');
if (profileForm) {
    profileForm.addEventListener('submit', function (e) {
        if (!profileForm.checkValidity()) return;
        const btn = document.getElementById('profileSubmitBtn');
        if (btn) {
            setTimeout(() => {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            }, 10);
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Saving Profile Details...</span>
            `;
        }
    });
}

// Double submit prevention for Security PIN Form
const pinForm = document.getElementById('customerPinForm');
if (pinForm) {
    pinForm.addEventListener('submit', function (e) {
        if (!pinForm.checkValidity()) return;
        const btn = document.getElementById('pinSubmitBtn');
        if (btn) {
            setTimeout(() => {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            }, 10);
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Updating PIN...</span>
            `;
        }
    });
}
</script>
