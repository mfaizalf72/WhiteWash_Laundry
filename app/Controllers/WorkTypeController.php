<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\WorkType;

class WorkTypeController {
    public function index(): void {
        $workTypes = WorkType::all();
        Router::view('admin/work_types/index', [
            'title'     => 'Work Types - Launder',
            'workTypes' => $workTypes
        ], 'admin');
    }

    public function store(): void {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['error'] = "Work type name cannot be empty.";
            Router::redirect('/work-types');
            return;
        }

        if (WorkType::findByName($name)) {
            $_SESSION['error'] = "A work type with the name '{$name}' already exists.";
            Router::redirect('/work-types');
            return;
        }

        WorkType::create($name);
        $_SESSION['success'] = "Work type '{$name}' created successfully.";
        Router::redirect('/work-types');
    }

    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || empty($name)) {
            $_SESSION['error'] = "Invalid work type details provided.";
            Router::redirect('/work-types');
            return;
        }

        if (!WorkType::find($id)) {
            $_SESSION['error'] = "Work type not found.";
            Router::redirect('/work-types');
            return;
        }

        if (WorkType::findByName($name, $id)) {
            $_SESSION['error'] = "Another work type with the name '{$name}' already exists.";
            Router::redirect('/work-types');
            return;
        }

        WorkType::update($id, $name);
        $_SESSION['success'] = "Work type updated successfully.";
        Router::redirect('/work-types');
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !WorkType::find($id)) {
            $_SESSION['error'] = "Work type not found.";
            Router::redirect('/work-types');
            return;
        }

        WorkType::delete($id);
        $_SESSION['success'] = "Work type deleted successfully.";
        Router::redirect('/work-types');
    }
}
