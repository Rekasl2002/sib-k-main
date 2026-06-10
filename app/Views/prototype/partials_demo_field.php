<?php
$field = is_array($field ?? null) ? $field : [];
$index = (int) ($index ?? 0);
$wide = (string) ($wide ?? 'col-md-6');
$label = (string) ($field['label'] ?? 'Isian');
$type = (string) ($field['type'] ?? 'text');
$placeholder = (string) ($field['placeholder'] ?? '');
$options = is_array($field['options'] ?? null) ? $field['options'] : [];
$name = 'demo_field_' . $index;
?>

<div class="<?= esc($wide) ?>">
  <label class="form-label" for="<?= esc($name) ?>"><?= esc($label) ?></label>
  <?php if ($type === 'select'): ?>
    <select class="form-select" id="<?= esc($name) ?>">
      <option value="">Pilih <?= esc(strtolower($label)) ?></option>
      <?php foreach ($options as $option): ?>
        <option><?= esc($option) ?></option>
      <?php endforeach; ?>
    </select>
  <?php elseif ($type === 'textarea'): ?>
    <textarea class="form-control" id="<?= esc($name) ?>" rows="4" placeholder="<?= esc($placeholder) ?>"></textarea>
  <?php elseif ($type === 'date'): ?>
    <input class="form-control" id="<?= esc($name) ?>" type="date" value="<?= esc($placeholder ?: '2026-06-15') ?>">
  <?php elseif ($type === 'file'): ?>
    <input class="form-control" id="<?= esc($name) ?>" type="file" accept="<?= esc($placeholder ?: '.xlsx,.xls,.csv') ?>">
    <div class="form-text">Format yang disarankan: <?= esc($placeholder ?: '.xlsx, .xls, .csv') ?></div>
  <?php else: ?>
    <input class="form-control" id="<?= esc($name) ?>" type="text" placeholder="<?= esc($placeholder) ?>">
  <?php endif; ?>
</div>
