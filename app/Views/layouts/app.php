<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Smart Campus Cash - Sistem Kas Keuangan Kampus') ?></title>
    <meta name="description" content="Sistem Informasi Kas Keuangan Kampus Cerdas Berbasis Web dengan Dukungan AI">

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
<body class="app-body">
    <div class="app-wrapper d-flex">
        <!-- Sidebar Navigation -->
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Mobile Sidebar Overlay Backdrop -->
        <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>

        <!-- Main Content Area -->
        <div class="main-wrapper d-flex flex-column flex-grow-1 min-vh-100">
            <!-- Navbar Header -->
            <?php require __DIR__ . '/partials/navbar.php'; ?>

            <!-- Page Content Container -->
            <main class="page-content flex-grow-1 p-3 p-md-4">
                <?= $content ?>
            </main>

            <!-- Footer -->
            <?php require __DIR__ . '/partials/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap 5.3.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Chart.js 4.4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <!-- Custom App JS -->
    <script src="<?= asset('js/app.js') ?>"></script>

    <!-- Floating AI Chatbot Widget (Node.js & NVIDIA NIM) -->
    <?php require __DIR__ . '/../components/home_chatbot.php'; ?>
</body>
</html>
