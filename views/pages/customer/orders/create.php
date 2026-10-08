<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;

$pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
$deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
$freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 font-display">Request Laundry Pickup</h1>
            <p class="text-sm text-slate-500">Schedule a pickup date and specify your laundry items and services.</p>
        </div>
        <a href="/laundry-lms/customer/orders" class="text-xs font-semibold text-slate-600 hover:text-slate-900 underline">
            Cancel
        </a>
    </div>

    <!-- Free Pickup Info Alert -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r text-sm text-blue-800 flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <span class="font-bold">Pickup Policy:</span>
            <?php if ($freePickupLimit > 0): ?>
                Free pickup & delivery on orders above ₹<?= number_format($freePickupLimit, 2) ?>. Orders below this limit incur a ₹<?= number_format($pickupCharge + $deliveryCharge, 2) ?> service fee.
            <?php else: ?>
                Standard pickup fee: ₹<?= number_format($pickupCharge, 2) ?> | Delivery fee: ₹<?= number_format($deliveryCharge, 2) ?>.
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Pickup Form -->
    <form action="/laundry-lms/customer/orders/create" method="POST" id="customerOrderForm" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6">
        <?= Csrf::field() ?>

        <?php if (!empty($branches) && count($branches) > 1): ?>
        <!-- Servicing Branch Selection -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-800 font-display border-b border-slate-100 pb-2">Servicing Branch</h3>
            <div>
                <label for="branch_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Branch <span class="text-rose-500">*</span></label>
                <select id="branch_id" name="branch_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option value="">-- Choose a branch near you --</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int)$branch['id'] ?>">
                            <?= htmlspecialchars($branch['name']) ?> (<?= htmlspecialchars($branch['code']) ?>)<?= !empty($branch['address']) ? ' — ' . htmlspecialchars($branch['address']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Select the laundry branch that will pick up, clean, and deliver your garments.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 1: Schedule Dates -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-800 font-display border-b border-slate-100 pb-2">1. Schedule Dates</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="pickup_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pickup Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="pickup_date" name="pickup_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label for="delivery_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Target Delivery Date (Optional)</label>
                    <input type="date" id="delivery_date" name="delivery_date" min="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </div>

        <!-- Step 2: Multi-Item Picker -->
        <div class="space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <div>
                    <h3 class="text-base font-bold text-slate-800 font-display">2. Service & Laundry Items</h3>
                    <p class="text-xs text-slate-500">Add all the clothes or linens you would like us to clean.</p>
                </div>
                <button type="button" onclick="addCustomerItemRow()" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    + Add Item
                </button>
            </div>

            <div id="custItemsContainer" class="space-y-3">
                <!-- Dynamically generated rows -->
            </div>

            <div id="custDuplicateWarning" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-lg">
                Duplicate item and service combinations detected. Each item and treatment pair must be unique per order.
            </div>

            <!-- Price Preview Box -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-sm">
                <div class="flex justify-between text-slate-600 text-xs">
                    <span>Items Subtotal:</span>
                    <span id="custItemsSubtotal" class="font-mono font-semibold text-slate-800">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600 text-xs">
                    <span>Pickup & Delivery Charge:</span>
                    <span id="custDeliveryFee" class="font-mono font-semibold text-slate-800">₹0.00</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-slate-900">
                    <span>Estimated Total:</span>
                    <span id="custTotal" class="font-mono text-base text-blue-700">₹0.00</span>
                </div>
                <p class="text-[11px] text-slate-400 italic">Our staff will weigh and inspect garments upon pickup to confirm the final price before processing.</p>
            </div>
        </div>

        <!-- Step 3: Special Instructions -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-800 font-display border-b border-slate-100 pb-2">3. Special Instructions & Notes</h3>
            <div>
                <label for="special_instructions" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stain details, fabric care, or pickup notes</label>
                <textarea id="special_instructions" name="special_instructions" rows="2" placeholder="e.g. Handle silk shirt gently, call before arriving..."
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
            <a href="/laundry-lms/customer/orders" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" id="custSubmitBtn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                Submit Pickup Request
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

let custRowIndex = 0;

function addCustomerItemRow(defaultItem = null, defaultWorkType = null, defaultQty = 1) {
    const container = document.getElementById('custItemsContainer');
    const idx = custRowIndex++;

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
    <div class="p-3 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2 cust-item-row" id="cust_row_${idx}">
        <div class="flex justify-between items-center text-xs font-semibold text-slate-500">
            <span class="cust-row-num">Item #${idx + 1}</span>
            <button type="button" onclick="removeCustRow(${idx})" class="text-rose-500 hover:text-rose-700 font-medium">Remove</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Item</label>
                <select name="items[${idx}][item_id]" required onchange="calculateCustTotals()" class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-slate-300 bg-white cust-item-sel">
                    ${itemOptions}
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Service</label>
                <select name="items[${idx}][work_type_id]" required onchange="calculateCustTotals()" class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-slate-300 bg-white cust-wt-sel">
                    ${wtOptions}
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1 cust-qty-lbl">Quantity / Weight</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="items[${idx}][quantity]" value="${defaultQty}" min="0.01" step="0.01" required oninput="calculateCustTotals()" class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-slate-300 bg-white cust-qty-inp font-mono">
                    <span class="text-xs font-bold text-slate-800 font-mono whitespace-nowrap cust-sub-disp">₹0.00</span>
                </div>
            </div>
        </div>
        <div>
            <input type="text" name="items[${idx}][item_notes]" placeholder="Optional line notes (e.g. stain on collar)..." class="w-full px-2.5 py-1 text-xs rounded border border-slate-200 bg-white">
        </div>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHtml);
    updateCustRowNums();
    calculateCustTotals();
}

