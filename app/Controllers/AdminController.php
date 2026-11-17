<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\AuditLogger;
use PDO;

class AdminController extends Controller
{
    /**
     * Dashboard Administrator
     */
    public function dashboard(): void
    {
        $pdo = Database::getConnection();

        // 1. Ringkasan 5 Statistik Kunci
        $stats = [
            'total_students' => 0,
            'total_users' => 0,
            'total_transactions' => 0,
            'total_income' => 0.0,
            'total_expense' => 0.0
        ];

        if ($pdo) {
            $stats['total_students'] = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE deleted_at IS NULL")->fetchColumn();
            $stats['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['total_transactions'] = (int)$pdo->query("SELECT COUNT(*) FROM cash_transactions WHERE status != 'voided'")->fetchColumn();
            $stats['total_income'] = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided'")->fetchColumn();
            $stats['total_expense'] = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided'")->fetchColumn();
        }

        // 2. Data Pengguna Terbaru
        $users = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT u.id, u.username, u.full_name, u.email, u.status, u.last_login_at, r.display_name AS role_name 
                                FROM users u 
                                JOIN roles r ON u.role_id = r.id 
                                ORDER BY u.id ASC LIMIT 8");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3. Audit Log Terbaru
        $auditLogs = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT a.*, u.username, u.full_name 
                                FROM audit_logs a 
                                LEFT JOIN users u ON a.user_id = u.id 
                                ORDER BY a.id DESC LIMIT 6");
            $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('admin/dashboard', [
            'title' => 'Dashboard Administrator - Smart Campus Cash',
            'user' => AuthService::user(),
            'stats' => $stats,
            'users' => $users,
            'auditLogs' => $auditLogs
        ], 'layouts/app');
    }

    /**
     * Halaman Konfigurasi Sistem
     */
    public function settings(): void
    {
        $this->render('admin/settings', [
            'title' => 'Pengaturan Sistem - Smart Campus Cash',
            'user' => AuthService::user(),
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    /**
     * Halaman Log Audit Sistem
     */
    public function auditLogs(): void
    {
        $pdo = Database::getConnection();
        $page = (int)$this->input('page', 1);
        $perPage = 20;
        $search = trim((string)$this->input('search', ''));
        $action = trim((string)$this->input('action', ''));

        $conditions = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $conditions[] = "(a.action LIKE :q OR a.entity_type LIKE :q2 OR u.username LIKE :q3 OR a.ip_address LIKE :q4)";
            $params['q'] = "%" . $search . "%";
            $params['q2'] = "%" . $search . "%";
            $params['q3'] = "%" . $search . "%";
            $params['q4'] = "%" . $search . "%";
        }

        if (!empty($action)) {
            $conditions[] = "a.action = :act";
            $params['act'] = $action;
        }

        $where = implode(" AND ", $conditions);

        $total = 0;
        $logs = [];

        if ($pdo) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $totalPages = max(1, (int)ceil($total / $perPage));
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare("SELECT a.*, u.username, u.full_name 
                                   FROM audit_logs a 
                                   LEFT JOIN users u ON a.user_id = u.id 
                                   WHERE {$where} 
                                   ORDER BY a.id DESC 
                                   LIMIT {$perPage} OFFSET {$offset}");
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $totalPages = 1;
        }

        $this->render('admin/audit_logs', [
            'title' => 'Log Audit Aktivitas Sistem - Smart Campus Cash',
            'logs' => $logs,
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'selectedAction' => $action
        ], 'layouts/app');
    }
}
