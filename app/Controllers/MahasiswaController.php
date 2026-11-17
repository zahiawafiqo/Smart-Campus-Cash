<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\Bill;
use App\Models\Student;
use App\Models\Payment;
use App\Services\AuthService;
use App\Services\AuditLogger;
use PDO;

class MahasiswaController extends Controller
{
    protected Bill $billModel;
    protected Student $studentModel;
    protected Payment $paymentModel;

    public function __construct()
    {
        $this->billModel = new Bill();
        $this->studentModel = new Student();
        $this->paymentModel = new Payment();
    }

    /**
     * Dashboard Portal Mahasiswa
     */
    public function dashboard(): void
    {
        $pdo = Database::getConnection();
        $userId = AuthService::id();

        // 1. Ambil data profil mahasiswa milik sesi aktif ini saja
        $student = null;
        if ($pdo && $userId) {
            $student = $this->studentModel->findByUserId($userId);
        }

        $bills = [];
        $payments = [];
        $totalTagihan = 0.0;
        $totalTerbayar = 0.0;
        $sisaTunggakan = 0.0;
        $nearestDue = null;

        if ($student && $pdo) {
            $studentId = (int)$student['id'];

            // 2. Query HANYA tagihan milik mahasiswa ini (Strict Ownership Guard)
            $billStmt = $pdo->prepare("SELECT b.*, c.name AS category_name 
                                       FROM bills b 
                                       JOIN categories c ON b.category_id = c.id 
                                       WHERE b.student_id = :sid 
                                       ORDER BY b.due_date ASC");
            $billStmt->execute(['sid' => $studentId]);
            $bills = $billStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Query 5 Pembayaran terakhir milik mahasiswa ini
            $payStmt = $pdo->prepare("SELECT p.*, b.invoice_number, b.title AS bill_title, u.full_name AS cashier_name 
                                      FROM payments p 
                                      LEFT JOIN bills b ON p.bill_id = b.id 
                                      LEFT JOIN users u ON p.recorded_by = u.id 
                                      WHERE p.student_id = :sid 
                                      ORDER BY p.payment_date DESC
                                      LIMIT 5");
            $payStmt->execute(['sid' => $studentId]);
            $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

            // Hitung akumulasi & cari tagihan terdekat jatuh tempo
            foreach ($bills as $b) {
                $totalTagihan += (float)$b['amount'];
                $totalTerbayar += (float)$b['paid_amount'];
                $sisaTunggakan += (float)$b['remaining_amount'];

                if (!$nearestDue && in_array($b['status'], ['UNPAID', 'PARTIAL', 'OVERDUE'])) {
                    $nearestDue = $b;
                }
            }
        }

        $this->render('mahasiswa/dashboard', [
            'title' => 'Portal Mahasiswa - Smart Campus Cash',
            'user' => AuthService::user(),
            'student' => $student,
            'totalTagihan' => $totalTagihan,
            'totalTerbayar' => $totalTerbayar,
            'sisaTunggakan' => $sisaTunggakan,
            'nearestDue' => $nearestDue,
            'payments' => $payments,
            'bills' => $bills
        ], 'layouts/app');
    }

    /**
     * Halaman Daftar Tagihan Kuliah Saya
     */
    public function tagihan(): void
    {
        $userId = AuthService::id();
        $student = $this->studentModel->findByUserId($userId);

        if (!$student) {
            $_SESSION['flash_error'] = 'Profil mahasiswa Anda tidak ditemukan.';
            $this->redirect(url('login'));
            return;
        }

        $pdo = Database::getConnection();
        $bills = [];
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT b.*, c.name AS category_name, c.code AS category_code
                                   FROM bills b
                                   JOIN categories c ON b.category_id = c.id
                                   WHERE b.student_id = :sid
                                   ORDER BY b.due_date ASC");
            $stmt->execute(['sid' => $student['id']]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('mahasiswa/tagihan', [
            'title' => 'Tagihan Kuliah Saya - Smart Campus Cash',
            'student' => $student,
            'bills' => $bills
        ], 'layouts/app');
    }

    /**
     * Detail Invoice Tagihan Saya (STRICT OWNERSHIP GUARD - ANTI IDOR)
     */
    public function tagihanDetail(): void
    {
        $userId = AuthService::id();
        $student = $this->studentModel->findByUserId($userId);

        if (!$student) {
            $_SESSION['flash_error'] = 'Profil mahasiswa tidak ditemukan.';
            $this->redirect(url('login'));
            return;
        }

        $billId = (int)$this->input('id', 0);
        $bill = $this->billModel->findWithDetails($billId);

        // Validasi keberadaan tagihan
        if (!$bill) {
            $_SESSION['flash_error'] = 'Tagihan tidak ditemukan.';
            $this->redirect(url('mahasiswa/tagihan'));
            return;
        }

        // STRICT DATA OWNERSHIP GUARD: Cek apakah tagihan benar-benar milik mahasiswa ini
        if ((int)$bill['student_id'] !== (int)$student['id']) {
            AuditLogger::log('IDOR_ACCESS_ATTEMPT', 'bills', $billId, null, [
                'student_id_session' => $student['id'],
                'student_id_target' => $bill['student_id'],
                'target_invoice' => $bill['invoice_number']
            ]);

            http_response_code(403);
            View::render('errors/403', [
                'title' => '403 - Akses Ditolak (Strict Data Ownership)',
                'userRole' => 'mahasiswa',
                'allowedRoles' => ['Pemilik Sah Tagihan Ini'],
                'attemptedUrl' => $_SERVER['REQUEST_URI'] ?? ''
            ], 'layouts/guest');
            exit;
        }

        // Ambil riwayat pembayaran untuk tagihan ini
        $pdo = Database::getConnection();
        $billPayments = [];
        if ($pdo) {
            $pStmt = $pdo->prepare("SELECT p.*, u.full_name as cashier_name 
                                    FROM payments p 
                                    LEFT JOIN users u ON p.recorded_by = u.id 
                                    WHERE p.bill_id = :bid 
                                    ORDER BY p.payment_date DESC");
            $pStmt->execute(['bid' => $billId]);
            $billPayments = $pStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $bill['payments'] = $billPayments;

        $this->render('mahasiswa/tagihan_detail', [
            'title' => 'Detail Tagihan ' . e($bill['invoice_number']),
            'student' => $student,
            'bill' => $bill,
            'clientKey' => \App\Services\MidtransService::getClientKey(),
            'snapJsUrl' => \App\Services\MidtransService::getSnapJsUrl()
        ], 'layouts/app');
    }

    /**
     * Request Token Pembayaran Online Snap Midtrans (AJAX / POST)
     */
    public function bayarOnline(): void
    {
        $userId = AuthService::id();
        $student = $this->studentModel->findByUserId($userId);

        if (!$student) {
            $this->json(['success' => false, 'message' => 'Profil mahasiswa tidak ditemukan.'], 403);
            return;
        }

        $billId = (int)$this->input('bill_id', 0);
        $amount = (float)$this->input('amount', 0);

        $bill = $this->billModel->findWithDetails($billId);

        if (!$bill) {
            $this->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
            return;
        }

        // Strict ownership check
        if ((int)$bill['student_id'] !== (int)$student['id']) {
            $this->json(['success' => false, 'message' => 'Akses ditolak: Tagihan bukan milik Anda.'], 403);
            return;
        }

        if (in_array($bill['status'], ['PAID', 'CANCELLED'])) {
            $this->json(['success' => false, 'message' => 'Tagihan ini sudah lunas atau dibatalkan.'], 400);
            return;
        }

        $payAmount = ($amount > 0) ? min($amount, (float)$bill['remaining_amount']) : (float)$bill['remaining_amount'];

        $result = \App\Services\MidtransService::createSnapTransaction($bill, $student, $payAmount);

        if (!$result['success']) {
            $this->json(['success' => false, 'message' => $result['message']], 400);
            return;
        }

        $this->json($result, 200);
    }

    /**
     * Riwayat Pembayaran Pribadi
     */
    public function riwayat(): void
    {
        $userId = AuthService::id();
        $student = $this->studentModel->findByUserId($userId);

        if (!$student) {
            $_SESSION['flash_error'] = 'Profil mahasiswa tidak ditemukan.';
            $this->redirect(url('login'));
            return;
        }

        $pdo = Database::getConnection();
        $payments = [];
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT p.*, b.invoice_number, b.title AS bill_title, u.full_name AS cashier_name
                                   FROM payments p
                                   LEFT JOIN bills b ON p.bill_id = b.id
                                   LEFT JOIN users u ON p.recorded_by = u.id
                                   WHERE p.student_id = :sid
                                   ORDER BY p.payment_date DESC");
            $stmt->execute(['sid' => $student['id']]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('mahasiswa/riwayat', [
            'title' => 'Riwayat Pembayaran Saya - Smart Campus Cash',
            'student' => $student,
            'payments' => $payments
        ], 'layouts/app');
    }
}

