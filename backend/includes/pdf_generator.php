<?php
// Simple PDF generator class
class PDFGenerator
{
    private $pageWidth = 595;    // A4 width in points
    private $pageHeight = 842;   // A4 height in points
    private $margin = 50;
    private $currentY;
    private $content;

    public function __construct()
    {
        $this->currentY = $this->margin;
        $this->content = '';
    }

    public function addText($text, $size = 12, $bold = false)
    {
        $text = $this->escapeText($text);

        $this->output("BT\n");
        $this->output("/F1 {$size} Tf\n");
        $this->output("{$this->margin} " . ($this->pageHeight - $this->currentY) . " Td\n");
        $this->output("({$text}) Tj\n");
        $this->output("ET\n");

        $this->currentY += $size + 5;
    }

    public function addTable($headers, $data)
    {
        if (empty($headers) || !is_array($headers)) {
            throw new Exception('Invalid table headers');
        }

        $columnCount = count($headers);
        $columnWidth = (int)(($this->pageWidth - ($this->margin * 2)) / $columnCount);

        // Draw headers
        $x = $this->margin;
        $y = $this->pageHeight - $this->currentY;

        foreach ($headers as $header) {
            $this->drawCell($x, $y, $columnWidth, 20, $header, true);
            $x += $columnWidth;
        }

        $this->currentY += 25;

        // Draw data rows
        foreach ($data as $row) {
            $x = $this->margin;
            $y = $this->pageHeight - $this->currentY;

            foreach ($row as $cell) {
                $this->drawCell($x, $y, $columnWidth, 20, $cell);
                $x += $columnWidth;
            }

            $this->currentY += 25;
        }
    }

    private function drawCell($x, $y, $width, $height, $text, $isHeader = false)
    {
        $text = $this->escapeText($text);

        // Draw cell border
        $this->output("{$width} {$height} re\n");
        $this->output("S\n");

        // Add text
        $this->output("BT\n");
        $this->output("/F1 10 Tf\n");
        $this->output(($x + 5) . " " . ($y - 15) . " Td\n");
        $this->output("({$text}) Tj\n");
        $this->output("ET\n");
    }

    private function escapeText($text)
    {
        return str_replace(
            ['\\', '(', ')', '\n'],
            ['\\\\', '\\(', '\\)', '\\n'],
            $text
        );
    }

    private function output($str)
    {
        $this->content .= $str;
    }

    public function save($filename)
    {
        // Basic PDF structure
        $pdf = "%PDF-1.4\n";

        // Add content
        $pdf .= "1 0 obj\n";
        $pdf .= "<< /Type /Pages /Kids [2 0 R] /Count 1 >>\n";
        $pdf .= "endobj\n";

        $pdf .= "2 0 obj\n";
        $pdf .= "<< /Type /Page /Parent 1 0 R /Resources << /Font << /F1 3 0 R >> >> /Contents 4 0 R >>\n";
        $pdf .= "endobj\n";

        $pdf .= "3 0 obj\n";
        $pdf .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
        $pdf .= "endobj\n";

        $pdf .= "4 0 obj\n";
        $pdf .= "<< /Length " . strlen($this->content) . " >>\n";
        $pdf .= "stream\n";
        $pdf .= $this->content;
        $pdf .= "\nendstream\n";
        $pdf .= "endobj\n";

        // Write to file
        file_put_contents($filename, $pdf);
    }
}
