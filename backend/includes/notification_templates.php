<?php
class NotificationTemplateManager
{
    private $conn;
    private $templates = [
        'slack' => [
            'error_alert' => [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => ['type' => 'plain_text', 'text' => '🚨 Error Alert']
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            ['type' => 'mrkdwn', 'text' => '*Type:* {{error_type}}'],
                            ['type' => 'mrkdwn', 'text' => '*Severity:* {{severity}}']
                        ]
                    ],
                    [
                        'type' => 'section',
                        'text' => ['type' => 'mrkdwn', 'text' => '```{{error_message}}```']
                    ]
                ]
            ]
        ],
        'email' => [
            'error_digest' => <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #e53e3e;">Error Digest Report</h2>
                <div style="background: #f7fafc; padding: 15px; border-radius: 5px;">
                    <h3>Summary</h3>
                    <p>Total Errors: {{total_errors}}</p>
                    <p>Critical Issues: {{critical_count}}</p>
                </div>
                <div style="margin-top: 20px;">
                    {{error_list}}
                </div>
            </div>
            HTML
        ]
    ];

    public function getTemplate($channel, $type, $data)
    {
        if (!isset($this->templates[$channel][$type])) {
            throw new Exception("Template not found: $channel/$type");
        }

        $template = $this->templates[$channel][$type];
        return $this->replaceVariables($template, $data);
    }
}
