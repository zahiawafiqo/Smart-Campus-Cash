<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuditLogger;
use PDO;
use Exception;

class CashTransaction extends Model
{
    protected string $table = 'cash_transactions';

    public function generateTransactionNumber(string $type = 'income'): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "TRX/{$year}/{$month}/";

        if (!$this->db) {
            return $prefix . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $stmt = $this->db->prepare("SELECT transaction_number FROM cash_transactions WHERE transaction_number LIKE :p ORDER BY id DESC LIMIT 1");
        $stmt->execute(['p' => "{$prefix}%"]);
        $lastNum = $stmt->fetchColumn();

        if ($lastNum) {
            $parts = explode('/', $lastNum);
            $next = (int)end($parts) + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function getBalanceSummary(?string $startDate = null, ?string $endDate = null): array
    {
        if (!$this->db) {
            return ['total_income' => 0.0, 'total_expense' => 0.0, 'balance' => 0.0];
        }

        $where = "WHERE status != 'voided'";
        $params = [];

        if ($startDate && $endDate) {
            $where .= " AND DATE(transaction_date) BETWEEN :start AND :end";
            $params['start'] = $startDate;
            $params['end'] = $endDate;
        }

        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
                FROM cash_transactions
                {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $income = (float)($res['total_income'] ?? 0);
        $expense = (float)($res['total_expense'] ?? 0);
        $balance = $income - $expense;

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $balance
        ];
    }

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(ct.transaction_number LIKE :q OR ct.description LIKE :q2 OR ct.reference_number LIKE :q3)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['type']) && in_array(strtolower($filters['type']), ['income', 'expense'])) {
            $conditions[] = "ct.type = :type";
            $params['type'] = strtolower($filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = "ct.category_id = :cid";
            $params['cid'] = (int)$filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "ct.status = :st";
            $params['st'] = $filters['status'];
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $conditions[] = "DATE(ct.transaction_date) BETWEEN :sdate AND :edate";
            $params['sdate'] = $filters['start_date'];
            $params['edate'] = $filters['end_date'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM cash_transactions ct WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Records
        $sql = "SELECT ct.*, c.name AS category_name, c.code AS category_code, u.full_name AS creator_name,
                       p.payment_number, s.nim, s.full_name AS student_name
                FROM cash_transactions ct
                JOIN categories c ON ct.category_id = c.id
                JOIN users u ON ct.created_by = u.id
                LEFT JOIN payments p ON ct.reference_number = p.payment_number
                LEFT JOIN students s ON p.student_id = s.id
                WHERE {$whereClause}
                ORDER BY ct.transaction_date DESC, ct.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $perPage
        ];
    }

    /**
     * Ambil transaksi buku kas terbaru
     */
    public function getRecentTransactions(int $limit = 10): array
    {
        $result = $this->paginate(1, $limit);
        return $result['data'] ?? [];
    }

    public function create(array $data): ?int
    {
        if (!$this->db) return null;

        $amount = (float)$data['amount'];
        if ($amount <= 0) return null;

        $type = in_array(strtolower($data['type']), ['income', 'expense']) ? strtolower($data['type']) : 'income';
        $tnum = !empty($data['transaction_number']) ? trim($data['transaction_number']) : $this->generateTransactionNumber($type);
        $date = !empty($data['transaction_date']) ? $data['transaction_date'] : date('Y-m-d H:i:s');
        $method = !empty($data['payment_method']) ? strtolower($data['payment_method']) : 'cash';

        $sql = "INSERT INTO cash_transactions (transaction_number, category_id, type, amount, transaction_date, description, reference_number, payment_method, status, created_by)
                VALUES (:tnum, :cid, :type, :amt, :tdate, :desc, :ref, :method, 'completed', :uid)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tnum' => $tnum,
            'cid' => (int)$data['category_id'],
            'type' => $type,
            'amt' => $amount,
            'tdate' => $date,
            'desc' => trim($data['description']),
            'ref' => trim($data['reference_number'] ?? ''),
            'method' => in_array($method, ['cash', 'qris', 'bank_transfer', 'edc']) ? $method : 'cash',
            'uid' => (int)$data['created_by']
        ]);

        $id = (int)$this->db->lastInsertId();

        AuditLogger::log($type === 'income' ? 'CREATE_INCOME' : 'CREATE_EXPENSE', 'cash_transactions', $id, null, [
            'transaction_number' => $tnum,
            'type' => $type,
            'amount' => $amount,
            'category_id' => $data['category_id'],
            'description' => $data['description']
        ]);

        return $id;
    }

    public function findWithDetails(int $id): ?array
    {
        if (!$this->db) return null;

        $sql = "SELECT ct.*, c.name AS category_name, c.code AS category_code, u.full_name AS creator_name, u.username AS creator_username,
                       p.payment_number, s.nim, s.full_name AS student_name
                FROM cash_transactions ct
                JOIN categories c ON ct.category_id = c.id
                JOIN users u ON ct.created_by = u.id
                LEFT JOIN payments p ON ct.reference_number = p.payment_number
                LEFT JOIN students s ON p.student_id = s.id
                WHERE ct.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getMonthlyChartData(int $months = 6): array
    {
        if (!$this->db) {
            return ['labels' => [], 'income' => [], 'expense' => []];
        }

        $sql = "SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
                    DATE_FORMAT(transaction_date, '%b %Y') AS month_label,
                    SUM(CASE WHEN type = 'income' AND status != 'voided' THEN amount ELSE 0 END) AS monthly_income,
                    SUM(CASE WHEN type = 'expense' AND status != 'voided' THEN amount ELSE 0 END) AS monthly_expense
                FROM cash_transactions
                WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY ym, month_label
                ORDER BY ym ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['months' => $months]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $income = [];
        $expense = [];

        foreach ($rows as $r) {
            $labels[] = $r['month_label'];
            $income[] = (float)$r['monthly_income'];
            $expense[] = (float)$r['monthly_expense'];
        }

        // Fallback jika database baru dan data sedikit
        if (empty($labels)) {
            $labels = [date('M Y')];
            $summary = $this->getBalanceSummary();
            $income = [$summary['total_income']];
            $expense = [$summary['total_expense']];
        }

        return [
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense
        ];
    }
}
