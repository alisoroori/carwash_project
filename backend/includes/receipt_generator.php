<?php
require_once 'db.php';
// Ensure TCPDF is included and available
if (!class_exists('TCPDF')) {
    $tcpdfPath = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    if (file_exists($tcpdfPath)) {
        require_once $tcpdfPath;
    } else {
        // Try XAMPP typical path if not found in vendor
        $tcpdfPathAlt = __DIR__ . '/../../../tcpdf/tcpdf.php';
        if (file_exists($tcpdfPathAlt)) {
            require_once $tcpdfPathAlt;
        } else {
            die('TCPDF library not found. Please install TCPDF in vendor/tecnickcom/tcpdf or tcpdf directory.');
        }
    }
}

// Define TCPDF constants if not already defined
if (!defined('PDF_PAGE_ORIENTATION')) define('PDF_PAGE_ORIENTATION', 'P');
if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');

class ReceiptGenerator
{
    private $conn;
    private $order;
    private $user;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function loadOrder($orderId)
    {
        $stmt = $this->conn->prepare("
            SELECT o.*, u.name, u.email, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $orderId);
        if (!$stmt->execute()) {
            return false;
        }

        // Try to fetch result using get_result (requires mysqlnd). Fallback to bind_result.
        $result = null;
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            if ($result && ($row = $result->fetch_assoc())) {
                $this->order = $row;
            } else {
                return false;
            }
        } else {
            // Fallback for environments without mysqlnd
            $meta = $stmt->result_metadata();
            if (!$meta) {
                return false;
            }
            $fields = $meta->fetch_fields();
            $params = [];
            $row = [];
            foreach ($fields as $field) {
                $params[] = &$row[$field->name];
            }
            call_user_func_array([$stmt, 'bind_result'], $params);
            if ($stmt->fetch()) {
                // $row contains the fetched values, but as references; create associative array
                $this->order = [];
                foreach ($row as $k => $v) {
                    $this->order[$k] = $v;
                }
            } else {
                return false;
            }
        }

        // Ensure items is an array
        $items = json_decode($this->order['items'] ?? '[]', true);
        if (!is_array($items)) {
            $items = [];
        }
        $this->order['items'] = $items;

        // Normalize numeric fields
        $this->order['subtotal'] = isset($this->order['subtotal']) ? (float)$this->order['subtotal'] : 0.0;
        $this->order['tax'] = isset($this->order['tax']) ? (float)$this->order['tax'] : 0.0;
        $this->order['discount'] = isset($this->order['discount']) ? (float)$this->order['discount'] : 0.0;
        $this->order['total'] = isset($this->order['total']) ? (float)$this->order['total'] : 0.0;

        return true;
    }

    public function generatePDF()
    {
        if (empty($this->order) || !isset($this->order['id'])) {
            return false;
        }

        // Ensure TCPDF is loaded
        if (!class_exists('TCPDF')) {
            // Try to include TCPDF if not already loaded
            $tcpdfPath = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
            if (file_exists($tcpdfPath)) {
                require_once $tcpdfPath;
            } else {
                $tcpdfPathAlt = __DIR__ . '/../../../tcpdf/tcpdf.php';
                if (file_exists($tcpdfPathAlt)) {
                    require_once $tcpdfPathAlt;
                } else {
                    die('TCPDF library not loaded. Please check your vendor/tecnickcom/tcpdf or tcpdf installation.');
                }
            }
        }
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('CarWash System');
        $pdf->SetAuthor('CarWash');
        $pdf->SetTitle('Receipt #' . $this->order['id']);

        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Set font with fallback
        $font = 'dejavusans';
        try {
            $pdf->SetFont($font, '', 10);
        } catch (Exception $e) {
            $pdf->SetFont('helvetica', '', 10);
        }

        // Generate content
        $html = $this->generateReceiptHTML();

        // Print content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document as string
        return $pdf->Output('receipt_' . $this->order['id'] . '.pdf', 'S');
    }

    private function formatCurrency($amount)
    {
        return number_format((float)$amount, 2, ',', '.');
    }

    public function generateReceiptHTML()
    {
        $createdAt = $this->order['created_at'] ?? null;
        $date = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '';

        $html = <<<HTML
        <div style="font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #2563eb;">CarWash</h1>
                <h2>Fatura #{$this->order['id']}</h2>
            </div>

            <div style="margin-bottom: 20px;">
                <strong>Tarih:</strong> {$date}<br>
                <strong>Müşteri:</strong> {$this->order['name']}<br>
                <strong>E-posta:</strong> {$this->order['email']}<br>
                <strong>Telefon:</strong> {$this->order['phone']}
            </div>

            <table cellpadding="5" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="border: 1px solid #e5e7eb; text-align: left;">Hizmet</th>
                        <th style="border: 1px solid #e5e7eb; text-align: right;">Fiyat</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        if (!empty($this->order['items']) && is_array($this->order['items'])) {
            foreach ($this->order['items'] as $item) {
                $serviceName = htmlspecialchars($item['service_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $price = isset($item['price']) ? $this->formatCurrency($item['price']) : $this->formatCurrency(0);
                $html .= <<<HTML
                    <tr>
                        <td style="border: 1px solid #e5e7eb;">{$serviceName}</td>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">{$price} TL</td>
                    </tr>
                HTML;
            }
        } else {
            $html .= <<<HTML
                    <tr>
                        <td colspan="2" style="border: 1px solid #e5e7eb; text-align: center;">No items</td>
                    </tr>
            HTML;
        }

        $subtotal = $this->formatCurrency($this->order['subtotal']);
        $tax = $this->formatCurrency($this->order['tax']);
        $discount = $this->formatCurrency($this->order['discount']);
        $total = $this->formatCurrency($this->order['total']);

        $html .= <<<HTML
                </tbody>
                <tfoot>
                    <tr>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">Ara Toplam:</td>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">{$subtotal} TL</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">KDV (18%):</td>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">{$tax} TL</td>
                    </tr>
        HTML;

        if ($this->order['discount'] > 0) {
            $html .= <<<HTML
                    <tr>
                        <td style="border: 1px solid #e5e7eb; text-align: right;">İndirim:</td>
                        <td style="border: 1px solid #e5e7eb; text-align: right; color: #059669;">-{$discount} TL</td>
                    </tr>
            HTML;
        }

        $html .= <<<HTML
                    <tr>
                        <td style="border: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Toplam:</td>
                        <td style="border: 1px solid #e5e7eb; text-align: right; font-weight: bold;">{$total} TL</td>
                    </tr>
                </tfoot>
            </table>

            <div style="margin-top: 20px; text-align: center; color: #6b7280; font-size: 12px;">
                Bizi tercih ettiğiniz için teşekkür ederiz!
            </div>
        </div>
        HTML;

        return $html;
    }
}
