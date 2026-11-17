<?php
/**
 * Reusable Pagination Component
 * Props:
 * - currentPage (int): default 1
 * - totalPages (int): default 1
 * - baseUrl (string): URL prefix for page link (e.g. url('mahasiswa'))
 * - queryParam (string): default 'page'
 * - showInfo (bool): default true
 * - totalItems (int|null)
 * - perPage (int|null)
 */

$currentPage = (int)($currentPage ?? 1);
$totalPages = max(1, (int)($totalPages ?? 1));
$baseUrl = $baseUrl ?? '';
$queryParam = $queryParam ?? 'page';
$showInfo = isset($showInfo) ? (bool)$showInfo : true;
$totalItems = $totalItems ?? null;
$perPage = $perPage ?? 10;

function buildPageUrl(string $base, string $param, int $p): string {
    $separator = str_contains($base, '?') ? '&' : '?';
    return e($base . $separator . $param . '=' . $p);
}
?>

<div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 py-2">
    <?php if ($showInfo && $totalItems !== null): ?>
        <?php
            $from = ($currentPage - 1) * $perPage + 1;
            $to = min($currentPage * $perPage, $totalItems);
        ?>
        <div class="text-muted small">
            Menampilkan <span class="fw-semibold text-dark"><?= $totalItems > 0 ? $from : 0 ?></span> - 
            <span class="fw-semibold text-dark"><?= $to ?></span> dari 
            <span class="fw-semibold text-dark"><?= $totalItems ?></span> data
        </div>
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm mb-0">
            <!-- Tombol Previous -->
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage > 1 ? buildPageUrl($baseUrl, $queryParam, $currentPage - 1) : '#' ?>" aria-label="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <!-- Nomor Halaman -->
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === 1 || $p === $totalPages || ($p >= $currentPage - 1 && $p <= $currentPage + 1)): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= buildPageUrl($baseUrl, $queryParam, $p) ?>"><?= $p ?></a>
                    </li>
                <?php elseif ($p === $currentPage - 2 || $p === $currentPage + 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage < $totalPages ? buildPageUrl($baseUrl, $queryParam, $currentPage + 1) : '#' ?>" aria-label="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
