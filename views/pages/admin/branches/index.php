<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <!-- Header Card -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Branch Management</h2>
            <p class="text-sm text-slate-500 mt-1">Manage operational laundry branches, facilities, and service locations.</p>
        </div>
        <button onclick="document.getElementById('createBranchModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Branch
        </button>
    </div>

    <!-- Search Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="/laundry-lms/admin/branches" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search branches by name, code, or phone..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-slate-900 outline-none">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/laundry-lms/admin/branches" class="bg-slate-50 hover:bg-slate-100 text-slate-500 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Branches Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Branch Details</th>
                        <th class="px-6 py-4">Code</th>
                        <th class="px-6 py-4">Contact Phone</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Created</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($branches)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No branches found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($branches as $b): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($b['name']) ?></div>
                                    <?php if (!empty($b['address'])): ?>
                                        <div class="text-xs text-slate-500 truncate max-w-xs"><?= htmlspecialchars($b['address']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 font-mono">
                                        <?= htmlspecialchars($b['code']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-600 text-xs">
                                    <?= !empty($b['phone']) ? htmlspecialchars($b['phone']) : '<span class="text-slate-400 italic">None</span>' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ((int)$b['is_active'] === 1): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    <?= date('M j, Y', strtotime($b['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button type="button" 
                                            onclick='openEditBranchModal(<?= json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">
                                        Edit
                                    </button>
                                    <span class="text-slate-200">|</span>
                                    <form action="/laundry-lms/admin/branches/toggle-status" method="POST" class="inline" onsubmit="return confirm('Toggle status for this branch?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="text-slate-500 hover:text-slate-800 font-medium text-xs">
                                            <?= ((int)$b['is_active'] === 1) ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <span class="text-slate-200">|</span>
                                    <form action="/laundry-lms/admin/branches/delete" method="POST" class="inline" onsubmit="return confirm('Delete this branch? (Will fail if orders or staff are attached)');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-medium text-xs">
                                            Delete
                                        </button>
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

<!-- Create Branch Modal -->
<div id="createBranchModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 space-y-5">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900 font-display">Add New Branch</h3>
            <button onclick="document.getElementById('createBranchModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/admin/branches" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Downtown Hub" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Code <span class="text-rose-500">*</span></label>
                <input type="text" name="code" required placeholder="e.g. DWTN" style="text-transform: uppercase;" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-slate-900 outline-none">
                <span class="text-[11px] text-slate-400">Short unique code (e.g., MAIN, DWTN, WEST)</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Contact Phone</label>
                <input type="text" name="phone" placeholder="e.g. 9876543210" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Address</label>
                <textarea name="address" rows="2" placeholder="Full street address of the branch..." class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none"></textarea>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="create_is_active" value="1" checked class="w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <label for="create_is_active" class="text-sm font-medium text-slate-700">Active (Accepts Orders)</label>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('createBranchModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div id="editBranchModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 space-y-5">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900 font-display">Edit Branch</h3>
            <button onclick="document.getElementById('editBranchModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/admin/branches/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" id="edit_branch_id">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="edit_branch_name" required class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Code <span class="text-rose-500">*</span></label>
                <input type="text" name="code" id="edit_branch_code" required style="text-transform: uppercase;" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Contact Phone</label>
                <input type="text" name="phone" id="edit_branch_phone" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Address</label>
                <textarea name="address" id="edit_branch_address" rows="2" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 outline-none"></textarea>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="edit_branch_is_active" value="1" class="w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <label for="edit_branch_is_active" class="text-sm font-medium text-slate-700">Active</label>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('editBranchModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm">Update Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditBranchModal(b) {
    document.getElementById('edit_branch_id').value = b.id;
    document.getElementById('edit_branch_name').value = b.name;
    document.getElementById('edit_branch_code').value = b.code;
    document.getElementById('edit_branch_phone').value = b.phone || '';
    document.getElementById('edit_branch_address').value = b.address || '';
    document.getElementById('edit_branch_is_active').checked = parseInt(b.is_active) === 1;
    document.getElementById('editBranchModal').classList.remove('hidden');
}
</script>
