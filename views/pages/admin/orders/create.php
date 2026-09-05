<?php use Faizal\LaundryLms\Helpers\Csrf; ?>

<div class="space-y-6 max-w-4xl mx-auto">
    <?php require_once __DIR__ . '/../../../components/flash.php'; ?>

    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <a href="/laundry-lms/orders" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                &larr; Back to Orders Directory
            </a>
            <h2 class="text-2xl font-bold text-slate-900 font-display">Create New Order</h2>
            <p class="text-sm text-slate-500 mt-1">Select customer, schedule pickup/delivery, and configure order items.</p>
        </div>
    </div>

    <form action="/laundry-lms/orders" method="POST" class="space-y-6">
        <?= Csrf::field() ?>

        <!-- Customer & Schedule Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">1. Customer & Scheduling Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Select Customer</label>
                    <select name="customer_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                        <option value="">Select Customer...</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['mobile']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pickup Date</label>
                    <input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Date (Optional)</label>
                    <input type="date" name="delivery_date" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                </div>
            </div>
        </div>

        <!-- Service & Item Details Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">2. Service & Item Configuration</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Laundry Item</label>
                    <select id="item_id" name="item_id" required onchange="updatePricePreview()" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                        <option value="">Select Item...</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Work Type</label>
                    <select id="work_type_id" name="work_type_id" required onchange="updatePricePreview()" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                        <option value="">Select Work Type...</option>
                        <?php foreach ($workTypes as $wt): ?>
                            <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Quantity / Weight</label>
                    <input type="number" step="0.01" min="0.01" id="quantity" name="quantity" value="1" required oninput="updatePricePreview()" placeholder="e.g. 2 or 3.5" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

            <!-- Server-Calculated Price Preview Box -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Configured Rate:</span>
                    <span id="rate_preview" class="font-mono font-semibold text-slate-800">Select Item & Work Type</span>
                </div>
                <div class="flex justify-between items-center text-sm border-t border-slate-200 pt-2">
                    <span class="text-slate-900 font-bold">Estimated Order Total:</span>
                    <span id="total_preview" class="font-mono font-bold text-lg text-slate-900">₹0.00</span>
                </div>
                <p class="text-xs text-slate-400 italic">Note: Final total amount is strictly calculated on the server.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Special Instructions (Optional)</label>
                <textarea name="special_instructions" rows="2" placeholder="e.g. Handle delicate silk fabric with extra care..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-slate-900"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/laundry-lms/orders" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-colors">
                Create Order
            </button>
        </div>
    </form>
</div>

<!-- Client-side Price Preview Matrix -->
<script>
const pricingMatrix = <?= json_encode($pricings, JSON_UNESCAPED_SLASHES) ?>;

function updatePricePreview() {
    const itemId = parseInt(document.getElementById('item_id').value);
    const workTypeId = parseInt(document.getElementById('work_type_id').value);
    const qty = parseFloat(document.getElementById('quantity').value) || 0;

    const rateElem = document.getElementById('rate_preview');
    const totalElem = document.getElementById('total_preview');

    if (!itemId || !workTypeId) {
        rateElem.innerText = 'Select Item & Work Type';
        totalElem.innerText = '₹0.00';
        return;
    }

    const matched = pricingMatrix.find(p => parseInt(p.item_id) === itemId && parseInt(p.work_type_id) === workTypeId);

    if (!matched) {
        rateElem.innerText = 'No pricing configured for this pair';
        rateElem.className = 'font-mono font-semibold text-rose-600';
        totalElem.innerText = 'N/A';
        return;
    }

    rateElem.className = 'font-mono font-semibold text-slate-800';
    const rate = parseFloat(matched.price);
    const typeLabel = matched.pricing_type === 'per_kg' ? 'per kg' : 'per item';
    rateElem.innerText = '₹' + rate.toFixed(2) + ' (' + typeLabel + ')';

    const subtotal = (rate * qty).toFixed(2);
    totalElem.innerText = '₹' + subtotal;
}
</script>
