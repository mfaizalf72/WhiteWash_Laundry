<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\LaundryItem;

class LaundryItemController {
    public function index(): void {
        $items = LaundryItem::all();
        Router::view('admin/items/index', [
            'title' => 'Laundry Items - Launder',
            'items' => $items
        ], 'admin');
    }

    public function store(): void {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['error'] = "Laundry item name cannot be empty.";
            Router::redirect('/items');
            return;
        }

        if (LaundryItem::findByName($name)) {
            $_SESSION['error'] = "A laundry item with the name '{$name}' already exists.";
            Router::redirect('/items');
            return;
        }

        LaundryItem::create($name);
        $_SESSION['success'] = "Laundry item '{$name}' created successfully.";
        Router::redirect('/items');
    }

    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || empty($name)) {
            $_SESSION['error'] = "Invalid item details provided.";
            Router::redirect('/items');
            return;
        }

        if (!LaundryItem::find($id)) {
            $_SESSION['error'] = "Laundry item not found.";
            Router::redirect('/items');
            return;
        }

        if (LaundryItem::findByName($name, $id)) {
            $_SESSION['error'] = "Another laundry item with the name '{$name}' already exists.";
            Router::redirect('/items');
            return;
        }

        LaundryItem::update($id, $name);
        $_SESSION['success'] = "Laundry item updated successfully.";
        Router::redirect('/items');
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !LaundryItem::find($id)) {
            $_SESSION['error'] = "Laundry item not found.";
            Router::redirect('/items');
            return;
        }

        LaundryItem::delete($id);
        $_SESSION['success'] = "Laundry item deleted successfully.";
        Router::redirect('/items');
    }
}