function removeCustRow(idx) {
    const row = document.getElementById('cust_row_' + idx);
    const rows = document.querySelectorAll('.cust-item-row');
    if (rows.length <= 1) {
        alert('Your pickup request must include at least one item.');
        return;
    }
    if (row) {
        row.remove();
        updateCustRowNums();
        calculateCustTotals();
    }
}

function updateCustRowNums() {
    const rows = document.querySelectorAll('.cust-item-row');
    rows.forEach((r, i) => {
        const lbl = r.querySelector('.cust-row-num');
        if (lbl) lbl.textContent = `Item #${i + 1}`;
    });
}

function calculateCustTotals() {
    const rows = document.querySelectorAll('.cust-item-row');
    let itemsSubtotal = 0.00;
    let hasDuplicates = false;
    const seen = new Set();

    rows.forEach(r => {
        const itemSelect = r.querySelector('.cust-item-sel');
        const wtSelect   = r.querySelector('.cust-wt-sel');
        const qtyInput   = r.querySelector('.cust-qty-inp');
        const subDisplay = r.querySelector('.cust-sub-disp');
        const qtyLabel   = r.querySelector('.cust-qty-lbl');

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
            return;
        }

        const isPerKg = pricing.pricing_type === 'per_kg';
        if (qtyLabel) {
            qtyLabel.textContent = isPerKg ? 'Weight (kg)' : 'Count (pcs)';
        }
        qtyInput.step = isPerKg ? '0.01' : '1';

        const unitRate = parseFloat(pricing.price) || 0;
        const lineTotal = Math.round(unitRate * qty * 100) / 100;
        subDisplay.textContent = `₹${lineTotal.toFixed(2)}`;
        itemsSubtotal += lineTotal;
    });

    itemsSubtotal = Math.round(itemsSubtotal * 100) / 100;
    document.getElementById('custItemsSubtotal').textContent = `₹${itemsSubtotal.toFixed(2)}`;

    let deliveryFee = 0.00;
    if (settingsConfig.freePickupLimit <= 0 || itemsSubtotal < settingsConfig.freePickupLimit) {
        deliveryFee = settingsConfig.pickupCharge + settingsConfig.deliveryCharge;
    }
    document.getElementById('custDeliveryFee').textContent = `₹${deliveryFee.toFixed(2)}`;

    const total = Math.round((itemsSubtotal + deliveryFee) * 100) / 100;
    document.getElementById('custTotal').textContent = `₹${total.toFixed(2)}`;

    const warn = document.getElementById('custDuplicateWarning');
    const submitBtn = document.getElementById('custSubmitBtn');
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

document.addEventListener('DOMContentLoaded', () => {
    addCustomerItemRow();
});
</script>
