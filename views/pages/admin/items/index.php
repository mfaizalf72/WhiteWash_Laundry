<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Laundry Items</h2>
            <p class="text-sm text-slate-500 mt-1">Manage laundry garment items available in your catalog.</p>
        </div>
        <button onclick="document.getElementById('createItemModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add New Item
        </button>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Item Name</th>
                        <th class="px-6 py-4">Created Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                No laundry items found. Click "Add New Item" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">#<?= htmlspecialchars((string)$item['id']) ?></td>
                                <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-6 py-4 text-slate-500 text-xs"><?= htmlspecialchars(date('M d, Y', strtotime($item['created_at']))) ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Edit</button>
                                    <form action="/laundry-lms/items/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this laundry item?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
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

<!-- Create Item Modal -->
<div id="createItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add Laundry Item</h3>
            <button onclick="document.getElementById('createItemModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/items" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Item Name</label>
                <input type="text" name="name" required placeholder="e.g. Shirt, Trousers, Saree" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createItemModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Edit Laundry Item</h3>
            <button onclick="document.getElementById('editItemModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/items/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="edit_item_id" name="id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Item Name</label>
                <input type="text" id="edit_item_name" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Update Item</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name) {
    document.getElementById('edit_item_id').value = id;
    document.getElementById('edit_item_name').value = name;
    document.getElementById('editItemModal').classList.remove('hidden');
}
</script>
