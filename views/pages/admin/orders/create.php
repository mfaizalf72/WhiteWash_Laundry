<?php 
use Faizal\LaundryLms\Helpers\Csrf; 
$pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
$deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
$freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <a href="/laundry-lms/orders" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                &larr; Back to Orders Directory
            </a>
            <h2 class="text-2xl font-bold text-slate-900 font-display">Create New Order</h2>
            <p class="text-sm text-slate-500 mt-1">Select customer, schedule dates, and configure multiple order items.</p>
        </div>
    </div>

    <form action="/laundry-lms/orders" method="POST" id="orderForm" class="space-y-6">
        <?= Csrf::field() ?>

        <!-- Customer & Schedule Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">1. Customer & Scheduling Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Select Customer <span class="text-rose-500">*</span></label>
                    <select name="customer_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                        <option value="">Select Customer...</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['mobile']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pickup Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Date (Optional)</label>
                    <input type="date" name="delivery_date" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                </div>
            </div>
        </div>

        <!-- Multi-Item Configuration Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">2. Order Items & Services</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Add multiple garments, select service treatments, and record item-specific notes.</p>
                </div>
                <button type="button" onclick="addItemRow()" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Another Item
                </button>
            </div>

            <!-- Dynamic Lines Container -->
            <div id="itemsContainer" class="space-y-4">
                <!-- Rows injected via JavaScript -->
            </div>

            <!-- Duplicate Warning Alert -->
            <div id="duplicateWarning" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-xl">
                Warning: Duplicate item and service combinations detected. Each item and treatment pair must be unique per order.
            </div>

            <!-- Financial Live Summary -->
            <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2.5 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Items Subtotal:</span>
                    <span id="summaryItemsSubtotal" class="font-mono font-semibold text-slate-800">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Pickup & Delivery Fee:</span>
                    <span id="summaryDeliveryFee" class="font-mono font-semibold text-slate-800">₹0.00</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                    <span>Estimated Order Total:</span>
                    <span id="summaryTotal" class="font-mono text-xl text-indigo-700">₹0.00</span>
                </div>
                <p class="text-xs text-slate-400 italic">Final pricing and fees are strictly verified server-side on submission.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">General Order Instructions (Optional)</label>
                <textarea name="special_instructions" rows="2" placeholder="e.g. Call before arrival, leave at reception..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 text-sm"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/laundry-lms/orders" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</a>
            <button type="submit" id="submitBtn" class="px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-colors">
                Create Order
            </button>
        </div>
    </form>
</div>

<script>
const itemsCatalog    = <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const workTypesCatalog = <?= json_encode($workTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const pricingMatrix   = <?= json_encode($pricings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const settingsConfig  = {
    pickupCharge: parseFloat(<?= json_encode($pickupCharge) ?>) || 0,
    deliveryCharge: parseFloat(<?= json_encode($deliveryCharge) ?>) || 0,
    freePickupLimit: parseFloat(<?= json_encode($freePickupLimit) ?>) || 0
};

let rowIndex = 0;

function addItemRow(defaultItem = null, defaultWorkType = null, defaultQty = 1) {
    const container = document.getElementById('itemsContainer');
    const idx = rowIndex++;

    let itemOptions = '<option value="">-- Select Item --</option>';
    itemsCatalog.forEach(it => {
        const sel = defaultItem && parseInt(defaultItem) === parseInt(it.id) ? 'selected' : '';
        itemOptions += `<option value="${it.id}" ${sel}>${escapeHtml(it.name)}</option>`;
    });

    let wtOptions = '<option value="">-- Select Service --</option>';
    workTypesCatalog.forEach(wt => {
        const sel = defaultWorkType && parseInt(defaultWorkType) === parseInt(wt.id) ? 'selected' : '';
        wtOptions += `<option value="${wt.id}" ${sel}>${escapeHtml(wt.name)}</option>`;
    });

    const rowHtml = `
    <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm space-y-3 item-row" id="row_${idx}" data-index="${idx}">
        <div class="flex justify-between items-center">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 row-number">Item Line</span>
            <button type="button" onclick="removeItemRow(${idx})" class="text-xs text-rose-500 hover:text-rose-700 font-semibold flex items-center gap-1 remove-btn">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Remove
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Item <span class="text-rose-500">*</span></label>
                <select name="items[${idx}][item_id]" required onchange="calculateOrderTotals()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 item-select">
                    ${itemOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Service / Work Type <span class="text-rose-500">*</span></label>
                <select name="items[${idx}][work_type_id]" required onchange="calculateOrderTotals()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 work-type-select">
                    ${wtOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1 qty-label">Quantity / Weight <span class="text-rose-500">*</span></label>
                <input type="number" name="items[${idx}][quantity]" value="${defaultQty}" min="0.01" step="0.01" required oninput="calculateOrderTotals()" placeholder="e.g. 2 or 3.5" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900 qty-input font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Line Total</label>
                <div class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-800 line-subtotal-display">
                    ₹0.00
                </div>
            </div>
        </div>
        <div>
            <input type="text" name="items[${idx}][item_notes]" placeholder="Line notes (e.g. Silk fabric, collar stain, fragile buttons)..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 focus:ring-1 focus:ring-slate-400">
        </div>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHtml);
    updateRowLabels();
    calculateOrderTotals();
}

function removeItemRow(idx) {
    const row = document.getElementById('row_' + idx);
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) {
        alert('An order must contain at least one item line.');
        return;
    }
    if (row) {
        row.remove();
        updateRowLabels();
        calculateOrderTotals();
    }
}

function updateRowLabels() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((r, i) => {
        const lbl = r.querySelector('.row-number');
        if (lbl) lbl.textContent = `Item Line #${i + 1}`;
    });
}

