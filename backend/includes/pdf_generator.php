<?php
// Simple PDF generator class
class PDFGenerator {
    private $pdf;
    private $pageWidth = 595.28;  // A4 width in points
    private $pageHeight = 841.89; // A4 height in points
    private $margin = 50;
    private $currentY = 0;

    public function __construct() {
        // Enable output buffering
        ob_start();
        $this->currentY = $this->margin;
    }

    public function addHeader($title) {
        $this->output('
            %PDF-1.7
            1 0 obj
            << /Type /Catalog
               /Pages 2 0 R
            >>
            endobj
        ');

        // Add title
        $this->currentY += 20;
        $this->addText($title, 16, true);
        $this->currentY += 20;
    }

    public function addText($text, $size = 12, $bold = false) {
        // Simple text rendering
        $this->output("BT\n");
        $this->output("/F1 $size Tf\n");
        $this->output("{$this->margin} {$this->pageHeight - $this->currentY} Td\n");
        $this->output("({$text}) Tj\n");
        $this->output("ET\n");
        
        $this->currentY += $size + 5;
    }

    public function addTable($headers, $data) {
        $columnWidth = ($this->pageWidth - ($this->margin * 2)) / count($headers);
        
        // Draw headers
        $x = $this->margin;
        foreach ($headers as $header) {
            $this->drawCell($x, $this->currentY, $columnWidth, 20, $header, true);
            $x += $columnWidth;
        }
        
        $this->currentY += 20;

        // Draw data
        foreach ($data as $row) {
            $x = $this->margin;
            foreach ($row as $cell) {
                $this->drawCell($x, $this->currentY, $columnWidth, 20, $cell);
                $x += $columnWidth;
            }
            $this->currentY += 20;
        }
    }

    private function drawCell($x, $y, $width, $height, $text, $isHeader = false) {
        // Draw cell border
        $this->output("q\n");
        $this->output("0.5 w\n"); // line width
        $this->output("{$x} {$this->pageHeight - $y} {$width} {$height} re\n");
        $this->output("S\n"); // stroke

        // Add text
        $fontSize = $isHeader ? 12 : 10;
        $this->output("BT\n");
        $this->output("/F1 $fontSize Tf\n");
        $this->output(($x + 5) . " " . ($this->pageHeight - $y + 5) . " Td\n");
        $this->output("($text) Tj\n");
        $this->output("ET\n");
        $this->output("Q\n");
    }

    public function output($content) {
        echo $content . "\n";
    }

    public function save($filename) {
        $content = ob_get_clean();
        file_put_contents($filename, $content);
    }
}
?>