<?php

namespace App\Services;

use App\Models\WhatsAppNotification;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppSetting;
use App\Core\Database;
use PDO;
use Exception;

class WhatsAppService
{
    /**
     * Dapatkan konfigurasi Fonnte API saat ini
     */
    public static function getConfig(): array
    {
        $token = trim((string)env('FONNTE_TOKEN', ''));
        $apiUrl = trim((string)env('FONNTE_API_URL', 'https://api.fonnte.com/send'));
        $deviceUrl = trim((string)env('FONNTE_DEVICE_URL', 'https://api.fonnte.com/device'));

        if (empty($apiUrl)) {
            $apiUrl = 'https://api.fonnte.com/send';
        }
        if (empty($deviceUrl)) {
            $deviceUrl = 'https://api.fonnte.com/device';
        }

        $maskedToken = !empty($token) 
            ? (substr($token, 0, 4) . '****************' . substr($token, -4))
            : 'Belum Dikonfigurasi di .env';

        return [
            'provider'       => 'Fonnte WhatsApp API',
            'token'          => $token,
            'token_masked'   => $maskedToken,
            'has_token'      => !empty($token),
            'api_url'        => $apiUrl,
            'device_url'     => $deviceUrl,
            'is_configured'  => !empty($token),
            'docs_url'       => 'https://docs.fonnte.com/'
        ];
    }

    /**
     * Periksa apakah integrasi Fonnte sudah dikonfigurasi (token tersedia)
     */
    public static function isConfigured(): bool
    {
        $token = trim((string)env('FONNTE_TOKEN', ''));
        return !empty($token);
    }

    /**
     * Alias status kesiapan integrasi live
     */
    public static function isLiveModeReady(): bool
    {
        return self::isConfigured();
    }

    /**
     * Mode simulasi ditiadakan (selalu false untuk menjamin pengiriman nyata)
     */
    public static function isSimulationMode(): bool
    {
        return false;
    }

    /**
     * Informasi status mode sistem untuk tampilan controller/view
     */
    public static function getModeInfo(): array
    {
        $cfg = self::getConfig();
        $missing = [];
        if (!$cfg['has_token']) {
            $missing[] = 'FONNTE_TOKEN belum diisi pada file .env';
        }

        return [
            'mode'          => 'REAL_FONNTE',
            'provider'      => 'Fonnte WhatsApp API',
            'is_simulation' => false,
            'has_token'     => $cfg['has_token'],
            'token_masked'  => $cfg['token_masked'],
            'api_url'       => $cfg['api_url'],
            'device_url'    => $cfg['device_url'],
            'live_ready'    => $cfg['has_token'],
            'missing'       => $missing,
        ];
    }

    /**
     * Normalisasi nomor telepon ke format internasional standar Indonesia (628xxxxxxxxxx)
     * Contoh:
     *   - 081234567890   -> 6281234567890
     *   - +6281234567890 -> 6281234567890
     *   - 0812-3456-7890 -> 6281234567890
     *   - 6208123456789  -> 628123456789
     *   - 81234567890    -> 6281234567890
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Hapus semua karakter selain angka
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return '';
        }

        // Tangani awalan nomor Indonesia
        if (str_starts_with($clean, '620')) {
            $clean = '62' . substr($clean, 3);
        } elseif (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }

        // Nomor WhatsApp Indonesia valid biasanya antara 9 s/d 16 digit
        if (strlen($clean) < 9 || strlen($clean) > 16) {
            return '';
        }

        return $clean;
    }

    /**
     * Sensor informasi sensitif seperti token dari string log atau pesan error
     */
    public static function sanitizeErrorMessage(string $message): string
    {
        $sanitized = preg_replace('/(Authorization:\s*(Bearer\s*)?|Bearer\s+|token\s+)[a-zA-Z0-9_\-\@]+/i', '$1[REDACTED_TOKEN]', $message);
        $sanitized = preg_replace('/(fonnte_[a-zA-Z0-9_\-]+)/i', '[REDACTED_TOKEN]', $sanitized);
        $sanitized = preg_replace('/(EAA[a-zA-Z0-9_\-]+)/i', '[REDACTED_TOKEN]', $sanitized);
        return trim($sanitized);
    }

