<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AiFinancialContextService
{
    /**
     * Dapatkan konteks fakta data terkontrol sesuai role (AI TIDAK MEMILIKI AKSES DATABASE LANGSUNG)
     */
    public static function getContextForRole(string $role, int $userId, string $prompt = ''): array
    {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return ['status' => 'database_unavailable'];
        }

        $now = date('Y-m-d H:i:s');
        $currentMonthName = self::formatMonthId('now');
        $prevMonthName = self::formatMonthId('-1 month');
        $currentYear = (int)date('Y');
        $todayDate = date('Y-m-d');
        $todayFormatted = self::formatDateId('now');

        // Ambil nama pengguna aktual dari database (hanya full_name, tanpa kredensial rahasia)
        $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :uid LIMIT 1");
        $userStmt->execute(['uid' => $userId]);
        $userName = (string)($userStmt->fetchColumn() ?: '');

        $roleLower = strtolower($role);

        switch ($roleLower) {
            case 'mahasiswa':
                // Hanya data tagihan dan pembayaran mahasiswa bersangkutan
                $studentStmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid AND deleted_at IS NULL LIMIT 1");
                $studentStmt->execute(['uid' => $userId]);
                $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    return [
                        'role' => 'mahasiswa',
                        'message' => 'Profil mahasiswa tidak ditemukan.'
                    ];
                }

                $sid = (int)$student['id'];

                // Tagihan
                $bStmt = $pdo->prepare("SELECT title, amount, paid_amount, remaining_amount, due_date, status FROM bills WHERE student_id = :sid AND status != 'CANCELLED' ORDER BY due_date ASC");
                $bStmt->execute(['sid' => $sid]);
                $bills = $bStmt->fetchAll(PDO::FETCH_ASSOC);

                $totalTagihan = 0.0;
                $totalPaid = 0.0;
                $totalUnpaid = 0.0;
                $nearestDue = null;
                $tagihanAktif = [];
                $tagihanLunas = [];

                foreach ($bills as $b) {
                    $totalTagihan += (float)$b['amount'];
                    $totalPaid += (float)$b['paid_amount'];
                    $totalUnpaid += (float)$b['remaining_amount'];
                    if (in_array($b['status'], ['UNPAID', 'PARTIAL', 'OVERDUE'])) {
                        $tagihanAktif[] = [
                            'judul' => $b['title'],
                            'amount' => (float)$b['amount'],
                            'paid_amount' => (float)$b['paid_amount'],
                            'sisa' => (float)$b['remaining_amount'],
                            'jatuh_tempo' => $b['due_date'],
                            'status' => $b['status']
                        ];
                        if (!$nearestDue) {
                            $nearestDue = $b;
                        }
                    } elseif ($b['status'] === 'PAID') {
                        $tagihanLunas[] = [
                            'judul' => $b['title'],
                            'amount' => (float)$b['amount'],
                            'jatuh_tempo' => $b['due_date']
                        ];
                    }
                }

                // 5 Transaksi pembayaran terakhir mahasiswa
                $pStmt = $pdo->prepare("SELECT payment_number, amount, payment_method, payment_date FROM payments WHERE student_id = :sid AND status = 'completed' ORDER BY payment_date DESC LIMIT 5");
                $pStmt->execute(['sid' => $sid]);
                $payments = $pStmt->fetchAll(PDO::FETCH_ASSOC);

                $latestPayment = !empty($payments) ? $payments[0] : null;

                return [
                    'role' => 'mahasiswa',
                    'logged_in_user_name' => $userName,
                    'student_name' => $student['full_name'],
                    'nim' => $student['nim'],
                    'faculty' => $student['faculty'] ?? '',
                    'study_program' => $student['study_program'] ?? '',
                    'total_tagihan' => $totalTagihan,
                    'total_sudah_dibayar' => $totalPaid,
                    'total_sisa_tunggakan' => $totalUnpaid,
                    'status_lunas' => ($totalUnpaid <= 0),
                    'tagihan_terdekat' => $nearestDue ? [
                        'judul' => $nearestDue['title'],
                        'sisa' => (float)$nearestDue['remaining_amount'],
                        'jatuh_tempo' => $nearestDue['due_date'],
                        'status' => $nearestDue['status']
                    ] : null,
                    'tagihan_aktif' => array_slice($tagihanAktif, 0, 5),
                    'tagihan_lunas' => array_slice($tagihanLunas, 0, 5),
                    'transaksi_terakhir' => $payments,
                    'pembayaran_terakhir' => $latestPayment,
                    'allowed_scope' => 'Hanya data tagihan dan pembayaran pribadi'
                ];

            case 'kasir':
                // Data operasional loket kasir
                $todaySum = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = CURDATE() AND status = 'completed'")->fetchColumn();
                $todayCnt = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE DATE(payment_date) = CURDATE() AND status = 'completed'")->fetchColumn();

                $myTodaySumStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = CURDATE() AND recorded_by = :uid AND status = 'completed'");
                $myTodaySumStmt->execute(['uid' => $userId]);
                $mySum = (float)$myTodaySumStmt->fetchColumn();

                $myTodayCntStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE DATE(payment_date) = CURDATE() AND recorded_by = :uid AND status = 'completed'");
                $myTodayCntStmt->execute(['uid' => $userId]);
                $myCnt = (int)$myTodayCntStmt->fetchColumn();

                // 5 Transaksi loket terbaru
                $recentPaymentsStmt = $pdo->query("SELECT p.payment_number, p.amount, p.payment_method, p.payment_date, s.nim, s.full_name as student_name, b.title as bill_title
                                                   FROM payments p 
                                                   JOIN students s ON p.student_id = s.id 
                                                   LEFT JOIN bills b ON p.bill_id = b.id 
                                                   WHERE p.status = 'completed' 
                                                   ORDER BY p.payment_date DESC LIMIT 5");
                $recentPayments = $recentPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);

                $refundedCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'refunded'")->fetchColumn();

                return [
                    'role' => 'kasir',
                    'logged_in_user_name' => $userName,
                    'tanggal' => $todayFormatted,
                    'total_pembayaran_hari_ini_kampus' => $todaySum,
                    'jumlah_transaksi_hari_ini_kampus' => $todayCnt,
                    'total_diterima_kasir_ini' => $mySum,
                    'jumlah_transaksi_kasir_ini' => $myCnt,
                    'pembayaran_terbaru' => $recentPayments,
                    'transaksi_bermasalah' => [
                        'refunded_payments' => $refundedCount
                    ],
                    'allowed_scope' => 'Operasional penerimaan kas loket dan kuitansi'
                ];

            case 'keuangan':
            case 'admin':
                // 1. Akumulasi Buku Kas All-time
                $incomeTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided'")->fetchColumn();
                $expenseTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided'")->fetchColumn();
                $saldoKas = $incomeTotal - $expenseTotal;

                // 2. Transaksi Hari Ini
                $todayIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND DATE(transaction_date) = CURDATE()")->fetchColumn();
                $todayExpense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND DATE(transaction_date) = CURDATE()")->fetchColumn();
                $todayTxCount = (int)$pdo->query("SELECT COUNT(*) FROM cash_transactions WHERE status != 'voided' AND DATE(transaction_date) = CURDATE()")->fetchColumn();

                // 3. Mutasi Bulan Berjalan (M1)
                $mIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
                $mExpense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
                $mTxCount = (int)$pdo->query("SELECT COUNT(*) FROM cash_transactions WHERE status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();

                // 4. Mutasi Bulan Sebelumnya (M0)
                $m0Income = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND MONTH(transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
                $m0Expense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND MONTH(transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();

                // 5. Mutasi Tahun Berjalan
                $yIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
                $yExpense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();

                // 6. Pembayaran Hari Ini & Total Pembayaran Mahasiswa
                $todayPayment = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = CURDATE() AND status = 'completed'")->fetchColumn();
                $totalSuccessPayments = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

                // 7. Tagihan & Tunggakan
                $totalBillsCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status != 'CANCELLED'")->fetchColumn();
                $totalBillsNominal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM bills WHERE status != 'CANCELLED'")->fetchColumn();
                $paidBillsCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'PAID'")->fetchColumn();
                $paidBillsNominal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM bills WHERE status = 'PAID'")->fetchColumn();
                $unpaidBillsCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status IN ('UNPAID', 'PARTIAL', 'OVERDUE')")->fetchColumn();
                $totalTunggakanNominal = (float)$pdo->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM bills WHERE status IN ('UNPAID', 'PARTIAL', 'OVERDUE')")->fetchColumn();
                $partialBillsCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'PARTIAL'")->fetchColumn();
                $partialBillsPaid = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM bills WHERE status = 'PARTIAL'")->fetchColumn();
                $overdueBillsCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'OVERDUE' OR (status IN ('UNPAID', 'PARTIAL') AND due_date < CURDATE())")->fetchColumn();

                $unpaidStudentsCount = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM bills WHERE status IN ('UNPAID', 'PARTIAL', 'OVERDUE')")->fetchColumn();

                // Daftar Mahasiswa Menunggak (Hanya untuk Admin & Keuangan)
                $unpaidStudentsStmt = $pdo->query("SELECT DISTINCT s.nim, s.full_name, s.study_program, SUM(b.remaining_amount) as total_tunggakan, COUNT(b.id) as jlh_tagihan 
                                                   FROM bills b 
                                                   JOIN students s ON b.student_id = s.id 
                                                   WHERE b.status IN ('UNPAID', 'PARTIAL', 'OVERDUE') 
                                                   GROUP BY s.id, s.nim, s.full_name, s.study_program 
                                                   ORDER BY total_tunggakan DESC LIMIT 10");
                $unpaidStudentsList = $unpaidStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

                // 8. Kategori Pengeluaran
                $catStmt = $pdo->query("SELECT c.name, c.code, SUM(ct.amount) AS total_spent, COUNT(ct.id) as tx_count
                                       FROM cash_transactions ct 
                                       JOIN categories c ON ct.category_id = c.id 
                                       WHERE ct.type = 'expense' AND ct.status != 'voided'
                                       GROUP BY c.id, c.name, c.code 
                                       ORDER BY total_spent DESC");
                $categoriesAllTime = $catStmt->fetchAll(PDO::FETCH_ASSOC);

                $mCatStmt = $pdo->query("SELECT c.name, c.code, SUM(ct.amount) AS total_spent, COUNT(ct.id) as tx_count
                                        FROM cash_transactions ct 
                                        JOIN categories c ON ct.category_id = c.id 
                                        WHERE ct.type = 'expense' AND ct.status != 'voided' AND MONTH(ct.transaction_date) = MONTH(CURDATE()) AND YEAR(ct.transaction_date) = YEAR(CURDATE())
                                        GROUP BY c.id, c.name, c.code 
                                        ORDER BY total_spent DESC 
                                        LIMIT 1");
                $biggestExpenseCat = $mCatStmt->fetch(PDO::FETCH_ASSOC);

                // Kategori yang mengalami kenaikan dibanding bulan lalu
                $catCompareStmt = $pdo->query("
                    SELECT c.name,
                           COALESCE(SUM(CASE WHEN MONTH(ct.transaction_date) = MONTH(CURDATE()) AND YEAR(ct.transaction_date) = YEAR(CURDATE()) THEN ct.amount ELSE 0 END), 0) AS m1_spent,
                           COALESCE(SUM(CASE WHEN MONTH(ct.transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(ct.transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN ct.amount ELSE 0 END), 0) AS m0_spent
                    FROM categories c
                    LEFT JOIN cash_transactions ct ON ct.category_id = c.id AND ct.type = 'expense' AND ct.status != 'voided'
                    WHERE c.type = 'expense'
                    GROUP BY c.id, c.name
                    HAVING m1_spent > m0_spent
                    ORDER BY (m1_spent - m0_spent) DESC
                ");
                $increasedCategories = $catCompareStmt ? $catCompareStmt->fetchAll(PDO::FETCH_ASSOC) : [];

                // 9. Transaksi Rincian
                $recentInStmt = $pdo->query("SELECT ct.transaction_number, ct.transaction_date, ct.amount, ct.description, c.name as category_name 
                                             FROM cash_transactions ct 
                                             JOIN categories c ON ct.category_id = c.id 
                                             WHERE ct.type = 'income' AND ct.status != 'voided' 
                                             ORDER BY ct.transaction_date DESC, ct.id DESC LIMIT 5");
                $recentIn = $recentInStmt ? $recentInStmt->fetchAll(PDO::FETCH_ASSOC) : [];

                $recentOutStmt = $pdo->query("SELECT ct.transaction_number, ct.transaction_date, ct.amount, ct.description, c.name as category_name 
                                              FROM cash_transactions ct 
                                              JOIN categories c ON ct.category_id = c.id 
                                              WHERE ct.type = 'expense' AND ct.status != 'voided' 
                                              ORDER BY ct.transaction_date DESC, ct.id DESC LIMIT 5");
                $recentOut = $recentOutStmt ? $recentOutStmt->fetchAll(PDO::FETCH_ASSOC) : [];

                $topExpenseTxStmt = $pdo->query("SELECT ct.transaction_number, ct.transaction_date, ct.amount, ct.description, c.name as category_name 
                                                 FROM cash_transactions ct 
                                                 JOIN categories c ON ct.category_id = c.id 
                                                 WHERE ct.type = 'expense' AND ct.status != 'voided' 
                                                 ORDER BY ct.amount DESC LIMIT 1");
                $topExpenseTx = $topExpenseTxStmt ? $topExpenseTxStmt->fetch(PDO::FETCH_ASSOC) : null;

                $latestTxStmt = $pdo->query("SELECT ct.transaction_number, ct.transaction_date, ct.type, ct.amount, ct.description, c.name as category_name 
                                             FROM cash_transactions ct 
                                             JOIN categories c ON ct.category_id = c.id 
                                             ORDER BY ct.transaction_date DESC, ct.id DESC LIMIT 1");
                $latestTx = $latestTxStmt ? $latestTxStmt->fetch(PDO::FETCH_ASSOC) : null;

                $voidedCount = (int)$pdo->query("SELECT COUNT(*) FROM cash_transactions WHERE status = 'voided'")->fetchColumn();
                $refundedCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'refunded'")->fetchColumn();
                $cancelledCount = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'CANCELLED'")->fetchColumn();

                // 10. Query Spesifik Berdasarkan Ekstraksi Prompt (Date, Amount, Category)
                $dynamicQuery = self::resolveDynamicQuery($pdo, $prompt);

                // Dapatkan statistik pengguna dari fungsi agregasi model User (Khusus Admin)
                $userStats = ($roleLower === 'admin') ? \App\Models\User::getRoleStatistics() : null;

                $baseContext = [
                    'role' => $roleLower,
                    'logged_in_user_name' => $userName,
                    'periode_bulan_ini' => $currentMonthName,
                    'periode_bulan_lalu' => $prevMonthName,
                    'tahun_ini' => $currentYear,
                    'tanggal_hari_ini' => $todayFormatted,
                    'saldo_kas_saat_ini' => $saldoKas,
                    'total_pemasukan_all_time' => $incomeTotal,
                    'total_pengeluaran_all_time' => $expenseTotal,
                    'pemasukan_hari_ini' => $todayIncome,
                    'pengeluaran_hari_ini' => $todayExpense,
                    'transaksi_hari_ini_count' => $todayTxCount,
                    'pemasukan_bulan_ini' => $mIncome,
                    'pengeluaran_bulan_ini' => $mExpense,
                    'transaksi_bulan_ini_count' => $mTxCount,
                    'pemasukan_bulan_lalu' => $m0Income,
                    'pengeluaran_bulan_lalu' => $m0Expense,
                    'pemasukan_tahun_ini' => $yIncome,
                    'pengeluaran_tahun_ini' => $yExpense,
                    'total_pembayaran_hari_ini' => $todayPayment,
                    'total_pembayaran_berhasil' => $totalSuccessPayments,
                    'mahasiswa_memiliki_tunggakan' => $unpaidStudentsCount,
                    'total_nominal_tunggakan' => $totalTunggakanNominal,
                    'kategori_pengeluaran_terbesar_bulan_ini' => $biggestExpenseCat ? [
                        'kategori' => $biggestExpenseCat['name'],
                        'nominal' => (float)$biggestExpenseCat['total_spent']
                    ] : null,
                    'kategori_pengeluaran_all_time' => $categoriesAllTime,
                    'kategori_naik_list' => $increasedCategories,
                    'tagihan_rekap' => [
                        'total_tercatat_count' => $totalBillsCount,
                        'total_tercatat_nominal' => $totalBillsNominal,
                        'lunas_count' => $paidBillsCount,
                        'lunas_nominal' => $paidBillsNominal,
                        'belum_lunas_count' => $unpaidBillsCount,
                        'belum_lunas_nominal' => $totalTunggakanNominal,
                        'parsial_count' => $partialBillsCount,
                        'parsial_paid' => $partialBillsPaid,
                        'overdue_count' => $overdueBillsCount,
                    ],
                    'mahasiswa_menunggak_list' => $unpaidStudentsList,
                    'transaksi_masuk_terbaru' => $recentIn,
                    'transaksi_keluar_terbaru' => $recentOut,
                    'transaksi_pengeluaran_terbesar' => $topExpenseTx ?: null,
                    'transaksi_terakhir' => $latestTx ?: null,
                    'transaksi_bermasalah' => [
                        'voided_cash' => $voidedCount,
                        'refunded_payments' => $refundedCount,
                        'cancelled_bills' => $cancelledCount
                    ],
                    'dynamic_query' => $dynamicQuery,
                    'allowed_scope' => ($roleLower === 'admin') 
                        ? 'Akses penuh administrasi, keuangan kampus, dan statistik pengguna'
                        : 'Data agregasi Buku Kas Umum dan Rekapitulasi Tagihan Kampus'
                ];

                if ($roleLower === 'admin' && $userStats) {
                    $baseContext['statistik_pengguna'] = [
                        'total' => $userStats['total'],
                        'total_pengguna_terdaftar' => $userStats['total'],
                        'admin' => $userStats['admin'],
                        'administrator' => $userStats['admin'],
                        'mahasiswa' => $userStats['mahasiswa'],
                        'kasir' => $userStats['kasir'],
                        'keuangan' => $userStats['keuangan'],
                        'bagian_keuangan' => $userStats['keuangan'],
                        'keterangan' => 'Dihitung berdasarkan akun yang tercatat di sistem'
                    ];
                }

                return $baseContext;

            default:
                return ['role' => 'guest', 'allowed_scope' => 'Tidak memiliki hak akses data'];
        }
    }

    /**
     * Ekstraksi tanggal aman dari teks pertanyaan bahasa Indonesia
     */
    public static function extractDate(string $prompt): ?string
    {
        $p = strtolower(trim($prompt));
        $months = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'
        ];

        // Format: 23 Oktober 2026 atau 23 Oktober
        if (preg_match('/\b(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)(?:\s+(\d{4}))?\b/i', $p, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($m[2])];
            $year = !empty($m[3]) ? $m[3] : date('Y');
            return "{$year}-{$month}-{$day}";
        }

        // Format: YYYY-MM-DD
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $p, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // Format: DD/MM/YYYY atau DD-MM-YYYY
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $p, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (str_contains($p, 'hari ini')) {
            return date('Y-m-d');
        }
        if (str_contains($p, 'kemarin')) {
            return date('Y-m-d', strtotime('-1 day'));
        }

        return null;
    }

    /**
     * Ekstraksi angka nominal uang aman dari pertanyaan
     */
    public static function extractAmount(string $prompt): ?float
    {
        $p = strtolower(trim($prompt));
        // Hindari pencocokan tahun seperti 2024, 2025, 2026
        if (preg_match('/\b(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+)\b/i', $p, $m)) {
            $cleaned = str_replace('.', '', $m[1]);
            return (float)$cleaned;
        }
        if (preg_match('/\b(?:rp\.?\s*)?(\d{5,10})\b/i', $p, $m)) {
            return (float)$m[1];
        }
        return null;
    }

    /**
     * Tangani query dinamis yang aman terhadap tabel dengan prepared statements
     */
    protected static function resolveDynamicQuery(PDO $pdo, string $prompt): array
    {
        $res = [];
        $p = strtolower(trim($prompt));

        // 0. Selalu ambil semua transaksi pengeluaran (expense_detail_query) — dipakai untuk menjawab
        //    pertanyaan lanjutan seperti "pengeluaran itu untuk apa saja", "rincian pengeluaran", dsb.
        //    Batas LIMIT 50 untuk keamanan performa; cukup untuk semua kasus riil.
        try {
            $allExpStmt = $pdo->query(
                "SELECT ct.transaction_number, ct.transaction_date, ct.amount, ct.description, c.name as category_name
                 FROM cash_transactions ct
                 JOIN categories c ON ct.category_id = c.id
                 WHERE ct.type = 'expense' AND ct.status != 'voided'
                 ORDER BY ct.transaction_date DESC, ct.id DESC
                 LIMIT 50"
            );
            $res['expense_detail_query'] = $allExpStmt ? $allExpStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Exception $e) {
            $res['expense_detail_query'] = [];
        }

        // 1. Cek tanggal spesifik
        $date = self::extractDate($prompt);
        if ($date) {
            $dateStmt = $pdo->prepare("SELECT ct.transaction_number, ct.type, ct.amount, ct.description, c.name as category_name, ct.transaction_date 
                                       FROM cash_transactions ct 
                                       JOIN categories c ON ct.category_id = c.id 
                                       WHERE DATE(ct.transaction_date) = :tgl AND ct.status != 'voided' 
                                       ORDER BY ct.transaction_date DESC, ct.id DESC LIMIT 5");
            $dateStmt->execute(['tgl' => $date]);
            $dateRows = $dateStmt->fetchAll(PDO::FETCH_ASSOC);

            $dateSumIncome = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE DATE(transaction_date) = :tgl AND type = 'income' AND status != 'voided'");
            $dateSumIncome->execute(['tgl' => $date]);
            $inSum = (float)$dateSumIncome->fetchColumn();

            $dateSumExpense = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE DATE(transaction_date) = :tgl AND type = 'expense' AND status != 'voided'");
            $dateSumExpense->execute(['tgl' => $date]);
            $outSum = (float)$dateSumExpense->fetchColumn();

            $res['date_query'] = [
                'target_date' => $date,
                'target_date_formatted' => self::formatDateId($date),
                'total_transaksi' => count($dateRows),
                'total_pemasukan' => $inSum,
                'total_pengeluaran' => $outSum,
                'transaksi' => $dateRows
            ];
        }

        // 2. Cek nominal spesifik
        $amount = self::extractAmount($prompt);
        if ($amount && $amount > 0) {
            $amtStmt = $pdo->prepare("SELECT ct.transaction_number, ct.type, ct.amount, ct.description, c.name as category_name, ct.transaction_date 
                                      FROM cash_transactions ct 
                                      JOIN categories c ON ct.category_id = c.id 
                                      WHERE ct.amount = :amt AND ct.status != 'voided' 
                                      ORDER BY ct.transaction_date DESC LIMIT 5");
            $amtStmt->execute(['amt' => $amount]);
            $amtRows = $amtStmt->fetchAll(PDO::FETCH_ASSOC);

            $res['amount_query'] = [
                'target_amount' => $amount,
                'found_count' => count($amtRows),
                'transaksi' => $amtRows
            ];
        }

        // 3. Cek kata kunci kategori tertentu
        $catKeywords = [
            'operasional' => 'EXP_OPERASIONAL',
            'utilitas' => 'EXP_UTILITAS',
            'listrik' => 'EXP_UTILITAS',
            'air' => 'EXP_UTILITAS',
            'internet' => 'EXP_UTILITAS',
            'maintenance' => 'EXP_MAINTENANCE',
            'pemeliharaan' => 'EXP_MAINTENANCE',
            'server' => 'EXP_MAINTENANCE',
            'atk' => 'EXP_ATK',
            'perlengkapan' => 'EXP_ATK',
            'spp' => 'INC_SPP',
            'praktikum' => 'INC_PRAK',
            'lab' => 'INC_PRAK',
            'dpp' => 'INC_DPP',
            'wisuda' => 'INC_WISUDA',
            'kelulusan' => 'INC_WISUDA'
        ];

        foreach ($catKeywords as $kw => $code) {
            if (str_contains($p, $kw)) {
                $specCatStmt = $pdo->prepare("SELECT c.id, c.name, c.code, c.type, 
                                                     COALESCE(SUM(ct.amount), 0) AS total_amount, 
                                                     COUNT(ct.id) AS tx_count
                                              FROM categories c 
                                              LEFT JOIN cash_transactions ct ON ct.category_id = c.id AND ct.status != 'voided'
                                              WHERE c.code = :code 
                                              GROUP BY c.id, c.name, c.code, c.type LIMIT 1");
                $specCatStmt->execute(['code' => $code]);
                $specCat = $specCatStmt->fetch(PDO::FETCH_ASSOC);

                if ($specCat) {
                    $recentCatTx = $pdo->prepare("SELECT ct.transaction_number, ct.type, ct.amount, ct.description, ct.transaction_date 
                                                  FROM cash_transactions ct 
                                                  WHERE ct.category_id = :cid AND ct.status != 'voided' 
                                                  ORDER BY ct.transaction_date DESC LIMIT 3");
                    $recentCatTx->execute(['cid' => $specCat['id']]);

                    $res['category_query'] = [
                        'matched_keyword' => $kw,
                        'category_name' => $specCat['name'],
                        'category_type' => $specCat['type'],
                        'total_amount' => (float)$specCat['total_amount'],
                        'tx_count' => (int)$specCat['tx_count'],
                        'transaksi' => $recentCatTx->fetchAll(PDO::FETCH_ASSOC)
                    ];
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * Format nama bulan dan tahun dalam bahasa Indonesia
     */
    public static function formatMonthId(string $dateStr = 'now'): string
    {
        $time = ($dateStr === 'now') ? time() : strtotime($dateStr);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $m = (int)date('n', $time);
        $y = date('Y', $time);
        return ($months[$m] ?? date('F', $time)) . " " . $y;
    }

    /**
     * Format tanggal lengkap dalam bahasa Indonesia (e.g. 24 Oktober 2026)
     */
    public static function formatDateId(string $dateStr = 'now'): string
    {
        $time = ($dateStr === 'now') ? time() : strtotime($dateStr);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $d = date('j', $time);
        $m = (int)date('n', $time);
        $y = date('Y', $time);
        return $d . " " . ($months[$m] ?? date('F', $time)) . " " . $y;
    }
}
