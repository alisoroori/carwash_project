<?php
class DisputeCommunicationTemplates
{
    private $templates = [
        'dispute_received' => [
            'subject' => 'Your Dispute Has Been Received - {{dispute_id}}',
            'template' => <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>We've Received Your Dispute</h2>
                <p>Dear {{customer_name}},</p>
                <p>We've received your dispute (ID: {{dispute_id}}) regarding transaction {{transaction_id}}.</p>
                <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                    <h3>Next Steps:</h3>
                    <ol>
                        <li>Our team will review your case within 24 hours</li>
                        <li>You may be contacted for additional information</li>
                        <li>We'll keep you updated on any progress</li>
                    </ol>
                </div>
                <p>Track your dispute status: <a href="{{tracking_url}}">{{tracking_url}}</a></p>
            </div>
            HTML
        ],
        'dispute_update' => [
            'subject' => 'Update on Your Dispute - {{dispute_id}}',
            'template' => <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Dispute Status Update</h2>
                <p>Dear {{customer_name}},</p>
                <p>There has been an update to your dispute case:</p>
                <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                    <p><strong>Status:</strong> {{status}}</p>
                    <p><strong>Update:</strong> {{update_message}}</p>
                </div>
            </div>
            HTML
        ]
    ];

    public function getTemplate($type, $data)
    {
        if (!isset($this->templates[$type])) {
            throw new Exception('Template not found');
        }

        $template = $this->templates[$type]['template'];
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return [
            'subject' => strtr($this->templates[$type]['subject'], $data),
            'body' => $template
        ];
    }
}
