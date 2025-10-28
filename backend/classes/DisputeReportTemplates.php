<?php
declare(strict_types=1);

namespace App\Classes;

class DisputeReportTemplates
{
    private $conn;
    private $templates = [
        'daily_summary' => [
            'title' => 'Daily Dispute Summary',
            'query' => "
                SELECT 
                    COUNT(*) as total_disputes,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_time
                FROM payment_disputes
                WHERE DATE(created_at) = CURDATE()
            "
        ],
        'weekly_trends' => [
            'title' => 'Weekly Dispute Trends',
            'query' => "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as disputes,
                    reason,
                    status
                FROM payment_disputes
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at), reason, status
            "
        ]
    ];

    public function generateReport($templateId, $params = [])
    {
        if (!isset($this->templates[$templateId])) {
            throw new Exception('Template not found');
        }

        $template = $this->templates[$templateId];
        $data = $this->executeReport($template['query'], $params);

        return [
            'title' => $template['title'],
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
    }
}

