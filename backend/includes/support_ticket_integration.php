<?php
class SupportTicketIntegration
{
    private $conn;
    private $apiKey;
    private $ticketingSystem = 'zendesk'; // configurable

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->apiKey = getenv('ZENDESK_API_KEY');
    }

    public function createTicketFromDispute($disputeId)
    {
        $dispute = $this->getDisputeDetails($disputeId);

        $ticketData = [
            'subject' => "Payment Dispute #{$disputeId}",
            'description' => $this->formatDisputeDescription($dispute),
            'priority' => $this->calculatePriority($dispute),
            'tags' => ['payment_dispute', $dispute['status']],
            'custom_fields' => [
                'dispute_id' => $disputeId,
                'transaction_id' => $dispute['transaction_id']
            ]
        ];

        return $this->createTicket($ticketData);
    }

    private function formatDisputeDescription($dispute)
    {
        return <<<EOT
        Dispute Details:
        - Amount: ₺{$dispute['amount']}
        - Reason: {$dispute['reason']}
        - Customer: {$dispute['customer_name']}
        - Transaction Date: {$dispute['transaction_date']}
        
        Customer Comments:
        {$dispute['customer_comments']}
        EOT;
    }

    private function updateTicketStatus($ticketId, $status)
    {
        $endpoint = "https://api.zendesk.com/v2/tickets/{$ticketId}.json";

        $data = [
            'ticket' => [
                'status' => $status,
                'updated_at' => date('c')
            ]
        ];

        return $this->makeApiRequest($endpoint, 'PUT', $data);
    }
}
