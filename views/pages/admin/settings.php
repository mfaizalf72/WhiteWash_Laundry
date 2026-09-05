<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-base font-semibold leading-6 text-slate-900">Company Configuration</h3>
        <p class="mt-1 text-sm text-slate-500">These settings will be applied to all customer invoices and system calculations.</p>
    </div>
    
    <form action="/laundry-lms/admin/settings" method="POST" class="p-6">
        <?= \Faizal\LaundryLms\Helpers\Csrf::field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Business Name</label>
                <input type="text" name="business_name" value="<?= htmlspecialchars($settings['business_name'] ?? '') ?>" required class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp Number (For API)</label>
                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Standard Pickup Charge (₹)</label>
                <input type="number" step="0.01" name="pickup_charge" value="<?= htmlspecialchars((string)($settings['pickup_charge'] ?? '0.00')) ?>" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Standard Delivery Charge (₹)</label>
                <input type="number" step="0.01" name="delivery_charge" value="<?= htmlspecialchars((string)($settings['delivery_charge'] ?? '0.00')) ?>" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Free Pickup Limit (₹)</label>
                <input type="number" step="0.01" name="free_pickup_limit" value="<?= htmlspecialchars((string)($settings['free_pickup_limit'] ?? '0.00')) ?>" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Orders above this amount get free pickup.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Working Hours</label>
                <input type="text" name="working_hours" value="<?= htmlspecialchars($settings['working_hours'] ?? '') ?>" placeholder="e.g. Mon-Sat, 9AM to 8PM" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Physical Address</label>
                <textarea name="address" rows="3" class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div class="flex justify-end pt-4 border-t border-slate-200">
            <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-800 shadow-sm transition-colors">
                Save Configurations
            </button>
        </div>
    </form>
</div>