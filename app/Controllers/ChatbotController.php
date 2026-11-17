<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AiAssistantService;

class ChatbotController extends Controller
{
    /**
     * Check chatbot status
     */
    public function status(): void
    {
        $nodeUrl = env('NODE_CHATBOT_URL', 'http://localhost:3001');
        $isOnline = false;
        $details = [];

        // Check if Node.js server is alive
        try {
            $ch = curl_init("{$nodeUrl}/api/status");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $isOnline = true;
                $details = json_decode($res, true) ?: [];
            }
        } catch (\Exception $e) {
            $isOnline = false;
        }

        // Periksa koneksi aktual ke database MySQL Smart Campus Cash
        $dbStatus = \App\Core\Database::checkConnection();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'database' => [
                'status' => $dbStatus['status'] ? 'connected' : 'disconnected',
                'message' => $dbStatus['message']
            ],
            'node_server' => $isOnline ? 'online' : 'offline',
            'node_url' => $nodeUrl,
            'ai_provider' => env('AI_PROVIDER', 'nvidia'),
            'nvidia_key_id' => env('NVIDIA_KEY_ID', '2cbc5b5a-b68d-4bd0-be93-91fc2736b703'),
            'nvidia_model' => env('NVIDIA_MODEL', 'meta/llama-3.2-11b-vision-instruct'),
            'details' => $details
        ]);
        exit;
    }

    /**
     * Forward chat prompt to Node.js chatbot service or NVIDIA API
     */
    /**
     * Process chat prompt using AiAssistantService with authenticated user & role context
     */
    public function chat(): void
    {
        $message = trim((string)$this->input('message', ''));
        if (empty($message)) {
            $message = trim((string)$this->input('prompt', ''));
        }

        if (empty($message)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Pesan pertanyaan tidak boleh kosong.'
            ]);
            exit;
        }

        // Ambil konteks otentikasi session pengguna yang sedang login
        \App\Services\AuthService::initSession();
        $role = \App\Services\AuthService::role() ?? 'guest';
        $userId = \App\Services\AuthService::id() ?? 0;
        $user = \App\Services\AuthService::user();
        $userName = $user['full_name'] ?? null;

        // Proses pertanyaan melalui AiAssistantService terpusat (RBAC, identitas, job desk, statistik aktual)
        $aiResult = AiAssistantService::ask($message, $role, $userId, $userName);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'reply' => $aiResult['response'],
            'engine' => 'smart-campus-ai',
            'model' => env('NVIDIA_MODEL', 'meta/llama-3.2-11b-vision-instruct'),
            'keyId' => env('NVIDIA_KEY_ID', '2cbc5b5a-b68d-4bd0-be93-91fc2736b703'),
            'timestamp' => date('H:i') . ' WIB'
        ]);
        exit;
    }
}
