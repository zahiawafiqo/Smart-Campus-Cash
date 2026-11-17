<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MidtransService;

class MidtransController extends Controller
{
    /**
     * Webhook Notification Handler (Public endpoint called by Midtrans)
     */
    public function notification(): void
    {
        $rawPayload = file_get_contents('php://input');
        $payload = json_decode($rawPayload, true);

        if (!$payload && !empty($_POST)) {
            $payload = $_POST;
        }

        if (empty($payload)) {
            $this->json([
                'status' => 'error',
                'message' => 'Empty notification payload'
            ], 400);
            return;
        }

        $result = MidtransService::handleNotification($payload);

        $httpCode = $result['http_code'] ?? ($result['success'] ? 200 : 400);
        $this->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'already_processed' => $result['already_processed'] ?? false
        ], $httpCode);
    }

    /**
     * Halaman Redirect setelah Mahasiswa menyelesaikan pembayaran Snap
     */
    public function finish(): void
    {
        $orderId = trim((string)$this->input('order_id', ''));
        $statusCode = trim((string)$this->input('status_code', ''));
        $transactionStatus = trim((string)$this->input('transaction_status', ''));

        $_SESSION['flash_success'] = 'Pembayaran online Anda sedang diverifikasi. Silakan cek status tagihan secara berkala.';
        $this->redirect(url('mahasiswa/tagihan'));
    }

    public function unfinish(): void
    {
        $_SESSION['flash_warning'] = 'Proses pembayaran belum diselesaikan. Anda dapat mencoba kembali kapan saja.';
        $this->redirect(url('mahasiswa/tagihan'));
    }

    public function error(): void
    {
        $_SESSION['flash_error'] = 'Terjadi kendala saat memproses pembayaran online. Silakan hubungi loket kasir jika saldo terpotong.';
        $this->redirect(url('mahasiswa/tagihan'));
    }
}
