<?php
/**
 * Reusable Card Component
 * Props:
 * - title (string|null)
 * - subtitle (string|null)
 * - icon (string|null): Bootstrap icon class
 * - iconBg (string|null): bg-primary, bg-success-subtle, etc.
 * - badge (string|null)
 * - badgeVariant (string|null): default 'primary'
 * - headerAction (string|null): HTML for buttons on right side of header
 * - body (string): content html/text
 * - footer (string|null)
 * - class (string|null)
 */

$title = $title ?? null;
$subtitle = $subtitle ?? null;
$icon = $icon ?? null;
$iconBg = $iconBg ?? 'bg-primary-subtle text-primary';
$badge = $badge ?? null;
$badgeVariant = $badgeVariant ?? 'primary';
$headerAction = $headerAction ?? null;
$body = $body ?? '';
$footer = $footer ?? null;
$class = $class ?? '';
?>

<div class="card border-0 shadow-sm rounded-3 <?= e($class) ?>">
    <?php if ($title || $icon || $headerAction || $badge): ?>
        <div class="card-header bg-transparent border-bottom border-light-subtle py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2.5">
                <?php if ($icon): ?>
                    <div class="p-2 rounded-2 <?= e($iconBg) ?> d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="<?= e($icon) ?> fs-5"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <?php if ($title): ?>
                        <h6 class="card-title mb-0 fw-bold text-dark"><?= e($title) ?></h6>
                    <?php endif; ?>
                    <?php if ($subtitle): ?>
                        <small class="text-muted d-block"><?= e($subtitle) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($badge): ?>
                    <span class="badge bg-<?= e($badgeVariant) ?> rounded-pill px-2.5 py-1.5"><?= e($badge) ?></span>
                <?php endif; ?>
                <?php if ($headerAction): ?>
                    <div><?= $headerAction ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-body">
        <?= $body ?>
    </div>

    <?php if ($footer): ?>
        <div class="card-footer bg-transparent border-top border-light-subtle py-2.5 text-muted small">
            <?= $footer ?>
        </div>
    <?php endif; ?>
</div>
