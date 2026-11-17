<?php
/**
 * Reusable Table Component
 * Props:
 * - columns (array): e.g. [['key' => 'nim', 'label' => 'NIM'], ['key' => 'name', 'label' => 'Nama Mahasiswa']]
 * - rows (array): array of associative arrays
 * - emptyMessage (string): pesan jika rows kosong
 * - striped (bool): default true
 * - hover (bool): default true
 * - bordered (bool): default false
 * - responsive (bool): default true
 * - class (string|null)
 */

$columns = $columns ?? [];
$rows = $rows ?? [];
$emptyMessage = $emptyMessage ?? 'Belum ada data tersedia.';
$striped = isset($striped) ? (bool)$striped : true;
$hover = isset($hover) ? (bool)$hover : true;
$bordered = isset($bordered) ? (bool)$bordered : false;
$responsive = isset($responsive) ? (bool)$responsive : true;
$class = $class ?? '';

$tableClasses = [
    'table',
    'align-middle',
    $striped ? 'table-striped' : '',
    $hover ? 'table-hover' : '',
    $bordered ? 'table-bordered' : '',
    $class
];
$tableClassStr = trim(implode(' ', array_filter($tableClasses)));
?>

<?php if ($responsive): ?>
<div class="table-responsive rounded-3 border bg-white shadow-xs">
<?php endif; ?>

<table class="<?= e($tableClassStr) ?> mb-0">
    <thead class="table-light text-secondary small text-uppercase fw-bold">
        <tr>
            <?php foreach ($columns as $col): ?>
                <th scope="col" class="<?= e($col['class'] ?? '') ?>">
                    <?= e($col['label'] ?? '') ?>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) ?: 1 ?>" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-1 opacity-50"></i>
                    <span><?= e($emptyMessage) ?></span>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <?php 
                            $key = $col['key'] ?? '';
                            $cellValue = $row[$key] ?? '';
                            $isRaw = !empty($col['raw']); // Jika kolom mengandung html (misal badge/tombol aksi)
                        ?>
                        <td class="<?= e($col['tdClass'] ?? '') ?>">
                            <?= $isRaw ? $cellValue : e($cellValue) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($responsive): ?>
</div>
<?php endif; ?>
