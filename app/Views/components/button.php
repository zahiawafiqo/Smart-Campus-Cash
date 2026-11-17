<?php
/**
 * Reusable Button Component
 * Props:
 * - text (string)
 * - type (string): button|submit|reset (default: button)
 * - variant (string): primary|secondary|success|danger|warning|info|outline-* (default: primary)
 * - size (string): sm|md|lg (default: md)
 * - icon (string|null): e.g. 'bi bi-check2-circle'
 * - iconPosition (string): left|right (default: left)
 * - id (string|null)
 * - class (string|null)
 * - disabled (bool)
 * - attributes (string|null): e.g. 'data-bs-toggle="modal" data-bs-target="#myModal"'
 */

$text = $text ?? 'Button';
$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
$icon = $icon ?? null;
$iconPosition = $iconPosition ?? 'left';
$id = isset($id) ? "id=\"{$id}\"" : '';
$class = $class ?? '';
$disabled = !empty($disabled) ? 'disabled' : '';
$attributes = $attributes ?? '';
?>
<button type="<?= e($type) ?>" <?= $id ?> class="btn btn-<?= e($variant) ?> <?= $sizeClass ?> <?= e($class) ?>" <?= $disabled ?> <?= $attributes ?>>
    <?php if ($icon && $iconPosition === 'left'): ?>
        <i class="<?= e($icon) ?> me-1.5"></i>
    <?php endif; ?>
    <span><?= e($text) ?></span>
    <?php if ($icon && $iconPosition === 'right'): ?>
        <i class="<?= e($icon) ?> ms-1.5"></i>
    <?php endif; ?>
</button>
