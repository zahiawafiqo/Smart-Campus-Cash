<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\WhatsAppNotification;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppSetting;
use App\Models\Student;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\AuthService;
use App\Services\AuditLogger;
use App\Services\WhatsAppService;
use PDO;

class WhatsAppNotificationController extends Controller
{
    private WhatsAppNotification $notifModel;
    private WhatsAppTemplate $templateModel;
    private Student $studentModel;
    private Bill $billModel;

    public function __construct()
    {
        $this->notifModel = new WhatsAppNotification();
        $this->templateModel = new WhatsAppTemplate();
        $this->studentModel = new Student();
        $this->billModel = new Bill();
    }

    /**
     * Dapatkan batasan kategori berdasarkan peran pengguna saat ini (Strict RBAC)
     */
    private function getAllowedCategoriesForRole(string $role): array
    {
        $role = strtolower(trim($role));
        switch ($role) {
            case 'admin':
                return [
                    'bill_created'           => 'Tagihan Baru Diterbitkan',
                    'due_reminder'           => 'Pengingat Menjelang Jatuh Tempo',
                    'overdue_reminder'       => 'Tagihan Terlambat / Tunggakan',
                    'payment_success'        => 'Konfirmasi Pembayaran Berhasil',
                    'counter_recap'          => 'Rekap Setoran Loket Kasir',
                    'financial_announcement' => 'Pengumuman Keuangan Kampus'
                ];
            case 'keuangan':
                return [
                    'bill_created'           => 'Tagihan Baru Diterbitkan',
                    'due_reminder'           => 'Pengingat Menjelang Jatuh Tempo',
                    'overdue_reminder'       => 'Tagihan Terlambat / Tunggakan',
                    'payment_success'        => 'Konfirmasi Pembayaran Berhasil',
                    'financial_announcement' => 'Pengumuman Keuangan Kampus'
                ];
            case 'kasir':
                return [
                    'payment_success' => 'Konfirmasi Pembayaran Berhasil',
                    'counter_recap'   => 'Rekap Setoran Loket Kasir'
                ];
            default:
                return [];
        }
    }

    /**
     * Pastikan pengguna bukan mahasiswa dan memiliki hak akses staf yang sah
     */
    private function authorizeStaff(): string
    {
        if (!AuthService::check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses menu notifikasi.';
            $this->redirect(url('login'));
        }

        $role = strtolower(AuthService::role() ?? '');
        if ($role === 'mahasiswa' || empty($role)) {
            AuditLogger::log('UNAUTHORIZED_ACCESS_ATTEMPT', 'security', null, null, [
                'target' => 'whatsapp_notifications',
                'role'   => $role
            ]);
            http_response_code(403);
            $this->render('errors/403', [
                'title'        => '403 - Akses Ditolak',
                'userRole'     => $role,
                'allowedRoles' => ['admin', 'keuangan', 'kasir'],
                'attemptedUrl' => $_SERVER['REQUEST_URI'] ?? ''
            ], 'layouts/guest');
            exit;
        }

        return $role;
    }

