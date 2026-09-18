<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Pricing;
use Faizal\LaundryLms\Models\LaundryItem;
use Faizal\LaundryLms\Models\WorkType;
use PDOException;

class PricingController {
    public function index(): void {
        $pricings  = Pricing::all();
        $items     = LaundryItem::all();
        $workTypes = WorkType::all();

        Router::view('admin/pricing/index', [
            'title'     => 'Pricing Management - Launder',
            'pricings'  => $pricings,
            'items'     => $items,
            'workTypes' => $workTypes
        ], 'admin');
    }

    public function store(): void {
        // Validate item_id (strict numeric > 0)
        if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']) || (int)$_POST['item_id'] <= 0) {
            $_SESSION['error'] = "Please select a valid laundry item.";
            Router::redirect('/pricing');
            return;
        }
        $itemId = (int)$_POST['item_id'];

        // Validate work_type_id (strict numeric > 0)
        if (!isset($_POST['work_type_id']) || !is_numeric($_POST['work_type_id']) || (int)$_POST['work_type_id'] <= 0) {
            $_SESSION['error'] = "Please select a valid work type.";
            Router::redirect('/pricing');
            return;
        }
        $workTypeId = (int)$_POST['work_type_id'];

        // Validate pricing_type (enum: per_kg, per_item, block empty strings)
        if (!isset($_POST['pricing_type'])) {
            $_SESSION['error'] = "Pricing type is required.";
            Router::redirect('/pricing');
            return;
        }
        $pricingType = trim((string)$_POST['pricing_type']);
        if ($pricingType === '' || !in_array($pricingType, ['per_kg', 'per_item'], true)) {
            $_SESSION['error'] = "Invalid pricing type specified. Must be 'per_kg' or 'per_item'.";
            Router::redirect('/pricing');
            return;
        }

        // Validate price (strict numeric, non-empty, >= 0)
        if (!isset($_POST['price'])) {
            $_SESSION['error'] = "Price field is required.";
            Router::redirect('/pricing');
            return;
        }
        $rawPrice = trim((string)$_POST['price']);
        if ($rawPrice === '' || !is_numeric($rawPrice) || (float)$rawPrice < 0) {
            $_SESSION['error'] = "Price must be a valid positive number.";
            Router::redirect('/pricing');
            return;
        }
        $price = round((float)$rawPrice, 2);

        // Verify referenced parent entities exist and are active
        if (!LaundryItem::find($itemId)) {
            $_SESSION['error'] = "Selected laundry item does not exist or has been deleted.";
            Router::redirect('/pricing');
            return;
        }

        if (!WorkType::find($workTypeId)) {
            $_SESSION['error'] = "Selected work type does not exist or has been deleted.";
            Router::redirect('/pricing');
            return;
        }

        // Check for duplicate pricing configuration
        if (Pricing::exists($itemId, $workTypeId)) {
            $_SESSION['error'] = "Pricing configuration for this item and work type combination already exists.";
            Router::redirect('/pricing');
            return;
        }

        try {
            Pricing::create($itemId, $workTypeId, $pricingType, $price);
            $_SESSION['success'] = "Pricing entry created successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create pricing entry: " . $e->getMessage();
        }

        Router::redirect('/pricing');
    }

    public function update(): void {
        // Validate item_id
        if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']) || (int)$_POST['item_id'] <= 0) {
            $_SESSION['error'] = "Invalid laundry item ID.";
            Router::redirect('/pricing');
            return;
        }
        $itemId = (int)$_POST['item_id'];

        // Validate work_type_id
        if (!isset($_POST['work_type_id']) || !is_numeric($_POST['work_type_id']) || (int)$_POST['work_type_id'] <= 0) {
            $_SESSION['error'] = "Invalid work type ID.";
            Router::redirect('/pricing');
            return;
        }
        $workTypeId = (int)$_POST['work_type_id'];

        // Verify target pricing entry exists
        if (!Pricing::find($itemId, $workTypeId)) {
            $_SESSION['error'] = "Target pricing entry not found.";
            Router::redirect('/pricing');
            return;
        }

        // Validate pricing_type
        if (!isset($_POST['pricing_type'])) {
            $_SESSION['error'] = "Pricing type is required.";
            Router::redirect('/pricing');
            return;
        }
        $pricingType = trim((string)$_POST['pricing_type']);
        if ($pricingType === '' || !in_array($pricingType, ['per_kg', 'per_item'], true)) {
            $_SESSION['error'] = "Invalid pricing type specified. Must be 'per_kg' or 'per_item'.";
            Router::redirect('/pricing');
            return;
        }

        // Validate price (strict numeric >= 0)
        if (!isset($_POST['price'])) {
            $_SESSION['error'] = "Price field is required.";
            Router::redirect('/pricing');
            return;
        }
        $rawPrice = trim((string)$_POST['price']);
        if ($rawPrice === '' || !is_numeric($rawPrice) || (float)$rawPrice < 0) {
            $_SESSION['error'] = "Price must be a valid positive number.";
            Router::redirect('/pricing');
            return;
        }
        $price = round((float)$rawPrice, 2);

        try {
            Pricing::update($itemId, $workTypeId, $pricingType, $price);
            $_SESSION['success'] = "Pricing entry updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update pricing entry: " . $e->getMessage();
        }

        Router::redirect('/pricing');
    }

    public function delete(): void {
        if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']) || (int)$_POST['item_id'] <= 0 ||
            !isset($_POST['work_type_id']) || !is_numeric($_POST['work_type_id']) || (int)$_POST['work_type_id'] <= 0) {
            $_SESSION['error'] = "Invalid pricing configuration parameters.";
            Router::redirect('/pricing');
            return;
        }

        $itemId     = (int)$_POST['item_id'];
        $workTypeId = (int)$_POST['work_type_id'];

        if (!Pricing::find($itemId, $workTypeId)) {
            $_SESSION['error'] = "Pricing entry not found.";
            Router::redirect('/pricing');
            return;
        }

        try {
            Pricing::delete($itemId, $workTypeId);
            $_SESSION['success'] = "Pricing entry deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Cannot delete pricing entry due to database constraint: " . $e->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to delete pricing entry: " . $e->getMessage();
        }

        Router::redirect('/pricing');
    }
}
