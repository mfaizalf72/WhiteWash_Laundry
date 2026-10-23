<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl ring-1 ring-slate-900/5">
        <div>
            <h2 class="text-center text-3xl font-display font-bold text-slate-900">Create your account</h2>
            <p class="mt-2 text-center text-sm text-slate-500">Register as a new customer with your mobile and PIN</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-md">
                <p class="text-sm text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form class="mt-8 space-y-5" action="<?= url('/register') ?>" method="POST">
            <?= \Faizal\LaundryLms\Helpers\Csrf::field() ?>
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
                    <input id="name" name="name" type="text" required maxlength="100" class="mt-1 block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" placeholder="Rahul Sharma">
                </div>
                <div>
                    <label for="mobile" class="block text-sm font-medium text-slate-700">Mobile Number</label>
                    <input id="mobile" name="mobile" type="tel" required maxlength="15" class="mt-1 block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" placeholder="9876543210">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-slate-700">Pickup Address</label>
                    <textarea id="address" name="address" required rows="2" class="mt-1 block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" placeholder="Flat / House No., Street, Landmark"></textarea>
                </div>
                <div>
                    <label for="pin" class="block text-sm font-medium text-slate-700">4-Digit PIN</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" required maxlength="4" pattern="\d{4}" class="mt-1 block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" placeholder="••••">
                    <p class="mt-1 text-xs text-slate-400">Choose any 4-digit PIN (avoid 1234, 4321, or repeated digits).</p>
                </div>
                <div>
                    <label for="confirm_pin" class="block text-sm font-medium text-slate-700">Confirm 4-Digit PIN</label>
                    <input id="confirm_pin" name="confirm_pin" type="password" inputmode="numeric" required maxlength="4" pattern="\d{4}" class="mt-1 block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" placeholder="••••">
                </div>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors">
                Create Account
            </button>
        </form>

        <div class="text-center pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">
                Already have an account? 
                <a href="<?= url('/login') ?>" class="font-semibold text-indigo-600 hover:text-indigo-500">Sign in</a>
            </p>
        </div>
    </div>
</div>