    /**
     * Dashboard & Riwayat Notifikasi WhatsApp
     */
    public function index(): void
    {
        $role = $this->authorizeStaff();
        $allowedCategoryMap = $this->getAllowedCategoriesForRole($role);
        $allowedCategoryKeys = array_keys($allowedCategoryMap);

        // Filter dari query string
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 15;
        $search = trim((string)$this->input('q', ''));
        $status = trim((string)$this->input('status', ''));
        $category = trim((string)$this->input('category', ''));
        $dateFrom = trim((string)$this->input('date_from', ''));
        $dateTo = trim((string)$this->input('date_to', ''));

        if (!empty($category) && !in_array($category, $allowedCategoryKeys)) {
            $category = '';
        }

        $filters = [
            'search'             => $search,
            'status'             => $status,
            'category'           => $category,
            'date_from'          => $dateFrom,
            'date_to'            => $dateTo,
            'allowed_categories' => ($role === 'admin') ? null : $allowedCategoryKeys
        ];

        if ($role === 'kasir') {
            $filters['allowed_categories'] = ['payment_success', 'counter_recap'];
        }

        $paginated = $this->notifModel->paginate($page, $perPage, $filters);

        $statsFilters = [];
        if ($role !== 'admin') {
            $statsFilters['allowed_categories'] = ($role === 'kasir') ? ['payment_success', 'counter_recap'] : $allowedCategoryKeys;
        }
        $stats = $this->notifModel->getStats($statsFilters);

        $fonnteConfig = WhatsAppService::getConfig();
        $modeInfo = WhatsAppService::getModeInfo();

        $this->render('notifications/index', [
            'title'             => 'Notifikasi WhatsApp - Smart Campus Cash',
            'user'              => AuthService::user(),
            'role'              => $role,
            'allowedCategories' => $allowedCategoryMap,
            'notifications'     => $paginated['data'],
            'total'             => $paginated['total'],
            'totalPages'        => $paginated['totalPages'],
            'currentPage'       => $paginated['page'],
            'perPage'           => $perPage,
            'stats'             => $stats,
            'filters'           => [
                'q'         => $search,
                'status'    => $status,
                'category'  => $category,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo
            ],
            'fonnteConfig'      => $fonnteConfig,
            'modeInfo'          => $modeInfo
        ]);
    }

