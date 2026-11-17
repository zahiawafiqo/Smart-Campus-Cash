<?php
$roleHint = $roleHint ?? 'admin';
$testAccounts = [
    'admin' => ['label' => 'Admin', 'user' => 'admin', 'pass' => 'admin123', 'color' => 'danger'],
    'mahasiswa' => ['label' => 'Mahasiswa', 'user' => '2110114001', 'pass' => 'password123', 'color' => 'info'],
    'kasir' => ['label' => 'Kasir', 'user' => 'kasir01', 'pass' => 'password123', 'color' => 'warning'],
    'keuangan' => ['label' => 'Keuangan', 'user' => 'keuangan01', 'pass' => 'password123', 'color' => 'success']
];

$activeAcc = $testAccounts[$roleHint] ?? $testAccounts['admin'];
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="text-center mb-4">
                <div class="brand-icon bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Masuk ke Smart Campus Cash</h3>
                <p class="text-muted small">Sistem Informasi Kas Keuangan Terpadu Kampus</p>
            </div>

            <!-- Quick Account Switcher (For Demo & Testing) -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 p-2 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-1.5 px-1">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Pilih Akun Demo (Otomatis Terisi)</small>
                    <span class="badge bg-<?= $activeAcc['color'] ?>-subtle text-<?= $activeAcc['color'] ?> rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                        Aktif: <?= $activeAcc['label'] ?>
                    </span>
                </div>
                <div class="btn-group w-100" role="group" aria-label="Demo Role Accounts">
                    <?php foreach ($testAccounts as $key => $acc): ?>
                        <button type="button" 
                                class="btn btn-sm btn-outline-secondary <?= $roleHint === $key ? 'active fw-bold' : '' ?>" 
                                onclick="fillAccount('<?= $acc['user'] ?>', '<?= $acc['pass'] ?>', '<?= $key ?>')">
                            <?= $acc['label'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Login Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    <!-- Flash Error Alert -->
                    <?php if (!empty($error)): ?>
                        <?php
                        render_component('alert', [
                            'type' => 'danger',
                            'icon' => 'bi-exclamation-triangle-fill',
                            'message' => e($error),
                            'dismissible' => true,
                            'class' => 'mb-3'
                        ]);
                        ?>
                    <?php endif; ?>

                    <!-- Flash Success Alert -->
                    <?php if (!empty($success)): ?>
                        <?php
                        render_component('alert', [
                            'type' => 'success',
                            'icon' => 'bi-check-circle-fill',
                            'message' => e($success),
                            'dismissible' => true,
                            'class' => 'mb-3'
                        ]);
                        ?>
                    <?php endif; ?>

                    <form action="<?= url('login') ?>" method="POST" id="loginForm">
                        <?= csrf_field() ?>

                        <!-- Username / NIM / Email Input -->
                        <?php
                        render_component('form', [
                            'type' => 'text',
                            'name' => 'identity',
                            'id' => 'loginIdentity',
                            'label' => 'Username, Email Kampus, atau NIM',
                            'placeholder' => 'Masukkan username/email/NIM',
                            'value' => $activeAcc['user'],
                            'required' => true,
                            'icon' => 'bi-person-badge'
                        ]);
                        ?>

                        <!-- Password Input -->
                        <?php
                        render_component('form', [
                            'type' => 'password',
                            'name' => 'password',
                            'id' => 'loginPassword',
                            'label' => 'Kata Sandi',
                            'placeholder' => 'Masukkan kata sandi',
                            'value' => $activeAcc['pass'],
                            'required' => true,
                            'icon' => 'bi-key'
                        ]);
                        ?>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <?php
                            render_component('form', [
                                'type' => 'checkbox',
                                'name' => 'remember',
                                'label' => 'Ingat saya di perangkat ini',
                                'value' => true,
                                'class' => 'mb-0'
                            ]);
                            ?>
                            <a href="#" class="text-decoration-none small text-primary fw-medium" onclick="alert('Untuk reset sandi, silakan hubungi bagian IT Kampus.'); return false;">Lupa Sandi?</a>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <?php
                            render_component('button', [
                                'text' => 'Masuk ke Sistem',
                                'type' => 'submit',
                                'variant' => 'primary',
                                'size' => 'lg',
                                'icon' => 'bi-box-arrow-in-right',
                                'class' => 'py-2.5 rounded-3 fw-semibold shadow-xs'
                            ]);
                            ?>
                        </div>

                        <div class="text-center">
                            <a href="<?= url('') ?>" class="text-decoration-none small text-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Depan
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer Security Note -->
            <div class="text-center mt-4 text-muted small">
                <i class="bi bi-shield-lock-fill text-success me-1"></i> Dilindungi enkripsi sandi <strong>Bcrypt</strong> &amp; <strong>RBAC Backend Guard</strong>.
            </div>
        </div>
    </div>
</div>

<script>
function fillAccount(user, pass, role) {
    document.getElementById('loginIdentity').value = user;
    document.getElementById('loginPassword').value = pass;
    showToast('Akun Dipilih', 'Identitas untuk ' + role.toUpperCase() + ' terisi otomatis.', 'info');
}
</script>
