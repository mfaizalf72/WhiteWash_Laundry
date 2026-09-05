<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Work Types</h2>
            <p class="text-sm text-slate-500 mt-1">Manage processing service types (e.g. Wash & Fold, Dry Cleaning, Steam Press).</p>
        </div>
        <button onclick="document.getElementById('createWorkTypeModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Work Type
        </button>
    </div>

    <!-- Work Types Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Work Type Name</th>
                        <th class="px-6 py-4">Created Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($workTypes)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                No work types found. Click "Add Work Type" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($workTypes as $wt): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">#<?= htmlspecialchars((string)$wt['id']) ?></td>
                                <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars($wt['name']) ?></td>
                                <td class="px-6 py-4 text-slate-500 text-xs"><?= htmlspecialchars(date('M d, Y', strtotime($wt['created_at']))) ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditWorkTypeModal(<?= $wt['id'] ?>, '<?= htmlspecialchars(addslashes($wt['name'])) ?>')" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Edit</button>
                                    <form action="/laundry-lms/work-types/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this work type?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $wt['id'] ?>">
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

<!-- Create Work Type Modal -->
<div id="createWorkTypeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add Work Type</h3>
            <button onclick="document.getElementById('createWorkTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/work-types" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Work Type Name</label>
                <input type="text" name="name" required placeholder="e.g. Wash & Iron, Dry Clean, Steam Press" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createWorkTypeModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Save Work Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Work Type Modal -->
<div id="editWorkTypeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Edit Work Type</h3>
            <button onclick="document.getElementById('editWorkTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/work-types/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="edit_wt_id" name="id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Work Type Name</label>
                <input type="text" id="edit_wt_name" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editWorkTypeModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Update Work Type</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditWorkTypeModal(id, name) {
    document.getElementById('edit_wt_id').value = id;
    document.getElementById('edit_wt_name').value = name;
    document.getElementById('editWorkTypeModal').classList.remove('hidden');
}
</script>
