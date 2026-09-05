<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Pricing;
use Faizal\LaundryLms\Models\LaundryItem;
use Faizal\LaundryLms\Models\WorkType;

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
        $itemId      = (int)($_POST['item_id'] ?? 0);
        $workTypeId  = (int)($_POST['work_type_id'] ?? 0);
        $pricingType = trim($_POST['pricing_type'] ?? '');
        $rawPrice    = trim($_POST['price'] ?? '');

        if ($itemId <= 0 || $workTypeId <= 0) {
            $_SESSION['error'] = "Please select both a valid laundry item and work type.";
            Router::redirect('/pricing');
            return;
        }

        if (!in_array($pricingType, ['per_kg', 'per_item'], true)) {
            $_SESSION['error'] = "Invalid pricing type specified.";
            Router::redirect('/pricing');
            return;
        }

        if (!is_numeric($rawPrice) || (float)$rawPrice < 0) {
            $_SESSION['error'] = "Price must be a valid positive number.";
            Router::redirect('/pricing');
            return;
        }
        $price = round((float)$rawPrice, 2);

        if (!LaundryItem::find($itemId)) {
            $_SESSION['error'] = "Selected laundry item does not exist.";
            Router::redirect('/pricing');
            return;
        }

        if (!WorkType::find($workTypeId)) {
            $_SESSION['error'] = "Selected work type does not exist.";
            Router::redirect('/pricing');
            return;
        }

        if (Pricing::exists($itemId, $workTypeId)) {
            $_SESSION['error'] = "Pricing configuration for this item and work type combination already exists.";
            Router::redirect('/pricing');
            return;
        }

        Pricing::create($itemId, $workTypeId, $pricingType, $price);
        $_SESSION['success'] = "Pricing entry created successfully.";
        Router::redirect('/pricing');
    }

    public function update(): void {
        $itemId      = (int)($_POST['item_id'] ?? 0);
        $workTypeId  = (int)($_POST['work_type_id'] ?? 0);
        $pricingType = trim($_POST['pricing_type'] ?? '');
        $rawPrice    = trim($_POST['price'] ?? '');

        if ($itemId <= 0 || $workTypeId <= 0 || !Pricing::find($itemId, $workTypeId)) {
            $_SESSION['error'] = "Target pricing entry not found.";
            Router::redirect('/pricing');
            return;
        }

        if (!in_array($pricingType, ['per_kg', 'per_item'], true)) {
            $_SESSION['error'] = "Invalid pricing type specified.";
            Router::redirect('/pricing');
            return;
        }

        if (!is_numeric($rawPrice) || (float)$rawPrice < 0) {
            $_SESSION['error'] = "Price must be a valid positive number.";
            Router::redirect('/pricing');
            return;
        }
        $price = round((float)$rawPrice, 2);

        Pricing::update($itemId, $workTypeId, $pricingType, $price);
        $_SESSION['success'] = "Pricing entry updated successfully.";
        Router::redirect('/pricing');
    }

    public function delete(): void {
        $itemId     = (int)($_POST['item_id'] ?? 0);
        $workTypeId = (int)($_POST['work_type_id'] ?? 0);

        if ($itemId <= 0 || $workTypeId <= 0 || !Pricing::find($itemId, $workTypeId)) {
            $_SESSION['error'] = "Pricing entry not found.";
            Router::redirect('/pricing');
            return;
        }

        Pricing::delete($itemId, $workTypeId);
        $_SESSION['success'] = "Pricing entry deleted successfully.";
        Router::redirect('/pricing');
    }
}
