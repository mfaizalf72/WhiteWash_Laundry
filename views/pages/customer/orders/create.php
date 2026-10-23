<?php
use Faizal\LaundryLms\Helpers\Csrf;
use Faizal\LaundryLms\Helpers\Security;

$pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
$deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
$freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);
$customerAddress = trim((string)($customer['address'] ?? ''));
$customerMobile  = trim((string)($customer['mobile'] ?? ''));
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <a href="/laundry-lms/customer/orders" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors gap-1 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Back to My Orders</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 font-display tracking-tight">Request Laundry Pickup</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>New Pickup</span>
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Schedule a convenient pickup date and specify your garments or linen care preferences.</p>
        </div>
        <div class="shrink-0 self-start sm:self-auto">
            <a href="/laundry-lms/customer/orders" class="text-xs font-semibold text-slate-500 hover:text-slate-800 underline transition-colors">
                Cancel
            </a>
        </div>
    </div>

    <!-- Pickup & Delivery Location Card -->
    <?php if (!empty($customerAddress)): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pickup & Delivery Address</span>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">On File</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-900 mt-1"><?= Security::e($customerAddress) ?></p>
                    <?php if (!empty($customerMobile)): ?>
                        <p class="text-xs text-slate-400 mt-0.5">Registered contact: <span class="font-mono text-slate-600"><?= Security::e($customerMobile) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="shrink-0 text-left sm:text-right">
                <a href="/laundry-lms/customer/profile" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline gap-1">
                    <span>Change in Profile</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-amber-50/90 rounded-2xl border border-amber-200/90 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700 border border-amber-200 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-900">No Pickup Address on File</span>
                    <p class="text-xs text-amber-800 mt-1">Please add your address in your Profile or specify your complete street address in the Special Instructions box below so our driver can reach you.</p>
                </div>
            </div>
            <div class="shrink-0">
                <a href="/laundry-lms/customer/profile" class="inline-flex items-center px-3.5 py-1.5 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 rounded-xl border border-amber-300 transition-colors gap-1">
                    <span>Add Address</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delivery Policy Banner -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-4 sm:p-5 text-sm text-blue-900 flex items-start space-x-3.5 shadow-xs">
        <div class="p-2 rounded-xl bg-blue-100/80 text-blue-700 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="space-y-0.5">
            <span class="font-bold text-blue-950 font-display">Pickup & Delivery Policy:</span>
            <?php if ($freePickupLimit > 0): ?>
                <p class="text-xs text-blue-800">
                    Free pickup & delivery on orders of <strong class="font-mono font-bold">₹<?= number_format($freePickupLimit, 2) ?></strong> or more.
                    Orders below this threshold incur a ₹<?= number_format($pickupCharge + $deliveryCharge, 2) ?> combined service fee (₹<?= number_format($pickupCharge, 2) ?> pickup + ₹<?= number_format($deliveryCharge, 2) ?> delivery).
                </p>
            <?php else: ?>
                <p class="text-xs text-blue-800">
                    Standard pickup fee: <strong class="font-mono font-bold">₹<?= number_format($pickupCharge, 2) ?></strong> | Standard delivery fee: <strong class="font-mono font-bold">₹<?= number_format($deliveryCharge, 2) ?></strong>.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Pickup Form -->
    <form action="/laundry-lms/customer/orders/create" method="POST" id="customerOrderForm" class="space-y-6">
        <?= Csrf::field() ?>

        <?php if (!empty($branches) && count($branches) > 1): ?>
        <!-- Servicing Branch Selection -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800 font-display flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>Servicing Branch</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Select the laundry branch that will pick up, clean, and fulfill your garments.</p>
            </div>
            <div>
                <label for="branch_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Select Branch <span class="text-rose-500">*</span></label>
                <select id="branch_id" name="branch_id" required class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs">
                    <option value="">-- Choose a branch near you --</option>
                    <?php
                    $defaultBranchId = \Faizal\LaundryLms\Models\Branch::getDefaultId();
                    foreach ($branches as $branch):
                        $isSelected = ((int)$branch['id'] === (int)$defaultBranchId);
                    ?>
                        <option value="<?= (int)$branch['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($branch['name']) ?> (<?= htmlspecialchars($branch['code']) ?>)<?= !empty($branch['address']) ? ' — ' . htmlspecialchars($branch['address']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 1: Schedule Dates -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800 font-display flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>1. Schedule Dates</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Select when our courier should collect your laundry and your optional target delivery date.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="pickup_date_display" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Pickup Date <span class="text-rose-500">*</span></label>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">DD/MM/YYYY</span>
                    </div>
                    <div class="relative flex items-center">
                        <input type="text" id="pickup_date_display" placeholder="DD/MM/YYYY" maxlength="10" value="<?= date('d/m/Y') ?>" required
                               class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs"
                               autocomplete="off">
                        <button type="button" id="pickup_calendar_btn" aria-label="Open pickup date calendar" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-blue-600 transition-colors focus:outline-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>
                        <input type="date" id="pickup_date" name="pickup_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required style="position: absolute; width: 0; height: 0; opacity: 0; pointer-events: none;" tabindex="-1" aria-hidden="true">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Our driver will arrive to collect garments on this day.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="delivery_date_display" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Target Delivery Date <span class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">DD/MM/YYYY</span>
                    </div>
                    <div class="relative flex items-center">
                        <input type="text" id="delivery_date_display" placeholder="DD/MM/YYYY" maxlength="10"
                               class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs"
                               autocomplete="off">
                        <button type="button" id="delivery_calendar_btn" aria-label="Open delivery date calendar" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-blue-600 transition-colors focus:outline-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>
                        <input type="date" id="delivery_date" name="delivery_date" min="<?= date('Y-m-d') ?>" style="position: absolute; width: 0; height: 0; opacity: 0; pointer-events: none;" tabindex="-1" aria-hidden="true">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Standard turnaround is typically 24–48 hours after pickup.</p>
                </div>
            </div>
        </div>

        <!-- Step 2: Multi-Item Picker -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-800 font-display flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>2. Service & Laundry Items</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Select garments or items to be cleaned, choose the service treatment, and specify quantities.</p>
                </div>
                <button type="button" onclick="addCustomerItemRow()" class="self-start sm:self-auto px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 border border-blue-200/70 shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>+ Add Item</span>
                </button>
            </div>

            <div id="custItemsContainer" class="space-y-3">
                <!-- Dynamically generated rows -->
            </div>

            <div id="custDuplicateWarning" class="hidden p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-xl flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Duplicate item and service combinations detected. Each item and treatment pair must be unique per order.</span>
            </div>
        </div>

        <!-- Step 3: Special Instructions -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-3">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800 font-display flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>3. Special Instructions & Care Notes</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Provide gate codes, stain locations, fabric care requests, or pickup notes.</p>
            </div>
            <div>
                <label for="special_instructions" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Care notes or pickup directions <span class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                <textarea id="special_instructions" name="special_instructions" rows="2" placeholder="e.g. Call upon arrival, handle silk blouse with care, stain on white collar..."
                          class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/50 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all shadow-xs placeholder:text-slate-400"></textarea>
            </div>
        </div>

        <!-- Step 4: Estimated Summary & Submission -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800 font-display flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span>Estimated Price Summary</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Live estimated total based on currently configured service rates.</p>
            </div>

            <!-- Free Delivery Progress (dynamic) -->
            <div id="custFreeDeliveryProgress"></div>

            <!-- Financial Breakdown Table -->
            <div class="bg-slate-50/70 border border-slate-200/80 rounded-xl p-4 space-y-2.5 text-sm">
                <div class="flex justify-between items-center text-slate-600 text-xs sm:text-sm">
                    <span>Items Subtotal:</span>
                    <span id="custItemsSubtotal" class="font-mono font-bold text-slate-800 tabular-nums">₹0.00</span>
                </div>
                <div class="flex justify-between items-center text-slate-600 text-xs sm:text-sm">
                    <span>Pickup & Delivery Fee:</span>
                    <span id="custDeliveryFee" class="font-mono font-bold text-slate-800 tabular-nums">₹0.00</span>
                </div>
                <div class="flex justify-between items-center border-t border-slate-200 pt-2.5 text-slate-900 font-bold">
                    <span class="text-sm sm:text-base">Estimated Total:</span>
                    <span id="custTotal" class="font-mono text-lg sm:text-xl font-bold text-blue-600 tabular-nums">₹0.00</span>
                </div>
            </div>

            <!-- Inspection Disclaimer Note -->
            <div class="flex items-start gap-2.5 text-xs text-slate-500 bg-slate-50 border border-slate-200/60 p-3 rounded-xl">
                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><strong class="font-semibold text-slate-700">Inspection Note:</strong> Our staff will weigh and inspect garments upon pickup to confirm exact count, weight, and final price before cleaning begins.</span>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                <a href="/laundry-lms/customer/orders" class="w-full sm:w-auto px-5 py-2.5 border border-slate-300 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors text-center">
                    Cancel
                </a>
                <button type="submit" id="custSubmitBtn" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                    <span>Submit Pickup Request</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const itemsCatalog     = <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const workTypesCatalog = <?= json_encode($workTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const pricingMatrix    = <?= json_encode($pricings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const settingsConfig   = {
    pickupCharge: parseFloat(<?= json_encode($pickupCharge) ?>) || 0,
    deliveryCharge: parseFloat(<?= json_encode($deliveryCharge) ?>) || 0,
    freePickupLimit: parseFloat(<?= json_encode($freePickupLimit) ?>) || 0
};

let custRowIndex = 0;

function onCustItemChange(itemSelect) {
    const row = itemSelect.closest('.cust-item-row');
    if (!row) return;
    updateServiceOptionsForRow(row);
    calculateCustTotals();
}

function updateServiceOptionsForRow(row, preselectedWtId = null) {
    const itemSel = row.querySelector('.cust-item-sel');
    const wtSel   = row.querySelector('.cust-wt-sel');
    if (!itemSel || !wtSel) return;

    const itemId = parseInt(itemSel.value) || 0;
    const currentWtId = (preselectedWtId !== null && preselectedWtId !== undefined && preselectedWtId !== '')
        ? parseInt(preselectedWtId)
        : (parseInt(wtSel.value) || 0);

    const itemPricings = pricingMatrix.filter(p => parseInt(p.item_id) === itemId);
    const pricedWtIds  = new Set(itemPricings.map(p => parseInt(p.work_type_id)));

    let html = '';

    if (itemId > 0) {
        if (itemPricings.length > 0) {
            html += '<optgroup label="Available Services">';
            itemPricings.forEach(p => {
                const wt = workTypesCatalog.find(w => parseInt(w.id) === parseInt(p.work_type_id));
                const name = wt ? wt.name : `Service #${p.work_type_id}`;
                const unit = p.pricing_type === 'per_kg' ? 'kg' : 'pc';
                const price = parseFloat(p.price).toFixed(2);
                html += `<option value="${p.work_type_id}">${escapeHtml(name)} (₹${price} / ${unit})</option>`;
            });
            html += '</optgroup>';
        } else {
            html += '<option value="">-- No services available for this item --</option>';
        }

        const otherWts = workTypesCatalog.filter(w => !pricedWtIds.has(parseInt(w.id)));
        if (otherWts.length > 0) {
            html += '<optgroup label="Other Services (Unavailable for this item)">';
            otherWts.forEach(w => {
                html += `<option value="${w.id}">${escapeHtml(w.name)} (Unavailable)</option>`;
            });
            html += '</optgroup>';
        }
    } else {
        html += '<option value="">-- Select Service --</option>';
        workTypesCatalog.forEach(wt => {
            html += `<option value="${wt.id}">${escapeHtml(wt.name)}</option>`;
        });
    }

    wtSel.innerHTML = html;

    if (currentWtId > 0 && Array.from(wtSel.options).some(o => parseInt(o.value) === currentWtId)) {
        wtSel.value = currentWtId;
    } else if (itemPricings.length > 0) {
        wtSel.value = itemPricings[0].work_type_id;
    } else {
        wtSel.value = '';
    }
}

function addCustomerItemRow(defaultItem = null, defaultWorkType = null, defaultQty = 1) {
    const container = document.getElementById('custItemsContainer');
    const idx = custRowIndex++;

    let itemOptions = '<option value="">-- Select Item --</option>';
    itemsCatalog.forEach(it => {
        const sel = defaultItem && parseInt(defaultItem) === parseInt(it.id) ? 'selected' : '';
        itemOptions += `<option value="${it.id}" ${sel}>${escapeHtml(it.name)}</option>`;
    });

    const rowHtml = `
    <div class="p-4 sm:p-5 bg-slate-50/70 border border-slate-200 rounded-2xl space-y-3 cust-item-row transition-colors" id="cust_row_${idx}">
        <div class="flex justify-between items-center text-xs font-semibold text-slate-500">
            <span class="cust-row-num text-xs font-bold text-slate-700 uppercase tracking-wider">Item #${idx + 1}</span>
            <div class="flex items-center gap-2">
                <span class="cust-rate-badge text-[11px] font-mono font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">Rate: —</span>
                <button type="button" onclick="removeCustRow(${idx})" class="cust-remove-btn text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition-colors inline-flex items-center gap-1 focus:outline-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Remove</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Garment / Item <span class="text-rose-500">*</span></label>
                <select name="items[${idx}][item_id]" required onchange="onCustItemChange(this)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none cust-item-sel shadow-xs transition-all">
                    ${itemOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Service Treatment <span class="text-rose-500">*</span></label>
                <select name="items[${idx}][work_type_id]" required onchange="calculateCustTotals()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none cust-wt-sel shadow-xs transition-all">
                    <option value="">-- Select Service --</option>
                </select>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-slate-600 cust-qty-lbl">Quantity / Count <span class="text-rose-500">*</span></label>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Subtotal</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="number" name="items[${idx}][quantity]" value="${defaultQty}" min="0.01" step="0.01" required oninput="calculateCustTotals()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white font-mono font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none cust-qty-inp shadow-xs transition-all">
                    <div class="shrink-0 px-3 py-2 bg-slate-100/90 border border-slate-200/90 rounded-xl text-right min-w-[85px]">
                        <span class="text-xs font-bold text-slate-900 font-mono cust-sub-disp tabular-nums">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="cust-unpriced-warn hidden text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-2.5 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>This service treatment is not currently available for the selected item. Please choose a different service or item.</span>
        </div>

        <div>
            <input type="text" name="items[${idx}][item_notes]" placeholder="Optional notes (e.g. delicate fabric, stain on collar, heavy starch)..." class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 outline-none transition-all">
        </div>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHtml);
    const newRow = document.getElementById(`cust_row_${idx}`);
    updateServiceOptionsForRow(newRow, defaultWorkType);
    updateCustRowNums();
    calculateCustTotals();
}


function removeCustRow(idx) {
    const rows = document.querySelectorAll('.cust-item-row');
    if (rows.length <= 1) {
        return; // Silent return, never show browser alert()
    }
    const row = document.getElementById('cust_row_' + idx);
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

        const remBtn = r.querySelector('.cust-remove-btn');
        if (remBtn) {
            if (rows.length <= 1) {
                remBtn.disabled = true;
                remBtn.classList.add('opacity-30', 'cursor-not-allowed', 'pointer-events-none');
                remBtn.setAttribute('title', 'At least one item is required');
            } else {
                remBtn.disabled = false;
                remBtn.classList.remove('opacity-30', 'cursor-not-allowed', 'pointer-events-none');
                remBtn.removeAttribute('title');
            }
        }
    });
}

function calculateCustTotals() {
    const rows = document.querySelectorAll('.cust-item-row');
    let itemsSubtotal = 0.00;
    let hasDuplicates = false;
    let hasUnpriced = false;
    const seen = new Set();

    rows.forEach(r => {
        const itemSelect   = r.querySelector('.cust-item-sel');
        const wtSelect     = r.querySelector('.cust-wt-sel');
        const qtyInput     = r.querySelector('.cust-qty-inp');
        const subDisplay   = r.querySelector('.cust-sub-disp');
        const qtyLabel     = r.querySelector('.cust-qty-lbl');
        const rateBadge    = r.querySelector('.cust-rate-badge');
        const unpricedWarn = r.querySelector('.cust-unpriced-warn');

        const itemId     = parseInt(itemSelect.value) || 0;
        const workTypeId = parseInt(wtSelect.value) || 0;
        const qty        = parseFloat(qtyInput.value) || 0;

        // Check duplicate item and work type combination
        if (itemId && workTypeId) {
            const pairKey = `${itemId}_${workTypeId}`;
            if (seen.has(pairKey)) {
                hasDuplicates = true;
                r.classList.add('border-rose-300', 'bg-rose-50/40');
            } else {
                seen.add(pairKey);
                r.classList.remove('border-rose-300', 'bg-rose-50/40');
            }
        } else {
            r.classList.remove('border-rose-300', 'bg-rose-50/40');
        }

        // If not both selected yet
        if (!itemId || !workTypeId) {
            if (rateBadge) rateBadge.textContent = 'Rate: —';
            if (unpricedWarn) unpricedWarn.classList.add('hidden');
            subDisplay.textContent = '₹0.00';
            return;
        }

        const pricing = pricingMatrix.find(p => parseInt(p.item_id) === itemId && parseInt(p.work_type_id) === workTypeId);

        if (!pricing) {
            hasUnpriced = true;
            if (rateBadge) {
                rateBadge.textContent = 'Unavailable';
                rateBadge.classList.add('bg-amber-100', 'text-amber-800');
            }
            if (unpricedWarn) unpricedWarn.classList.remove('hidden');
            subDisplay.textContent = '—';
            r.classList.add('border-amber-300');
            return;
        }

        // Pricing found
        if (unpricedWarn) unpricedWarn.classList.add('hidden');
        r.classList.remove('border-amber-300');

        const isPerKg   = pricing.pricing_type === 'per_kg';
        const unitLabel = isPerKg ? 'kg' : 'pc';
        const unitRate  = parseFloat(pricing.price) || 0;

        if (rateBadge) {
            rateBadge.textContent = `@ ₹${unitRate.toFixed(2)} / ${unitLabel}`;
            rateBadge.classList.remove('bg-amber-100', 'text-amber-800');
        }

        if (qtyLabel) {
            qtyLabel.innerHTML = isPerKg
                ? 'Weight in kg <span class="text-rose-500">*</span>'
                : 'Item Count (pcs) <span class="text-rose-500">*</span>';
        }
        qtyInput.step = isPerKg ? '0.01' : '1';
        qtyInput.min  = isPerKg ? '0.01' : '1';

        if (qty <= 0) {
            subDisplay.textContent = '₹0.00';
            return;
        }

        const lineTotal = Math.round(unitRate * qty * 100) / 100;
        subDisplay.textContent = `₹${lineTotal.toFixed(2)}`;
        itemsSubtotal += lineTotal;
    });

    itemsSubtotal = Math.round(itemsSubtotal * 100) / 100;
    const itemsSubDisplay = document.getElementById('custItemsSubtotal');
    if (itemsSubDisplay) {
        itemsSubDisplay.textContent = `₹${itemsSubtotal.toFixed(2)}`;
    }

    // Delivery fee calculation matching Order::calculateDeliveryCharges()
    let deliveryFee = 0.00;
    const isChargeable = (settingsConfig.freePickupLimit <= 0 || itemsSubtotal < settingsConfig.freePickupLimit);
    if (isChargeable) {
        deliveryFee = settingsConfig.pickupCharge + settingsConfig.deliveryCharge;
    }
    deliveryFee = Math.round(deliveryFee * 100) / 100;

    const feeDisplay = document.getElementById('custDeliveryFee');
    if (feeDisplay) {
        if (!isChargeable && settingsConfig.freePickupLimit > 0) {
            feeDisplay.innerHTML = '<span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">FREE</span>';
        } else {
            feeDisplay.textContent = `₹${deliveryFee.toFixed(2)}`;
        }
    }

    // Free delivery progress banner
    const progressContainer = document.getElementById('custFreeDeliveryProgress');
    if (progressContainer && settingsConfig.freePickupLimit > 0) {
        if (itemsSubtotal >= settingsConfig.freePickupLimit) {
            progressContainer.innerHTML = `
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Free pickup & delivery unlocked! You qualify for a ₹0 service fee.</span>
                </div>
            `;
        } else {
            const needed = (settingsConfig.freePickupLimit - itemsSubtotal).toFixed(2);
            const pct = Math.min(100, Math.max(0, Math.round((itemsSubtotal / settingsConfig.freePickupLimit) * 100)));
            progressContainer.innerHTML = `
                <div class="space-y-1.5 bg-blue-50/60 border border-blue-100 p-2.5 rounded-xl">
                    <div class="flex justify-between text-xs text-blue-900 font-medium">
                        <span>Add <strong class="font-bold font-mono">₹${needed}</strong> more to qualify for FREE pickup & delivery</span>
                        <span class="font-mono font-bold text-blue-700">${pct}%</span>
                    </div>
                    <div class="w-full bg-blue-200/60 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: ${pct}%"></div>
                    </div>
                </div>
            `;
        }
    }

    const total = Math.round((itemsSubtotal + deliveryFee) * 100) / 100;
    const totalDisplay = document.getElementById('custTotal');
    if (totalDisplay) {
        totalDisplay.textContent = `₹${total.toFixed(2)}`;
    }

    const warn = document.getElementById('custDuplicateWarning');
    const submitBtn = document.getElementById('custSubmitBtn');

    if (warn) {
        if (hasDuplicates) {
            warn.classList.remove('hidden');
        } else {
            warn.classList.add('hidden');
        }
    }

    if (submitBtn) {
        if (hasDuplicates || hasUnpriced) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            if (hasUnpriced) {
                submitBtn.title = "Some selected services are unavailable for the selected items. Please remove or change them.";
            } else if (hasDuplicates) {
                submitBtn.title = "Duplicate item and service combinations detected. Each item and treatment pair must be unique.";
            }
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.removeAttribute('title');
        }
    }
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Synchronize pickup and delivery dates & DD/MM/YYYY display
const pickupDateInput       = document.getElementById('pickup_date');
const pickupDateDisplay     = document.getElementById('pickup_date_display');
const pickupCalendarBtn     = document.getElementById('pickup_calendar_btn');

const deliveryDateInput     = document.getElementById('delivery_date');
const deliveryDateDisplay   = document.getElementById('delivery_date_display');
const deliveryCalendarBtn   = document.getElementById('delivery_calendar_btn');

function formatYmdToDmy(ymd) {
    if (!ymd || typeof ymd !== 'string') return '';
    const parts = ymd.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return ymd;
}

function parseDmyToYmd(dmy) {
    if (!dmy || typeof dmy !== 'string') return '';
    const match = dmy.trim().match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (!match) return '';
    const d = match[1].padStart(2, '0');
    const m = match[2].padStart(2, '0');
    const y = match[3];
    const dInt = parseInt(d, 10);
    const mInt = parseInt(m, 10);
    const yInt = parseInt(y, 10);
    if (mInt < 1 || mInt > 12 || dInt < 1 || dInt > 31 || yInt < 2000 || yInt > 2100) {
        return '';
    }
    return `${y}-${m}-${d}`;
}

function syncDates() {
    if (!pickupDateInput || !deliveryDateInput) return;
    const pVal = pickupDateInput.value;
    if (pVal) {
        deliveryDateInput.min = pVal;
        if (deliveryDateInput.value && deliveryDateInput.value < pVal) {
            deliveryDateInput.value = '';
            if (deliveryDateDisplay) deliveryDateDisplay.value = '';
        }
        if (pickupDateDisplay) {
            pickupDateDisplay.value = formatYmdToDmy(pVal);
        }
    }
    if (deliveryDateDisplay && deliveryDateInput.value) {
        deliveryDateDisplay.value = formatYmdToDmy(deliveryDateInput.value);
    }
}

function openCalendar(inputEl) {
    if (!inputEl) return;
    if (typeof inputEl.showPicker === 'function') {
        try {
            inputEl.showPicker();
            return;
        } catch (e) {}
    }
    inputEl.focus();
}

if (pickupCalendarBtn && pickupDateInput) {
    pickupCalendarBtn.addEventListener('click', () => openCalendar(pickupDateInput));
}
if (deliveryCalendarBtn && deliveryDateInput) {
    deliveryCalendarBtn.addEventListener('click', () => openCalendar(deliveryDateInput));
}

if (pickupDateDisplay && pickupDateInput) {
    pickupDateDisplay.addEventListener('input', () => {
        let val = pickupDateDisplay.value.replace(/[^\d\/]/g, '');
        if (!val.includes('/') && val.length > 2) {
            if (val.length <= 4) {
                val = val.slice(0, 2) + '/' + val.slice(2);
            } else {
                val = val.slice(0, 2) + '/' + val.slice(2, 4) + '/' + val.slice(4, 8);
            }
        }
        pickupDateDisplay.value = val;
        const ymd = parseDmyToYmd(val);
        if (ymd) {
            pickupDateInput.value = ymd;
            syncDates();
        }
    });
}

if (deliveryDateDisplay && deliveryDateInput) {
    deliveryDateDisplay.addEventListener('input', () => {
        let val = deliveryDateDisplay.value.replace(/[^\d\/]/g, '');
        if (!val.includes('/') && val.length > 2) {
            if (val.length <= 4) {
                val = val.slice(0, 2) + '/' + val.slice(2);
            } else {
                val = val.slice(0, 2) + '/' + val.slice(2, 4) + '/' + val.slice(4, 8);
            }
        }
        deliveryDateDisplay.value = val;
        const ymd = parseDmyToYmd(val);
        if (ymd) {
            deliveryDateInput.value = ymd;
            syncDates();
        } else if (val === '') {
            deliveryDateInput.value = '';
            syncDates();
        }
    });
}

if (pickupDateInput) {
    pickupDateInput.addEventListener('change', syncDates);
    pickupDateInput.addEventListener('input', syncDates);
}
if (deliveryDateInput) {
    deliveryDateInput.addEventListener('change', syncDates);
    deliveryDateInput.addEventListener('input', syncDates);
}

// Double submit prevention and validation check
const orderForm = document.getElementById('customerOrderForm');
if (orderForm) {
    orderForm.addEventListener('submit', function (e) {
        if (!orderForm.checkValidity()) {
            return;
        }

        // Ensure hidden date values are in sync before submission
        if (pickupDateDisplay && (!pickupDateInput.value || pickupDateDisplay.value)) {
            const ymd = parseDmyToYmd(pickupDateDisplay.value);
            if (ymd) pickupDateInput.value = ymd;
        }
        if (deliveryDateDisplay) {
            if (deliveryDateDisplay.value) {
                const ymd = parseDmyToYmd(deliveryDateDisplay.value);
                if (ymd) deliveryDateInput.value = ymd;
            } else {
                deliveryDateInput.value = '';
            }
        }

        calculateCustTotals();

        const warn = document.getElementById('custDuplicateWarning');
        if (warn && !warn.classList.contains('hidden')) {
            e.preventDefault();
            return;
        }

        const submitBtn = document.getElementById('custSubmitBtn');
        if (submitBtn) {
            setTimeout(() => {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            }, 10);
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Submitting Pickup Request...</span>
            `;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    addCustomerItemRow();
    syncDates();
});
</script>