    /**
     * Terjemahkan respons error Fonnte API ke pesan yang aman, jelas, dan dapat ditindaklanjuti Admin
     */
    public static function interpretFonnteError(int $httpCode, array $jsonResult, string $curlErr = '', int $curlErrNo = 0): string
    {
        if ($curlErrNo !== 0 || !empty($curlErr)) {
            return "Kesalahan jaringan: Gagal terhubung ke server Fonnte WhatsApp API ({$curlErr}). Periksa koneksi internet server.";
        }

        if ($httpCode === 401) {
            return "Autentikasi Fonnte ditolak (HTTP 401). Token FONNTE_TOKEN tidak valid atau belum diisi pada file .env.";
        }

        if ($httpCode === 404) {
            return "Endpoint Fonnte API tidak ditemukan (HTTP 404). Periksa konfigurasi FONNTE_API_URL pada file .env.";
        }

        $rawReason = (string)($jsonResult['reason'] ?? $jsonResult['detail'] ?? $jsonResult['message'] ?? '');
        $lowerReason = strtolower($rawReason);

        if (str_contains($lowerReason, 'invalid token') || str_contains($lowerReason, 'token invalid') || str_contains($lowerReason, 'unauthorized')) {
            return "Token Fonnte API (FONNTE_TOKEN) tidak valid atau telah kedaluwarsa. Silakan periksa kembali token akun Fonnte Anda di file .env.";
        }

        if (str_contains($lowerReason, 'device disconnect') || str_contains($lowerReason, 'disconnect') || str_contains($lowerReason, 'device not connected')) {
            return "Perangkat WhatsApp pada akun Fonnte Anda sedang terputus (disconnected). Harap lakukan scan ulang QR code pada dashboard Fonnte.";
        }

        if (str_contains($lowerReason, 'target invalid') || str_contains($lowerReason, 'invalid target') || str_contains($lowerReason, 'not registered') || str_contains($lowerReason, 'not a valid whatsapp user')) {
            return "Nomor WhatsApp penerima tidak valid atau tidak terdaftar sebagai pengguna WhatsApp aktif.";
        }

        if (str_contains($lowerReason, 'out of quota') || str_contains($lowerReason, 'quota') || str_contains($lowerReason, 'insufficient')) {
            return "Batas kuota pengiriman pesan Fonnte telah habis. Silakan periksa paket langganan Anda pada dashboard Fonnte.";
        }

        if (str_contains($lowerReason, 'delay') || str_contains($lowerReason, 'rate limit') || str_contains($lowerReason, 'too many')) {
            return "Batas frekuensi pengiriman pesan Fonnte terlampaui. Harap tunggu beberapa saat sebelum mencoba kembali.";
        }

        if (!empty($rawReason)) {
            return "Permintaan pengiriman ditolak oleh Fonnte API: " . self::sanitizeErrorMessage($rawReason);
        }

        return "HTTP {$httpCode}: Server Fonnte API mengembalikan status gagal.";
    }