function calculateOrderTotals() {
    const rows = document.querySelectorAll('.item-row');
    let itemsSubtotal = 0.00;
    let hasDuplicates = false;
    const seen = new Set();

    rows.forEach(r => {
        const itemSelect = r.querySelector('.item-select');
        const wtSelect   = r.querySelector('.work-type-select');
        const qtyInput   = r.querySelector('.qty-input');
        const subDisplay = r.querySelector('.line-subtotal-display');
        const qtyLabel   = r.querySelector('.qty-label');

        const itemId     = parseInt(itemSelect.value) || 0;
        const workTypeId = parseInt(wtSelect.value) || 0;
        const qty        = parseFloat(qtyInput.value) || 0;

        if (itemId && workTypeId) {
            const pairKey = `${itemId}_${workTypeId}`;
            if (seen.has(pairKey)) {
                hasDuplicates = true;
                r.classList.add('border-rose-300', 'bg-rose-50/20');
            } else {
                seen.add(pairKey);
                r.classList.remove('border-rose-300', 'bg-rose-50/20');
            }
        }

        const pricing = pricingMatrix.find(p => parseInt(p.item_id) === itemId && parseInt(p.work_type_id) === workTypeId);

        if (!pricing || qty <= 0) {
            subDisplay.textContent = '₹0.00';
            if (qtyLabel) qtyLabel.innerHTML = 'Quantity / Weight <span class="text-rose-500">*</span>';
            return;
        }

        const isPerKg = pricing.pricing_type === 'per_kg';
        if (qtyLabel) {
            qtyLabel.innerHTML = isPerKg ? 'Weight (kg, decimal) <span class="text-rose-500">*</span>' : 'Quantity (pcs, whole number) <span class="text-rose-500">*</span>';
        }
        qtyInput.step = isPerKg ? '0.01' : '1';

        const unitRate = parseFloat(pricing.price) || 0;
        const lineTotal = Math.round(unitRate * qty * 100) / 100;
        subDisplay.textContent = `₹${lineTotal.toFixed(2)} (${isPerKg ? '₹' + unitRate.toFixed(2) + '/kg' : '₹' + unitRate.toFixed(2) + '/pc'})`;
        itemsSubtotal += lineTotal;
    });

    itemsSubtotal = Math.round(itemsSubtotal * 100) / 100;
    document.getElementById('summaryItemsSubtotal').textContent = `₹${itemsSubtotal.toFixed(2)}`;

    let deliveryFee = 0.00;
    if (settingsConfig.freePickupLimit <= 0 || itemsSubtotal < settingsConfig.freePickupLimit) {
        deliveryFee = settingsConfig.pickupCharge + settingsConfig.deliveryCharge;
    }
    document.getElementById('summaryDeliveryFee').textContent = `₹${deliveryFee.toFixed(2)}`;

    const total = Math.round((itemsSubtotal + deliveryFee) * 100) / 100;
    document.getElementById('summaryTotal').textContent = `₹${total.toFixed(2)}`;

    const warn = document.getElementById('duplicateWarning');
    const submitBtn = document.getElementById('submitBtn');
    if (hasDuplicates) {
        warn.classList.remove('hidden');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        warn.classList.add('hidden');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Initialize with 1 empty row on load
document.addEventListener('DOMContentLoaded', () => {
    addItemRow();
});
</script>
