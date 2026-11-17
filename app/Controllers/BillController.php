<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Bill;
use App\Models\Student;
use App\Models\Category;
use App\Services\AuditLogger;
use App\Services\AuthService;

class BillController extends Controller
{
    protected Bill $billModel;
    protected Student $studentModel;
    protected Category $categoryModel;

    public function __construct()
    {
        $this->billModel = new Bill();
        $this->studentModel = new Student();
        $this->categoryModel = new Category();
    }

    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $status = trim((string)$this->input('status', ''));
        $period = trim((string)$this->input('period', ''));

        $filters = array_filter([
            'search' => $search,
            'status' => $status,
            'period' => $period
        ]);

        $result = $this->billModel->paginate($page, 10, $filters);

        $this->render('bills/index', [
            'title' => 'Daftar Tagihan Mahasiswa - Smart Campus Cash',
            'bills' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPeriod' => $period,
            'periodList' => $this->billModel->getDistinctPeriods(),
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function create(): void
    {
        $suggestedInv = $this->billModel->generateInvoiceNumber();
        $studentsResult = $this->studentModel->paginate(1, 100, ['status' => 'active']);
        $categories = $this->categoryModel->allByType('income');

        $this->render('bills/create', [
            'title' => 'Buat Tagihan Mahasiswa Baru - Smart Campus Cash',
            'suggestedInv' => $suggestedInv,
            'students' => $studentsResult['data'],
            'categories' => $categories,
            'error' => flash('flash_error'),
            'old' => $_SESSION['old_input'] ?? []
        ], 'layouts/app');
        unset($_SESSION['old_input']);
    }

    public function store(): void
    {
        $inv = trim((string)$this->input('invoice_number', ''));
        $studentId = (int)$this->input('student_id', 0);
        $categoryId = (int)$this->input('category_id', 0);
        $title = trim((string)$this->input('title', ''));
        $period = trim((string)$this->input('period', '2026/2027 Gasal'));
        $amount = (float)$this->input('amount', 0);
        $dueDate = trim((string)$this->input('due_date', ''));
        $desc = trim((string)$this->input('description', ''));
        $notes = trim((string)$this->input('notes', ''));

        $_SESSION['old_input'] = $_POST;

        // Validasi
        if (empty($inv)) {
            $_SESSION['flash_error'] = 'Nomor invoice wajib diisi.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if ($this->billModel->isInvoiceNumberTaken($inv)) {
            $_SESSION['flash_error'] = "Nomor invoice '{$inv}' sudah pernah digunakan.";
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if ($studentId <= 0 || !$this->studentModel->find($studentId)) {
            $_SESSION['flash_error'] = 'Pilih mahasiswa yang valid.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if ($categoryId <= 0) {
            $_SESSION['flash_error'] = 'Pilih kategori jenis tagihan.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if (empty($title)) {
            $_SESSION['flash_error'] = 'Judul tagihan wajib diisi.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if ($amount <= 0) {
            $_SESSION['flash_error'] = 'Nominal tagihan harus lebih besar dari Rp 0.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        if (empty($dueDate) || !strtotime($dueDate)) {
            $_SESSION['flash_error'] = 'Tanggal jatuh tempo wajib diisi dengan format tanggal yang benar.';
            $this->redirect(url('admin/bills/create'));
            return;
        }

        $creatorId = AuthService::id() ?? 1;

        $billId = $this->billModel->create([
            'invoice_number' => $inv,
            'student_id' => $studentId,
            'category_id' => $categoryId,
            'title' => $title,
            'period' => $period,
            'amount' => $amount,
            'due_date' => $dueDate,
            'description' => $desc,
            'notes' => $notes,
            'created_by' => $creatorId
        ]);

        if ($billId) {
            AuditLogger::log('CREATE_INVOICE', 'bills', $billId, null, [
                'invoice_number' => $inv,
                'student_id' => $studentId,
                'amount' => $amount,
                'due_date' => $dueDate,
                'title' => $title
            ]);

            // WhatsApp Notification (Fail-safe, Non-blocking)
            try {
                $studentData = $this->studentModel->find($studentId) ?: [];
                $billData = [
                    'id' => $billId,
                    'invoice_number' => $inv,
                    'title' => $title,
                    'amount' => $amount,
                    'due_date' => $dueDate
                ];
                \App\Services\WhatsAppService::notifyBillCreated($billData, $studentData, \App\Services\AuthService::id());
            } catch (\Exception $waEx) {
                error_log("WhatsApp notifyBillCreated error: " . $waEx->getMessage());
            }


            unset($_SESSION['old_input']);
            $_SESSION['flash_success'] = "Tagihan '{$title}' ({$inv}) berhasil diterbitkan.";
            $this->redirect(url('admin/bills'));
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan tagihan ke database.';
            $this->redirect(url('admin/bills/create'));
        }
    }

    public function detail(): void
    {
        $id = (int)$this->input('id', 0);
        $bill = $this->billModel->findWithDetails($id);

        if (!$bill) {
            $_SESSION['flash_error'] = 'Tagihan tidak ditemukan.';
            $this->redirect(url('admin/bills'));
            return;
        }

        $this->render('bills/detail', [
            'title' => 'Invoice ' . e($bill['invoice_number']) . ' - ' . e($bill['student_name']),
            'bill' => $bill
        ], 'layouts/app');
    }

    public function edit(): void
    {
        $id = (int)$this->input('id', 0);
        $bill = $this->billModel->findWithDetails($id);

        if (!$bill) {
            $_SESSION['flash_error'] = 'Tagihan tidak ditemukan.';
            $this->redirect(url('admin/bills'));
            return;
        }

        if (in_array($bill['status'], ['PAID', 'CANCELLED'])) {
            $_SESSION['flash_error'] = "Tagihan berstatus {$bill['status']} tidak dapat diedit.";
            $this->redirect(url('admin/bills'));
            return;
        }

        $categories = $this->categoryModel->allByType('income');

        $this->render('bills/edit', [
            'title' => 'Edit Tagihan - ' . e($bill['invoice_number']),
            'bill' => $bill,
            'categories' => $categories,
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function update(): void
    {
        $id = (int)$this->input('id', 0);
        $bill = $this->billModel->find($id);

        if (!$bill) {
            $_SESSION['flash_error'] = 'Tagihan tidak ditemukan.';
            $this->redirect(url('admin/bills'));
            return;
        }

        if (in_array($bill['status'], ['PAID', 'CANCELLED'])) {
            $_SESSION['flash_error'] = "Tagihan berstatus {$bill['status']} tidak dapat diedit.";
            $this->redirect(url('admin/bills'));
            return;
        }

        $inv = trim((string)$this->input('invoice_number', ''));
        $categoryId = (int)$this->input('category_id', 0);
        $title = trim((string)$this->input('title', ''));
        $period = trim((string)$this->input('period', '2026/2027 Gasal'));
        $amount = (float)$this->input('amount', 0);
        $dueDate = trim((string)$this->input('due_date', ''));
        $notes = trim((string)$this->input('notes', ''));

        if (empty($inv) || empty($title) || $categoryId <= 0 || $amount <= 0 || empty($dueDate)) {
            $_SESSION['flash_error'] = 'Field wajib tidak boleh kosong dan nominal harus > Rp 0.';
            $this->redirect(url('admin/bills/edit?id=' . $id));
            return;
        }

        if ($this->billModel->isInvoiceNumberTaken($inv, $id)) {
            $_SESSION['flash_error'] = "Nomor invoice '{$inv}' sudah digunakan tagihan lain.";
            $this->redirect(url('admin/bills/edit?id=' . $id));
            return;
        }

        $paid = (float)$bill['paid_amount'];
        if ($amount < $paid) {
            $_SESSION['flash_error'] = "Nominal tagihan tidak boleh lebih kecil dari jumlah yang telah dibayar (Rp " . number_format($paid) . ").";
            $this->redirect(url('admin/bills/edit?id=' . $id));
            return;
        }

        $success = $this->billModel->update($id, [
            'invoice_number' => $inv,
            'category_id' => $categoryId,
            'title' => $title,
            'period' => $period,
            'amount' => $amount,
            'due_date' => $dueDate,
            'notes' => $notes
        ]);

        if ($success) {
            AuditLogger::log('UPDATE_INVOICE', 'bills', $id, [
                'invoice_number' => $bill['invoice_number'],
                'amount' => $bill['amount'],
                'due_date' => $bill['due_date']
            ], [
                'invoice_number' => $inv,
                'amount' => $amount,
                'due_date' => $dueDate
            ]);

            $_SESSION['flash_success'] = "Tagihan '{$inv}' berhasil diperbarui.";
            $this->redirect(url('admin/bills'));
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui data tagihan.';
            $this->redirect(url('admin/bills/edit?id=' . $id));
        }
    }

    public function cancel(): void
    {
        $id = (int)$this->input('id', 0);
        $reason = trim((string)$this->input('reason', 'Dibatalkan oleh operator'));

        $bill = $this->billModel->find($id);
        if (!$bill) {
            $_SESSION['flash_error'] = 'Tagihan tidak ditemukan.';
            $this->redirect(url('admin/bills'));
            return;
        }

        if ($bill['status'] === 'PAID' || (float)$bill['paid_amount'] > 0) {
            $_SESSION['flash_error'] = 'Tagihan yang sudah memiliki riwayat pembayaran tidak dapat dibatalkan.';
            $this->redirect(url('admin/bills'));
            return;
        }

        $success = $this->billModel->cancel($id, $reason);

        if ($success) {
            AuditLogger::log('CANCEL_INVOICE', 'bills', $id, ['status' => $bill['status']], [
                'status' => 'CANCELLED',
                'reason' => $reason
            ]);

            // WhatsApp Notification (Fail-safe, Non-blocking)
            try {
                $studentData = $this->studentModel->find((int)$bill['student_id']) ?: [];
                \App\Services\WhatsAppService::notifyBillStatusChanged($bill, $studentData, $bill['status'], 'DIBATALKAN (CANCELLED)', $reason);
            } catch (\Exception $waEx) {
                error_log("WhatsApp notifyBillStatusChanged error: " . $waEx->getMessage());
            }


            $_SESSION['flash_success'] = "Tagihan '{$bill['invoice_number']}' berhasil dibatalkan.";
        } else {
            $_SESSION['flash_error'] = 'Gagal membatalkan tagihan.';
        }

        $this->redirect(url('admin/bills'));
    }
}
