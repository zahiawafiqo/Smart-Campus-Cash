<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\AiAssistantService;

class AiAssistantController extends Controller
{
    public function index(): void
    {
        $role = AuthService::role() ?? 'mahasiswa';
        $user = AuthService::user();

        // Prompt suggestions per role
        $suggestions = match ($role) {
            'mahasiswa' => [
                'Berapa sisa tagihan saya saat ini?',
                'Kapan tagihan terdekat saya jatuh tempo?',
                'Berapa total tagihan yang sudah saya bayar?'
            ],
            'kasir' => [
                'Berapa total pembayaran hari ini?',
                'Berapa jumlah transaksi yang diproses loket kasir hari ini?',
                'Transaksi terbaru.'
            ],
            'keuangan', 'admin' => [
                'Total pemasukan bulan ini.',
                'Total pengeluaran bulan ini.',
                'Saldo saat ini.',
                'Pengeluaran terbesar bulan ini.',
                'Pengeluaran berdasarkan kategori.',
                'Perbandingan pemasukan dan pengeluaran.',
                'Tagihan yang belum lunas.',
                'Transaksi terbaru.'
            ],
            default => [
                'Saldo saat ini.',
                'Total pemasukan bulan ini.',
                'Transaksi terbaru.'
            ]
        };

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $history = $_SESSION['ai_chat_history'] ?? [];

        $this->render('ai/index', [
            'title' => 'AI Financial Assistant - Smart Campus Cash',
            'user' => $user,
            'role' => $role,
            'suggestions' => $suggestions,
            'history' => $history
        ], 'layouts/app');
    }

    public function chat(): void
    {
        $prompt = trim((string)$this->input('prompt', ''));

        if (empty($prompt)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Pertanyaan tidak boleh kosong.']);
            exit;
        }

        $role = AuthService::role() ?? 'mahasiswa';
        $userId = AuthService::id() ?? 0;
        $user = AuthService::user();
        $userName = $user['full_name'] ?? null;

        $response = AiAssistantService::ask($prompt, $role, $userId, $userName);

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION['ai_chat_history']) || !is_array($_SESSION['ai_chat_history'])) {
            $_SESSION['ai_chat_history'] = [];
        }

        // Keep last 20 messages in session
        $_SESSION['ai_chat_history'][] = [
            'prompt' => $prompt,
            'response' => $response['response'],
            'time' => $response['timestamp']
        ];

        if (count($_SESSION['ai_chat_history']) > 20) {
            array_shift($_SESSION['ai_chat_history']);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'prompt' => $prompt,
            'response' => $response['response'],
            'timestamp' => $response['timestamp']
        ]);
        exit;
    }

    public function clearHistory(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION['ai_chat_history'] = [];
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Riwayat percakapan berhasil dibersihkan.']);
        exit;
    }
}
