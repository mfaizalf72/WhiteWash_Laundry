<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 font-display">Pricing Management</h2>
            <p class="text-sm text-slate-500 mt-1">Configure pricing rates per laundry item and work type combination.</p>
        </div>
        <button onclick="document.getElementById('createPricingModal').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Configure Price
        </button>
    </div>

    <!-- Pricing Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Laundry Item</th>
                        <th class="px-6 py-4">Work Type</th>
                        <th class="px-6 py-4">Pricing Type</th>
                        <th class="px-6 py-4">Price (₹)</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($pricings)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No pricing configurations found. Click "Configure Price" to add one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pricings as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($p['item_name']) ?></td>
                                <td class="px-6 py-4 text-slate-700 font-medium"><?= htmlspecialchars($p['work_type_name']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $p['pricing_type'] === 'per_kg' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                        <?= $p['pricing_type'] === 'per_kg' ? 'Per KG' : 'Per Item' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">₹<?= number_format((float)$p['price'], 2) ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditPricingModal(<?= $p['item_id'] ?>, <?= $p['work_type_id'] ?>, '<?= htmlspecialchars(addslashes($p['item_name'])) ?>', '<?= htmlspecialchars(addslashes($p['work_type_name'])) ?>', '<?= $p['pricing_type'] ?>', '<?= number_format((float)$p['price'], 2, '.', '') ?>')" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Edit</button>
                                    <form action="/laundry-lms/pricing/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this pricing configuration?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="item_id" value="<?= $p['item_id'] ?>">
                                        <input type="hidden" name="work_type_id" value="<?= $p['work_type_id'] ?>">
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

<!-- Create Pricing Modal -->
<div id="createPricingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add Pricing Configuration</h3>
            <button onclick="document.getElementById('createPricingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/pricing" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Laundry Item</label>
                <select name="item_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                    <option value="">Select Item...</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Work Type</label>
                <select name="work_type_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                    <option value="">Select Work Type...</option>
                    <?php foreach ($workTypes as $wt): ?>
                        <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Pricing Type</label>
                <select name="pricing_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                    <option value="per_item">Per Item</option>
                    <option value="per_kg">Per KG</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Price Amount (₹)</label>
                <input type="number" step="0.01" min="0" name="price" required placeholder="0.00" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createPricingModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Save Pricing</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Pricing Modal -->
<div id="editPricingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Edit Pricing</h3>
            <button onclick="document.getElementById('editPricingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="/laundry-lms/pricing/update" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="edit_pricing_item_id" name="item_id">
            <input type="hidden" id="edit_pricing_work_type_id" name="work_type_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Combination</label>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800" id="edit_pricing_label"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Pricing Type</label>
                <select id="edit_pricing_type" name="pricing_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                    <option value="per_item">Per Item</option>
                    <option value="per_kg">Per KG</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Price Amount (₹)</label>
                <input type="number" step="0.01" min="0" id="edit_pricing_price" name="price" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editPricingModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">Update Pricing</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditPricingModal(itemId, workTypeId, itemName, workTypeName, pricingType, price) {
    document.getElementById('edit_pricing_item_id').value = itemId;
    document.getElementById('edit_pricing_work_type_id').value = workTypeId;
    document.getElementById('edit_pricing_label').innerText = itemName + ' - ' + workTypeName;
    document.getElementById('edit_pricing_type').value = pricingType;
    document.getElementById('edit_pricing_price').value = price;
    document.getElementById('editPricingModal').classList.remove('hidden');
}
</script>
