<?php
declare(strict_types=1);

namespace App\Classes;

class DisputeDocumentationGenerator
{
    private $conn;
    private $templatePath = '../templates/dispute/';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function generateDocumentation($disputeId)
    {
        $dispute = $this->getDisputeDetails($disputeId);
        $pdf = new TCPDF();

        // Document setup
        $pdf->SetCreator('CarWash System');
        $pdf->SetTitle('Dispute Documentation #' . $disputeId);
        $pdf->AddPage();

        // Add content
        $pdf->writeHTML($this->generateDisputeContent($dispute));

        // Save file
        $filename = 'dispute_' . $disputeId . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output(dirname(__FILE__) . '/../uploads/disputes/' . $filename, 'F');

        return $filename;
    }

    private function generateDisputeContent($dispute)
    {
        $timeline = $this->getDisputeTimeline($dispute['id']);
        $evidence = $this->getDisputeEvidence($dispute['id']);

        return "
            <h1>Dispute Documentation</h1>
            <div class='dispute-details'>
                <p><strong>Dispute ID:</strong> {$dispute['id']}</p>
                <p><strong>Created Date:</strong> {$dispute['created_at']}</p>
                <p><strong>Status:</strong> {$dispute['status']}</p>
            </div>
            <div class='timeline'>
                <h2>Dispute Timeline</h2>
                {$this->formatTimeline($timeline)}
            </div>
            <div class='evidence'>
                <h2>Evidence Submitted</h2>
                {$this->formatEvidence($evidence)}
            </div>
        ";
    }
}

