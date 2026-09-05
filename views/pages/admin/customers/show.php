<div class="space-y-6">
    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <a href="/laundry-lms/customers" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                &larr; Back to Customers Directory
            </a>
            <h2 class="text-2xl font-bold text-slate-900 font-display"><?= htmlspecialchars($customer['name']) ?></h2>
            <p class="text-sm text-slate-500 mt-0.5">Registered Customer Profile</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= (int)$customer['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <?= (int)$customer['is_active'] === 1 ? 'Active Account' : 'Inactive Account' ?>
        </span>
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
