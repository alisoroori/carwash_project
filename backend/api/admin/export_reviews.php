<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class ReviewExporter
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function exportCSV($filters = [])
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reviews_export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, [
            'Review ID',
            'CarWash',
            'Customer',
            'Rating',
            'Comment',
            'Date',
            'Status'
        ]);

        $query = "
            SELECT 
                r.id,
                c.name as carwash_name,
                u.name as customer_name,
                r.rating,
                r.comment,
                r.created_at,
                r.status
            FROM reviews r
            JOIN carwash c ON r.carwash_id = c.id
            JOIN users u ON r.user_id = u.id
            WHERE 1=1
        ";

        // Add filters
        if (!empty($filters['status'])) {
            $query .= " AND r.status = '" . $filters['status'] . "'";
        }
        if (!empty($filters['date_from'])) {
            $query .= " AND r.created_at >= '" . $filters['date_from'] . "'";
        }

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['carwash_name'],
                $row['customer_name'],
                $row['rating'],
                $row['comment'],
                $row['created_at'],
                $row['status']
            ]);
        }

        fclose($output);
    }
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exporter = new ReviewExporter($conn);
    $filters = $_POST['filters'] ?? [];
    $exporter->exportCSV($filters);
}
