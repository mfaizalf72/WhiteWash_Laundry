<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;

$pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
$deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
$freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 font-display">Request Laundry Pickup</h1>
            <p class="text-sm text-slate-500">Schedule a pickup date and specify your laundry service preferences.</p>
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
    <form action="/laundry-lms/customer/orders/create" method="POST" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6">
        <?= Csrf::field() ?>

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

        <!-- Step 2: Select Items & Service -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-800 font-display border-b border-slate-100 pb-2">2. Service & Laundry Items</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="item_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Laundry Item <span class="text-rose-500">*</span></label>
                    <select id="item_id" name="item_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">-- Select Item --</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int)$item['id'] ?>"><?= Security::e($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="work_type_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Work / Service Type <span class="text-rose-500">*</span></label>
                    <select id="work_type_id" name="work_type_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="">-- Select Work Type --</option>
                        <?php foreach ($workTypes as $wt): ?>
                            <option value="<?= (int)$wt['id'] ?>"><?= Security::e($wt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="quantity" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estimated Quantity / Weight (kg or pcs) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0.1" id="quantity" name="quantity" placeholder="e.g. 5.0" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <p class="text-xs text-slate-400 mt-1">Specify weight in kg for Wash & Fold/Iron, or count in pcs for Dry Clean.</p>
                </div>
            </div>
        </div>

        <!-- Step 3: Special Instructions -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-800 font-display border-b border-slate-100 pb-2">3. Special Instructions & Notes</h3>
            <div>
                <label for="special_instructions" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stain details, fabric care, or pickup notes</label>
                <textarea id="special_instructions" name="special_instructions" rows="3" placeholder="e.g. Handle silk shirt gently, call before arriving..."
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
            <a href="/laundry-lms/customer/orders" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                Submit Pickup Request
            </button>
        </div>
    </form>

</div>
