<?php
// categories/index.php — menggunakan pola Bootstrap Modal (sama seperti resetPasswordModal di users/index.php)
// Modal Tambah & Edit Category ditambahkan di bawah tabel; file create.php & edit.php TIDAK diubah.
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Kategori Transaksi</h4>
        <p class="text-secondary small mb-0">Klasifikasi pos pemasukan (Income) dan pengeluaran (Expense) keuangan kampus.</p>
    </div>
    <button type="button" id="btnTambahKategori" class="btn btn-primary btn-sm"
            onclick="openCategoryModal('tambah')">
        <i class="bi bi-plus-circle me-1"></i> Tambah Kategori Baru
    </button>
</div>

<?php if (!empty($success)): ?>
    <?php render_component('alert', ['type' => 'success', 'message' => $success, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('admin/categories') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari kode atau nama kategori..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    <option value="income" <?= $selectedType === 'income' ? 'selected' : '' ?>>Pemasukan (Income)</option>
                    <option value="expense" <?= $selectedType === 'expense' ? 'selected' : '' ?>>Pengeluaran (Expense)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1" <?= $selectedStatus === '1' ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= $selectedStatus === '0' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= url('admin/categories') ?>" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Categories -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Tipe Arus Kas</th>
                    <th>Deskripsi</th>
                    <th>Total Mutasi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($categories)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada kategori yang cocok.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="font-monospace fw-bold text-primary"><?= e($c['code']) ?></td>
                            <td class="fw-bold text-dark"><?= e($c['name']) ?></td>
                            <td>
                                <?php if ($c['type'] === 'income'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-down-left me-1"></i>Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-arrow-up-right me-1"></i>Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($c['description'] ?: '-') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$c['tx_count'] ?> Transaksi</span>
                            </td>
                            <td>
                                <?php if ($c['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            title="Edit Kategori"
                                            onclick="openCategoryModal('edit', {
                                                id: <?= (int)$c['id'] ?>,
                                                code: <?= json_encode($c['code']) ?>,
                                                name: <?= json_encode($c['name']) ?>,
                                                type: <?= json_encode($c['type']) ?>,
                                                description: <?= json_encode($c['description'] ?? '') ?>,
                                                is_active: <?= $c['is_active'] ? '1' : '0' ?>
                                            })">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="<?= url('admin/categories/delete?id=' . $c['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top">
            <?php
            render_component('pagination', [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'baseUrl' => url('admin/categories?search=' . urlencode($search) . '&type=' . urlencode($selectedType)),
                'totalItems' => $total,
                'perPage' => $perPage
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================================================================
     MODAL: Tambah / Edit Kategori Transaksi
     Mengikuti pola Bootstrap 5 modal yang sudah ada (resetPasswordModal)
     Form submit POST langsung ke route controller — tidak ada AJAX.
     ================================================================ -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-3">

            <!-- Header Modal -->
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="categoryModalLabel">
                    <i class="bi bi-tags text-primary me-1" id="categoryModalIcon"></i>
                    <span id="categoryModalTitle">Tambah Kategori Transaksi</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"
                        id="categoryModalCloseBtn"></button>
            </div>

            <!-- Form: action & method berubah via JavaScript sesuai mode tambah/edit -->
            <form id="categoryModalForm" method="POST" action="<?= url('admin/categories') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="modal-body py-3">
                    <!-- Error di dalam modal (jika form disubmit dan kembali dengan error) -->
                    <?php if (!empty($error) && !empty($_SESSION['modal_mode'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 small py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="cat_code" class="form-label small fw-semibold">
                                Kode Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="cat_code" name="code"
                                   class="form-control font-monospace"
                                   placeholder="Contoh: KAT-SPP" required
                                   value="<?= e($_SESSION['old_input']['code'] ?? '') ?>">
                            <div class="invalid-feedback">Kode kategori wajib diisi.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="cat_name" class="form-label small fw-semibold">
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="cat_name" name="name"
                                   class="form-control"
                                   placeholder="Contoh: SPP Kuliah Reguler" required
                                   value="<?= e($_SESSION['old_input']['name'] ?? '') ?>">
                            <div class="invalid-feedback">Nama kategori wajib diisi.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="cat_type" class="form-label small fw-semibold">
                                Tipe Arus Kas <span class="text-danger">*</span>
                            </label>
                            <select id="cat_type" name="type" class="form-select" required>
                                <option value="income">Pemasukan (Income)</option>
                                <option value="expense">Pengeluaran (Expense)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cat_is_active" class="form-label small fw-semibold">Status Kategori</label>
                            <select id="cat_is_active" name="is_active" class="form-select">
                                <option value="1">Aktif Digunakan</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="cat_description" class="form-label small fw-semibold">Deskripsi / Keterangan Pos</label>
                        <textarea id="cat_description" name="description"
                                  class="form-control" rows="3"
                                  placeholder="Penjelasan mengenai pos keuangan ini..."></textarea>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                            id="categoryModalCancelBtn">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3" id="categoryModalSubmitBtn">
                        <span id="categoryModalSubmitIcon" class="me-1"><i class="bi bi-check2-circle"></i></span>
                        <span id="categoryModalSubmitText">Simpan Kategori</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ---- State ----
    let categoryModal = null;
    let formDirty = false;
    const form = document.getElementById('categoryModalForm');

    // ---- Init Bootstrap Modal ----
    function getCategoryModal() {
        if (!categoryModal) {
            categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'), {
                backdrop: 'static', // cegah tutup klik luar
                keyboard: false     // cegah ESC langsung; kita tangani manual
            });
        }
        return categoryModal;
    }

    // ---- Deteksi form sudah diisi (untuk konfirmasi tutup) ----
    if (form) {
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.addEventListener('input', function () { formDirty = true; });
            el.addEventListener('change', function () { formDirty = true; });
        });
    }

    // ---- ESC: konfirmasi jika form kotor ----
    document.getElementById('categoryModal').addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            tryCloseModal();
        }
    });

    // ---- Tombol tutup (×) & Batal ----
    document.getElementById('categoryModalCloseBtn').addEventListener('click', tryCloseModal);
    document.getElementById('categoryModalCancelBtn').addEventListener('click', tryCloseModal);

    function tryCloseModal() {
        if (formDirty) {
            if (!confirm('Data yang sudah Anda isi belum disimpan. Tutup dan buang perubahan?')) {
                return;
            }
        }
        getCategoryModal().hide();
    }

    // ---- Loading state saat submit ----
    if (form) {
        form.addEventListener('submit', function (e) {
            // Jalankan validasi native browser terlebih dahulu
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            // Tampilkan indikator loading
            var btn = document.getElementById('categoryModalSubmitBtn');
            var txt = document.getElementById('categoryModalSubmitText');
            var ico = document.getElementById('categoryModalSubmitIcon');
            btn.disabled = true;
            ico.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            txt.textContent = 'Menyimpan...';
        });
    }

    // ---- Fungsi utama: buka modal dengan mode tambah atau edit ----
    window.openCategoryModal = function (mode, data) {
        data = data || {};
        formDirty = false;
        form.classList.remove('was-validated');

        var title     = document.getElementById('categoryModalTitle');
        var icon      = document.getElementById('categoryModalIcon');
        var submitTxt = document.getElementById('categoryModalSubmitText');
        var submitIco = document.getElementById('categoryModalSubmitIcon');
        var submitBtn = document.getElementById('categoryModalSubmitBtn');

        // Reset loading state
        submitBtn.disabled = false;
        submitIco.innerHTML = '<i class="bi bi-check2-circle"></i>';

        if (mode === 'tambah') {
            // --- Mode Tambah ---
            title.textContent = 'Tambah Kategori Transaksi';
            icon.className = 'bi bi-plus-circle text-primary me-1';
            submitTxt.textContent = 'Simpan Kategori';
            form.action = '<?= url('admin/categories') ?>';

            // Kosongkan semua field
            document.getElementById('cat_code').value        = '';
            document.getElementById('cat_name').value        = '';
            document.getElementById('cat_type').value        = 'income';
            document.getElementById('cat_is_active').value   = '1';
            document.getElementById('cat_description').value = '';

        } else if (mode === 'edit') {
            // --- Mode Edit ---
            title.textContent = 'Edit Kategori Transaksi';
            icon.className = 'bi bi-pencil-square text-warning me-1';
            submitTxt.textContent = 'Simpan Perubahan';
            form.action = '<?= url('admin/categories/update') ?>?id=' + data.id;

            // Isi field dengan data baris yang dipilih
            document.getElementById('cat_code').value        = data.code        || '';
            document.getElementById('cat_name').value        = data.name        || '';
            document.getElementById('cat_type').value        = data.type        || 'income';
            document.getElementById('cat_is_active').value   = String(data.is_active);
            document.getElementById('cat_description').value = data.description || '';
        }

        getCategoryModal().show();

        // Fokus ke field pertama setelah modal terbuka
        document.getElementById('categoryModal').addEventListener('shown.bs.modal', function focusFirst() {
            document.getElementById('cat_code').focus();
            document.getElementById('categoryModal').removeEventListener('shown.bs.modal', focusFirst);
        });
    };

    // ---- Buka modal otomatis jika ada old_input (form disubmit tapi gagal validasi server) ----
    <?php if (!empty($_SESSION['old_input']) && !empty($_SESSION['modal_mode'])): ?>
    (function () {
        var mode = <?= json_encode($_SESSION['modal_mode']) ?>;
        var oldData = {
            id:          <?= json_encode($_SESSION['old_input']['_edit_id'] ?? 0) ?>,
            code:        <?= json_encode($_SESSION['old_input']['code'] ?? '') ?>,
            name:        <?= json_encode($_SESSION['old_input']['name'] ?? '') ?>,
            type:        <?= json_encode($_SESSION['old_input']['type'] ?? 'income') ?>,
            is_active:   <?= json_encode($_SESSION['old_input']['is_active'] ?? '1') ?>,
            description: <?= json_encode($_SESSION['old_input']['description'] ?? '') ?>
        };
        window.addEventListener('DOMContentLoaded', function () {
            openCategoryModal(mode, oldData);
        });
    })();
    <?php unset($_SESSION['modal_mode']); ?>
    <?php endif; ?>

})();
</script>
