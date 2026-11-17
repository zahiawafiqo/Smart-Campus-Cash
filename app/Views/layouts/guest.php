<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Smart Campus Cash') ?></title>
    <meta name="description" content="Sistem Informasi Kas Keuangan Kampus Cerdas Berbasis Web">

    <!-- Instant Theme Mode Resolver (Avoid FOUC) -->
    <script>
        (function() {
            try {
                const saved = localStorage.getItem('scc_theme');
                const theme = (saved === 'dark' || saved === 'light') ? saved : 'light';
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.documentElement.setAttribute('data-theme', theme);
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom App CSS -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="guest-body min-vh-100 d-flex flex-column">

    <!-- Guest Navbar -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-xs py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('') ?>">
                <div class="brand-icon bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
                <div>
                    <span class="fw-bold fs-5 text-dark tracking-tight d-block lh-1">Smart Campus Cash</span>
                    <span class="text-primary small fw-semibold" style="font-size: 0.72rem;">SISTEM KEUANGAN KAMPUS</span>
                </div>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#guestNav" aria-controls="guestNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="guestNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2 pt-3 pt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium px-2" href="<?= url('') ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium px-2" href="<?= url('dashboard') ?>">Demo Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary fw-medium px-2" href="<?= url('components') ?>">UI Components</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <!-- Theme Toggle Button -->
                        <button class="btn btn-light btn-sm border d-inline-flex align-items-center justify-content-center p-2 rounded-2 theme-toggle-btn" id="guestThemeToggleBtn" type="button" aria-label="Ganti ke Mode Gelap" title="Ganti ke Mode Gelap">
                            <i class="bi bi-moon-stars-fill fs-6 text-secondary theme-icon-moon"></i>
                            <i class="bi bi-sun-fill fs-6 text-warning theme-icon-sun d-none"></i>
                        </button>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="btn btn-primary btn-sm px-3 py-2 rounded-2 fw-medium d-inline-flex align-items-center gap-1.5" href="<?= url('login') ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Masuk Sistem
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <?= $content ?>
    </main>

    <!-- Guest Footer -->
    <footer class="bg-white border-top py-4 text-center text-muted small">
        <div class="container">
            <p class="mb-1 fw-semibold text-dark">Smart Campus Cash &copy; <?= date('Y') ?></p>
            <p class="mb-0 text-secondary">Sistem Pengelolaan Kas Keuangan Kampus Terpadu Berbasis Web & AI (Admin, Mahasiswa, Kasir, Bagian Keuangan)</p>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom App JS -->
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
