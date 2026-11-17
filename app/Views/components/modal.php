<?php
/**
 * Reusable Modal Component
 * Props:
 * - id (string): modal DOM ID (required)
 * - title (string)
 * - body (string): HTML content
 * - footer (string|null): HTML buttons
 * - size (string|null): sm|lg|xl (default: null)
 * - centered (bool): default true
 * - scrollable (bool): default false
 * - staticBackdrop (bool): default false
 */

$id = $id ?? 'appModal';
$title = $title ?? 'Modal Title';
$body = $body ?? '';
$footer = $footer ?? null;
$size = $size ?? '';
$sizeClass = $size ? "modal-{$size}" : '';
$centered = isset($centered) ? (bool)$centered : true;
$scrollable = isset($scrollable) ? (bool)$scrollable : false;
$staticBackdrop = !empty($staticBackdrop);
?>

<div class="modal fade" id="<?= e($id) ?>" tabindex="-1" aria-labelledby="<?= e($id) ?>Label" aria-hidden="true" <?= $staticBackdrop ? 'data-bs-backdrop="static" data-bs-keyboard="false"' : '' ?>>
    <div class="modal-dialog <?= $sizeClass ?> <?= $centered ? 'modal-dialog-centered' : '' ?> <?= $scrollable ? 'modal-dialog-scrollable' : '' ?>">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom border-light-subtle py-3">
                <h5 class="modal-title fs-6 fw-bold text-dark" id="<?= e($id) ?>Label"><?= e($title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-3">
                <?= $body ?>
            </div>
            <?php if ($footer): ?>
                <div class="modal-footer border-top border-light-subtle py-2.5">
                    <?= $footer ?>
                </div>
            <?php else: ?>
                <div class="modal-footer border-top border-light-subtle py-2.5">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
