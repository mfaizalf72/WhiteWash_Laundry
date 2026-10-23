<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;
?>

<div class="max-w-md mx-auto my-6 sm:my-10 bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">

    <!-- Header & Badge -->
    <div class="text-center space-y-2">
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mx-auto border border-amber-200/80 shadow-xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold font-display text-slate-900 tracking-tight">Establish Secure PIN</h2>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">A personalized 4-digit PIN is required for your account before you can access the customer portal. Please choose a new private PIN.</p>
    </div>

    <!-- Security Guidance Note -->
    <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3 text-xs text-slate-600 space-y-1">
        <div class="font-semibold text-slate-700 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>PIN Requirements:</span>
        </div>
        <p class="pl-5 text-slate-500">Must be exactly 4 numeric digits. The default PIN (1234), sequential (4321), and repeated digits (0000) are blocked.</p>
    </div>

    <form action="<?= url('/customer/force-pin-update') ?>" method="POST" id="forcePinForm" class="space-y-4">
        <?= Csrf::field() ?>

        <div>
            <label for="new_pin" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                New 4-Digit PIN <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <input type="password" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" required placeholder="••••"
                       class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono tracking-widest text-center font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                <button type="button" onclick="toggleForcePinField('new_pin', this)" aria-label="Toggle New PIN visibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
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
                <input type="password" id="confirm_pin" name="confirm_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" required placeholder="••••"
                       class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-mono tracking-widest text-center font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                <button type="button" onclick="toggleForcePinField('confirm_pin', this)" aria-label="Toggle Confirm PIN visibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
                    <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                </button>
            </div>
            <!-- Live PIN Match Indicator -->
            <div id="forcePinMatchStatus" class="hidden mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 transition-all"></div>
        </div>

        <button type="submit" id="forcePinSubmitBtn" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
            <span>Update PIN & Access Account</span>
        </button>
    </form>

    <div class="text-center pt-2">
        <a href="/laundry-lms/logout" class="text-xs text-slate-400 hover:text-rose-600 font-medium transition-colors">Sign Out</a>
    </div>
</div>

<script>
function toggleForcePinField(inputId, btn) {
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

// Live PIN match validation check for forced PIN update
const forceNewPinInp = document.getElementById('new_pin');
const forceConfirmPinInp = document.getElementById('confirm_pin');
const forceMatchStatus = document.getElementById('forcePinMatchStatus');

function checkForcePinMatch() {
    if (!forceNewPinInp || !forceConfirmPinInp || !forceMatchStatus) return;
    const nVal = forceNewPinInp.value.trim();
    const cVal = forceConfirmPinInp.value.trim();

    if (!cVal && !nVal) {
        forceMatchStatus.className = 'hidden';
        forceMatchStatus.innerHTML = '';
        return;
    }

    if (cVal.length > 0 || nVal.length > 0) {
        if (nVal === cVal && nVal.length === 4) {
            forceMatchStatus.className = 'mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200';
            forceMatchStatus.innerHTML = `
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>New PIN and confirmation match.</span>
            `;
        } else if (cVal.length >= nVal.length && nVal !== cVal) {
            forceMatchStatus.className = 'mt-1.5 text-xs font-semibold py-1 px-2.5 rounded-lg flex items-center gap-1.5 bg-rose-50 text-rose-800 border border-rose-200';
            forceMatchStatus.innerHTML = `
                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>Confirmation PIN does not match.</span>
            `;
        } else {
            forceMatchStatus.className = 'hidden';
            forceMatchStatus.innerHTML = '';
        }
    }
}

if (forceNewPinInp && forceConfirmPinInp) {
    forceNewPinInp.addEventListener('input', checkForcePinMatch);
    forceConfirmPinInp.addEventListener('input', checkForcePinMatch);
}

// Double submit prevention
const forcePinForm = document.getElementById('forcePinForm');
if (forcePinForm) {
    forcePinForm.addEventListener('submit', function (e) {
        if (!forcePinForm.checkValidity()) return;
        const btn = document.getElementById('forcePinSubmitBtn');
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