    /**
     * Periksa status koneksi perangkat WhatsApp di Fonnte secara langsung
     * Endpoint: POST https://api.fonnte.com/device
     * Header: Authorization: {FONNTE_TOKEN}
     */
    public static function checkDeviceStatus(): array
    {
        $cfg = self::getConfig();
        if (!$cfg['has_token']) {
            return [
                'success'      => false,
                'connected'    => false,
                'status_text'  => 'Belum Dikonfigurasi',
                'message'      => 'Token Fonnte belum diisi pada file .env (FONNTE_TOKEN).',
                'device'       => '-',
                'name'         => '-',
                'quota'        => '-',
                'package'      => '-',
                'expired'      => '-'
            ];
        }

        $url = $cfg['device_url'];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $cfg['token']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $rawResult = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            $curlErrNo = curl_errno($ch);
            curl_close($ch);

            if ($curlErrNo !== 0 || !empty($curlErr)) {
                return [
                    'success'      => false,
                    'connected'    => false,
                    'status_text'  => 'Gangguan Jaringan',
                    'message'      => "Gagal menghubungi server Fonnte: {$curlErr}",
                    'device'       => '-',
                    'name'         => '-',
                    'quota'        => '-',
                    'package'      => '-',
                    'expired'      => '-'
                ];
            }

            $json = json_decode($rawResult, true) ?: [];

            // Evaluasi respons Fonnte device
            $statusFlag = $json['status'] ?? false;
            $deviceStatus = strtolower((string)($json['device_status'] ?? ''));

            if ($httpCode >= 200 && $httpCode < 300 && $statusFlag === true) {
                $isConnected = ($deviceStatus === 'connect');
                return [
                    'success'      => true,
                    'connected'    => $isConnected,
                    'status_text'  => $isConnected ? 'Terhubung (Connected)' : 'Terputus (Disconnected)',
                    'device'       => (string)($json['device'] ?? '-'),
                    'name'         => (string)($json['name'] ?? '-'),
                    'quota'        => (string)($json['quota'] ?? '-'),
                    'package'      => (string)($json['package'] ?? '-'),
                    'expired'      => (string)($json['expired'] ?? '-'),
                    'message'      => $isConnected 
                        ? 'Perangkat WhatsApp terhubung aktif dan siap mengirim pesan.'
                        : 'Perangkat WhatsApp terputus. Silakan hubungkan WhatsApp melalui scan QR di akun Fonnte Anda.',
                    'raw'          => $json
                ];
            }

            // Jika token salah atau Fonnte mengembalikan status: false
            $errMsg = self::interpretFonnteError($httpCode, $json);
            return [
                'success'      => false,
                'connected'    => false,
                'status_text'  => 'Gagal Autentikasi / Disconnected',
                'message'      => $errMsg,
                'device'       => '-',
                'name'         => '-',
                'quota'        => '-',
                'package'      => '-',
                'expired'      => '-',
                'raw'          => $json
            ];
        } catch (Exception $e) {
            $msg = self::sanitizeErrorMessage($e->getMessage());
            return [
                'success'      => false,
                'connected'    => false,
                'status_text'  => 'Error Sistem',
                'message'      => "Terjadi kesalahan saat memeriksa perangkat Fonnte: {$msg}",
                'device'       => '-',
                'name'         => '-',
                'quota'        => '-',
                'package'      => '-',
                'expired'      => '-'
            ];
        }
    }

    /**
     * Kirim pesan WhatsApp sungguhan melalui Fonnte API resmi
     * (POST https://api.fonnte.com/send dengan Header Authorization)
     *
     * Format payload:
     *   - target: nomor telepon penerima
     *   - message: teks pesan
     *   - countryCode: 62 (default)
     */
    public static function sendMessage(string $to, string $message): array
    {
        $cfg = self::getConfig();

        // 1. Validasi Token Fonnte
        if (!$cfg['has_token']) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Integrasi WhatsApp belum dikonfigurasi: FONNTE_TOKEN belum diisi pada file .env.'
            ];
        }

        // 2. Normalisasi & Validasi Nomor Penerima
        $recipient = self::normalizePhoneNumber($to);
        if (empty($recipient)) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Nomor WhatsApp penerima tidak valid atau format tidak dikenali.'
            ];
        }

        // 3. Validasi Isi Pesan
        $cleanMessage = trim($message);
        if (empty($cleanMessage)) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Isi pesan WhatsApp tidak boleh kosong.'
            ];
        }

        $url = $cfg['api_url'];

        $postFields = [
            'target'      => $recipient,
            'message'     => $cleanMessage,
            'countryCode' => '62'
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $cfg['token']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $rawResult = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            $curlErrNo = curl_errno($ch);
            curl_close($ch);

            // Tangani kegagalan jaringan / cURL
            if ($curlErrNo !== 0 || !empty($curlErr)) {
                $errMsg = self::interpretFonnteError($httpCode, [], $curlErr, $curlErrNo);
                error_log("Fonnte API cURL Error: {$errMsg}");
                return [
                    'success' => false,
                    'status'  => 'failed',
                    'message' => $errMsg
                ];
            }

            $jsonResult = json_decode($rawResult, true) ?: [];

            // Evaluasi respons Fonnte API: status === true
            // Contoh sukses Fonnte: {"status": true, "id": ["80367170"], "process": "pending", "detail": "success! message in queue"}
            if ($httpCode >= 200 && $httpCode < 300 && isset($jsonResult['status']) && $jsonResult['status'] === true) {
                $providerMsgId = null;
                if (!empty($jsonResult['id'])) {
                    $providerMsgId = is_array($jsonResult['id']) ? (string)($jsonResult['id'][0] ?? '') : (string)$jsonResult['id'];
                }
                if (empty($providerMsgId) && !empty($jsonResult['requestid'])) {
                    $providerMsgId = (string)$jsonResult['requestid'];
                }

                $detailMsg = $jsonResult['detail'] ?? 'Pesan WhatsApp berhasil diterima oleh antrean Fonnte API.';

                return [
                    'success'             => true,
                    'status'              => 'sent',
                    'provider_message_id' => $providerMsgId,
                    'message'             => $detailMsg,
                    'response'            => $jsonResult
                ];
            }

            // Gagal dari sisi Fonnte (token salah, device disconnect, kuota habis, dsb)
            $errMsg = self::interpretFonnteError($httpCode, $jsonResult);
            error_log("Fonnte API Rejected Error: {$errMsg}");

            return [
                'success'  => false,
                'status'   => 'failed',
                'message'  => $errMsg,
                'response' => $jsonResult
            ];
        } catch (Exception $e) {
            $errMsg = self::sanitizeErrorMessage($e->getMessage());
            error_log("WhatsApp Service Exception: " . $errMsg);
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'Terjadi kesalahan sistem pengiriman WhatsApp: ' . $errMsg
            ];
        }
    }

    /**
     * Kirim Pesan Uji Coba Langsung ke Fonnte API (Khusus Admin)
     */
    public static function sendTestMessage(string $to, string $message, ?int $userId = null): array
    {
        $normalizedPhone = self::normalizePhoneNumber($to);
        $testMsg = !empty(trim($message)) 
            ? trim($message) 
            : "Halo! Ini adalah pesan uji coba (Test Message) dari Smart Campus Cash melalui Fonnte WhatsApp API.\n\nWaktu pengujian: " . date('d F Y H:i:s') . "\nStatus: Integrasi Berhasil.";

        $idempotencyKey = "test_msg_" . ($userId ?: '0') . "_" . time() . "_" . bin2hex(random_bytes(3));

        return self::sendAndRecord([
            'recipient_phone' => $normalizedPhone ?: $to,
            'recipient_name'  => 'Pengujian WhatsApp Admin',
            'recipient_type'  => 'user',
            'user_id'         => $userId,
            'category'        => 'financial_announcement',
            'template_code'   => 'TEST_MESSAGE',
            'title'           => 'Uji Coba Pengiriman WhatsApp Fonnte',
            'message'         => $testMsg,
            'idempotency_key' => $idempotencyKey
        ]);
    }

    /**
     * Kirim pesan dan simpan riwayat ke tabel whatsapp_notifications dengan proteksi idempotency
     */
    public static function sendAndRecord(array $params): array
    {
        $notifModel = new WhatsAppNotification();
        $idempotencyKey = $params['idempotency_key'] ?? null;

        // 1. Cek Idempotency: hindari duplikasi pengiriman pesan pada peristiwa yang sama
        if (!empty($idempotencyKey)) {
            $existing = $notifModel->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                $alreadyOk = in_array($existing['status'], ['sent', 'delivered', 'read']);
                return [
                    'success'             => $alreadyOk,
                    'status'              => $existing['status'],
                    'already_sent'        => true,
                    'notification_id'     => (int)$existing['id'],
                    'provider_message_id' => $existing['provider_message_id'],
                    'message'             => 'Notifikasi sudah pernah diproses sebelumnya (idempotent).'
                ];
            }
        }

        $rawPhone = $params['recipient_phone'] ?? '';
        $normalizedPhone = self::normalizePhoneNumber($rawPhone);
        $recipientName = trim($params['recipient_name'] ?? 'Mahasiswa');
        $message = trim($params['message'] ?? '');
        $title = trim($params['title'] ?? 'Notifikasi WhatsApp');
        $category = $params['category'] ?? 'financial_announcement';

        // 2. Validasi nomor telepon
        if (empty($normalizedPhone)) {
            $notifId = $notifModel->createNotification([
                'recipient_phone' => $rawPhone ?: '-',
                'recipient_name'  => $recipientName,
                'recipient_type'  => $params['recipient_type'] ?? 'student',
                'student_id'      => $params['student_id'] ?? null,
                'user_id'         => $params['user_id'] ?? null,
                'bill_id'         => $params['bill_id'] ?? null,
                'payment_id'      => $params['payment_id'] ?? null,
                'category'        => $category,
                'template_code'   => $params['template_code'] ?? null,
                'title'           => $title,
                'message'         => $message,
                'status'          => 'skipped',
                'error_message'   => 'Nomor WhatsApp penerima tidak valid atau belum tersedia pada data mahasiswa.',
                'attempts'        => 1,
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'success'         => false,
                'status'          => 'skipped',
                'notification_id' => $notifId,
                'message'         => 'Nomor WhatsApp penerima tidak valid atau kosong.'
            ];
        }

        // 3. Validasi token Fonnte sebelum memanggil provider
        if (!self::isConfigured()) {
            $notifId = $notifModel->createNotification([
                'recipient_phone' => $normalizedPhone,
                'recipient_name'  => $recipientName,
                'recipient_type'  => $params['recipient_type'] ?? 'student',
                'student_id'      => $params['student_id'] ?? null,
                'user_id'         => $params['user_id'] ?? null,
                'bill_id'         => $params['bill_id'] ?? null,
                'payment_id'      => $params['payment_id'] ?? null,
                'category'        => $category,
                'template_code'   => $params['template_code'] ?? null,
                'title'           => $title,
                'message'         => $message,
                'status'          => 'skipped',
                'error_message'   => 'Integrasi WhatsApp belum dikonfigurasi: FONNTE_TOKEN belum diisi di file .env server.',
                'attempts'        => 1,
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'success'         => false,
                'status'          => 'skipped',
                'notification_id' => $notifId,
                'message'         => 'Integrasi WhatsApp belum dikonfigurasi: FONNTE_TOKEN belum diisi pada file .env.'
            ];
        }

        // 4. Catat record awal dengan status 'processing'
        $notifId = $notifModel->createNotification([
            'recipient_phone' => $normalizedPhone,
            'recipient_name'  => $recipientName,
            'recipient_type'  => $params['recipient_type'] ?? 'student',
            'student_id'      => $params['student_id'] ?? null,
            'user_id'         => $params['user_id'] ?? null,
            'bill_id'         => $params['bill_id'] ?? null,
            'payment_id'      => $params['payment_id'] ?? null,
            'category'        => $category,
            'template_code'   => $params['template_code'] ?? null,
            'title'           => $title,
            'message'         => $message,
            'status'          => 'processing',
            'attempts'        => 0,
            'idempotency_key' => $idempotencyKey,
        ]);

        if (!$notifId) {
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'Gagal mencatat notifikasi ke database.'
            ];
        }

        // 5. Kirim melalui Fonnte API secara real
        $apiResult = self::sendMessage($normalizedPhone, $message);

        // 6. Update status hasil pengiriman berdasarkan respons nyata Fonnte
        $newStatus     = $apiResult['status'] ?? 'failed';
        $providerMsgId = $apiResult['provider_message_id'] ?? null;
        $errMsg        = !$apiResult['success'] ? ($apiResult['message'] ?? 'Pengiriman gagal') : null;
        $rawResp       = $apiResult['response'] ?? null;

        $notifModel->updateStatus($notifId, $newStatus, $providerMsgId, $errMsg, $rawResp);

        return [
            'success'             => $apiResult['success'] ?? false,
            'status'              => $newStatus,
            'notification_id'     => $notifId,
            'provider_message_id' => $providerMsgId,
            'message'             => $apiResult['message'] ?? 'Proses selesai.'
        ];
    }

    /**
     * Notifikasi saat Tagihan Baru Diterbitkan
     */
    public static function notifyBillCreated(array $bill, array $student, ?int $userId = null): array
    {
        $phone = $student['phone'] ?? $bill['student_phone'] ?? '';
        $name = $student['full_name'] ?? $bill['student_name'] ?? 'Mahasiswa';
        $nim = $student['nim'] ?? $bill['nim'] ?? '-';
        $inv = $bill['invoice_number'] ?? '-';
        $title = $bill['title'] ?? 'Tagihan Kuliah';
        $amount = number_format((float)($bill['amount'] ?? 0), 0, ',', '.');
        $dueDate = !empty($bill['due_date']) ? date('d F Y', strtotime($bill['due_date'])) : '-';

        // Render template jika tersedia di database
        $tmplModel = new WhatsAppTemplate();
        $template = $tmplModel->findByCode('BILL_CREATED');

        $variables = [
            'nama_mahasiswa'      => $name,
            'nim'                 => $nim,
            'invoice'             => $inv,
            'nama_tagihan'        => $title,
            'jenis_tagihan'       => $title,
            'nominal'             => "Rp {$amount}",
            'tanggal_jatuh_tempo' => $dueDate
        ];

        if ($template && !empty($template['body'])) {
            $msg = WhatsAppTemplate::render($template['body'], $variables);
        } else {
            $msg = "Halo {$name}, terdapat tagihan baru di Smart Campus Cash. Keterangan: {$title}. Jumlah: Rp {$amount}. Jatuh tempo: {$dueDate}. Silakan periksa detail tagihan melalui sistem.";
        }

        $billId = !empty($bill['id']) ? (int)$bill['id'] : null;
        $studentId = !empty($student['id']) ? (int)$student['id'] : null;

        return self::sendAndRecord([
            'recipient_phone' => $phone,
            'recipient_name'  => $name,
            'recipient_type'  => 'student',
            'student_id'      => $studentId,
            'user_id'         => $userId,
            'bill_id'         => $billId,
            'category'        => 'bill_created',
            'template_code'   => 'BILL_CREATED',
            'title'           => "Tagihan Baru: {$title}",
            'message'         => $msg,
            'idempotency_key' => $billId ? "bill_created_{$billId}" : null
        ]);
    }

    /**
     * Notifikasi saat Pembayaran Berhasil Diterima & Diverifikasi
     */
    public static function notifyPaymentSuccess(array $payment, array $bill, array $student, ?int $userId = null): array
    {
        $phone = $student['phone'] ?? $bill['student_phone'] ?? $payment['student_phone'] ?? '';
        $name = $student['full_name'] ?? $bill['student_name'] ?? $payment['student_name'] ?? 'Mahasiswa';
        $nim = $student['nim'] ?? $bill['nim'] ?? $payment['nim'] ?? '-';
        $payNum = $payment['payment_number'] ?? '-';
        $inv = $bill['invoice_number'] ?? $payment['invoice_number'] ?? '-';
        $title = $bill['title'] ?? $payment['bill_title'] ?? 'Tagihan Kuliah';
        $amountPaid = number_format((float)($payment['amount'] ?? 0), 0, ',', '.');
        $payDate = !empty($payment['payment_date']) ? date('d F Y H:i', strtotime($payment['payment_date'])) : date('d F Y H:i');
        $remaining = (float)($bill['remaining_amount'] ?? 0);
        $billStatus = strtoupper($bill['status'] ?? 'PAID');

        // Pembayaran Parsial vs Lunas (Strict Requirement)
        if ($billStatus === 'PAID' || $remaining <= 0) {
            $statusText = "LUNAS";
        } else {
            $remainingFmt = number_format(max(0, $remaining), 0, ',', '.');
            $statusText = "SEBAGIAN (Sisa Tagihan: Rp {$remainingFmt})";
        }

        $tmplModel = new WhatsAppTemplate();
        $template = $tmplModel->findByCode('PAYMENT_SUCCESS');

        $variables = [
            'nama_mahasiswa'     => $name,
            'nim'                => $nim,
            'nomor_kuitansi'     => $payNum,
            'invoice'            => $inv,
            'nama_tagihan'       => $title,
            'jenis_tagihan'      => $title,
            'nominal_pembayaran' => "Rp {$amountPaid}",
            'nominal'            => "Rp {$amountPaid}",
            'tanggal_pembayaran' => $payDate,
            'status_pembayaran'  => $statusText,
            'sisa_tagihan'       => "Rp " . number_format(max(0, $remaining), 0, ',', '.')
        ];

        if ($template && !empty($template['body'])) {
            $msg = WhatsAppTemplate::render($template['body'], $variables);
            if (!str_contains($msg, 'LUNAS') && !str_contains($msg, 'SEBAGIAN')) {
                $msg = rtrim($msg, " \t\n\r.") . ". Status pembayaran: {$statusText}. Terima kasih.";
            }
        } else {
            $msg = "Halo {$name}, pembayaran Anda telah tercatat di Smart Campus Cash. Tagihan: {$title}. Jumlah pembayaran: Rp {$amountPaid}. Tanggal: {$payDate}. Status: {$statusText}. Terima kasih.";
        }

        $paymentId = !empty($payment['id']) ? (int)$payment['id'] : null;
        $billId = !empty($bill['id']) ? (int)$bill['id'] : null;
        $studentId = !empty($student['id']) ? (int)$student['id'] : null;

        return self::sendAndRecord([
            'recipient_phone' => $phone,
            'recipient_name'  => $name,
            'recipient_type'  => 'student',
            'student_id'      => $studentId,
            'user_id'         => $userId,
            'bill_id'         => $billId,
            'payment_id'      => $paymentId,
            'category'        => 'payment_success',
            'template_code'   => 'PAYMENT_SUCCESS',
            'title'           => "Konfirmasi Pembayaran: {$payNum}",
            'message'         => $msg,
            'idempotency_key' => $paymentId ? "payment_success_{$paymentId}" : null
        ]);
    }

    /**
     * Notifikasi Pengingat Jatuh Tempo (Due Reminder)
     */
    public static function notifyDueReminder(array $bill, array $student, ?int $userId = null): array
    {
        // Jangan kirim pengingat jika tagihan sudah lunas atau dibatalkan
        $status = strtoupper($bill['status'] ?? '');
        if (in_array($status, ['PAID', 'CANCELLED'])) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Tagihan sudah lunas atau dibatalkan, pengingat tidak diperlukan.'
            ];
        }

        $phone = $student['phone'] ?? $bill['student_phone'] ?? '';
        $name = $student['full_name'] ?? $bill['student_name'] ?? 'Mahasiswa';
        $title = $bill['title'] ?? 'Tagihan Kuliah';
        $amount = number_format((float)($bill['remaining_amount'] ?? $bill['amount'] ?? 0), 0, ',', '.');
        $dueDate = !empty($bill['due_date']) ? date('d F Y', strtotime($bill['due_date'])) : '-';

        $tmplModel = new WhatsAppTemplate();
        $template = $tmplModel->findByCode('DUE_REMINDER');

        $variables = [
            'nama_mahasiswa'      => $name,
            'nama_tagihan'        => $title,
            'jenis_tagihan'       => $title,
            'nominal'             => "Rp {$amount}",
            'tanggal_jatuh_tempo' => $dueDate
        ];

        if ($template && !empty($template['body'])) {
            $msg = WhatsAppTemplate::render($template['body'], $variables);
        } else {
            $msg = "Halo {$name}, ini pengingat bahwa tagihan {$title} sebesar Rp {$amount} akan jatuh tempo pada {$dueDate}. Silakan periksa status tagihan Anda di Smart Campus Cash.";
        }

        $billId = (int)($bill['id'] ?? 0);
        $studentId = (int)($student['id'] ?? 0);
        $dueDateStr = date('Ymd', strtotime($bill['due_date'] ?? 'now'));

        return self::sendAndRecord([
            'recipient_phone' => $phone,
            'recipient_name'  => $name,
            'recipient_type'  => 'student',
            'student_id'      => $studentId ?: null,
            'user_id'         => $userId,
            'bill_id'         => $billId ?: null,
            'category'        => 'due_reminder',
            'template_code'   => 'DUE_REMINDER',
            'title'           => "Pengingat Jatuh Tempo: {$title}",
            'message'         => $msg,
            'idempotency_key' => $billId ? "due_reminder_{$billId}_{$dueDateStr}" : null
        ]);
    }

    /**
     * Notifikasi Tagihan Terlambat / Tunggakan (Overdue Reminder)
     */
    public static function notifyOverdueReminder(array $bill, array $student, ?int $userId = null): array
    {
        // Tagihan harus belum lunas dan telah melewati jatuh tempo
        $status = strtoupper($bill['status'] ?? '');
        if (in_array($status, ['PAID', 'CANCELLED'])) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Tagihan sudah lunas atau dibatalkan. Pengingat tunggakan ditolak.'
            ];
        }

        $dueDateTimestamp = strtotime($bill['due_date'] ?? '');
        if ($dueDateTimestamp && $dueDateTimestamp >= strtotime('today')) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => 'Tagihan belum melewati tanggal jatuh tempo.'
            ];
        }

        // Cek cooldown tunggakan (misal 7 hari) agar tidak spam
        $cooldownDays = (int)WhatsAppSetting::get('overdue_cooldown_days', 7);
        $notifModel = new WhatsAppNotification();
        if ($notifModel->hasRecentReminder((int)$student['id'], 'overdue_reminder', (int)$bill['id'], $cooldownDays)) {
            return [
                'success' => false,
                'status'  => 'skipped',
                'message' => "Pengingat tunggakan sudah pernah dikirim dalam {$cooldownDays} hari terakhir (cooldown aktif)."
            ];
        }

        $phone = $student['phone'] ?? $bill['student_phone'] ?? '';
        $name = $student['full_name'] ?? $bill['student_name'] ?? 'Mahasiswa';
        $title = $bill['title'] ?? 'Tagihan Kuliah';
        $remaining = number_format(max(0, (float)($bill['remaining_amount'] ?? $bill['amount'] ?? 0)), 0, ',', '.');
        $dueDate = !empty($bill['due_date']) ? date('d F Y', strtotime($bill['due_date'])) : '-';

        $tmplModel = new WhatsAppTemplate();
        $template = $tmplModel->findByCode('OVERDUE_REMINDER');

        $variables = [
            'nama_mahasiswa'      => $name,
            'nama_tagihan'        => $title,
            'jenis_tagihan'       => $title,
            'sisa_tagihan'        => "Rp {$remaining}",
            'nominal'             => "Rp {$remaining}",
            'tanggal_jatuh_tempo' => $dueDate
        ];

        if ($template && !empty($template['body'])) {
            $msg = WhatsAppTemplate::render($template['body'], $variables);
        } else {
            $msg = "Halo {$name}, berdasarkan data Smart Campus Cash, tagihan {$title} sebesar Rp {$remaining} masih belum lunas dan telah melewati tanggal jatuh tempo {$dueDate}. Jika Anda sudah membayar, silakan periksa status pembayaran atau hubungi petugas keuangan.";
        }

        $billId = (int)($bill['id'] ?? 0);
        $studentId = (int)($student['id'] ?? 0);
        $cycleKey = date('Ymd'); // satu kali pengiriman per hari per tagihan

        return self::sendAndRecord([
            'recipient_phone' => $phone,
            'recipient_name'  => $name,
            'recipient_type'  => 'student',
            'student_id'      => $studentId ?: null,
            'user_id'         => $userId,
            'bill_id'         => $billId ?: null,
            'category'        => 'overdue_reminder',
            'template_code'   => 'OVERDUE_REMINDER',
            'title'           => "Peringatan Tunggakan: {$title}",
            'message'         => $msg,
            'idempotency_key' => $billId ? "overdue_{$billId}_{$cycleKey}" : null
        ]);
    }

    /**
     * Notifikasi Informasi Transaksi / Rekap Setoran Loket Kasir
     */
    public static function notifyCounterRecap(array $recapData, string $recipientPhone, ?int $userId = null): array
    {
        $officer = $recapData['officer_name'] ?? 'Kasir';
        $date = $recapData['date'] ?? date('d F Y');
        $txCount = (int)($recapData['transaction_count'] ?? 0);
        $totalNominal = number_format((float)($recapData['total_amount'] ?? 0), 0, ',', '.');

        $tmplModel = new WhatsAppTemplate();
        $template = $tmplModel->findByCode('COUNTER_RECAP');

        $variables = [
            'nama_petugas'     => $officer,
            'tanggal'          => $date,
            'jumlah_transaksi' => $txCount,
            'total_nominal'    => "Rp {$totalNominal}"
        ];

        if ($template && !empty($template['body'])) {
            $msg = WhatsAppTemplate::render($template['body'], $variables);
        } else {
            $msg = "📢 *REKAP SETORAN LOKET KASIR*\n\n"
                 . "Petugas: {$officer}\n"
                 . "Tanggal: {$date}\n"
                 . "Total Transaksi: {$txCount}\n"
                 . "Total Penerimaan Kas: Rp {$totalNominal}\n\n"
                 . "Laporan setoran loket telah terekam di sistem Smart Campus Cash.";
        }

        $idempotencyKey = "counter_recap_" . ($userId ?: '0') . "_" . date('Ymd');

        return self::sendAndRecord([
            'recipient_phone' => $recipientPhone,
            'recipient_name'  => $officer,
            'recipient_type'  => 'user',
            'user_id'         => $userId,
            'category'        => 'counter_recap',
            'template_code'   => 'COUNTER_RECAP',
            'title'           => "Rekap Setoran Loket: {$date}",
            'message'         => $msg,
            'idempotency_key' => $idempotencyKey
        ]);
    }

    /**
     * Notifikasi saat Status Tagihan Berubah
     */
    public static function notifyBillStatusChanged(array $bill, array $student, string $oldStatus, string $newStatus, string $reason = ''): array
    {
        $phone = $student['phone'] ?? $bill['student_phone'] ?? '';
        $name = $student['full_name'] ?? $bill['student_name'] ?? 'Mahasiswa';
        $nim = $student['nim'] ?? $bill['nim'] ?? '-';
        $inv = $bill['invoice_number'] ?? '-';
        $title = $bill['title'] ?? 'Tagihan Kuliah';

        $reasonText = !empty($reason) ? "\n📝 *Keterangan*: {$reason}" : '';

        $msg = "ℹ️ *PEMBARUAN STATUS TAGIHAN KULIAH*\n\n"
             . "Halo, *{$name}* (NIM: {$nim})\n"
             . "Informasi status tagihan perkuliahan Anda telah diperbarui:\n\n"
             . "📄 *No. Invoice*: `{$inv}`\n"
             . "📌 *Uraian*: {$title}\n"
             . "🔄 *Status Sebelumnya*: {$oldStatus}\n"
             . "✨ *Status Baru*: *{$newStatus}*"
             . $reasonText . "\n\n"
             . "Silakan cek rincian selengkapnya pada Portal Mahasiswa.\n\n"
             . "— *Smart Campus Cash*";

        $billId = (int)($bill['id'] ?? 0);
        $studentId = (int)($student['id'] ?? 0);

        return self::sendAndRecord([
            'recipient_phone' => $phone,
            'recipient_name'  => $name,
            'recipient_type'  => 'student',
            'student_id'      => $studentId ?: null,
            'bill_id'         => $billId ?: null,
            'category'        => 'bill_created',
            'title'           => "Perubahan Status Tagihan: {$inv}",
            'message'         => $msg,
            'idempotency_key' => $billId ? "bill_status_{$billId}_{$newStatus}_" . time() : null
        ]);
    }

    /**
     * Jalankan Scheduler Pengingat Otomatis (Jatuh Tempo & Tunggakan)
     * Dapat dipanggil lewat cron job CLI atau trigger controller
     */
    public static function runAutomatedReminders(): array
    {
        $enabled = (int)WhatsAppSetting::get('auto_reminders_enabled', 1);
        if ($enabled !== 1) {
            return [
                'status'       => 'disabled',
                'message'      => 'Pengingat otomatis dinonaktifkan dalam pengaturan sistem.',
                'due_sent'     => 0,
                'overdue_sent' => 0,
                'skipped'      => 0
            ];
        }

        if (!self::isConfigured()) {
            return [
                'status'       => 'unconfigured',
                'message'      => 'Pengingat otomatis ditangguhkan: FONNTE_TOKEN belum dikonfigurasi pada file .env.',
                'due_sent'     => 0,
                'overdue_sent' => 0,
                'skipped'      => 0
            ];
        }

        $pdo = Database::getConnection();
        if (!$pdo) {
            return [
                'status'       => 'error',
                'message'      => 'Koneksi database gagal.',
                'due_sent'     => 0,
                'overdue_sent' => 0,
                'skipped'      => 0
            ];
        }

        $dueDays = (int)WhatsAppSetting::get('due_reminder_days', 3);
        $cooldownDays = (int)WhatsAppSetting::get('overdue_cooldown_days', 7);

        $dueSent = 0;
        $overdueSent = 0;
        $skipped = 0;

        // 1. Scan tagihan yang mendekati jatuh tempo (H-N s/d H-0)
        $dueSql = "SELECT b.*, s.full_name AS student_name, s.phone AS student_phone, s.nim 
                   FROM bills b
                   JOIN students s ON b.student_id = s.id
                   WHERE b.status IN ('UNPAID', 'PARTIAL')
                     AND b.due_date >= CURDATE()
                     AND b.due_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";

        $dueStmt = $pdo->prepare($dueSql);
        $dueStmt->execute(['days' => $dueDays]);
        $dueBills = $dueStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $notifModel = new WhatsAppNotification();
        foreach ($dueBills as $bill) {
            $dueDateStr = date('Ymd', strtotime($bill['due_date']));
            $idempotencyKey = "due_reminder_{$bill['id']}_{$dueDateStr}";

            if ($notifModel->findByIdempotencyKey($idempotencyKey)) {
                $skipped++;
                continue;
            }

            $res = self::notifyDueReminder($bill, [
                'id'        => $bill['student_id'],
                'full_name' => $bill['student_name'],
                'phone'     => $bill['student_phone'],
                'nim'       => $bill['nim']
            ]);

            if ($res['success'] ?? false) {
                $dueSent++;
            } else {
                $skipped++;
            }
        }

        // 2. Scan tagihan yang sudah lewat jatuh tempo & belum lunas (Tunggakan)
        $overdueSql = "SELECT b.*, s.full_name AS student_name, s.phone AS student_phone, s.nim 
                       FROM bills b
                       JOIN students s ON b.student_id = s.id
                       WHERE b.status IN ('UNPAID', 'PARTIAL')
                         AND b.due_date < CURDATE()";

        $overdueStmt = $pdo->query($overdueSql);
        $overdueBills = $overdueStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($overdueBills as $bill) {
            if ($notifModel->hasRecentReminder((int)$bill['student_id'], 'overdue_reminder', (int)$bill['id'], $cooldownDays)) {
                $skipped++;
                continue;
            }

            $res = self::notifyOverdueReminder($bill, [
                'id'        => $bill['student_id'],
                'full_name' => $bill['student_name'],
                'phone'     => $bill['student_phone'],
                'nim'       => $bill['nim']
            ]);

            if ($res['success'] ?? false) {
                $overdueSent++;
            } else {
                $skipped++;
            }
        }

        return [
            'status'       => 'completed',
            'message'      => "Scheduler selesai diproses: {$dueSent} pengingat jatuh tempo, {$overdueSent} pengingat tunggakan, {$skipped} dilewati.",
            'due_sent'     => $dueSent,
            'overdue_sent' => $overdueSent,
            'skipped'      => $skipped
        ];
    }

    /**
     * Tangani incoming webhook dari Fonnte (Pembaruan status pesan: sent, delivered, read, failed)
     * Format webhook Fonnte:
     *   { "id": "80367170", "status": "delivered", "device": "628..." }
     */
    public static function handleWebhook(array $payload): array
    {
        $notifModel = new WhatsAppNotification();

        $msgId = $payload['id'] ?? $payload['message_id'] ?? null;
        $status = strtolower((string)($payload['status'] ?? ''));

        if (empty($msgId) || empty($status)) {
            return ['success' => false, 'message' => 'Payload webhook tidak memiliki ID atau status.'];
        }

        $existing = $notifModel->findByProviderMessageId((string)$msgId);
        if (!$existing) {
            return ['success' => false, 'message' => "Notifikasi dengan provider ID {$msgId} tidak ditemukan."];
        }

        // Petakan status Fonnte ke status database
        $mappedStatus = match ($status) {
            'pending', 'queue'      => 'processing',
            'sent'                  => 'sent',
            'delivered', 'received' => 'delivered',
            'read'                  => 'read',
            'failed', 'rejected'    => 'failed',
            default                 => $status
        };

        $errMsg = null;
        if ($mappedStatus === 'failed') {
            $errMsg = $payload['reason'] ?? $payload['detail'] ?? 'Webhook Fonnte melaporkan pengiriman gagal.';
        }

        $notifModel->updateStatus((int)$existing['id'], $mappedStatus, (string)$msgId, $errMsg, $payload);

        return ['success' => true, 'id' => (int)$existing['id'], 'new_status' => $mappedStatus];
    }
}
