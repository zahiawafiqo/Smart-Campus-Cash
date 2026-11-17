<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Student;
use App\Services\AuthService;
use App\Services\AiFinancialInsightService;
use App\Services\AuditLogger;
use PDO;

class KeuanganController extends Controller
{
    protected CashTransaction $cashModel;
    protected Category $categoryModel;
    protected Student $studentModel;

    public function __construct()
    {
        $this->cashModel = new CashTransaction();
        $this->categoryModel = new Category();
        $this->studentModel = new Student();
    }

    /**
     * Dashboard Bagian Keuangan
     */
    public function dashboard(): void
    {
        $pdo = Database::getConnection();

        // 1. Akumulasi Buku Kas All-time
        $summary = $this->cashModel->getBalanceSummary();
        $totalIncome = $summary['total_income'];
        $totalExpense = $summary['total_expense'];
        $saldoKas = $summary['balance'];

        // 2. Mutasi Bulan Berjalan
        $mIncome = 0.0;
        $mExpense = 0.0;
        if ($pdo) {
            $mIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
            $mExpense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
        }

        // 3. Data Grafik Pemasukan vs Pengeluaran (Chart.js)
        $chartData = $this->cashModel->getMonthlyChartData(6);

        // 4. Daftar Transaksi Buku Kas Terbaru
        $transactions = $this->cashModel->getRecentTransactions(10);

        // 5. Rekapitulasi Pos Anggaran / Kategori
        $categoriesSummary = $this->categoryModel->getCategoriesSummary();

        // 6. AI Financial Insight
        $aiInsight = AiFinancialInsightService::generateInsight();

        $this->render('keuangan/dashboard', [
            'title' => 'Dashboard Keuangan - Smart Campus Cash',
            'pageTitle' => 'Manajemen Keuangan & Arus Kas',
            'user' => AuthService::user(),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'saldoKas' => $saldoKas,
            'mIncome' => $mIncome,
            'mExpense' => $mExpense,
            'chartData' => $chartData,
            'transactions' => $transactions,
            'recentTransactions' => $transactions,
            'categoriesSummary' => $categoriesSummary,
            'aiInsight' => $aiInsight
        ], 'layouts/app');
    }

