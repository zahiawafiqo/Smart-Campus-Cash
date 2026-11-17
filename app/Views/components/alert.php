<?php
/**
 * Reusable Alert Component
 * Props:
 * - type (string): primary|secondary|success|danger|warning|info (default: info)
 * - title (string|null)
 * - message (string)
 * - dismissible (bool): default true
 * - icon (string|null): Bootstrap icon class
 * - class (string|null)
 */

$type = $type ?? 'info';
$title = $title ?? null;
$message = $message ?? '';
$dismissible = isset($dismissible) ? (bool)$dismissible : true;
$class = $class ?? '';

// Default icon mapping jika tidak di-supply
if (!isset($icon)) {
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill',
        'primary' => 'bi-bell-fill'
    ];
    $icon = $iconMap[$type] ?? 'bi-info-circle-fill';
}
?>

<div class="alert alert-<?= e($type) ?> <?= $dismissible ? 'alert-dismissible' : '' ?> fade show border-0 shadow-xs d-flex align-items-start gap-2.5 rounded-3 <?= e($class) ?>" role="alert">
    <?php if ($icon): ?>
        <i class="bi <?= e($icon) ?> fs-5 flex-shrink-0 mt-0.5"></i>
    <?php endif; ?>
    <div class="flex-grow-1">
        <?php if ($title): ?>
            <h6 class="alert-heading mb-1 fw-bold"><?= e($title) ?></h6>
        <?php endif; ?>
        <div class="small mb-0"><?= $message ?></div>
    </div>
    <?php if ($dismissible): ?>
        <button type="button" class="btn-close ms-auto mt-0.5" data-bs-dismiss="alert" aria-label="Tutup"></button>
    <?php endif; ?>
</div>
