<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $action = $_POST['action'] ?? '';
    $carwashId = $_SESSION['carwash_id'];

    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("
                INSERT INTO service_categories (carwash_id, name, description, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param('issi', $carwashId, $_POST['name'], $_POST['description'], $_POST['sort_order']);
            $stmt->execute();
            $response = ['message' => 'Category added successfully'];
            break;

        case 'update':
            $stmt = $conn->prepare("
                UPDATE service_categories 
                SET name = ?, description = ?, sort_order = ?
                WHERE id = ? AND carwash_id = ?
            ");
            $stmt->bind_param(
                'ssiii',
                $_POST['name'],
                $_POST['description'],
                $_POST['sort_order'],
                $_POST['id'],
                $carwashId
            );
            $stmt->execute();
            $response = ['message' => 'Category updated successfully'];
            break;

        case 'delete':
            // First move services to uncategorized
            $stmt = $conn->prepare("
                UPDATE services 
                SET category_id = NULL 
                WHERE category_id = ? AND carwash_id = ?
            ");
            $stmt->bind_param('ii', $_POST['id'], $carwashId);
            $stmt->execute();

            // Then delete category
            $stmt = $conn->prepare("
                DELETE FROM service_categories 
                WHERE id = ? AND carwash_id = ?
            ");
            $stmt->bind_param('ii', $_POST['id'], $carwashId);
            $stmt->execute();
            $response = ['message' => 'Category deleted successfully'];
            break;

        case 'list':
            $stmt = $conn->prepare("
                SELECT c.*, COUNT(s.id) as service_count
                FROM service_categories c
                LEFT JOIN services s ON c.id = s.category_id
                WHERE c.carwash_id = ?
                GROUP BY c.id
                ORDER BY c.sort_order, c.name
            ");
            $stmt->bind_param('i', $carwashId);
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $response = ['categories' => $categories];
            break;

        default:
            throw new Exception('Invalid action');
    }

    echo json_encode([
        'success' => true,
        'data' => $response
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
