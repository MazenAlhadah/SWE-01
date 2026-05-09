<?php
class POPdfService {

    public function streamPurchaseOrderPdf($po) {
        $lines = [];
        $lines[] = 'Purchase Order';
        $lines[] = 'PO ID: ' . $po['po_id'];
        $lines[] = 'Supplier: ' . $this->clean($po['company_name']);
        $lines[] = 'Generated At: ' . $this->clean($po['generated_at']);
        $lines[] = 'Status: ' . $this->clean($po['status']);
        $lines[] = 'Digital Signature: ' . $this->clean($po['digital_signature']);
        $lines[] = '';
        $lines[] = 'Items';

        foreach ($po['items'] as $item) {
            $subtotal = (float)$item['quantity_ordered'] * (float)$item['unit_price'];
            $lines[] = $this->clean($item['sku']) . ' | ' .
                $this->clean($item['name']) . ' | Qty ' . (int)$item['quantity_ordered'] .
                ' | $' . number_format((float)$item['unit_price'], 2) .
                ' | $' . number_format($subtotal, 2);
        }

        $lines[] = '';
        $lines[] = 'Total Cost: $' . number_format((float)$po['total_cost'], 2);

        $content = $this->buildPdfText($lines);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="PO-' . (int)$po['po_id'] . '.pdf"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit();
    }

    private function buildPdfText($lines) {
        $stream = "BT\n/F1 12 Tf\n50 780 Td\n14 TL\n";

        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $stream .= "T*\n";
            }
            $stream .= '(' . $this->escapePdfText($line) . ") Tj\n";
        }

        $stream .= "ET";
        $length = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[] = strlen($pdf);
        $pdf .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";

        $offsets[] = strlen($pdf);
        $pdf .= "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";

        $offsets[] = strlen($pdf);
        $pdf .= "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources<< /Font<< /F1 4 0 R >> >> /Contents 5 0 R >>endobj\n";

        $offsets[] = strlen($pdf);
        $pdf .= "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

        $offsets[] = strlen($pdf);
        $pdf .= "5 0 obj<< /Length {$length} >>stream\n{$stream}\nendstream\nendobj\n";

        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function escapePdfText($text) {
        $text = $this->clean($text);
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return $text;
    }

    private function clean($text) {
        $text = (string)$text;
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        return trim($text);
    }
}
