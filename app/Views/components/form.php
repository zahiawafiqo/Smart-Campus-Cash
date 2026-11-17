<?php
/**
 * Reusable Form Input Component
 * Props:
 * - type (string): text|email|password|number|date|textarea|select|checkbox|switch
 * - name (string)
 * - label (string|null)
 * - value (mixed)
 * - placeholder (string|null)
 * - options (array): untuk select e.g. ['val' => 'Label']
 * - rows (int): untuk textarea (default: 3)
 * - required (bool)
 * - disabled (bool)
 * - helpText (string|null)
 * - error (string|null)
 * - icon (string|null): Bootstrap icon class
 * - id (string|null)
 * - class (string|null)
 */

$type = $type ?? 'text';
$name = $name ?? '';
$id = $id ?? 'input_' . ($name ?: uniqid());
$label = $label ?? null;
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$required = !empty($required);
$disabled = !empty($disabled) ? 'disabled' : '';
$helpText = $helpText ?? null;
$error = $error ?? null;
$icon = $icon ?? null;
$class = $class ?? '';
$isInvalidClass = $error ? 'is-invalid' : '';
?>
<div class="mb-3 <?= e($class) ?>">
    <?php if ($label && !in_array($type, ['checkbox', 'switch'])): ?>
        <label for="<?= e($id) ?>" class="form-label fw-semibold text-secondary-emphasis">
            <?= e($label) ?>
            <?php if ($required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if ($type === 'textarea'): ?>
        <textarea 
            name="<?= e($name) ?>" 
            id="<?= e($id) ?>" 
            rows="<?= (int)($rows ?? 3) ?>" 
            class="form-control <?= $isInvalidClass ?>" 
            placeholder="<?= e($placeholder) ?>" 
            <?= $required ? 'required' : '' ?> 
            <?= $disabled ?>><?= e($value) ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <select 
            name="<?= e($name) ?>" 
            id="<?= e($id) ?>" 
            class="form-select <?= $isInvalidClass ?>" 
            <?= $required ? 'required' : '' ?> 
            <?= $disabled ?>>
            <?php if ($placeholder): ?>
                <option value="" disabled <?= empty($value) ? 'selected' : '' ?>><?= e($placeholder) ?></option>
            <?php endif; ?>
            <?php foreach (($options ?? []) as $optValue => $optLabel): ?>
                <option value="<?= e($optValue) ?>" <?= (string)$optValue === (string)$value ? 'selected' : '' ?>>
                    <?= e($optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'checkbox' || $type === 'switch'): ?>
        <div class="form-check <?= $type === 'switch' ? 'form-switch' : '' ?>">
            <input 
                class="form-check-input <?= $isInvalidClass ?>" 
                type="checkbox" 
                name="<?= e($name) ?>" 
                id="<?= e($id) ?>" 
                value="1" 
                <?= $value ? 'checked' : '' ?> 
                <?= $disabled ?>>
            <?php if ($label): ?>
                <label class="form-check-label user-select-none" for="<?= e($id) ?>">
                    <?= e($label) ?>
                    <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="<?= $icon ? 'input-group' : '' ?>">
            <?php if ($icon): ?>
                <span class="input-group-text bg-light text-muted border-end-0">
                    <i class="<?= e($icon) ?>"></i>
                </span>
            <?php endif; ?>
            <input 
                type="<?= e($type) ?>" 
                name="<?= e($name) ?>" 
                id="<?= e($id) ?>" 
                value="<?= e($value) ?>" 
                class="form-control <?= $icon ? 'border-start-0 ps-1' : '' ?> <?= $isInvalidClass ?>" 
                placeholder="<?= e($placeholder) ?>" 
                <?= $required ? 'required' : '' ?> 
                <?= $disabled ?>>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="invalid-feedback d-block"><?= e($error) ?></div>
    <?php elseif ($helpText): ?>
        <div class="form-text text-muted"><?= e($helpText) ?></div>
    <?php endif; ?>
</div>
