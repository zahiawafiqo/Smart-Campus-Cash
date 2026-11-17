<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Bill;
use App\Services\AuthService;
use PDO;

class KasirController extends Controller
{
    protected Payment $paymentModel;
    protected Student $studentModel;
    protected Bill $billModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->studentModel = new Student();
        $this->billModel = new Bill();
    }

    /**
     * Dashboard Loket Kasir
     */
    public function dashboard(): void
    {
        $pdo = Database::getConnection();
        $cashierId = AuthService::id() ?? 1;

        // 1. Total Pembayaran Hari Ini & Jumlah Transaksi Hari Ini
        $todayTotal = 0.0;
        $todayCount = 0;
        if ($pdo) {
            $sumStmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total_amt, COUNT(*) AS total_cnt FROM payments WHERE DATE(payment_date) = CURDATE() AND status != 'refunded'");
            $row = $sumStmt->fetch(PDO::FETCH_ASSOC);
            $todayTotal = (float)($row['total_amt'] ?? 0);
            $todayCount = (int)($row['total_cnt'] ?? 0);
        }

        // 2. Daftar Transaksi Hari Ini
        $todayPayments = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT p.*, s.nim, s.full_name AS student_name, b.invoice_number, b.title AS bill_title, u.full_name AS cashier_name
                                FROM payments p
                                JOIN students s ON p.student_id = s.id
                                LEFT JOIN bills b ON p.bill_id = b.id
                                LEFT JOIN users u ON p.recorded_by = u.id
                                WHERE DATE(p.payment_date) = CURDATE()
                                ORDER BY p.payment_date DESC
                                LIMIT 8");
            $todayPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3. Transaksi Terbaru (All time)
        $recentPayments = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT p.*, s.nim, s.full_name AS student_name, b.invoice_number, u.full_name AS cashier_name
                                FROM payments p
                                JOIN students s ON p.student_id = s.id
                                LEFT JOIN bills b ON p.bill_id = b.id
                                LEFT JOIN users u ON p.recorded_by = u.id
                                ORDER BY p.payment_date DESC
                                LIMIT 5");
            $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4. Tagihan Antrian
        $pendingBills = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT b.*, s.nim, s.full_name AS student_name, c.name AS category_name
                                FROM bills b
                                JOIN students s ON b.student_id = s.id
                                JOIN categories c ON b.category_id = c.id
                                WHERE b.status IN ('UNPAID', 'PARTIAL', 'OVERDUE')
                                ORDER BY b.due_date ASC
                                LIMIT 6");
            $pendingBills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('kasir/dashboard', [
            'title' => 'Dashboard Kasir Loket - Smart Campus Cash',
            'user' => AuthService::user(),
            'todayTotal' => $todayTotal,
            'todayCount' => $todayCount,
            'todayPayments' => $todayPayments,
            'recentPayments' => $recentPayments,
            'pendingBills' => $pendingBills
        ], 'layouts/app');
    }

    /**
     * Halaman Loket Pembayaran Mahasiswa
     */
    public function pembayaran(): void
    {
        $search = trim((string)$this->input('search', ''));
        $selectedStudentId = (int)$this->input('student_id', 0);

        $searchResults = [];
        $selectedStudent = null;
        $activeBills = [];

        if (!empty($search)) {
            $pdo = Database::getConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT s.*, 
                                              (SELECT COUNT(*) FROM bills WHERE student_id = s.id AND status IN ('UNPAID','PARTIAL','OVERDUE')) AS unpaid_count,
                                              (SELECT COALESCE(SUM(remaining_amount), 0) FROM bills WHERE student_id = s.id AND status IN ('UNPAID','PARTIAL','OVERDUE')) AS total_unpaid
                                       FROM students s
                                       WHERE (s.nim LIKE :q OR s.full_name LIKE :q2) AND s.deleted_at IS NULL
                                       LIMIT 8");
                $stmt->execute(['q' => "%{$search}%", 'q2' => "%{$search}%"]);
                $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($searchResults) === 1 && $selectedStudentId === 0) {
                    $selectedStudentId = (int)$searchResults[0]['id'];
                }
            }
        }

        if ($selectedStudentId > 0) {
            $selectedStudent = $this->studentModel->findWithUser($selectedStudentId);
            if ($selectedStudent) {
                $pdo = Database::getConnection();
                $bStmt = $pdo->prepare("SELECT b.*, c.name AS category_name 
                                        FROM bills b 
                                        JOIN categories c ON b.category_id = c.id 
                                        WHERE b.student_id = :sid AND b.status IN ('UNPAID', 'PARTIAL', 'OVERDUE')
                                        ORDER BY b.due_date ASC");
                $bStmt->execute(['sid' => $selectedStudentId]);
                $activeBills = $bStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $this->render('kasir/pembayaran', [
            'title' => 'Loket Pembayaran Mahasiswa - Smart Campus Cash',
            'search' => $search,
            'searchResults' => $searchResults,
            'selectedStudent' => $selectedStudent,
            'activeBills' => $activeBills,
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    /**
     * Proses transaksi pembayaran (POST)
     */
    public function prosesBayar(): void
    {
        $billId = (int)$this->input('bill_id', 0);
        $amount = (float)$this->input('amount', 0);
        $method = trim((string)$this->input('payment_method', 'cash'));
        $notes = trim((string)$this->input('notes', ''));
        $idempotencyToken = trim((string)$this->input('idempotency_token', ''));

        // Proteksi duplicate submission
        if (!empty($idempotencyToken)) {
            if (isset($_SESSION['last_processed_token']) && $_SESSION['last_processed_token'] === $idempotencyToken) {
                $_SESSION['flash_error'] = 'Transaksi ini sudah pernah diproses sebelumnya (Pencegahan double-submit).';
                $this->redirect(url('kasir/pembayaran'));
                return;
            }
            $_SESSION['last_processed_token'] = $idempotencyToken;
        }

        if ($billId <= 0) {
            $_SESSION['flash_error'] = 'Silakan pilih tagihan yang akan dibayar.';
            $this->redirect(url('kasir/pembayaran'));
            return;
        }

        if ($amount <= 0) {
            $_SESSION['flash_error'] = 'Nominal pembayaran harus lebih besar dari Rp 0.';
            $this->redirect(url('kasir/pembayaran'));
            return;
        }

        $cashierId = AuthService::id() ?? 1;

        $result = $this->paymentModel->processPayment([
            'bill_id' => $billId,
            'amount' => $amount,
            'payment_method' => $method,
            'notes' => $notes,
            'recorded_by' => $cashierId
        ]);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            $this->redirect(url('kasir/pembayaran'));
            return;
        }

        $_SESSION['flash_success'] = $result['message'];
        $this->redirect(url('kasir/sukses?id=' . $result['payment_id']));
    }

    /**
     * Halaman Konfirmasi Sukses Pembayaran
     */
    public function sukses(): void
    {
        $id = (int)$this->input('id', 0);
        $payment = $this->paymentModel->findWithDetails($id);

        if (!$payment) {
            $_SESSION['flash_error'] = 'Transaksi pembayaran tidak ditemukan.';
            $this->redirect(url('kasir/pembayaran'));
            return;
        }

        $this->render('kasir/sukses', [
            'title' => 'Pembayaran Berhasil - ' . e($payment['payment_number']),
            'payment' => $payment
        ], 'layouts/app');
    }

    /**
     * Detail Lembar Kuitansi Resmi (Printable)
     */
    public function kuitansi(): void
    {
        $id = (int)$this->input('id', 0);
        $payment = $this->paymentModel->findWithDetails($id);

        if (!$payment) {
            $_SESSION['flash_error'] = 'Kuitansi tidak ditemukan.';
            $this->redirect(url('kasir/dashboard'));
            return;
        }

        $this->render('kasir/kuitansi', [
            'title' => 'Kuitansi Pembayaran ' . e($payment['payment_number']),
            'payment' => $payment
        ], 'layouts/app');
    }

    /**
     * Riwayat Pembayaran Loket Kasir
     */
    public function riwayat(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $date = trim((string)$this->input('date', ''));
        $method = trim((string)$this->input('method', ''));

        $filters = array_filter([
            'search' => $search,
            'date' => $date,
            'method' => $method
        ]);

        $result = $this->paymentModel->paginate($page, 15, $filters);

        $this->render('kasir/riwayat', [
            'title' => 'Riwayat Pembayaran Loket - Smart Campus Cash',
            'payments' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'search' => $search,
            'selectedDate' => $date,
            'selectedMethod' => $method,
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    /**
     * Void/Reversal Pembayaran (Hanya jika ada otorisasi khusus)
     */
    public function void(): void
    {
        $id = (int)$this->input('id', 0);
        $reason = trim((string)$this->input('reason', 'Pembatalan transaksi oleh kasir'));

        if (empty($reason)) {
            $_SESSION['flash_error'] = 'Alasan pembatalan (void) wajib dicantumkan.';
            $this->redirect(url('kasir/riwayat'));
            return;
        }

        $userId = AuthService::id() ?? 1;
        $result = $this->paymentModel->voidPayment($id, $userId, $reason);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        $this->redirect(url('kasir/riwayat'));
    }
}
