<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdfService {
    /**
     * Build and configure a Dompdf instance with safe production settings.
     */
    public static function createDompdf(): Dompdf {
        $options = new Options();
        $options->set('isRemoteEnabled', false);          // Prevent SSRF / remote resource fetching
        $options->set('isPhpEnabled', false);             // Prevent PHP code execution inside templates
        $options->set('defaultFont', 'DejaVu Sans');      // Bundled font with UTF-8 & Rupee symbol support
        $options->set('isFontSubsettingEnabled', true);   // Optimize generated PDF size
        $options->set('dpi', 96);

        return new Dompdf($options);
    }

    /**
     * Render the given HTML into PDF binary data.
     */
    public static function renderHtml(string $html): string {
        $dompdf = self::createDompdf();
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return (string)$dompdf->output();
    }

    /**
     * Sanitize a job ID for safe usage in HTTP Content-Disposition headers.
     */
    public static function sanitizeFilename(string $jobId): string {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $jobId);
        return 'Invoice-' . (!empty($clean) ? $clean : 'Document') . '.pdf';
    }

    /**
     * Stream the rendered PDF directly to the browser output.
     */
    public static function stream(string $html, string $jobId, bool $download = false): void {
        $pdfContent = self::renderHtml($html);
        $filename   = self::sanitizeFilename($jobId);

        // Discard any prior output buffers so no debug text or whitespace corrupts binary PDF
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');

        echo $pdfContent;
        exit;
    }
}
