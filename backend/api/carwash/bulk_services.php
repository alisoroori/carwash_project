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

    switch ($action) {
        case 'export':
            // Export services to CSV
            $stmt = $conn->prepare("
                SELECT name, description, price, duration, category_id
                FROM services 
                WHERE carwash_id = ?
            ");

            $stmt->bind_param('i', $_SESSION['carwash_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            $filename = "services_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Description', 'Price', 'Duration', 'Category ID']);

            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        case 'import':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip header row
                fgetcsv($handle);

                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO services (carwash_id, name, description, price, duration, category_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $stmt->bind_param(
                            'issdii',
                            $_SESSION['carwash_id'],
                            $data[0], // name
                            $data[1], // description
                            $data[2], // price
                            $data[3], // duration
                            $data[4]  // category_id
                        );
                        $stmt->execute();
                    }

                    $conn->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Services imported successfully'
                    ]);
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }

                fclose($handle);
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