    /**
     * AJAX Endpoint untuk Refresh AI Financial Insight
     */
    public function refreshInsight(): void
    {
        $insight = AiFinancialInsightService::generateInsight(true);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'insight' => $insight]);
        exit;
    }

    /**
     * Buku Kas Umum (Cash Management List)
     */
    public function transaksi(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $type = trim((string)$this->input('type', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $startDate = trim((string)$this->input('start_date', ''));
        $endDate = trim((string)$this->input('end_date', ''));
        $status = trim((string)$this->input('status', ''));

        $filters = array_filter([
            'search' => $search,
            'type' => $type,
            'category_id' => $categoryId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ]);

        $result = $this->cashModel->paginate($page, 15, $filters);
        $summary = $this->cashModel->getBalanceSummary($startDate ?: null, $endDate ?: null);

        $this->render('keuangan/transaksi/index', [
            'title' => 'Buku Kas Umum - Smart Campus Cash',
            'transactions' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalIncome' => $summary['total_income'],
            'totalExpense' => $summary['total_expense'],
            'balance' => $summary['balance'],
            'categories' => $this->categoryModel->allByType(),
            'search' => $search,
            'selectedType' => $type,
            'selectedCategory' => $categoryId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedStatus' => $status,
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    /**
     * Form Catat Transaksi Kas Masuk / Kas Keluar
     */
    public function createTransaksi(): void
    {
        $type = strtolower(trim((string)$this->input('type', 'income')));
        if (!in_array($type, ['income', 'expense'])) {
            $type = 'income';
        }

        $suggestedTnum = $this->cashModel->generateTransactionNumber($type);
        $categories = $this->categoryModel->allByType($type);

        $this->render('keuangan/transaksi/create', [
            'title' => ($type === 'income' ? 'Catat Kas Masuk' : 'Catat Kas Keluar') . ' - Smart Campus Cash',
            'type' => $type,
            'suggestedTnum' => $suggestedTnum,
            'categories' => $categories,
            'error' => flash('flash_error'),
            'old' => $_SESSION['old_input'] ?? []
        ], 'layouts/app');
        unset($_SESSION['old_input']);
    }

    /**
     * Simpan Transaksi Kas Masuk / Kas Keluar (POST)
     */
    public function storeTransaksi(): void
    {
        $type = strtolower(trim((string)$this->input('type', 'income')));
        $tnum = trim((string)$this->input('transaction_number', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $amount = (float)$this->input('amount', 0);
        $tdate = trim((string)$this->input('transaction_date', date('Y-m-d H:i:s')));
        $desc = trim((string)$this->input('description', ''));
        $ref = trim((string)$this->input('reference_number', ''));
        $method = trim((string)$this->input('payment_method', 'cash'));

        $_SESSION['old_input'] = $_POST;

        if (empty($tnum) || empty($desc) || $categoryId <= 0) {
            $_SESSION['flash_error'] = 'Seluruh field wajib diisi lengkap.';
            $this->redirect(url("keuangan/transaksi/create?type={$type}"));
            return;
        }

        if ($amount <= 0) {
            $_SESSION['flash_error'] = 'Nominal transaksi harus lebih besar dari Rp 0.';
            $this->redirect(url("keuangan/transaksi/create?type={$type}"));
            return;
        }

        $userId = AuthService::id() ?? 1;

        $txId = $this->cashModel->create([
            'transaction_number' => $tnum,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $tdate,
            'description' => $desc,
            'reference_number' => $ref,
            'payment_method' => $method,
            'created_by' => $userId
        ]);

        if ($txId) {
            unset($_SESSION['old_input']);
            $_SESSION['flash_success'] = "Transaksi kas (" . strtoupper($type) . ") senilai Rp " . number_format($amount) . " berhasil dicatat.";
            $this->redirect(url('keuangan/transaksi'));
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan transaksi ke buku kas.';
            $this->redirect(url("keuangan/transaksi/create?type={$type}"));
        }
    }

    /**
     * Detail Transaksi Kas
     */
    public function detailTransaksi(): void
    {
        $id = (int)$this->input('id', 0);
        $tx = $this->cashModel->findWithDetails($id);

        if (!$tx) {
            $_SESSION['flash_error'] = 'Transaksi kas tidak ditemukan.';
            $this->redirect(url('keuangan/transaksi'));
            return;
        }

        $this->render('keuangan/transaksi/detail', [
            'title' => 'Detail Transaksi ' . e($tx['transaction_number']),
            'tx' => $tx
        ], 'layouts/app');
    }

    /**
     * Modul Laporan Keuangan (5 Jenis Laporan dengan Server-Side Filtering & Agregasi)
     */
    public function laporan(): void
    {
        $jenisLaporan = trim((string)$this->input('jenis_laporan', 'kas'));
        if (!in_array($jenisLaporan, ['kas', 'pemasukan', 'pengeluaran', 'pembayaran', 'tunggakan'])) {
            $jenisLaporan = 'kas';
        }

        $startDate = trim((string)$this->input('start_date', ''));
        $endDate = trim((string)$this->input('end_date', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $status = trim((string)$this->input('status', ''));
        $searchStudent = trim((string)$this->input('search_student', ''));

        $reportData = $this->fetchReportData($jenisLaporan, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category_id' => $categoryId,
            'status' => $status,
            'search_student' => $searchStudent
        ]);

        $this->render('keuangan/laporan', [
            'title' => 'Laporan Keuangan - Smart Campus Cash',
            'jenisLaporan' => $jenisLaporan,
            'rows' => $reportData['rows'],
            'summary' => $reportData['summary'],
            'categories' => $this->categoryModel->allByType(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCategory' => $categoryId,
            'selectedStatus' => $status,
            'searchStudent' => $searchStudent
        ], 'layouts/app');
    }

    /**
     * Export Laporan ke format CSV / Excel
     */
    public function exportCsv(): void
    {
        $jenisLaporan = trim((string)$this->input('jenis_laporan', 'kas'));
        $startDate = trim((string)$this->input('start_date', ''));
        $endDate = trim((string)$this->input('end_date', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $status = trim((string)$this->input('status', ''));
        $searchStudent = trim((string)$this->input('search_student', ''));

        $reportData = $this->fetchReportData($jenisLaporan, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category_id' => $categoryId,
            'status' => $status,
            'search_student' => $searchStudent
        ]);

        $filename = "Laporan_" . ucfirst($jenisLaporan) . "_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM untuk Microsoft Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Dokumen
        fputcsv($output, ['SMART CAMPUS CASH - LAPORAN ' . strtoupper($jenisLaporan)]);
        fputcsv($output, ['Periode:', ($startDate ?: 'Semua') . ' s/d ' . ($endDate ?: 'Semua')]);
        fputcsv($output, ['Tanggal Unduh:', date('d/m/Y H:i:s')]);
        fputcsv($output, []);

        if ($jenisLaporan === 'kas' || $jenisLaporan === 'pemasukan' || $jenisLaporan === 'pengeluaran') {
            fputcsv($output, ['No', 'No Transaksi', 'Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Nominal (Rp)', 'Status']);
            $no = 1;
            foreach ($reportData['rows'] as $r) {
                fputcsv($output, [
                    $no++,
                    $r['transaction_number'],
                    $r['transaction_date'],
                    strtoupper($r['type']),
                    $r['category_name'],
                    $r['description'],
                    $r['amount'],
                    $r['status']
                ]);
            }
            fputcsv($output, []);
            fputcsv($output, ['RINGKASAN TOTAL']);
            fputcsv($output, ['Total Pemasukan', $reportData['summary']['total_pemasukan']]);
            fputcsv($output, ['Total Pengeluaran', $reportData['summary']['total_pengeluaran']]);
            fputcsv($output, ['Saldo Kas', $reportData['summary']['saldo']]);
        } elseif ($jenisLaporan === 'pembayaran') {
            fputcsv($output, ['No', 'No Kuitansi', 'Tanggal', 'NIM', 'Nama Mahasiswa', 'Invoice', 'Metode', 'Nominal (Rp)', 'Status']);
            $no = 1;
            foreach ($reportData['rows'] as $r) {
                fputcsv($output, [
                    $no++,
                    $r['payment_number'],
                    $r['payment_date'],
                    $r['nim'],
                    $r['student_name'],
                    $r['invoice_number'],
                    strtoupper($r['payment_method']),
                    $r['amount'],
                    $r['status']
                ]);
            }
            fputcsv($output, []);
            fputcsv($output, ['Total Penerimaan Pembayaran', $reportData['summary']['total_pembayaran']]);
        } elseif ($jenisLaporan === 'tunggakan') {
            fputcsv($output, ['No', 'No Invoice', 'NIM', 'Nama Mahasiswa', 'Judul Tagihan', 'Jatuh Tempo', 'Total Tagihan (Rp)', 'Terbayar (Rp)', 'Sisa Tunggakan (Rp)', 'Status']);
            $no = 1;
            foreach ($reportData['rows'] as $r) {
                fputcsv($output, [
                    $no++,
                    $r['invoice_number'],
                    $r['nim'],
                    $r['student_name'],
                    $r['title'],
                    $r['due_date'],
                    $r['amount'],
                    $r['paid_amount'],
                    $r['remaining_amount'],
                    $r['status']
                ]);
            }
            fputcsv($output, []);
            fputcsv($output, ['Total Sisa Tunggakan', $reportData['summary']['total_tunggakan']]);
        }

        fclose($output);
        exit;
    }

    /**
     * Cetak Laporan Resmi (Printable View)
     */
    public function printLaporan(): void
    {
        $jenisLaporan = trim((string)$this->input('jenis_laporan', 'kas'));
        $startDate = trim((string)$this->input('start_date', ''));
        $endDate = trim((string)$this->input('end_date', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $status = trim((string)$this->input('status', ''));
        $searchStudent = trim((string)$this->input('search_student', ''));

        $reportData = $this->fetchReportData($jenisLaporan, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category_id' => $categoryId,
            'status' => $status,
            'search_student' => $searchStudent
        ]);

        $this->render('keuangan/laporan_print', [
            'title' => 'Cetak Laporan ' . ucfirst($jenisLaporan),
            'jenisLaporan' => $jenisLaporan,
            'rows' => $reportData['rows'],
            'summary' => $reportData['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'printedBy' => AuthService::user()['full_name'] ?? 'Bendahara Kampus',
            'printedDate' => date('d F Y, H:i') . ' WIB'
        ], 'layouts/guest');
    }

    /**
     * Helper Server-Side Query Data Laporan & Agregasi Akurat
     */
    protected function fetchReportData(string $jenis, array $filters): array
    {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return ['rows' => [], 'summary' => [
                'total_pemasukan' => 0, 'total_pengeluaran' => 0, 'saldo' => 0,
                'total_pembayaran' => 0, 'total_tunggakan' => 0
            ]];
        }

        $summary = [
            'total_pemasukan' => 0.0,
            'total_pengeluaran' => 0.0,
            'saldo' => 0.0,
            'total_pembayaran' => 0.0,
            'total_tunggakan' => 0.0
        ];
        $rows = [];

        if (in_array($jenis, ['kas', 'pemasukan', 'pengeluaran'])) {
            $conds = ["ct.status != 'voided'"];
            $params = [];

            if ($jenis === 'pemasukan') {
                $conds[] = "ct.type = 'income'";
            } elseif ($jenis === 'pengeluaran') {
                $conds[] = "ct.type = 'expense'";
            }

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $conds[] = "DATE(ct.transaction_date) BETWEEN :sdate AND :edate";
                $params['sdate'] = $filters['start_date'];
                $params['edate'] = $filters['end_date'];
            }

            if (!empty($filters['category_id'])) {
                $conds[] = "ct.category_id = :cid";
                $params['cid'] = (int)$filters['category_id'];
            }

            $where = implode(" AND ", $conds);

            $stmt = $pdo->prepare("SELECT ct.*, c.name AS category_name, u.full_name AS creator_name,
                                          s.nim, s.full_name AS student_name
                                   FROM cash_transactions ct
                                   JOIN categories c ON ct.category_id = c.id
                                   JOIN users u ON ct.created_by = u.id
                                   LEFT JOIN payments p ON ct.reference_number = p.payment_number
                                   LEFT JOIN students s ON p.student_id = s.id
                                   WHERE {$where}
                                   ORDER BY ct.transaction_date ASC, ct.id ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hitung ringkasan akurat di backend
            foreach ($rows as $r) {
                if ($r['type'] === 'income') {
                    $summary['total_pemasukan'] += (float)$r['amount'];
                } else {
                    $summary['total_pengeluaran'] += (float)$r['amount'];
                }
            }
            $summary['saldo'] = $summary['total_pemasukan'] - $summary['total_pengeluaran'];

        } elseif ($jenis === 'pembayaran') {
            $conds = ["p.status != 'refunded'"];
            $params = [];

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $conds[] = "DATE(p.payment_date) BETWEEN :sdate AND :edate";
                $params['sdate'] = $filters['start_date'];
                $params['edate'] = $filters['end_date'];
            }

            if (!empty($filters['search_student'])) {
                $conds[] = "(s.nim LIKE :sq OR s.full_name LIKE :sq2)";
                $params['sq'] = "%" . $filters['search_student'] . "%";
                $params['sq2'] = "%" . $filters['search_student'] . "%";
            }

            $where = implode(" AND ", $conds);

            $stmt = $pdo->prepare("SELECT p.*, s.nim, s.full_name AS student_name, b.invoice_number, b.title AS bill_title, u.full_name AS cashier_name
                                   FROM payments p
                                   JOIN students s ON p.student_id = s.id
                                   LEFT JOIN bills b ON p.bill_id = b.id
                                   LEFT JOIN users u ON p.recorded_by = u.id
                                   WHERE {$where}
                                   ORDER BY p.payment_date ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $summary['total_pembayaran'] += (float)$r['amount'];
            }

        } elseif ($jenis === 'tunggakan') {
            $conds = ["b.status IN ('UNPAID', 'PARTIAL', 'OVERDUE')"];
            $params = [];

            if (!empty($filters['search_student'])) {
                $conds[] = "(s.nim LIKE :sq OR s.full_name LIKE :sq2)";
                $params['sq'] = "%" . $filters['search_student'] . "%";
                $params['sq2'] = "%" . $filters['search_student'] . "%";
            }

            if (!empty($filters['status'])) {
                $conds[] = "b.status = :st";
                $params['st'] = strtoupper($filters['status']);
            }

            $where = implode(" AND ", $conds);

            $stmt = $pdo->prepare("SELECT b.*, s.nim, s.full_name AS student_name, s.study_program, c.name AS category_name
                                   FROM bills b
                                   JOIN students s ON b.student_id = s.id
                                   JOIN categories c ON b.category_id = c.id
                                   WHERE {$where}
                                   ORDER BY b.due_date ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $summary['total_tunggakan'] += (float)$r['remaining_amount'];
            }
        }

        return ['rows' => $rows, 'summary' => $summary];
    }
}
