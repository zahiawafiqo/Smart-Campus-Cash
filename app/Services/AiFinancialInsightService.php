<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AiFinancialInsightService
{
    /**
     * Hasilkan interpretasi analisis AI Financial Insight untuk Dashboard Keuangan
     */
    public static function generateInsight(bool $forceRefresh = false): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Cek cache sesi jika tidak dipaksa refresh
        if (!$forceRefresh && !empty($_SESSION['cached_ai_insight'])) {
            return $_SESSION['cached_ai_insight'];
        }

        $pdo = Database::getConnection();
        if (!$pdo) {
            return self::emptyInsight('Koneksi database tidak tersedia.');
        }

        // 1. Cek jumlah data transaksi keseluruhan
        $totalTxCount = (int)$pdo->query("SELECT COUNT(*) FROM cash_transactions WHERE status != 'voided'")->fetchColumn();
        if ($totalTxCount < 2) {
            $empty = self::emptyInsight('Insight belum tersedia karena data transaksi pada periode ini belum mencukupi untuk analisis tren statistik.');
            $_SESSION['cached_ai_insight'] = $empty;
            return $empty;
        }

        // 2. Agregasi Bulan Berjalan (M1) vs Bulan Sebelumnya (M0)
        $m1Income = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();
        $m1Expense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetchColumn();

        $m0Income = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND status != 'voided' AND MONTH(transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
        $m0Expense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND status != 'voided' AND MONTH(transaction_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();

        // Hitung % Perubahan
        $incomeDiffPct = ($m0Income > 0) ? round((($m1Income - $m0Income) / $m0Income) * 100, 1) : 0.0;
        $expenseDiffPct = ($m0Expense > 0) ? round((($m1Expense - $m0Expense) / $m0Expense) * 100, 1) : 0.0;

        // 3. Kategori Pengeluaran Terbesar Bulan Ini
        $catStmt = $pdo->query("SELECT c.name, SUM(ct.amount) AS total_spent 
                               FROM cash_transactions ct 
                               JOIN categories c ON ct.category_id = c.id 
                               WHERE ct.type = 'expense' AND ct.status != 'voided' AND MONTH(ct.transaction_date) = MONTH(CURDATE()) AND YEAR(ct.transaction_date) = YEAR(CURDATE())
                               GROUP BY c.id, c.name 
                               ORDER BY total_spent DESC 
                               LIMIT 1");
        $topCat = $catStmt->fetch(PDO::FETCH_ASSOC);

        // 4. Transaksi Signifikan (Pengeluaran terbesar di bulan berjalan)
        $sigStmt = $pdo->query("SELECT ct.*, c.name AS category_name 
                               FROM cash_transactions ct 
                               JOIN categories c ON ct.category_id = c.id 
                               WHERE ct.type = 'expense' AND ct.status != 'voided' AND MONTH(ct.transaction_date) = MONTH(CURDATE()) AND YEAR(ct.transaction_date) = YEAR(CURDATE())
                               ORDER BY ct.amount DESC 
                               LIMIT 1");
        $sigTx = $sigStmt->fetch(PDO::FETCH_ASSOC);

        // 5. Deteksi Transaksi Tidak Biasa (Outlier Detection: transaksi > 2.5x rata-rata transaksi)
        $avgAmount = (float)$pdo->query("SELECT COALESCE(AVG(amount), 0) FROM cash_transactions WHERE status != 'voided'")->fetchColumn();
        $outlierStmt = $pdo->prepare("SELECT ct.*, c.name AS category_name 
                                     FROM cash_transactions ct 
                                     JOIN categories c ON ct.category_id = c.id 
                                     WHERE ct.status != 'voided' AND ct.amount > :thresh AND MONTH(ct.transaction_date) = MONTH(CURDATE())
                                     ORDER BY ct.amount DESC 
                                     LIMIT 1");
        $outlierStmt->execute(['thresh' => $avgAmount * 2.5]);
        $outlierTx = $outlierStmt->fetch(PDO::FETCH_ASSOC);

        $m1Name = date('F Y');
        $m0Name = date('F Y', strtotime('-1 month'));

        // Susun Ringkasan Eksekutif (Summary)
        $summaryParts = [];
        if ($expenseDiffPct > 0) {
            $summaryParts[] = "Pengeluaran bulan {$m1Name} meningkat {$expenseDiffPct}% dibandingkan {$m0Name}.";
        } elseif ($expenseDiffPct < 0) {
            $summaryParts[] = "Pengeluaran bulan {$m1Name} berhasil ditekan sebesar " . abs($expenseDiffPct) . "% dibandingkan {$m0Name}.";
        } else {
            $summaryParts[] = "Tingkat pengeluaran bulan {$m1Name} terpantau stabil dibandingkan {$m0Name}.";
        }

        if ($topCat) {
            $summaryParts[] = "Kategori **{$topCat['name']}** (Rp " . number_format($topCat['total_spent'], 0, ',', '.') . ") menjadi penyerap pengeluaran terbesar.";
        }
        $summaryText = implode(" ", $summaryParts);

        // Susun 6 Poin Detail Analisis
        $details = [
            [
                'title' => 'Tren Pemasukan',
                'icon' => ($incomeDiffPct >= 0) ? 'bi-arrow-up-right-circle text-success' : 'bi-arrow-down-right-circle text-warning',
                'text' => ($incomeDiffPct >= 0) 
                    ? "Arus kas masuk bulan {$m1Name} (Rp " . number_format($m1Income, 0, ',', '.') . ") mengalami tren kenaikan {$incomeDiffPct}% dari bulan sebelumnya."
                    : "Pemasukan bulan {$m1Name} (Rp " . number_format($m1Income, 0, ',', '.') . ") lebih rendah " . abs($incomeDiffPct) . "% dari {$m0Name}."
            ],
            [
                'title' => 'Tren Pengeluaran',
                'icon' => ($expenseDiffPct <= 0) ? 'bi-shield-check text-success' : 'bi-exclamation-circle text-danger',
                'text' => ($expenseDiffPct > 0)
                    ? "Realisasi belanja kas keluar mencapai Rp " . number_format($m1Expense, 0, ',', '.') . " (naik {$expenseDiffPct}%)."
                    : "Realisasi belanja kas keluar terkendali di angka Rp " . number_format($m1Expense, 0, ',', '.') . "."
            ],
            [
                'title' => 'Perbandingan Periode',
                'icon' => 'bi-calendar3-range text-primary',
                'text' => "Net cashflow bulan {$m1Name} mencatatkan surplus/defisit sebesar Rp " . number_format($m1Income - $m1Expense, 0, ',', '.') . " dibandingkan periode {$m0Name} (Rp " . number_format($m0Income - $m0Expense, 0, ',', '.') . ")."
            ],
            [
                'title' => 'Kategori Pengeluaran Terbesar',
                'icon' => 'bi-pie-chart text-info',
                'text' => $topCat 
                    ? "Pos '{$topCat['name']}' menyerap anggaran sebesar Rp " . number_format($topCat['total_spent'], 0, ',', '.') . " pada bulan berjalan."
                    : "Belum tercatat transaksi pengeluaran pada pos anggaran bulan ini."
            ],
            [
                'title' => 'Perubahan Transaksi Signifikan',
                'icon' => 'bi-lightning-charge text-warning',
                'text' => $sigTx
                    ? "Transaksi tunggal terbesar dicatat pada {$sigTx['transaction_number']} ({$sigTx['category_name']}) senilai Rp " . number_format($sigTx['amount'], 0, ',', '.') . "."
                    : "Tidak ada transaksi bernilai ekstrim yang mempengaruhi saldo kas secara drastis."
            ],
            [
                'title' => 'Pola Transaksi & Anomali',
                'icon' => $outlierTx ? 'bi-shield-exclamation text-warning' : 'bi-check2-all text-success',
                'text' => $outlierTx
                    ? "Perhatian: Transaksi {$outlierTx['transaction_number']} (Rp " . number_format($outlierTx['amount'], 0, ',', '.') . ") bernilai 2.5x di atas rata-rata transaksi harian."
                    : "Pola transaksi kas berjalan dalam batas deviasi normal dan tidak ditemukan anomali mencurigakan."
            ]
        ];

        $result = [
            'available' => true,
            'period' => "{$m1Name} vs {$m0Name}",
            'summary' => $summaryText,
            'details' => $details,
            'timestamp' => date('d M Y, H:i') . ' WIB'
        ];

        $_SESSION['cached_ai_insight'] = $result;
        return $result;
    }

    protected static function emptyInsight(string $message): array
    {
        return [
            'available' => false,
            'period' => date('F Y'),
            'summary' => $message,
            'details' => [],
            'timestamp' => date('d M Y, H:i') . ' WIB'
        ];
    }
}
