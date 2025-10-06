<?php
require_once 'db.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

class InvoiceGenerator
{
    private $conn;
    private $order;
    private $user;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function generateInvoice($orderId)
    {
        if (!$this->loadOrderData($orderId)) {
            return false;
        }

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('CarWash System');
        $pdf->SetAuthor('CarWash');
        $pdf->SetTitle('Fatura #' . $orderId);

        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('dejavusans', '', 10);

        // Add content
        $pdf->writeHTML($this->generateInvoiceHTML(), true, false, true, false, '');

        return $pdf;
    }

    private function loadOrderData($orderId)
    {
        $stmt = $this->conn->prepare("
            SELECT o.*, u.name, u.email, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");

        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->order = $row;
            $this->order['items'] = json_decode($this->order['items'], true);
            return true;
        }
        return false;
    }

    private function generateInvoiceHTML()
    {
        $date = date('d/m/Y', strtotime($this->order['created_at']));

        return <<<HTML
        <div style="font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #2563eb;">CarWash</h1>
                <h2>Fatura #{$this->order['id']}</h2>
            </div>

            <div style="margin-bottom: 20px;">
                <div style="float: left;">
                    <strong>Müşteri Bilgileri:</strong><br>
                    {$this->order['name']}<br>
                    {$this->order['email']}<br>
                    {$this->order['phone']}
                </div>
                <div style="float: right; text-align: right;">
                    <strong>Fatura Tarihi:</strong><br>
                    {$date}
                </div>
                <div style="clear: both;"></div>
            </div>

            <table cellpadding="5" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="border: 1px solid #e5e7eb; text-align: left;">Hizmet</th>
                        <th style="border: 1px solid #e5e7eb; text-align: right;">Fiyat</th>
                    </tr>
                </thead>
                <tbody>
                    {$this->generateItemsHTML()}
                </tbody>
                <tfoot>
                    {$this->generateTotalsHTML()}
                </tfoot>
            </table>

            <div style="text-align: center; margin-top: 40px; color: #6b7280; font-size: 12px;">
                <p>Bu bir e-faturadır.</p>
                <p>CarWash - Profesyonel Araç Yıkama Hizmetleri</p>
            </div>
        </div>
        HTML;
    }

    private function generateItemsHTML()
    {
        $html = '';
        foreach ($this->order['items'] as $item) {
            $html .= <<<HTML
            <tr>
                <td style="border: 1px solid #e5e7eb;">{$item['service_name']}</td>
                <td style="border: 1px solid #e5e7eb; text-align: right;">{$item['price']} TL</td>
            </tr>
            HTML;
        }
        return $html;
    }

    private function generateTotalsHTML()
    {
        $html = <<<HTML
        <tr>
            <td style="border: 1px solid #e5e7eb; text-align: right;">Ara Toplam:</td>
            <td style="border: 1px solid #e5e7eb; text-align: right;">{$this->order['subtotal']} TL</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb; text-align: right;">KDV (18%):</td>
            <td style="border: 1px solid #e5e7eb; text-align: right;">{$this->order['tax']} TL</td>
        </tr>
        HTML;

        if ($this->order['discount'] > 0) {
            $html .= <<<HTML
            <tr>
                <td style="border: 1px solid #e5e7eb; text-align: right;">İndirim:</td>
                <td style="border: 1px solid #e5e7eb; text-align: right; color: #059669;">
                    -{$this->order['discount']} TL
                </td>
            </tr>
            HTML;
        }

        $html .= <<<HTML
        <tr>
            <td style="border: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Toplam:</td>
            <td style="border: 1px solid #e5e7eb; text-align: right; font-weight: bold;">
                {$this->order['total']} TL
            </td>
        </tr>
        HTML;

        return $html;
    }
}
