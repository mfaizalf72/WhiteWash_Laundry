<div class="space-y-6">
    <?php if (isset($_SESSION['temp_pin_modal']) && (int)$_SESSION['temp_pin_modal']['customer_id'] === (int)$customer['id']): ?>
        <div class="bg-indigo-50 border-2 border-indigo-200 rounded-2xl p-6 shadow-md space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">🔑</div>
                <div>
                    <h4 class="text-base font-bold text-indigo-950">Temporary PIN Generated</h4>
                    <p class="text-xs text-indigo-700">Communicate this temporary PIN to the customer securely via phone or in person.</p>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-indigo-100 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 uppercase tracking-wider block">Temporary 4-Digit PIN</span>
                    <span class="font-mono text-3xl font-extrabold text-indigo-600 tracking-widest"><?= htmlspecialchars($_SESSION['temp_pin_modal']['temp_pin']) ?></span>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200">Forced Reset on Login</span>
            </div>
            <p class="text-xs text-slate-500">
                <strong>Security Notice:</strong> This PIN will not be shown again. It is not written to audit logs, notification contents, or persistent storage.
            </p>
        </div>
        <?php unset($_SESSION['temp_pin_modal']); ?>
    <?php endif; ?>

    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <a href="/laundry-lms/customers" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                &larr; Back to Customers Directory
            </a>
            <h2 class="text-2xl font-bold text-slate-900 font-display"><?= htmlspecialchars($customer['name']) ?></h2>
            <p class="text-sm text-slate-500 mt-0.5">Registered Customer Profile</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= (int)$customer['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                <?= (int)$customer['is_active'] === 1 ? 'Active Account' : 'Inactive Account' ?>
            </span>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <button type="button" onclick="document.getElementById('resetPinModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-colors">
                    Reset PIN & Unlock
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Details Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Contact Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Customer ID</span>
                <span class="font-mono text-slate-800 font-bold text-base mt-1 block">#<?= htmlspecialchars((string)$customer['id']) ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Role</span>
                <span class="capitalize font-semibold text-slate-800 mt-1 block"><?= htmlspecialchars($customer['role']) ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Mobile Number</span>
                <span class="font-mono text-slate-800 mt-1 block font-medium"><?= htmlspecialchars($customer['mobile']) ?></span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Member Since</span>
                <span class="text-slate-800 mt-1 block font-medium"><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($customer['created_at']))) ?></span>
            </div>
            <div class="md:col-span-2">
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Physical Address</span>
                <p class="text-slate-700 mt-1 font-medium bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <?= !empty($customer['address']) ? nl2br(htmlspecialchars($customer['address'])) : '<em class="text-slate-400">No physical address provided.</em>' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
<!-- Reset PIN Confirmation Modal -->
<div id="resetPinModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Reset Customer PIN</h3>
            <button type="button" onclick="document.getElementById('resetPinModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-slate-600">
            <p>Are you sure you want to reset the login PIN for <strong class="text-slate-900"><?= htmlspecialchars($customer['name']) ?></strong> (<span class="font-mono"><?= htmlspecialchars($customer['mobile']) ?></span>)?</p>
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-1">
                <p class="font-semibold">Security Actions:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Generates a secure, random temporary PIN.</li>
                    <li>Invalidates all existing active sessions for this customer.</li>
                    <li>Clears any active login lockouts on their mobile number.</li>
                    <li>Requires the customer to establish a new PIN upon next login.</li>
                </ul>
            </div>
        </div>
        <form action="/laundry-lms/admin/customers/reset-pin" method="POST" class="pt-2">
            <?= \Faizal\LaundryLms\Helpers\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('resetPinModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition-colors">Confirm & Reset PIN</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
