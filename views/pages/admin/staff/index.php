<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <!-- Header Card -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Staff Management</h2>
            <p class="text-sm text-slate-500 mt-1">Manage operational staff accounts, authentication credentials, and system access.</p>
        </div>
        <button onclick="document.getElementById('createStaffModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Staff Member
        </button>
    </div>

    <!-- Search Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="/laundry-lms/admin/staff" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search staff by name or mobile..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-slate-900 outline-none">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/laundry-lms/admin/staff" class="bg-slate-50 hover:bg-slate-100 text-slate-500 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Staff Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Staff Member</th>
                        <th class="px-6 py-4">Mobile Number</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Created Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No staff accounts found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staffMembers as $s): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="text-xs text-slate-400">Role: <?= htmlspecialchars($s['role']) ?></div>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-600 text-xs"><?= htmlspecialchars($s['mobile']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= (int)$s['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                        <?= (int)$s['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    <?= htmlspecialchars(date('M d, Y', strtotime($s['created_at']))) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button onclick="openEditStaffModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">
                                        Edit
                                    </button>

                                    <form action="/laundry-lms/admin/staff/toggle-status" method="POST" class="inline" onsubmit="return confirm('<?= (int)$s['is_active'] === 1 ? 'Deactivate this staff account? They will not be able to log in.' : 'Reactivate this staff account?' ?>');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="<?= (int)$s['is_active'] === 1 ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900' ?> font-medium text-xs">
                                            <?= (int)$s['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
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

<!-- Create Staff Modal -->
<div id="createStaffModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add New Staff Member</h3>
            <button onclick="document.getElementById('createStaffModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>
        <form action="/laundry-lms/admin/staff" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required maxlength="100" placeholder="Staff Name" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile Number <span class="text-rose-500">*</span></label>
                <input type="tel" name="mobile" required placeholder="10-15 digits" maxlength="15" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">4-Digit PIN <span class="text-rose-500">*</span></label>
                <input type="password" name="pin" required maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm font-mono tracking-widest text-center">
                <p class="text-xs text-slate-400 mt-1">Must be exactly 4 numeric digits. Stored securely as bcrypt hash.</p>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="create_is_active" name="is_active" value="1" checked class="rounded text-slate-900 focus:ring-slate-900">
                <label for="create_is_active" class="text-sm font-medium text-slate-700">Account is Active</label>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onclick="document.getElementById('createStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-colors shadow-sm">Save Staff Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Edit Staff Member</h3>
            <button onclick="document.getElementById('editStaffModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>
        <form action="/laundry-lms/admin/staff/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="edit_staff_id" name="id" value="">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_staff_name" name="name" required maxlength="100" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile Number <span class="text-rose-500">*</span></label>
                <input type="tel" id="edit_staff_mobile" name="mobile" required maxlength="15" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">New 4-Digit PIN (Optional)</label>
                <input type="password" id="edit_staff_pin" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 outline-none text-sm font-mono tracking-widest text-center">
                <p class="text-xs text-slate-400 mt-1">Leave blank to keep existing PIN unchanged.</p>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_staff_is_active" name="is_active" value="1" class="rounded text-slate-900 focus:ring-slate-900">
                <label for="edit_staff_is_active" class="text-sm font-medium text-slate-700">Account is Active</label>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onclick="document.getElementById('editStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-colors shadow-sm">Update Staff Member</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditStaffModal(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_staff_name').value = staff.name;
    document.getElementById('edit_staff_mobile').value = staff.mobile;
    document.getElementById('edit_staff_pin').value = '';
    document.getElementById('edit_staff_is_active').checked = (parseInt(staff.is_active) === 1);
    document.getElementById('editStaffModal').classList.remove('hidden');
}
</script>
