<?php
/**
 * Komponen tombol WhatsApp generik.
 *
 * Parameter yang bisa dikirim dari view:
 * - $phone : string|null  Nomor HP mentah (08..., +62..., 62..., 8...)
 * - $label : string       Teks tombol, contoh: "WhatsApp"
 * - $class : string       Kelas CSS tambahan (opsional)
 * - $text  : string|null  Prefill message (opsional)
 */

helper('phone');

$phone = $phone ?? null;
$label = $label ?? 'WhatsApp';
$class = $class ?? 'btn btn-success btn-sm';
$text  = $text ?? null;

$waUrl = wa_url($phone, $text);
?>

<?php if (! empty($waUrl)): ?>
    <a href="<?= esc($waUrl) ?>"
       target="_blank"
       rel="noopener"
       class="<?= esc($class) ?>"
       title="Chat via WhatsApp">
        <i class="mdi mdi-whatsapp me-1"></i><?= esc($label) ?>
    </a>
<?php endif; ?>