    /**
     * Form Pengiriman Pesan Notifikasi Baru
     */
    public function send(): void
    {
        $role = $this->authorizeStaff();
        $allowedCategoryMap = $this->getAllowedCategoriesForRole($role);

        $pdo = Database::getConnection();
        $students = [];
        $studyPrograms = [];
        if ($pdo) {
            $stStmt = $pdo->query("SELECT id, nim, full_name, study_program, class_group, phone 
                                   FROM students 
                                   WHERE deleted_at IS NULL 
                                   ORDER BY full_name ASC");
            $students = $stStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $prodiStmt = $pdo->query("SELECT DISTINCT study_program FROM students WHERE deleted_at IS NULL AND study_program IS NOT NULL AND study_program != '' ORDER BY study_program ASC");
            $studyPrograms = $prodiStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $allTemplates = $this->templateModel->allActive();
        $templatesByCategory = [];
        foreach ($allTemplates as $tpl) {
            if (isset($allowedCategoryMap[$tpl['category']])) {
                $templatesByCategory[$tpl['category']][] = $tpl;
            }
        }

        $activeBills = [];
        if ($pdo && in_array($role, ['admin', 'keuangan'])) {
            $billStmt = $pdo->query("SELECT b.id, b.invoice_number, b.title, b.amount, b.remaining_amount, b.due_date, b.status, s.full_name AS student_name, s.nim, s.phone 
                                     FROM bills b 
                                     JOIN students s ON b.student_id = s.id 
                                     WHERE b.status IN ('UNPAID', 'PARTIAL') 
                                     ORDER BY b.due_date ASC LIMIT 50");
            $activeBills = $billStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $fonnteConfig = WhatsAppService::getConfig();
        $modeInfo = WhatsAppService::getModeInfo();

        $this->render('notifications/send', [
            'title'               => 'Kirim Notifikasi WhatsApp - Smart Campus Cash',
            'user'                => AuthService::user(),
            'role'                => $role,
            'allowedCategories'   => $allowedCategoryMap,
            'students'            => $students,
            'studyPrograms'       => $studyPrograms,
            'templatesByCategory' => $templatesByCategory,
            'activeBills'         => $activeBills,
            'fonnteConfig'        => $fonnteConfig,
            'modeInfo'            => $modeInfo
        ]);
    }

    /**
     * Proses Pengiriman Notifikasi (Single / Bulk Broadcast)
     */
    public function processSend(): void
    {
        $role = $this->authorizeStaff();
        $allowedCategoryMap = $this->getAllowedCategoriesForRole($role);
        $allowedCategoryKeys = array_keys($allowedCategoryMap);

        $category = trim((string)$this->input('category', ''));
        $recipientType = trim((string)$this->input('recipient_scope', 'single'));
        $studentId = (int)$this->input('student_id', 0);
        $targetProdi = trim((string)$this->input('target_prodi', ''));
        $billId = (int)$this->input('bill_id', 0);
        $title = trim((string)$this->input('title', ''));
        $message = trim((string)$this->input('message', ''));
        $templateCode = trim((string)$this->input('template_code', ''));

        // 1. Validasi Kategori sesuai RBAC
        if (!in_array($category, $allowedCategoryKeys)) {
            $_SESSION['flash_error'] = "Role {$role} tidak memiliki hak akses untuk mengirim kategori notifikasi '{$category}'.";
            $this->redirect(url('notifications/send'));
        }

        // 2. Kasir dilarang mengirim broadcast massal
        if ($role === 'kasir' && $recipientType === 'bulk') {
            $_SESSION['flash_error'] = 'Kasir hanya berwenang mengirim notifikasi perorangan terkait transaksi loket.';
            $this->redirect(url('notifications/send'));
        }

        // 3. Validasi konten pesan
        if (empty($message)) {
            $_SESSION['flash_error'] = 'Isi pesan WhatsApp tidak boleh kosong.';
            $this->redirect(url('notifications/send'));
        }

        $currentUserId = AuthService::id();
        $pdo = Database::getConnection();

        // 4. Pengiriman Tunggal (Single Recipient)
        if ($recipientType === 'single') {
            if ($studentId <= 0) {
                $_SESSION['flash_error'] = 'Pilih mahasiswa penerima terlebih dahulu.';
                $this->redirect(url('notifications/send'));
            }

            $student = $this->studentModel->find($studentId);
            if (!$student) {
                $_SESSION['flash_error'] = 'Data mahasiswa penerima tidak ditemukan.';
                $this->redirect(url('notifications/send'));
            }

            $renderedMsg = WhatsAppTemplate::render($message, [
                'nama_mahasiswa' => $student['full_name'],
                'nim'            => $student['nim'] ?? '',
                'prodi'          => $student['study_program'] ?? ''
            ]);

            $formToken = trim((string)$this->input('form_token', ''));
            $idempotencyKey = !empty($formToken) 
                ? "manual_{$currentUserId}_{$studentId}_{$formToken}" 
                : "manual_{$currentUserId}_{$studentId}_" . md5($category . $title . $renderedMsg . date('YmdHi'));

            $result = WhatsAppService::sendAndRecord([
                'recipient_phone' => $student['phone'],
                'recipient_name'  => $student['full_name'],
                'recipient_type'  => 'student',
                'student_id'      => $student['id'],
                'user_id'         => $currentUserId,
                'bill_id'         => $billId > 0 ? $billId : null,
                'category'        => $category,
                'template_code'   => $templateCode ?: null,
                'title'           => $title ?: 'Pemberitahuan Smart Campus Cash',
                'message'         => $renderedMsg,
                'idempotency_key' => $idempotencyKey
            ]);

            AuditLogger::log('WHATSAPP_SEND_SINGLE', 'whatsapp', $result['notification_id'] ?? null, null, [
                'student_id' => $student['id'],
                'category'   => $category,
                'status'     => $result['status'] ?? 'unknown',
                'provider'   => 'FONNTE'
            ]);

            if (!empty($result['already_sent'])) {
                $_SESSION['flash_warning'] = 'Permintaan pengiriman terdeteksi ganda (idempotent). Notifikasi sudah pernah dikirim sebelumnya.';
            } elseif ($result['status'] === 'sent') {
                $_SESSION['flash_success'] = "Pesan WhatsApp berhasil dikirim ke antrean Fonnte untuk {$student['full_name']} ({$student['nim']}).";
            } elseif ($result['status'] === 'skipped') {
                $_SESSION['flash_warning'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = "Pengiriman gagal: " . ($result['message'] ?? 'Kesalahan API Fonnte.');
            }

            $this->redirect(url('notifications'));
        }

        // 5. Pengiriman Massal (Bulk Broadcast - Admin & Keuangan Only)
        if ($recipientType === 'bulk') {
            $studentQuery = "SELECT id, nim, full_name, phone, study_program FROM students WHERE deleted_at IS NULL";
            $queryParams = [];

            if (!empty($targetProdi) && $targetProdi !== 'all') {
                $studentQuery .= " AND study_program = :prodi";
                $queryParams['prodi'] = $targetProdi;
            }

            $studentQuery .= " ORDER BY id ASC";
            $stmt = $pdo->prepare($studentQuery);
            $stmt->execute($queryParams);
            $targets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (empty($targets)) {
                $_SESSION['flash_error'] = 'Tidak ada mahasiswa yang memenuhi kriteria pengiriman massal terpilih.';
                $this->redirect(url('notifications/send'));
            }

            $batchId = 'batch_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $sentCount = 0;
            $skippedCount = 0;
            $failedCount = 0;

            foreach ($targets as $tgt) {
                $personalizedMsg = WhatsAppTemplate::render($message, [
                    'nama_mahasiswa' => $tgt['full_name'],
                    'nim'            => $tgt['nim'] ?? '',
                    'prodi'          => $tgt['study_program'] ?? ''
                ]);

                $idemKey = "bulk_{$batchId}_{$tgt['id']}";

                $res = WhatsAppService::sendAndRecord([
                    'recipient_phone' => $tgt['phone'],
                    'recipient_name'  => $tgt['full_name'],
                    'recipient_type'  => 'student',
                    'student_id'      => $tgt['id'],
                    'user_id'         => $currentUserId,
                    'category'        => $category,
                    'template_code'   => $templateCode ?: null,
                    'title'           => $title ?: 'Pengumuman Keuangan Kampus',
                    'message'         => $personalizedMsg,
                    'idempotency_key' => $idemKey
                ]);

                if ($res['status'] === 'sent') {
                    $sentCount++;
                } elseif ($res['status'] === 'skipped') {
                    $skippedCount++;
                } else {
                    $failedCount++;
                }
            }

            AuditLogger::log('WHATSAPP_SEND_BULK', 'whatsapp', null, null, [
                'batch_id'     => $batchId,
                'category'     => $category,
                'target_count' => count($targets),
                'sent'         => $sentCount,
                'skipped'      => $skippedCount,
                'failed'       => $failedCount,
                'provider'     => 'FONNTE'
            ]);

            $summaryMsg = "Pengiriman massal selesai diproses untuk " . count($targets) . " mahasiswa. "
                        . "({$sentCount} Terkirim ke antrean provider, {$skippedCount} Dilewati/Nomor kosong, {$failedCount} Gagal).";

            if ($sentCount > 0) {
                $_SESSION['flash_success'] = $summaryMsg;
            } else {
                $_SESSION['flash_warning'] = $summaryMsg;
            }

            $this->redirect(url('notifications'));
        }
    }

    /**
     * API Preview template via AJAX
     */
    public function preview(): void
    {
        $role = $this->authorizeStaff();
        $templateCode = trim((string)$this->input('template_code', ''));
        $studentId = (int)$this->input('student_id', 0);
        $billId = (int)$this->input('bill_id', 0);

        $template = $this->templateModel->findByCode($templateCode);
        if (!$template) {
            $this->json(['success' => false, 'message' => 'Template tidak ditemukan'], 404);
            return;
        }

        $student = $studentId > 0 ? $this->studentModel->find($studentId) : null;
        $bill = $billId > 0 ? $this->billModel->find($billId) : null;

        $variables = [
            'nama_mahasiswa'      => $student['full_name'] ?? '[Nama Mahasiswa]',
            'nim'                 => $student['nim'] ?? '[NIM]',
            'nama_tagihan'        => $bill['title'] ?? 'SPP Semester Gasal',
            'jenis_tagihan'       => $bill['title'] ?? 'SPP Semester Gasal',
            'nominal'             => !empty($bill['amount']) ? "Rp " . number_format($bill['amount'], 0, ',', '.') : 'Rp 1.500.000',
            'sisa_tagihan'        => !empty($bill['remaining_amount']) ? "Rp " . number_format($bill['remaining_amount'], 0, ',', '.') : 'Rp 1.500.000',
            'tanggal_jatuh_tempo' => !empty($bill['due_date']) ? date('d F Y', strtotime($bill['due_date'])) : date('d F Y', strtotime('+7 days')),
            'nominal_pembayaran'  => 'Rp 1.500.000',
            'tanggal_pembayaran'  => date('d F Y H:i'),
            'status_pembayaran'   => 'LUNAS',
            'nama_petugas'        => AuthService::user()['full_name'] ?? 'Petugas Loket',
            'tanggal'             => date('d F Y'),
            'jumlah_transaksi'    => '15 Transaksi',
            'total_nominal'       => 'Rp 22.500.000',
            'isi_pengumuman'      => 'Diumumkan batas akhir pembayaran perkuliahan Semester Gasal adalah 30 November 2026.'
        ];

        $rendered = WhatsAppTemplate::render($template['body'], $variables);

        $this->json([
            'success'      => true,
            'template'     => $template,
            'preview_text' => $rendered
        ]);
    }

    /**
     * Detail notifikasi via AJAX
     */
    public function detail(): void
    {
        $role = $this->authorizeStaff();
        $id = (int)$this->input('id', 0);

        $notif = $this->notifModel->findWithDetails($id);
        if (!$notif) {
            $this->json(['success' => false, 'message' => 'Data notifikasi tidak ditemukan.'], 404);
            return;
        }

        if ($role === 'kasir' && !in_array($notif['category'], ['payment_success', 'counter_recap'])) {
            $this->json(['success' => false, 'message' => 'Anda tidak berwenang melihat detail notifikasi ini.'], 403);
            return;
        }

        $this->json([
            'success' => true,
            'data'    => $notif
        ]);
    }

    /**
     * Kirim Ulang (Retry) Pesan WhatsApp Nyata
     */
    public function resend(): void
    {
        $role = $this->authorizeStaff();
        if ($role === 'kasir') {
            $_SESSION['flash_error'] = 'Kasir tidak memiliki izin untuk melakukan pengiriman ulang manual.';
            $this->redirect(url('notifications'));
        }

        $id = (int)$this->input('id', 0);
        $notif = $this->notifModel->find($id);

        if (!$notif) {
            $_SESSION['flash_error'] = 'Data notifikasi tidak ditemukan.';
            $this->redirect(url('notifications'));
        }

        // Kirim langsung ke Fonnte API secara real
        $res = WhatsAppService::sendMessage($notif['recipient_phone'], $notif['message']);

        $newStatus = $res['status'] ?? 'failed';
        $pMsgId = $res['provider_message_id'] ?? null;
        $errMsg = !$res['success'] ? ($res['message'] ?? 'Gagal kirim') : null;
        $raw = $res['response'] ?? null;

        $this->notifModel->updateStatus($id, $newStatus, $pMsgId, $errMsg, $raw);

        AuditLogger::log('WHATSAPP_RESEND', 'whatsapp', $id, null, [
            'new_status' => $newStatus,
            'success'    => $res['success'] ?? false,
            'provider'   => 'FONNTE'
        ]);

        if ($res['status'] === 'sent') {
            $_SESSION['flash_success'] = "Pesan berhasil dikirim ulang ke antrean Fonnte untuk {$notif['recipient_name']}.";
        } elseif ($res['status'] === 'skipped') {
            $_SESSION['flash_warning'] = "Pengiriman dilewati: " . ($res['message'] ?? 'Konfigurasi belum siap.');
        } else {
            $_SESSION['flash_error'] = "Gagal mengirim ulang: " . ($res['message'] ?? 'Koneksi Fonnte API gagal.');
        }

        $this->redirect(url('notifications'));
    }

    /**
     * Kelola Template Pesan WhatsApp (Khusus Admin)
     */
    public function templates(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            http_response_code(403);
            $this->render('errors/403', [
                'title'        => '403 - Akses Ditolak',
                'userRole'     => $role,
                'allowedRoles' => ['admin'],
                'attemptedUrl' => $_SERVER['REQUEST_URI'] ?? ''
            ], 'layouts/guest');
            exit;
        }

        $templates = $this->templateModel->all();

        $this->render('notifications/templates', [
            'title'     => 'Kelola Template WhatsApp - Smart Campus Cash',
            'user'      => AuthService::user(),
            'role'      => $role,
            'templates' => $templates
        ]);
    }

    /**
     * Update Konten Template (Khusus Admin)
     */
    public function updateTemplate(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            $_SESSION['flash_error'] = 'Hanya Administrator yang berwenang mengubah template pesan.';
            $this->redirect(url('notifications'));
        }

        $id = (int)$this->input('id', 0);
        $name = trim((string)$this->input('name', ''));
        $subject = trim((string)$this->input('subject', ''));
        $body = trim((string)$this->input('body', ''));
        $description = trim((string)$this->input('description', ''));
        $isActive = (int)$this->input('is_active', 1);

        if (empty($name) || empty($body)) {
            $_SESSION['flash_error'] = 'Nama dan Isi template wajib diisi.';
            $this->redirect(url('notifications/templates'));
        }

        $updated = $this->templateModel->updateTemplate($id, [
            'name'        => $name,
            'subject'     => $subject,
            'body'        => $body,
            'description' => $description,
            'is_active'   => $isActive
        ]);

        if ($updated) {
            AuditLogger::log('WHATSAPP_TEMPLATE_UPDATED', 'whatsapp', $id, null, ['name' => $name]);
            $_SESSION['flash_success'] = "Template '{$name}' berhasil diperbarui.";
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan pembaruan template.';
        }

        $this->redirect(url('notifications/templates'));
    }

    /**
     * Pengaturan WhatsApp Fonnte API & Scheduler (Khusus Admin)
     */
    public function settings(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            http_response_code(403);
            $this->render('errors/403', [
                'title'        => '403 - Akses Ditolak',
                'userRole'     => $role,
                'allowedRoles' => ['admin'],
                'attemptedUrl' => $_SERVER['REQUEST_URI'] ?? ''
            ], 'layouts/guest');
            exit;
        }

        $settings = WhatsAppSetting::allKeyValues();
        $fonnteConfig = WhatsAppService::getConfig();
        $modeInfo = WhatsAppService::getModeInfo();

        // Cek status perangkat secara real jika token tersedia
        $deviceStatus = WhatsAppService::checkDeviceStatus();

        $this->render('notifications/settings', [
            'title'        => 'Pengaturan Integrasi WhatsApp Fonnte - Smart Campus Cash',
            'user'         => AuthService::user(),
            'role'         => $role,
            'settings'     => $settings,
            'fonnte'       => $fonnteConfig,
            'deviceStatus' => $deviceStatus,
            'modeInfo'     => $modeInfo
        ]);
    }

    /**
     * Simpan Pengaturan Scheduler (Khusus Admin)
     */
    public function updateSettings(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            $_SESSION['flash_error'] = 'Hanya Administrator yang berwenang mengubah konfigurasi sistem.';
            $this->redirect(url('notifications'));
        }

        $dueDays       = max(1, (int)$this->input('due_reminder_days', 3));
        $cooldownDays  = max(1, (int)$this->input('overdue_cooldown_days', 7));
        $autoReminders = (int)$this->input('auto_reminders_enabled', 0);
        $maskPhone     = (int)$this->input('mask_phone_in_logs', 1);

        WhatsAppSetting::set('due_reminder_days',      $dueDays,       'Jumlah hari sebelum jatuh tempo untuk mengirim pengingat otomatis');
        WhatsAppSetting::set('overdue_cooldown_days',  $cooldownDays,  'Jeda hari minimum pengingat tunggakan agar tidak berulang spam');
        WhatsAppSetting::set('auto_reminders_enabled', $autoReminders, 'Aktifkan scheduler pengingat otomatis (1 = Ya, 0 = Tidak)');
        WhatsAppSetting::set('mask_phone_in_logs',     $maskPhone,     'Sensor nomor telepon pada riwayat tampilan UI');

        AuditLogger::log('WHATSAPP_SETTINGS_UPDATED', 'whatsapp', null, null, [
            'due_reminder_days'      => $dueDays,
            'overdue_cooldown_days'  => $cooldownDays,
            'auto_reminders_enabled' => $autoReminders,
            'mask_phone_in_logs'     => $maskPhone
        ]);

        $_SESSION['flash_success'] = 'Konfigurasi scheduler pengingat berhasil disimpan.';
        $this->redirect(url('notifications/settings'));
    }

    /**
     * Kirim Pesan Uji Coba WhatsApp Sungguhan (Admin Only)
     */
    public function testSend(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            $_SESSION['flash_error'] = 'Hanya Administrator yang berwenang melakukan uji kirim pesan WhatsApp.';
            $this->redirect(url('notifications/settings'));
        }

        $targetPhone = trim((string)$this->input('target_phone', ''));
        $testMessage = trim((string)$this->input('test_message', ''));

        if (empty($targetPhone)) {
            $_SESSION['flash_error'] = 'Nomor WhatsApp tujuan uji coba wajib diisi.';
            $this->redirect(url('notifications/settings'));
        }

        $currentUserId = AuthService::id();

        $result = WhatsAppService::sendTestMessage($targetPhone, $testMessage, $currentUserId);

        AuditLogger::log('WHATSAPP_TEST_SEND', 'whatsapp', $result['notification_id'] ?? null, null, [
            'target_phone' => WhatsAppNotification::maskPhone($targetPhone),
            'status'       => $result['status'] ?? 'unknown',
            'success'      => $result['success'] ?? false
        ]);

        if (($result['status'] ?? '') === 'sent') {
            $msgId = $result['provider_message_id'] ? " (ID Provider: {$result['provider_message_id']})" : '';
            $_SESSION['flash_success'] = "Pesan uji berhasil dikirim ke server Fonnte!{$msgId} Periksa WhatsApp penerima Anda.";
        } elseif (($result['status'] ?? '') === 'skipped') {
            $_SESSION['flash_warning'] = "Uji pengiriman ditolak: " . $result['message'];
        } else {
            $_SESSION['flash_error'] = "Uji pengiriman gagal: " . ($result['message'] ?? 'Fonnte API menolak permintaan.');
        }

        $this->redirect(url('notifications/settings'));
    }

