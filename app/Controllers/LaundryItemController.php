<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\LaundryItem;
use PDOException;

class LaundryItemController {
    public function index(): void {
        $items = LaundryItem::all();
        Router::view('admin/items/index', [
            'title' => 'Laundry Items - Launder',
            'items' => $items
        ], 'admin');
    }

    public function store(): void {
        if (!isset($_POST['name'])) {
            $_SESSION['error'] = "Laundry item name is required.";
            Router::redirect('/items');
            return;
        }

        $name = trim((string)$_POST['name']);
        if ($name === '') {
            $_SESSION['error'] = "Laundry item name cannot be empty.";
            Router::redirect('/items');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Laundry item name cannot exceed 100 characters.";
            Router::redirect('/items');
            return;
        }

        if (LaundryItem::findByName($name)) {
            $_SESSION['error'] = "A laundry item with the name '{$name}' already exists.";
            Router::redirect('/items');
            return;
        }

        try {
            LaundryItem::create($name);
            $_SESSION['success'] = "Laundry item '{$name}' created successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to create laundry item: " . $e->getMessage();
        }

        Router::redirect('/items');
    }

    public function update(): void {
        if (!isset($_POST['id']) || !is_numeric($_POST['id']) || (int)$_POST['id'] <= 0) {
            $_SESSION['error'] = "Invalid laundry item ID provided.";
            Router::redirect('/items');
            return;
        }

        $id = (int)$_POST['id'];

        if (!isset($_POST['name'])) {
            $_SESSION['error'] = "Laundry item name is required.";
            Router::redirect('/items');
            return;
        }

        $name = trim((string)$_POST['name']);
        if ($name === '') {
            $_SESSION['error'] = "Laundry item name cannot be empty.";
            Router::redirect('/items');
            return;
        }

        if (mb_strlen($name) > 100) {
            $_SESSION['error'] = "Laundry item name cannot exceed 100 characters.";
            Router::redirect('/items');
            return;
        }

        $existing = LaundryItem::find($id);
        if (!$existing) {
            $_SESSION['error'] = "Laundry item not found.";
            Router::redirect('/items');
            return;
        }

        if (LaundryItem::findByName($name, $id)) {
            $_SESSION['error'] = "Another laundry item with the name '{$name}' already exists.";
            Router::redirect('/items');
            return;
        }

        try {
            LaundryItem::update($id, $name);
            $_SESSION['success'] = "Laundry item updated successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to update laundry item: " . $e->getMessage();
        }

        Router::redirect('/items');
    }

    public function delete(): void {
        if (!isset($_POST['id']) || !is_numeric($_POST['id']) || (int)$_POST['id'] <= 0) {
            $_SESSION['error'] = "Invalid laundry item ID provided.";
            Router::redirect('/items');
            return;
        }

        $id = (int)$_POST['id'];
        $item = LaundryItem::find($id);
        if (!$item) {
            $_SESSION['error'] = "Laundry item not found.";
            Router::redirect('/items');
            return;
        }

        try {
            LaundryItem::delete($id);
            $_SESSION['success'] = "Laundry item deleted successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Failed to delete laundry item: " . $e->getMessage();
        }

        Router::redirect('/items');
    }
}
