<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use Config\Services;

/**
 * PDFGenerator
 * Pembungkus Dompdf untuk CodeIgniter 4.
 *
 * Fitur:
 * - Remote asset enabled (IMG/CSS eksternal/absolute URL) (opsional, default ON).
 * - Chroot ke FCPATH (public/) untuk keamanan asset lokal.
 * - Temp & font cache di writable/dompdf.
 * - HTML5 parser aktif, default font Unicode (DejaVu Sans).
 * - Footer nomor halaman otomatis (template {PAGE_NUM}/{PAGE_COUNT}) dan bisa dimatikan.
 *
 * Catatan penggunaan di Controller CI4:
 * - Disarankan pakai $stream=false lalu return Response->setBody($binary)
 *   agar tidak bentrok dengan output buffering / header CI.
 */
class PDFGenerator
{
    protected Options $options;

    protected string $paper = 'A4';
    protected string $orientation = 'portrait';
    protected string $defaultFont = 'DejaVu Sans';

    /** Template footer; gunakan {PAGE_NUM}/{PAGE_COUNT}. Set null/'' untuk tanpa footer. */
    protected ?string $footerText = 'Hal. {PAGE_NUM}/{PAGE_COUNT}';

    /** Direktori kerja Dompdf (writable/dompdf) */
    protected string $baseDir;

    public function __construct(?array $opt = null)
    {
        $this->options = new Options();

        // Default options (aman & kompatibel)
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('isHtml5ParserEnabled', true);

        // Keamanan ekstra (umumnya tidak dibutuhkan di report view)
        $this->options->set('isPhpEnabled', false);
        $this->options->set('isJavascriptEnabled', false);

        $this->options->set('defaultMediaType', 'screen');
        $this->options->set('defaultFont', $this->defaultFont);

        // Direktori kerja Dompdf: writable/dompdf
        $this->baseDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dompdf';
        $this->ensureDir($this->baseDir);

        $fontDir = $this->baseDir . DIRECTORY_SEPARATOR . 'fonts';
        $this->ensureDir($fontDir);

        // Temp & cache
        $this->options->set('tempDir', $this->baseDir);
        $this->options->set('fontDir', $fontDir);
        $this->options->set('fontCache', $fontDir);

        /**
         * Chroot: batasi akses file lokal.
         * Minimal FCPATH (public/). Kalau kamu butuh load file dari writable/uploads,
         * kamu bisa tambah manual ke array chroot.
         */
        $chroot = [];
        $fc = realpath(FCPATH);
        if ($fc) {
            $chroot[] = $fc;
        }

        // Opsional: izinkan baca asset dari writable/uploads (kalau project kamu pakai itu)
        $uploads = realpath(WRITEPATH . 'uploads');
        if ($uploads) {
            $chroot[] = $uploads;
        }

        // Fallback aman jika realpath gagal
        if (empty($chroot)) {
            $chroot = [FCPATH];
        }

        $this->options->setChroot($chroot);

        // Override kustom bila diberikan
        if ($opt) {
            foreach ($opt as $k => $v) {
                $this->options->set($k, $v);
            }
        }
    }

    public function setPaper(string $paper = 'A4', string $orientation = 'portrait'): self
    {
        $this->paper = $paper;
        $this->orientation = $orientation;
        return $this;
    }

    /** Set footer template; null/'' untuk mematikan footer. */
    public function setFooterText(?string $template): self
    {
        $this->footerText = ($template !== null && trim($template) !== '') ? $template : null;
        return $this;
    }

    /**
     * Render view menjadi PDF dan stream ke browser (inline).
     * Jika $stream=false, mengembalikan string biner PDF.
     */
    public function generate(
        string $view,
        array $data = [],
        string $filename = 'report.pdf',
        bool $stream = true,
        ?string $paper = null,
        ?string $orientation = null
    ) {
        $renderer = Services::renderer();

        // setData default-nya escape, tapi view report biasanya sudah pakai esc() manual.
        // Tetap pakai setData standar agar konsisten dengan project kamu.
        $html = $renderer->setData($data)->render($view);

        $dompdf = $this->makeDompdf($paper ?? $this->paper, $orientation ?? $this->orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $this->applyFooter($dompdf);

        if ($stream) {
            // WARNING: stream() akan langsung output header+body (bisa bentrok dengan CI Response).
            // Untuk controller CI4, lebih aman pakai stream=false lalu setBody().
            return $dompdf->stream($filename, ['Attachment' => false]);
        }

        return $dompdf->output();
    }

    /**
     * Simpan PDF ke file dan kembalikan path-nya.
     */
    public function saveTo(
        string $view,
        array $data,
        string $savePath,
        string $paper = 'A4',
        string $orientation = 'portrait'
    ): string {
        $renderer = Services::renderer();
        $html = $renderer->setData($data)->render($view);

        $dompdf = $this->makeDompdf($paper, $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $this->applyFooter($dompdf);

        $dir = dirname($savePath);
        $this->ensureDir($dir);

        file_put_contents($savePath, $dompdf->output());
        return $savePath;
    }

    /**
     * Render HTML langsung menjadi biner PDF (tanpa view renderer).
     * Dipakai oleh controller download untuk full kontrol response.
     */
    public function render(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $dompdf = $this->makeDompdf($paper, $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $this->applyFooter($dompdf);

        return $dompdf->output();
    }

    // ============================
    // Helpers (internal)
    // ============================

    /** Buat instance Dompdf yang konsisten dengan base path & opsi. */
    protected function makeDompdf(string $paper, string $orientation): Dompdf
    {
        $dompdf = new Dompdf($this->options);

        // Base path penting untuk asset relatif (CSS/IMG) dalam HTML.
        if (method_exists($dompdf, 'setBasePath')) {
            $dompdf->setBasePath(FCPATH);
        }

        $dompdf->setPaper($paper, $orientation);

        return $dompdf;
    }

    /** Tambahkan footer halaman adaptif jika template tersedia. */
    protected function applyFooter(Dompdf $dompdf): void
    {
        if (!$this->footerText) {
            return;
        }

        $canvas  = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font    = $metrics->get_font($this->defaultFont, 'normal');

        $w = $canvas->get_width();
        $h = $canvas->get_height();

        $size = 9;

        // Perkiraan lebar footer: ganti placeholder dengan angka maksimal
        $sample = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], ['999', '999'], $this->footerText);
        $textWidth = $metrics->getTextWidth($sample, $font, $size);

        // Margin bawah/kanan
        $marginRight  = 18;
        $marginBottom = 18;

        $x = max(18, $w - $marginRight - $textWidth);
        $y = max(18, $h - $marginBottom);

        $canvas->page_text($x, $y, $this->footerText, $font, $size, [0, 0, 0]);
    }

    /** Pastikan direktori ada (portable Windows/cPanel). */
    protected function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        // 0755 lebih aman daripada 0777
        @mkdir($dir, 0755, true);

        // Kalau gagal, coba lagi dengan 0775 (kadang hosting perlu group write)
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}