    /**
     * Cek status perangkat Fonnte secara live via AJAX (Admin Only)
     */
    public function deviceStatus(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $status = WhatsAppService::checkDeviceStatus();
        $this->json($status);
    }

    /**
     * Manual Trigger Scheduler Pengingat (Admin Only)
     */
    public function triggerScheduler(): void
    {
        $role = $this->authorizeStaff();
        if ($role !== 'admin') {
            $_SESSION['flash_error'] = 'Hanya Administrator yang berwenang memicu scheduler manual.';
            $this->redirect(url('notifications'));
        }

        $result = WhatsAppService::runAutomatedReminders();

        if ($result['status'] === 'unconfigured') {
            $_SESSION['flash_warning'] = $result['message'];
        } else {
            $_SESSION['flash_success'] = "Hasil Scheduler: {$result['message']}";
        }

        $this->redirect(url('notifications'));
    }

    /**
     * Endpoint Webhook Fonnte WhatsApp API (Publik)
     * Menerima event pembaruan status pesan (sent, delivered, read, failed)
     */
    public function webhook(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Healthcheck endpoint
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'ACTIVE',
                'service' => 'Smart Campus Cash - Fonnte WhatsApp Webhook Listener',
                'time'    => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true);

            // Jika payload form-data (bukan JSON)
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }

            if (empty($data)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Payload webhook kosong atau tidak valid']);
                exit;
            }

            $res = WhatsAppService::handleWebhook($data);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'EVENT_RECEIVED', 'details' => $res]);
            exit;
        }
    }
}
