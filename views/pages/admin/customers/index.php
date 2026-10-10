<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Customer Directory</h2>
            <p class="text-sm text-slate-500 mt-1">Manage registered laundry customers and contact details.</p>
        </div>
        <button onclick="document.getElementById('createCustomerModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Customer
        </button>
    </div>

    <!-- Search Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="/laundry-lms/customers" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search customers by name or mobile..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-slate-900">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/laundry-lms/customers" class="bg-slate-50 hover:bg-slate-100 text-slate-500 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Customer Name</th>
                        <th class="px-6 py-4">Mobile Number</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No customers found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="/laundry-lms/customers/show?id=<?= $c['id'] ?>" class="font-bold text-slate-900 hover:text-indigo-600">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-600 text-xs"><?= htmlspecialchars($c['mobile']) ?></td>
                                <td class="px-6 py-4 text-slate-500 text-xs truncate max-w-xs"><?= htmlspecialchars($c['address'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= (int)$c['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                        <?= (int)$c['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="/laundry-lms/customers/show?id=<?= $c['id'] ?>" class="text-slate-600 hover:text-slate-900 font-medium text-xs">View</a>
                                    <button onclick="openEditCustomerModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Edit</button>
                                    <form action="/laundry-lms/customers/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-medium text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Customer Modal -->
<div id="createCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add New Customer</h3>
            <button onclick="document.getElementById('createCustomerModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/customers" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                <input type="text" name="name" required placeholder="John Doe" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile Number</label>
                <input type="tel" name="mobile" required placeholder="9876543210" maxlength="15" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Login PIN (Default: 1234)</label>
                <input type="password" name="pin" placeholder="1234" maxlength="4" pattern="\d{4}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                <p class="text-xs text-slate-500 mt-1">Leave empty to assign default PIN 1234.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <textarea name="address" rows="2" placeholder="Customer physical address..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createCustomerModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Edit Customer</h3>
            <button onclick="document.getElementById('editCustomerModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/customers/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="edit_cust_id" name="id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                <input type="text" id="edit_cust_name" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile Number</label>
                <input type="tel" id="edit_cust_mobile" name="mobile" required maxlength="15" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <textarea id="edit_cust_address" name="address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="edit_cust_is_active" name="is_active" value="1" class="rounded text-slate-900 focus:ring-slate-900">
                <label for="edit_cust_is_active" class="text-sm font-medium text-slate-700">Account Active</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editCustomerModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Update Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCustomerModal(cust) {
    document.getElementById('edit_cust_id').value = cust.id;
    document.getElementById('edit_cust_name').value = cust.name;
    document.getElementById('edit_cust_mobile').value = cust.mobile;
    document.getElementById('edit_cust_address').value = cust.address || '';
    document.getElementById('edit_cust_is_active').checked = parseInt(cust.is_active) === 1;
    document.getElementById('editCustomerModal').classList.remove('hidden');
}
</script>
