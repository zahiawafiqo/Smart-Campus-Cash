<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AiAssistantService
{
    /**
     * Proses pertanyaan pengguna dan kembalikan jawaban AI
     */
    /**
     * Respon standar penolakan permintaan coding umum
     */
    public static function getCodingRefusalResponse(): string
    {
        return "Maaf, karena saya adalah AI Assistant Smart Campus Cash yang berfokus pada informasi keuangan kampus, saya tidak dapat membantu membuat kode pemrograman umum.";
    }

    /**
     * Respon standar identitas AI
     */
    public static function getIdentityResponse(): string
    {
        return "Saya adalah AI Assistant Smart Campus Cash, asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem.";
    }

    /**
     * Respon standar tugas AI
     */
    public static function getDutiesResponse(): string
    {
        return "Saya membantu memberikan informasi dan ringkasan keuangan Smart Campus Cash sesuai data yang tersedia dan hak akses pengguna.";
    }

    /**
     * Respon standar identitas pengguna yang sedang login
     */
    public static function getUserIdentityResponse(string $role, ?string $userName): string
    {
        $userName = trim((string)$userName);
        if (empty($userName)) {
            return "Identitas pengguna belum dapat diperoleh.";
        }

        $roleDisplay = match(strtolower($role)) {
            'admin' => 'Administrator',
            'mahasiswa' => 'Mahasiswa',
            'kasir' => 'Kasir',
            'keuangan' => 'Bagian Keuangan',
            default => ucfirst($role)
        };

        return "Anda adalah {$userName}, {$roleDisplay} Smart Campus Cash.";
    }

    /**
     * Respon standar statistik pengguna sistem berdasarkan database aktual
     */
    public static function getUserStatsResponse(string $prompt, string $role): string
    {
        if (strtolower($role) !== 'admin') {
            return "Maaf, Anda tidak memiliki hak akses untuk melihat data statistik pengguna sistem. Informasi ini hanya dapat diakses oleh Administrator sesuai kebijakan keamanan dan hak akses (RBAC) Smart Campus Cash.";
        }

        $stats = \App\Models\User::getRoleStatistics();
        if (!$stats || !isset($stats['total'])) {
            return "Maaf, data statistik pengguna belum dapat dimuat saat ini karena kendala koneksi ke database. Silakan coba beberapa saat lagi.";
        }

        $total = (int)$stats['total'];
        $adminCount = (int)$stats['admin'];
        $mhsCount = (int)$stats['mahasiswa'];
        $kasirCount = (int)$stats['kasir'];
        $keuCount = (int)$stats['keuangan'];

        $p = strtolower(trim($prompt));

        // Pertanyaan spesifik untuk mahasiswa
        if (preg_match('/\b(mahasiswa)\b/i', $p) && !preg_match('/\b(admin|administrator|kasir|keuangan|role)\b/i', $p)) {
            return "Jumlah mahasiswa terdaftar: {$mhsCount}";
        }

        // Pertanyaan spesifik untuk kasir
        if (preg_match('/\b(kasir)\b/i', $p) && !preg_match('/\b(admin|administrator|mahasiswa|keuangan|role)\b/i', $p)) {
            return "Jumlah kasir terdaftar: {$kasirCount}";
        }

        // Pertanyaan spesifik untuk bagian keuangan
        if (preg_match('/\b(bagian\s+keuangan|keuangan)\b/i', $p) && !preg_match('/\b(admin|administrator|mahasiswa|kasir|role)\b/i', $p)) {
            return "Jumlah bagian keuangan terdaftar: {$keuCount}";
        }

        // Pertanyaan spesifik untuk admin
        if (preg_match('/\b(admin|administrator)\b/i', $p) && !preg_match('/\b(mahasiswa|kasir|keuangan|role)\b/i', $p)) {
            return "Jumlah administrator terdaftar: {$adminCount}";
        }

        // Ringkasan umum statistik pengguna berdasarkan role
        return "Jumlah pengguna terdaftar: {$total}\n\nRincian berdasarkan role:\n- Administrator: {$adminCount}\n- Mahasiswa: {$mhsCount}\n- Kasir: {$kasirCount}\n- Bagian Keuangan: {$keuCount}";
    }

    /**
     * Respon standar penolakan di luar topik keuangan
     */
    public static function getOffTopicRefusalResponse(): string
    {
        return "Maaf, karena saya adalah AI Assistant Smart Campus Cash yang berfokus pada informasi keuangan kampus, saya tidak dapat membantu pertanyaan di luar topik keuangan dan sistem Smart Campus Cash.";
    }

    /**
     * Deteksi apakah pertanyaan merupakan permintaan identitas pengguna yang sedang login
     */
    public static function isUserIdentityRequest(string $prompt): bool
    {
        $p = strtolower(trim($prompt));

        if (
            preg_match('/\b(siapa|siapakah)\s+(nama\s+)?(saya|aku|kita|kami)\b/i', $p) ||
            preg_match('/\b(saya|aku)\s+(ini\s+)?siapa\b/i', $p) ||
            preg_match('/\b(nama\s+(saya|aku)\s+(siapa|apa))\b/i', $p) ||
            preg_match('/\bapa\s+nama\s+(saya|aku)\b/i', $p) ||
            preg_match('/\b(role|peran)\s+(saya|aku)\s+(apa|siapa)\b/i', $p) ||
            preg_match('/\bapa\s+(role|peran)\s+(saya|aku)\b/i', $p) ||
            preg_match('/\b(tahu|kenal)\s+(siapa\s+)?(nama\s+)?(saya|aku)\b/i', $p) ||
            preg_match('/\b(identitas\s+(saya|aku|pengguna))\b/i', $p) ||
            $p === 'siapa saya' || $p === 'siapakah saya' || $p === 'saya siapa' || $p === 'saya ini siapa' ||
            $p === 'apa nama saya' || $p === 'siapa nama saya' || $p === 'nama saya' ||
            $p === 'apa role saya' || $p === 'role saya' || $p === 'apa peran saya' || $p === 'peran saya'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Deteksi apakah pertanyaan merupakan pertanyaan identitas AI
     */
    public static function isAiIdentityRequest(string $prompt): bool
    {
        $p = strtolower(trim($prompt));

        // Jika menanyakan identitas diri pengguna ("saya"), jangan tangani sebagai identitas AI
        if (self::isUserIdentityRequest($prompt)) {
            return false;
        }

        if (
            preg_match('/\b(siapa|siapakah)\s+(kamu|anda|dirimu|asisten|ai|bot|chatbot)\b/i', $p) ||
            preg_match('/\b(kamu|anda)\s+(ini\s+)?siapa\b/i', $p) ||
            preg_match('/\b(identitas\s+(kamu|anda|ai|asisten))\b/i', $p) ||
            $p === 'siapa kamu' || $p === 'siapakah kamu' || $p === 'kamu siapa' ||
            $p === 'siapa anda' || $p === 'siapakah anda' || $p === 'anda siapa' ||
            $p === 'kamu ini siapa' || $p === 'anda ini siapa' || $p === 'siapa dirimu' || $p === 'siapakah dirimu'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Deteksi apakah pertanyaan menanyakan tugas/job desk AI
     */
    public static function isJobDeskRequest(string $prompt): bool
    {
        $p = strtolower(trim($prompt));

        if (
            preg_match('/\bapa\s+(tugas|fungsi|peran|pekerjaan)\s+(kamu|anda|mu|dirimu|ai|asisten)\b/i', $p) ||
            preg_match('/\b(tugas|fungsi|peran|pekerjaan)\s+(kamu|anda|mu|dirimu|ai|asisten)\s+(apa|seperti apa)\b/i', $p) ||
            preg_match('/\bapa\s+yang\s+(bisa\s+)?(kamu|anda)\s+(bantu|lakukan)\b/i', $p) ||
            $p === 'apa tugas anda' || $p === 'apa tugas kamu' || $p === 'apa tugasmu' ||
            $p === 'apa fungsi anda' || $p === 'apa fungsi kamu' || $p === 'tugas anda apa' || $p === 'tugas kamu apa'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Deteksi apakah pertanyaan menanyakan statistik pengguna / jumlah role
     */
    public static function isUserStatsRequest(string $prompt): bool
    {
        $p = strtolower(trim($prompt));

        return (
            str_contains($p, 'jumlah pengguna') ||
            str_contains($p, 'total pengguna') ||
            str_contains($p, 'banyak pengguna') ||
            str_contains($p, 'jumlah user') ||
            str_contains($p, 'total user') ||
            str_contains($p, 'banyak user') ||
            str_contains($p, 'pengguna terdaftar') ||
            str_contains($p, 'user terdaftar') ||
            str_contains($p, 'statistik pengguna') ||
            str_contains($p, 'statistik user') ||
            str_contains($p, 'pengguna di sistem') ||
            str_contains($p, 'user di sistem') ||
            str_contains($p, 'jumlah akun') ||
            str_contains($p, 'total akun') ||
            str_contains($p, 'akun terdaftar') ||
            str_contains($p, 'berdasarkan role') ||
            preg_match('/\b(berapa|ada berapa)\s+(jumlah|total)?\s*(admin|administrator|mahasiswa|kasir|bagian\s+keuangan)\b/i', $p) ||
            preg_match('/\b(jumlah|total)\s+(admin|administrator|mahasiswa|kasir|bagian\s+keuangan)\s*(berapa|ada berapa)?\b/i', $p) ||
            preg_match('/\bada\s+berapa\s+(admin|administrator|mahasiswa|kasir|bagian\s+keuangan)\b/i', $p)
        );
    }

    /**
     * Deteksi apakah pertanyaan merupakan permintaan coding / programming umum
     */
    public static function isGeneralCodingRequest(string $prompt): bool
    {
        $p = strtolower(trim($prompt));

        // Bahasa pemrograman murni & tools developer umum
        $pureProgLanguages = [
            'python', 'javascript', 'typescript', 'golang', 'rust', 'ruby', 'kotlin', 'swift'
        ];
        foreach ($pureProgLanguages as $lang) {
            if (str_contains($p, $lang)) {
                return true;
            }
        }

        // Bahasa / framework dengan boundary
        if (preg_match('/\b(java|php|html|css|c\+\+|c\#|dart|react|vue|angular|flutter|laravel|nodejs|node\.js)\b/i', $p)) {
            return true;
        }

        // Istilah coding / programming / debugging eksplisit
        if (
            preg_match('/\b(coding|koding|codingan|pemrograman|programmer)\b/i', $p) ||
            preg_match('/\b(debug|debugging)\b/i', $p) ||
            preg_match('/\b(print\(|input\(|console\.log|echo\s+[\'"])\b/i', $p) ||
            preg_match('/\b(bubble\s*sort|quick\s*sort|sorting|luas\s+lingkaran)\b/i', $p) ||
            preg_match('/\btutorial\s+(pemrograman|coding|koding)/i', $p)
        ) {
            return true;
        }

        // Frasa aksi membuat/menulis/memberikan kode/program/script
        if (
            preg_match('/\b(buatkan|buat|bikin|tuliskan|tulis|berikan|contoh|ajari|bantu)\s+.*(kode|koding|coding|script|skrip|program|website|aplikasi|algoritma|fungsi|function|class|sintaks|syntax)\b/i', $p) ||
            preg_match('/\b(script|skrip|syntax|sintaks)\b/i', $p)
        ) {
            // Kecualikan jika hanya istilah keuangan internal sistem (kode transaksi, kode invoice, program studi, dsb.)
            $financialTermsOnly = preg_replace('/\b(kode\s+transaksi|kode\s+invoice|kode\s+bayar|kode\s+billing|kode\s+pos|kode\s+voucher|program\s+studi|program\s+beasiswa|program\s+cicilan)\b/i', '', $p);
            $financialTermsOnly = trim($financialTermsOnly);

            if (
                preg_match('/\b(kode|script|skrip|coding|koding|program|syntax|sintaks|aplikasi|website|algoritma|fungsi|function)\b/i', $financialTermsOnly) &&
                preg_match('/\b(buatkan|buat|bikin|tuliskan|tulis|berikan|contoh|ajari|bantu|debug)\b/i', $p)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Proses pertanyaan pengguna dan kembalikan jawaban AI
     */
    public static function ask(string $prompt, string $role, int $userId, ?string $userName = null): array
    {
        // Pastikan nama pengguna aktual tersedia dari database/sesi jika belum diisi
        if (empty($userName) && $userId > 0) {
            try {
                $pdo = Database::getConnection();
                if ($pdo) {
                    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :uid LIMIT 1");
                    $stmt->execute(['uid' => $userId]);
                    $userName = (string)($stmt->fetchColumn() ?: '');
                }
            } catch (Exception $e) {
                $userName = '';
            }
        }

        // 1. Intersepsi dini permintaan coding umum
        if (self::isGeneralCodingRequest($prompt)) {
            $responseContent = self::getCodingRefusalResponse();
            self::logQuery($userId, $role, $prompt, $responseContent);
            return [
                'success' => true,
                'prompt' => $prompt,
                'response' => $responseContent,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];
        }

        // 2. Intersepsi pertanyaan identitas pengguna yang sedang login
        if (self::isUserIdentityRequest($prompt)) {
            $responseContent = self::getUserIdentityResponse($role, $userName);
            self::logQuery($userId, $role, $prompt, $responseContent);
            return [
                'success' => true,
                'prompt' => $prompt,
                'response' => $responseContent,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];
        }

        // 3. Intersepsi pertanyaan identitas AI ("Siapa Anda?", "Siapa kamu?")
        if (self::isAiIdentityRequest($prompt)) {
            $responseContent = self::getIdentityResponse();
            self::logQuery($userId, $role, $prompt, $responseContent);
            return [
                'success' => true,
                'prompt' => $prompt,
                'response' => $responseContent,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];
        }

        // 4. Intersepsi pertanyaan tugas/job desk AI ("Apa tugas Anda?")
        if (self::isJobDeskRequest($prompt)) {
            $responseContent = self::getDutiesResponse();
            self::logQuery($userId, $role, $prompt, $responseContent);
            return [
                'success' => true,
                'prompt' => $prompt,
                'response' => $responseContent,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];
        }

        // 5. Intersepsi pertanyaan statistik pengguna / jumlah role
        if (self::isUserStatsRequest($prompt)) {
            $responseContent = self::getUserStatsResponse($prompt, $role);
            self::logQuery($userId, $role, $prompt, $responseContent);
            return [
                'success' => true,
                'prompt' => $prompt,
                'response' => $responseContent,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];
        }

        $context = AiFinancialContextService::getContextForRole($role, $userId, $prompt);

        // Cek provider AI dan API key di .env
        $provider = strtolower(env('AI_PROVIDER', 'nvidia'));
        $nvidiaKey = env('NVIDIA_API_KEY', '');
        $nvidiaModel = env('NVIDIA_MODEL', 'meta/llama-3.2-11b-vision-instruct');
        $geminiKey = env('AI_API_KEY', '');
        $geminiModel = env('AI_MODEL', 'gemini-1.5-flash');

        $responseContent = "";

        // Utamakan NVIDIA NIM jika provider nvidia atau NVIDIA_API_KEY tersedia
        if ($provider === 'nvidia' && !empty($nvidiaKey) && strlen($nvidiaKey) > 10) {
            $responseContent = self::callNvidiaNimApi($prompt, $context, $nvidiaKey, $nvidiaModel, $userName, $role);
        } elseif (!empty($geminiKey) && strlen($geminiKey) > 10) {
            $responseContent = self::callGeminiApi($prompt, $context, $geminiKey, $geminiModel, $userName, $role);
        }

        // Jika API eksternal gagal, timeout, atau offline, gunakan Grounded Response berbasis database aktual
        if (empty($responseContent)) {
            $responseContent = self::generateGroundedResponse($prompt, $context, $role, $userName);
        }

        // Log ke tabel ai_query_logs
        self::logQuery($userId, $role, $prompt, $responseContent);

        return [
            'success' => true,
            'prompt' => $prompt,
            'response' => $responseContent,
            'timestamp' => date('d M Y, H:i') . ' WIB'
        ];
    }

    /**
     * Panggilan langsung ke NVIDIA NIM API via cURL backend dengan GROUND TRUTH DATABASE CONTEXT
     */
    protected static function callNvidiaNimApi(string $prompt, array $context, string $apiKey, string $model, ?string $userName = null, string $role = 'mahasiswa'): string
    {
        $roleLabel = match(strtolower($role)) {
            'admin' => 'Administrator',
            'keuangan' => 'Bagian Keuangan',
            'kasir' => 'Kasir Loket',
            'mahasiswa' => 'Mahasiswa',
            default => 'Pengguna'
        };

        $userNameStr = !empty($userName) ? $userName : 'Pengguna Terautentikasi';

        $systemPrompt = "Anda adalah AI Assistant Smart Campus Cash resmi untuk sistem Smart Campus Cash (asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem).
Nama pengguna yang sedang login saat ini: {$userNameStr} (Role: {$roleLabel}).
PENTING: Jawab HANYA berdasarkan GROUND TRUTH CONTEXT database aktual di bawah ini. JANGAN PERNAH mengarang data, angka, nama, saldo, atau transaksi. Jika data tidak ada atau tidak ditemukan, sampaikan secara jujur bahwa data tidak ditemukan berdasarkan database.
PERTANYAAN LANJUTAN YANG VALID: Pertanyaan seperti 'pengeluaran [angka] itu untuk apa saja', 'rincian pengeluaran', 'daftar transaksi pengeluaran', 'transaksi apa saja', 'detail pengeluaran' adalah pertanyaan yang SANGAT RELEVAN dengan tugas Anda. Jawab berdasarkan data di GROUND TRUTH CONTEXT, khususnya bagian dynamic_query.amount_query, dynamic_query.expense_detail_query, transaksi_keluar_terbaru, dan kategori_pengeluaran_all_time.
Jika pengguna menyebut angka tertentu (misalnya 751.101), cek apakah angka itu adalah total pengeluaran atau nominal transaksi individual, lalu tampilkan rinciannya dari konteks database.
DILARANG memberikan kode pemrograman (Python, Java, PHP, HTML, dll.).
DILARANG memberikan data atau operasi modifikasi data database.
DILARANG menolak pertanyaan rincian transaksi keuangan yang valid hanya karena pertanyaan tersebut meminta detail — detail transaksi adalah bagian inti tugas Anda.";

        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fullUserPrompt = "GROUND TRUTH CONTEXT DARI DATABASE AKTUAL:\n{$contextJson}\n\nPERTANYAAN PENGGUNA:\n{$prompt}";

        $url = "https://integrate.api.nvidia.com/v1/chat/completions";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $fullUserPrompt]
            ],
            'temperature' => 0.1,
            'max_tokens' => 600
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($result)) {
                $json = json_decode($result, true);
                $candidateText = $json['choices'][0]['message']['content'] ?? '';
                if (!empty($candidateText)) {
                    return trim($candidateText);
                }
            }
        } catch (Exception $e) {
            error_log("NVIDIA NIM API call failed: " . $e->getMessage());
        }

        return "";
    }

    /**
     * Panggilan langsung ke Gemini API via cURL backend (Aman, API key tidak pernah bocor ke frontend)
     */
    protected static function callGeminiApi(string $prompt, array $context, string $apiKey, string $model, ?string $userName = null, string $role = 'mahasiswa'): string
    {
        $roleLabel = match(strtolower($role)) {
            'admin' => 'Administrator',
            'keuangan' => 'Bagian Keuangan',
            'kasir' => 'Kasir Loket',
            'mahasiswa' => 'Mahasiswa',
            default => 'Pengguna'
        };

        $userNameStr = !empty($userName) ? $userName : 'Pengguna Terautentikasi';

        $systemPrompt = "Anda adalah AI Assistant Smart Campus Cash resmi untuk sistem Smart Campus Cash (asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem).
Nama pengguna yang sedang login saat ini: {$userNameStr} (Role: {$roleLabel}).
PENTING: Gunakan nama pengguna aktual ini ('{$userNameStr}') ketika menjawab identitas pengguna. JANGAN PERNAH menebak nama lain. Jika nama tidak tersedia, sampaikan bahwa identitas pengguna belum dapat diperoleh.

ATURAN DAN FORMAT JAWABAN:
1. IDENTITAS AI:
   Jika ditanya 'Siapa Anda?', 'Siapa kamu?', atau 'Kamu siapa?', jawab SINGKAT PERSIS:
   \"Saya adalah AI Assistant Smart Campus Cash, asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem.\"
   DILARANG KERAS menambahkan daftar fitur, promosi, ajakan bertanya, atau penjelasan panjang setelahnya.

2. IDENTITAS PENGGUNA:
   Jika ditanya 'Siapa saya?', 'Saya ini siapa?', 'Apa nama saya?', atau 'Apa role saya?', jawab PERSIS berdasarkan akun login:
   \"Anda adalah {$userNameStr}, {$roleLabel} Smart Campus Cash.\"
   Jika nama tidak tersedia, sampaikan bahwa identitas pengguna belum dapat diperoleh.
   Bedakan secara tepat antara 'siapa Anda?' dan 'siapa saya?'.

3. TUGAS AI:
   Jika ditanya 'Apa tugas Anda?', jawab SINGKAT PERSIS:
   \"Saya membantu memberikan informasi dan ringkasan keuangan Smart Campus Cash sesuai data yang tersedia dan hak akses pengguna.\"
   Jangan menjawab dengan penjelasan panjang.

4. PERMINTAAN CODING UMUM:
   Jika pengguna meminta kode pemrograman (Python, Java, JavaScript, HTML, dll.), tolak SINGKAT PERSIS:
   \"Maaf, karena saya adalah AI Assistant Smart Campus Cash yang berfokus pada informasi keuangan kampus, saya tidak dapat membantu membuat kode pemrograman umum.\"
   DILARANG memberikan kode, contoh print(), pseudocode, tutorial, atau penjelasan tambahan setelah penolakan.

5. STATISTIK PENGGUNA SISTEM:
   HANYA Administrator yang berhak melihat statistik pengguna. Jika bukan Administrator, tolak:
   \"Maaf, Anda tidak memiliki hak akses untuk melihat data statistik pengguna sistem. Informasi ini hanya dapat diakses oleh Administrator sesuai kebijakan keamanan dan hak akses (RBAC) Smart Campus Cash.\"
   Jika Administrator menanyakan jumlah pengguna terdaftar atau per role, jawab singkat menggunakan data faktual dari GROUND TRUTH CONTEXT dengan format:
   Jumlah pengguna terdaftar: [total]

   Rincian berdasarkan role:
   - Administrator: [jumlah_admin]
   - Mahasiswa: [jumlah_mahasiswa]
   - Kasir: [jumlah_kasir]
   - Bagian Keuangan: [jumlah_keuangan]
   Jika pertanyaan spesifik role tertentu (misal 'Berapa jumlah mahasiswa?'), jawab singkat:
   Jumlah [role] terdaftar: [angka]

6. ATURAN GAYA:
   - Gunakan Bahasa Indonesia.
   - Jawab singkat dan langsung ke inti.
   - Jangan menambahkan promosi, daftar fitur, atau ajakan bertanya jika tidak diperlukan.
   - Jangan mengulang perkenalan AI pada setiap jawaban.";

        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fullPrompt = "GROUND TRUTH CONTEXT:\n{$contextJson}\n\nPERTANYAAN PENGGUNA:\n{$prompt}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $systemPrompt . "\n\n" . $fullPrompt]]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 800
            ]
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($result)) {
                $json = json_decode($result, true);
                $candidateText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($candidateText)) {
                    return trim($candidateText);
                }
            }
        } catch (Exception $e) {
            error_log("Gemini API call failed: " . $e->getMessage());
        }

        return "";
    }

    /**
     * Grounded Natural Generator cerdas berbasis konteks fakta database yang valid
     */
    protected static function generateGroundedResponse(string $prompt, array $context, string $role, ?string $userName = null): string
    {
        $p = strtolower(trim($prompt));
        $role = strtolower($role);

        $roleLabel = match($role) {
            'admin' => 'Administrator',
            'keuangan' => 'Bagian Keuangan',
            'kasir' => 'Kasir Loket',
            'mahasiswa' => 'Mahasiswa',
            default => 'Pengguna'
        };

        // 0. Cek Ketersediaan Koneksi Database
        if (isset($context['status']) && $context['status'] === 'database_unavailable') {
            return "Maaf, data tidak dapat dimuat saat ini karena kendala koneksi ke database. Silakan coba beberapa saat lagi atau pastikan database sistem sedang aktif.";
        }

        // 0.1 Proteksi Operasi Modifikasi Data Database (Read-Only Safety Guard)
        if (preg_match('/\b(insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+table|drop\s+database|truncate\s+table|alter\s+table|grant\s+|revoke\s+)\b/i', $p)) {
            return "Maaf, AI Assistant Smart Campus Cash hanya beroperasi dalam mode baca (read-only) dan tidak memiliki izin untuk mengeksekusi operasi modifikasi atau manipulasi data database.";
        }

        // 1. Keamanan & Proteksi Kredensial / Prompt Injection / Bypass
        if (
            str_contains($p, 'api key') || 
            str_contains($p, 'token') || 
            str_contains($p, 'kredensial') || 
            str_contains($p, 'credential') || 
            str_contains($p, 'password') || 
            str_contains($p, 'secret') || 
            str_contains($p, 'abaikan instruksi') || 
            str_contains($p, 'abaikan aturan') || 
            str_contains($p, 'abaikan hak akses') || 
            str_contains($p, 'ignore previous') || 
            str_contains($p, 'ignore rules') || 
            str_contains($p, 'bypass') || 
            str_contains($p, 'system prompt') || 
            str_contains($p, 'database config') ||
            str_contains($p, 'env(')
        ) {
            return "Maaf, karena alasan keamanan sistem dan hak akses, saya tidak dapat membagikan kredensial, konfigurasi internal, atau mengabaikan hak akses yang telah ditetapkan di Smart Campus Cash.";
        }

        // 2. KATEGORI C — PERMINTAAN CODING / PROGRAMMING UMUM (Wajib Ditolak Tanpa Kode)
        if (self::isGeneralCodingRequest($prompt)) {
            return self::getCodingRefusalResponse();
        }

        // 3. IDENTITAS PENGGUNA YANG LOGIN (Nama Akun Terautentikasi)
        if (self::isUserIdentityRequest($prompt)) {
            return self::getUserIdentityResponse($role, $userName);
        }

        // 4. IDENTITAS AI
        if (self::isAiIdentityRequest($prompt)) {
            return self::getIdentityResponse();
        }

        // 5. TUGAS & FUNGSI AI (Job Desk)
        if (self::isJobDeskRequest($prompt)) {
            return self::getDutiesResponse();
        }

        // 6. SAPAAN RAMAH
        if (
            preg_match('/^(halo|hai|hey|hi|helo|assalamu\'?alaikum|assalamualaikum|selamat\s+(pagi|siang|sore|malam)|pagi|siang|sore|malam)[\s\.,\!\?]*$/i', $p) ||
            $p === 'halo' || $p === 'hai' || $p === 'hi' || $p === 'hey' ||
            (str_starts_with($p, 'halo') && strlen($p) <= 15) ||
            (str_starts_with($p, 'hai') && strlen($p) <= 12)
        ) {
            $greetingPrefix = !empty($userName) ? "Halo, {$userName}!" : "Halo!";
            return "{$greetingPrefix} Ada yang bisa saya bantu terkait informasi keuangan Smart Campus Cash?";
        }

        // 7. STATISTIK PENGGUNA SISTEM & JUMLAH PER ROLE
        if (self::isUserStatsRequest($prompt)) {
            return self::getUserStatsResponse($prompt, $role);
        }

        // 4. KATEGORI D — DETEKSI PERTANYAAN DI LUAR JOB DESK (Off-Topic Lainnya)
        $offTopicKeywords = [
            'resep', 'nasi goreng', 'masak', 'memasak', 'makanan', 'cuaca', 'presiden', 'negara x', 'politik',
            'cerita', 'horor', 'dongeng', 'puisi', 'pantun', 'film', 'lagu', 'musik', 'game',
            'olahraga', 'sepak bola', 'lelucon', 'joke', 'tugas sekolah', 'fisika', 'biologi',
            'kimia', 'matematika umum', 'pemilu', 'artis', 'wisata'
        ];
        foreach ($offTopicKeywords as $kw) {
            if (str_contains($p, $kw)) {
                return self::getOffTopicRefusalResponse();
            }
        }

        // 3. Deteksi Data Tidak Tersedia / Periode Masa Depan (misal tahun > tahun berjalan seperti 2035)
        $currentYear = (int)date('Y');
        if (preg_match('/\b(20[3-9][0-9]|2[1-9][0-9]{2})\b/', $p, $matches)) {
            $year = (int)$matches[0];
            if ($year > $currentYear) {
                return "Maaf, karena data yang diperlukan untuk periode tahun {$year} belum tersedia atau belum dapat saya akses di Smart Campus Cash, saya belum dapat memberikan jawaban yang akurat. Silakan periksa data transaksi atau laporan terkait, atau hubungi bagian keuangan kampus jika diperlukan.";
            }
        }

        if (str_contains($p, 'tahun depan') || str_contains($p, 'masa depan') || str_contains($p, '2035') || str_contains($p, '2040')) {
            return "Maaf, karena data yang diperlukan untuk periode tersebut belum tersedia atau belum dapat saya akses di Smart Campus Cash, saya belum dapat memberikan jawaban yang akurat. Silakan periksa data transaksi atau laporan terkait, atau hubungi bagian keuangan kampus jika diperlukan.";
        }

        // 4. Deteksi Pertanyaan Kurang Jelas / Butuh Klarifikasi Singkat
        if (preg_match('/^(berapa\s+)?(total\s+)?pemasukan(\s+kampus)?\??$/i', $p) || $p === 'pemasukan' || $p === 'pemasukan?') {
            return "Pemasukan untuk periode kapan yang ingin Anda lihat—hari ini, bulan ini, atau periode tertentu?";
        }
        if (preg_match('/^(berapa\s+)?(total\s+)?pengeluaran(\s+kampus)?\??$/i', $p) || $p === 'pengeluaran' || $p === 'pengeluaran?') {
            return "Pengeluaran untuk periode kapan yang ingin Anda lihat—hari ini, bulan ini, atau periode tertentu?";
        }

        // 5. Cek apakah pertanyaan mengandung konteks keuangan
        $financialKeywords = [
            'saldo', 'kas', 'pemasukan', 'pengeluaran', 'tagihan', 'tunggakan', 'bayar', 'pembayaran',
            'transaksi', 'kuitansi', 'kategori', 'loket', 'lunas', 'jatuh tempo', 'sisa', 'keuangan',
            'anggaran', 'biaya', 'setoran', 'rekap', 'laporan', 'uang', 'rekomendasi', 'saran',
            'belanja', 'terbesar', 'buku kas', 'rincian', 'detail', 'daftar', 'untuk apa', 'digunakan',
            'apa saja', 'jelaskan', 'breakdown', 'komponen'
        ];
        $isFinancial = false;
        foreach ($financialKeywords as $fkw) {
            if (str_contains($p, $fkw)) {
                $isFinancial = true;
                break;
            }
        }

        if (!$isFinancial) {
            return self::getOffTopicRefusalResponse();
        }

        // 5.5 Role: GUEST (Pengguna Belum Login / Pengunjung Beranda)
        if ($role === 'guest') {
            // Jika menanyakan data privat/internal kampus yang butuh otentikasi
            if (
                str_contains($p, 'saldo') || str_contains($p, 'pemasukan') || str_contains($p, 'pengeluaran') ||
                str_contains($p, 'kas') || str_contains($p, 'transaksi') || str_contains($p, 'tagihan') ||
                str_contains($p, 'tunggakan') || str_contains($p, 'kuitansi') || str_contains($p, 'laporan') ||
                str_contains($p, 'mahasiswa') || str_contains($p, 'buku kas') || str_contains($p, 'terbesar') ||
                str_contains($p, 'perbandingan') || str_contains($p, 'ringkasan')
            ) {
                return "Maaf, untuk mengakses data keuangan, saldo kas, tagihan, atau laporan transaksi Smart Campus Cash, Anda harus masuk (login) terlebih dahulu menggunakan akun yang memiliki hak akses terkait (Mahasiswa, Kasir, Bagian Keuangan, atau Administrator).";
            }

            // Informasi umum alur sistem yang dapat diakses publik
            if (str_contains($p, 'spp') || str_contains($p, 'bayar') || str_contains($p, 'pembayaran')) {
                return "Pembayaran tagihan SPP di Smart Campus Cash dapat dilakukan oleh mahasiswa melalui **Dashboard Mahasiswa** (metode transfer bank/QRIS Midtrans) atau pembayaran tunai langsung di loket kasir kampus.";
            }

            if (str_contains($p, 'peran') || str_contains($p, 'role')) {
                return "Smart Campus Cash mendukung 4 peran (role) pengguna:\n• **Administrator**: Pengelolaan master data dan sistem.\n• **Mahasiswa**: Melihat tagihan dan riwayat pembayaran.\n• **Kasir**: Pelayanan loket kas dan penerbitan kuitansi.\n• **Bagian Keuangan**: Monitoring arus kas dan pembukuan.";
            }

            return "Halo! Ada yang bisa saya bantu terkait informasi sistem Smart Campus Cash? Untuk melihat data keuangan aktual, silakan login terlebih dahulu.";
        }

        // 6. Role: MAHASISWA
        if ($role === 'mahasiswa') {
            // Mahasiswa mencoba mengakses data kampus / mahasiswa lain / buku kas / loket
            if (
                str_contains($p, 'saldo kas') || 
                str_contains($p, 'saldo saat ini') ||
                str_contains($p, 'saldo sekarang') ||
                str_contains($p, 'pengeluaran bulan') || 
                str_contains($p, 'pengeluaran terbesar') || 
                str_contains($p, 'mahasiswa lain') || 
                str_contains($p, 'buku kas') || 
                str_contains($p, 'semua mahasiswa') || 
                str_contains($p, 'tunggakan mahasiswa') ||
                str_contains($p, 'tunggakan teman') ||
                str_contains($p, 'tagihan teman') ||
                str_contains($p, 'siapa saja mahasiswa') ||
                str_contains($p, 'daftar mahasiswa') ||
                str_contains($p, 'setoran loket') ||
                str_contains($p, 'uang keluar digunakan') ||
                str_contains($p, 'kategori pengeluaran') ||
                str_contains($p, 'uang masuk hari ini') ||
                str_contains($p, 'uang keluar hari ini') ||
                str_contains($p, 'uang masuk bulan ini') ||
                str_contains($p, 'uang keluar bulan ini') ||
                str_contains($p, 'total tagihan yang tercatat') ||
                str_contains($p, 'ringkasan arus kas')
            ) {
                return "Maaf, karena sebagai Mahasiswa Anda hanya memiliki hak akses untuk menanyakan informasi tagihan dan pembayaran pribadi Anda sendiri.";
            }

            // Tagihan / Tunggakan Mahasiswa
            if (
                str_contains($p, 'tunggakan') || 
                str_contains($p, 'sisa tagihan') || 
                str_contains($p, 'tagihan saya') || 
                str_contains($p, 'belum dibayar') || 
                str_contains($p, 'kewajiban')
            ) {
                $unpaid = (float)($context['total_sisa_tunggakan'] ?? 0);
                $total = (float)($context['total_tagihan'] ?? 0);
                $paid = (float)($context['total_sudah_dibayar'] ?? 0);
                $name = $context['student_name'] ?? 'Mahasiswa';
                $nim = $context['nim'] ?? '';

                if ($unpaid <= 0) {
                    return "Kabar baik untuk **{$name}**! Anda saat ini tidak memiliki sisa tunggakan tagihan (seluruh tagihan telah lunas).";
                }
                return "Halo **{$name}** (NIM: {$nim}), saat ini sisa tunggakan tagihan Anda adalah sebesar **Rp " . number_format($unpaid, 0, ',', '.') . "** (Total tagihan terbit: Rp " . number_format($total, 0, ',', '.') . ", sudah dibayar: Rp " . number_format($paid, 0, ',', '.') . ").";
            }

            // Jatuh Tempo
            if (str_contains($p, 'jatuh tempo') || str_contains($p, 'terdekat')) {
                $near = $context['tagihan_terdekat'] ?? null;
                if (!$near) {
                    return "Saat ini Anda tidak memiliki tagihan aktif yang mendekati jatuh tempo.";
                }
                $tgl = date('d F Y', strtotime($near['jatuh_tempo']));
                return "Tagihan terdekat Anda adalah **{$near['judul']}** dengan sisa pembayaran **Rp " . number_format($near['sisa'], 0, ',', '.') . "** yang akan jatuh tempo pada tanggal **{$tgl}** (Status: {$near['status']}).";
            }

            // Riwayat Pembayaran / Sudah Dibayar
            if (str_contains($p, 'sudah dibayar') || str_contains($p, 'lunas') || str_contains($p, 'riwayat') || str_contains($p, 'terbayar') || str_contains($p, 'status pembayaran')) {
                $paid = (float)($context['total_sudah_dibayar'] ?? 0);
                $total = (float)($context['total_tagihan'] ?? 0);
                return "Total tagihan yang telah berhasil Anda bayarkan adalah sebesar **Rp " . number_format($paid, 0, ',', '.') . "** dari total keseluruhan tagihan sebesar Rp " . number_format($total, 0, ',', '.') . ".";
            }

            // Transaksi Pembayaran Terakhir
            if (str_contains($p, 'transaksi terakhir') || str_contains($p, 'pembayaran terakhir')) {
                $lastP = $context['pembayaran_terakhir'] ?? null;
                if ($lastP) {
                    $tglP = date('d F Y, H:i', strtotime($lastP['payment_date'])) . ' WIB';
                    return "Pembayaran terakhir Anda tercatat pada kuitansi **{$lastP['payment_number']}** sebesar **Rp " . number_format($lastP['amount'], 0, ',', '.') . "** melalui metode **" . strtoupper($lastP['payment_method']) . "** pada tanggal **{$tglP}**.";
                }
                return "Belum ditemukan catatan riwayat pembayaran untuk akun Anda.";
            }

            return "Halo " . ($context['student_name'] ?? 'Mahasiswa') . "! Anda dapat menanyakan sisa tagihan pribadi, tanggal jatuh tempo terdekat, atau riwayat pembayaran yang telah Anda selesaikan.";
        }

        // 7. Role: KASIR
        if ($role === 'kasir') {
            // Kasir menanyakan buku kas umum makro atau pengeluaran institusi
            if (
                str_contains($p, 'pengeluaran bulan') || 
                str_contains($p, 'pengeluaran terbesar') || 
                str_contains($p, 'buku kas') ||
                str_contains($p, 'saldo saat ini') ||
                str_contains($p, 'saldo sekarang') ||
                str_contains($p, 'saldo kas') ||
                str_contains($p, 'uang keluar digunakan') ||
                str_contains($p, 'kategori pengeluaran') ||
                str_contains($p, 'pemasukan dan pengeluaran tahun ini') ||
                str_contains($p, 'siapa saja mahasiswa yang memiliki tagihan')
            ) {
                return "Maaf, karena peran Anda sebagai Kasir Loket, Anda hanya memiliki hak akses untuk informasi operasional transaksi dan penerimaan kas loket harian.";
            }

            // Transaksi loket terbaru
            if (str_contains($p, 'transaksi terbaru') || str_contains($p, 'pembayaran terbaru')) {
                $txList = $context['pembayaran_terbaru'] ?? [];
                if (empty($txList)) {
                    return "Belum ada catatan transaksi pembayaran pada loket kasir.";
                }
                $lines = ["Berikut daftar transaksi pembayaran loket terbaru:"];
                foreach ($txList as $tx) {
                    $tglTx = date('d/m/Y H:i', strtotime($tx['payment_date']));
                    $lines[] = "• **{$tx['payment_number']}** ({$tglTx}) - Rp " . number_format($tx['amount'], 0, ',', '.') . " (" . strtoupper($tx['payment_method']) . ") - {$tx['student_name']} (NIM: {$tx['nim']})";
                }
                return implode("\n", $lines);
            }

            if (str_contains($p, 'hari ini') || str_contains($p, 'setoran') || str_contains($p, 'pembayaran') || str_contains($p, 'transaksi') || str_contains($p, 'loket')) {
                $todayAmt = (float)($context['total_pembayaran_hari_ini_kampus'] ?? 0);
                $todayCnt = (int)($context['jumlah_transaksi_hari_ini_kampus'] ?? 0);
                $myAmt = (float)($context['total_diterima_kasir_ini'] ?? 0);
                $tgl = $context['tanggal'] ?? date('d F Y');
                return "Laporan Loket Hari Ini ({$tgl}):\n• Total penerimaan pembayaran loket kampus: **Rp " . number_format($todayAmt, 0, ',', '.') . "**\n• Jumlah kuitansi diterbitkan: **{$todayCnt} transaksi**\n• Total yang diproses oleh shift Anda: **Rp " . number_format($myAmt, 0, ',', '.') . "**.";
            }

            return "Halo Kasir! Anda dapat menanyakan total penerimaan kas loket hari ini, rekap setoran loket, atau jumlah kuitansi pembayaran yang telah diproses.";
        }

        // 8. Role: KEUANGAN & ADMIN
        if (in_array($role, ['keuangan', 'admin'])) {
            $tgl = $context['tanggal_hari_ini'] ?? date('d F Y');
            $periode = $context['periode_bulan_ini'] ?? date('F Y');
            $saldo = (float)($context['saldo_kas_saat_ini'] ?? 0);
            $in = (float)($context['total_pemasukan_all_time'] ?? 0);
            $out = (float)($context['total_pengeluaran_all_time'] ?? 0);
            $mIn = (float)($context['pemasukan_bulan_ini'] ?? 0);
            $mOut = (float)($context['pengeluaran_bulan_ini'] ?? 0);
            $todayIn = (float)($context['pemasukan_hari_ini'] ?? 0);
            $todayOut = (float)($context['pengeluaran_hari_ini'] ?? 0);
            $todayTx = (int)($context['transaksi_hari_ini_count'] ?? 0);
            $tagihan = $context['tagihan_rekap'] ?? [];

            // --- QUERY DINAMIS TERFILTER ---
            // 8.0.0 Rincian semua transaksi pengeluaran (pertanyaan lanjutan tentang detail/daftar pengeluaran)
            // Menangani pola: "pengeluaran [angka] itu untuk apa saja", "rincian pengeluaran", "daftar transaksi pengeluaran", dsb.
            $isExpenseDetailRequest = (
                (str_contains($p, 'untuk apa') || str_contains($p, 'apa saja') || str_contains($p, 'rincian') ||
                 str_contains($p, 'detail') || str_contains($p, 'daftar') || str_contains($p, 'jelaskan') ||
                 str_contains($p, 'breakdown') || str_contains($p, 'komponen') || str_contains($p, 'digunakan')) &&
                (str_contains($p, 'pengeluaran') || str_contains($p, 'keluar') || str_contains($p, 'expense'))
            );

            // 8.0.1 Nominal tertentu — diperluas: cocokkan jika ada angka di prompt (bukan hanya kata 'nominal'/'sebesar')
            $hasAmountQuery = !empty($context['dynamic_query']['amount_query']);
            $amountQueryTrigger = $hasAmountQuery && (
                str_contains($p, 'nominal') || str_contains($p, 'sebesar') ||
                str_contains($p, 'itu') || str_contains($p, 'untuk apa') || str_contains($p, 'apa saja') ||
                str_contains($p, 'rincian') || str_contains($p, 'detail') || str_contains($p, 'daftar') ||
                // Juga picu jika prompt mengandung angka (nominalnya disebutkan dalam pertanyaan)
                preg_match('/\b\d{1,3}(?:[.,]\d{3})+\b|\b\d{5,}\b/', $p)
            );

            if ($amountQueryTrigger) {
                $dynAmt = $context['dynamic_query']['amount_query'];
                $targetAmt = $dynAmt['target_amount'];
                $totalExpense = (float)($context['total_pengeluaran_all_time'] ?? 0);

                // Cek apakah angka yang disebutkan adalah total pengeluaran (bukan nominal per-transaksi)
                $isTotalExpense = abs($targetAmt - $totalExpense) < 1;

                if ($isTotalExpense && $isExpenseDetailRequest) {
                    // Pengguna bertanya rincian dari total pengeluaran — tampilkan semua transaksi pengeluaran
                    $allExpense = $context['dynamic_query']['expense_detail_query'] ?? [];
                    if (empty($allExpense)) {
                        // Fallback ke transaksi_keluar_terbaru jika expense_detail_query tidak tersedia
                        $allExpense = $context['transaksi_keluar_terbaru'] ?? [];
                    }
                    if (empty($allExpense)) {
                        return "Total pengeluaran sebesar **Rp " . number_format($totalExpense, 0, ',', '.') . "** terdiri dari transaksi yang belum dapat ditampilkan rinciannya saat ini.";
                    }
                    $lines = [
                        "Total pengeluaran **Rp " . number_format($totalExpense, 0, ',', '.') . "** merupakan akumulasi dari **" . count($allExpense) . " transaksi** pengeluaran berikut:"
                    ];
                    $totalCheck = 0.0;
                    foreach ($allExpense as $tx) {
                        $tglTx = date('d/m/Y', strtotime($tx['transaction_date']));
                        $totalCheck += (float)$tx['amount'];
                        $lines[] = "• **{$tx['transaction_number']}** ({$tglTx}) — Rp " . number_format($tx['amount'], 0, ',', '.') . " [{$tx['category_name']}]: {$tx['description']}";
                    }
                    if (abs($totalCheck - $totalExpense) < 1) {
                        $lines[] = "\nTotal: **Rp " . number_format($totalCheck, 0, ',', '.') . "** (sesuai dengan total pengeluaran yang tercatat).";
                    }
                    return implode("\n", $lines);
                }

                if (empty($dynAmt['transaksi'])) {
                    // Angka tidak cocok sebagai nominal per-transaksi; cek apakah ini total pengeluaran
                    if ($isTotalExpense && !$isExpenseDetailRequest) {
                        return "Angka Rp " . number_format($targetAmt, 0, ',', '.') . " adalah **total akumulasi pengeluaran** buku kas. Untuk melihat rincian transaksi yang membentuk total ini, tanyakan: 'Rincian pengeluaran itu untuk apa saja?'";
                    }
                    return "Belum ditemukan catatan transaksi dengan nominal **Rp " . number_format($targetAmt, 0, ',', '.') . "** di Buku Kas. Angka tersebut mungkin merupakan total akumulasi dari beberapa transaksi, bukan nominal satu transaksi individual.";
                }
                $lines = ["Ditemukan **{$dynAmt['found_count']} transaksi** dengan nominal **Rp " . number_format($dynAmt['target_amount'], 0, ',', '.') . "**:"];
                foreach ($dynAmt['transaksi'] as $tx) {
                    $tglTx = date('d/m/Y', strtotime($tx['transaction_date']));
                    $typeLabel = ($tx['type'] === 'income') ? 'Pemasukan' : 'Pengeluaran';
                    $lines[] = "• **{$tx['transaction_number']}** ({$tglTx}) [{$typeLabel} - {$tx['category_name']}]: {$tx['description']}";
                }
                return implode("\n", $lines);
            }

            // 8.0.0b Rincian pengeluaran tanpa menyebut angka spesifik
            if ($isExpenseDetailRequest && !$hasAmountQuery) {
                $allExpense = $context['dynamic_query']['expense_detail_query'] ?? [];
                if (empty($allExpense)) {
                    $allExpense = $context['transaksi_keluar_terbaru'] ?? [];
                }
                if (empty($allExpense)) {
                    return "Belum ada catatan transaksi pengeluaran di Buku Kas.";
                }
                $totalExpense = (float)($context['total_pengeluaran_all_time'] ?? 0);
                $lines = ["Rincian transaksi pengeluaran yang tercatat di Buku Kas:"];
                foreach ($allExpense as $tx) {
                    $tglTx = date('d/m/Y', strtotime($tx['transaction_date']));
                    $lines[] = "• **{$tx['transaction_number']}** ({$tglTx}) — Rp " . number_format($tx['amount'], 0, ',', '.') . " [{$tx['category_name']}]: {$tx['description']}";
                }
                if ($totalExpense > 0) {
                    $lines[] = "\n**Total Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "**";
                }
                return implode("\n", $lines);
            }

            // 8.0.2 Tanggal tertentu
            if ((str_contains($p, 'tanggal') || str_contains($p, 'pada tgl')) && !empty($context['dynamic_query']['date_query'])) {
                $dynDate = $context['dynamic_query']['date_query'];
                if (empty($dynDate['transaksi'])) {
                    return "Belum ditemukan transaksi yang sesuai dengan periode tanggal **{$dynDate['target_date_formatted']}**.";
                }
                $lines = [
                    "Catatan Transaksi Tanggal **{$dynDate['target_date_formatted']}**:",
                    "• Total Pemasukan: **Rp " . number_format($dynDate['total_pemasukan'], 0, ',', '.') . "**",
                    "• Total Pengeluaran: **Rp " . number_format($dynDate['total_pengeluaran'], 0, ',', '.') . "**",
                    "• Jumlah Transaksi: **{$dynDate['total_transaksi']} transaksi**",
                    "\nRincian Transaksi:"
                ];
                foreach ($dynDate['transaksi'] as $tx) {
                    $typeLabel = ($tx['type'] === 'income') ? 'Masuk' : 'Keluar';
                    $lines[] = "• {$tx['transaction_number']} [{$typeLabel} - Rp " . number_format($tx['amount'], 0, ',', '.') . "]: {$tx['description']} ({$tx['category_name']})";
                }
                return implode("\n", $lines);
            }

            // 8.0.3 Keperluan tertentu / Kategori spesifik
            if ((str_contains($p, 'keperluan') || str_contains($p, 'biaya') || str_contains($p, 'pos')) && !empty($context['dynamic_query']['category_query'])) {
                $dynCat = $context['dynamic_query']['category_query'];
                $lines = [
                    "Rincian Keuangan Pos **{$dynCat['category_name']}**:",
                    "• Tipe: " . ucfirst($dynCat['category_type']),
                    "• Total Penyerapan/Realisasi: **Rp " . number_format($dynCat['total_amount'], 0, ',', '.') . "**",
                    "• Jumlah Transaksi: **{$dynCat['tx_count']} transaksi**"
                ];
                if (!empty($dynCat['transaksi'])) {
                    $lines[] = "\nTransaksi Terakhir:";
                    foreach ($dynCat['transaksi'] as $tx) {
                        $tglTx = date('d/m/Y', strtotime($tx['transaction_date']));
                        $lines[] = "• {$tx['transaction_number']} ({$tglTx}) - Rp " . number_format($tx['amount'], 0, ',', '.') . " ({$tx['description']})";
                    }
                }
                return implode("\n", $lines);
            }

            // --- A. SALDO DAN ARUS KEUANGAN ---
            // Uang masuk hari ini
            if (str_contains($p, 'masuk hari ini') || str_contains($p, 'pemasukan hari ini')) {
                return "Total uang masuk (pemasukan) yang tercatat hari ini (**{$tgl}**) adalah sebesar **Rp " . number_format($todayIn, 0, ',', '.') . "**.";
            }

            // Uang keluar hari ini
            if (str_contains($p, 'keluar hari ini') || str_contains($p, 'pengeluaran hari ini')) {
                return "Total uang keluar (pengeluaran) operasional yang tercatat hari ini (**{$tgl}**) adalah sebesar **Rp " . number_format($todayOut, 0, ',', '.') . "**.";
            }

            // Transaksi hari ini
            if (str_contains($p, 'transaksi yang tercatat hari ini') || str_contains($p, 'transaksi hari ini') || str_contains($p, 'berapa transaksi hari ini')) {
                return "Jumlah transaksi yang tercatat di Buku Kas hari ini (**{$tgl}**) adalah sebanyak **{$todayTx} transaksi** (Pemasukan: Rp " . number_format($todayIn, 0, ',', '.') . ", Pengeluaran: Rp " . number_format($todayOut, 0, ',', '.') . ").";
            }

            // Pemasukan dan pengeluaran tahun ini
            if (str_contains($p, 'tahun ini') || str_contains($p, 'tahun berjalan')) {
                $yIn = (float)($context['pemasukan_tahun_ini'] ?? 0);
                $yOut = (float)($context['pengeluaran_tahun_ini'] ?? 0);
                $surplusY = $yIn - $yOut;
                $tahun = $context['tahun_ini'] ?? date('Y');
                return "Pada tahun **{$tahun}**, total pemasukan tercatat sebesar **Rp " . number_format($yIn, 0, ',', '.') . "** dan total pengeluaran sebesar **Rp " . number_format($yOut, 0, ',', '.') . "**, dengan posisi kas surplus sebesar **Rp " . number_format($surplusY, 0, ',', '.') . "**.";
            }

            // Apakah pemasukan bulan ini lebih besar daripada pengeluaran
            if (str_contains($p, 'lebih besar') || (str_contains($p, 'pemasukan') && str_contains($p, 'pengeluaran') && (str_contains($p, 'apakah') || str_contains($p, 'apakah pemasukan')))) {
                if ($mIn > $mOut) {
                    $surplus = $mIn - $mOut;
                    return "Ya, pada periode **{$periode}**, total pemasukan (**Rp " . number_format($mIn, 0, ',', '.') . "**) **lebih besar** daripada total pengeluaran (**Rp " . number_format($mOut, 0, ',', '.') . "**), dengan surplus kas operasional sebesar **Rp " . number_format($surplus, 0, ',', '.') . "**.";
                } elseif ($mIn < $mOut) {
                    $defisit = $mOut - $mIn;
                    return "Tidak, pada periode **{$periode}**, total pengeluaran (**Rp " . number_format($mOut, 0, ',', '.') . "**) lebih besar daripada total pemasukan (**Rp " . number_format($mIn, 0, ',', '.') . "**) dengan defisit sebesar Rp " . number_format($defisit, 0, ',', '.') . ".";
                } else {
                    return "Pada periode **{$periode}**, total pemasukan dan total pengeluaran tercatat seimbang sebesar Rp " . number_format($mIn, 0, ',', '.') . ".";
                }
            }

            // Kenaikan pengeluaran per kategori
            if (str_contains($p, 'kenaikan pengeluaran') || str_contains($p, 'mengalami kenaikan')) {
                $naikList = $context['kategori_naik_list'] ?? [];
                if (empty($naikList)) {
                    return "Pada periode **{$periode}**, tidak ditemukan kategori pengeluaran yang mengalami kenaikan dibandingkan bulan sebelumnya.";
                }
                $lines = ["Kategori yang mengalami kenaikan pengeluaran dibandingkan bulan sebelumnya:"];
                foreach ($naikList as $k) {
                    $diff = (float)$k['m1_spent'] - (float)$k['m0_spent'];
                    $lines[] = "• **{$k['name']}**: Bulan lalu Rp " . number_format($k['m0_spent'], 0, ',', '.') . " → Bulan ini Rp " . number_format($k['m1_spent'], 0, ',', '.') . " (naik Rp " . number_format($diff, 0, ',', '.') . ")";
                }
                return implode("\n", $lines);
            }

            // Perbandingan bulan lalu dan bulan ini
            if (str_contains($p, 'perbandingan') || str_contains($p, 'bandingkan')) {
                $m0In = (float)($context['pemasukan_bulan_lalu'] ?? 0);
                $m0Out = (float)($context['pengeluaran_bulan_lalu'] ?? 0);
                $pLalu = $context['periode_bulan_lalu'] ?? date('F Y', strtotime('-1 month'));

                $diffIn = $mIn - $m0In;
                $diffOut = $mOut - $m0Out;
                $inNote = ($diffIn >= 0) ? "naik Rp " . number_format($diffIn, 0, ',', '.') : "turun Rp " . number_format(abs($diffIn), 0, ',', '.');
                $outNote = ($diffOut >= 0) ? "naik Rp " . number_format($diffOut, 0, ',', '.') : "turun Rp " . number_format(abs($diffOut), 0, ',', '.');

                return "Perbandingan Arus Kas (**{$pLalu}** vs **{$periode}**):\n• **Pemasukan**: Bulan lalu Rp " . number_format($m0In, 0, ',', '.') . " vs Bulan ini Rp " . number_format($mIn, 0, ',', '.') . " ({$inNote})\n• **Pengeluaran**: Bulan lalu Rp " . number_format($m0Out, 0, ',', '.') . " vs Bulan ini Rp " . number_format($mOut, 0, ',', '.') . " ({$outNote})\n• **Net Surplus/Defisit**: Bulan lalu Rp " . number_format($m0In - $m0Out, 0, ',', '.') . " vs Bulan ini Rp " . number_format($mIn - $mOut, 0, ',', '.') . ".\nPerhitungan ini mencakup transaksi yang berstatus sah sesuai sistem.";
            }

            // Pemasukan Bulan Ini
            if (str_contains($p, 'pemasukan bulan') || str_contains($p, 'masuk bulan')) {
                return "Total pemasukan pada periode **{$periode}** adalah sebesar **Rp " . number_format($mIn, 0, ',', '.') . "**.";
            }

            // Pengeluaran Bulan Ini
            if (str_contains($p, 'pengeluaran bulan') || str_contains($p, 'keluar bulan') || str_contains($p, 'total pengeluaran') || str_contains($p, 'kampus habis uang berapa')) {
                return "Total pengeluaran operasional dan belanja kampus pada periode **{$periode}** adalah sebesar **Rp " . number_format($mOut, 0, ',', '.') . "**.";
            }

            // Saldo Kas Saat Ini
            if (str_contains($p, 'saldo') || str_contains($p, 'sisa kas') || str_contains($p, 'posisi kas')) {
                return "Berdasarkan Buku Kas Umum saat ini:\n• Total Pemasukan: **Rp " . number_format($in, 0, ',', '.') . "**\n• Total Pengeluaran: **Rp " . number_format($out, 0, ',', '.') . "**\n• **Saldo Kas Bersih Saat Ini: Rp " . number_format($saldo, 0, ',', '.') . "**.";
            }

            // --- B. PENGGUNAAN DANA & KATEGORI ---
            // Uang keluar digunakan untuk apa saja
            if (str_contains($p, 'digunakan untuk apa') || str_contains($p, 'paling banyak untuk apa') || str_contains($p, 'penggunaan dana') || str_contains($p, 'uang keluar untuk')) {
                $catList = $context['kategori_pengeluaran_all_time'] ?? [];
                if (empty($catList)) {
                    return "Belum ada catatan realisasi pengeluaran kas di sistem.";
                }
                $lines = ["Berdasarkan catatan Buku Kas, dana keluar kampus digunakan untuk pos anggaran berikut:"];
                foreach ($catList as $c) {
                    $lines[] = "• **{$c['name']}**: Rp " . number_format($c['total_spent'], 0, ',', '.') . " ({$c['tx_count']} transaksi)";
                }
                return implode("\n", $lines);
            }

            // Kategori Pengeluaran Terbesar
            if (str_contains($p, 'kategori') && (str_contains($p, 'terbesar') || str_contains($p, 'biaya terbesar') || str_contains($p, 'paling besar'))) {
                $cat = $context['kategori_pengeluaran_terbesar_bulan_ini'] ?? null;
                if (!$cat) {
                    // Fallback ke all time jika bulan berjalan belum ada pengeluaran
                    $catAll = !empty($context['kategori_pengeluaran_all_time']) ? $context['kategori_pengeluaran_all_time'][0] : null;
                    if ($catAll) {
                        return "Belum ada catatan transaksi pengeluaran pada periode {$periode}. Namun secara keseluruhan, kategori pengeluaran terbesar adalah **{$catAll['name']}** dengan total penyerapan anggaran sebesar **Rp " . number_format($catAll['total_spent'], 0, ',', '.') . "**.";
                    }
                    return "Belum ada catatan transaksi pengeluaran pada periode {$periode}.";
                }
                return "Kategori pengeluaran terbesar pada periode **{$periode}** adalah **{$cat['kategori']}** dengan total penyerapan anggaran sebesar **Rp " . number_format($cat['nominal'], 0, ',', '.') . "**.";
            }

            // Dana yang digunakan untuk setiap kategori / berdasarkan kategori
            if (str_contains($p, 'setiap kategori') || str_contains($p, 'berdasarkan kategori') || (str_contains($p, 'kategori') && str_contains($p, 'pengeluaran'))) {
                $catList = $context['kategori_pengeluaran_all_time'] ?? [];
                if (empty($catList)) {
                    return "Belum ditemukan catatan transaksi pengeluaran pada kategori yang tersedia.";
                }
                $lines = ["Realisasi pengeluaran berdasarkan kategori anggaran:"];
                foreach ($catList as $c) {
                    $lines[] = "• **{$c['name']}**: Rp " . number_format($c['total_spent'], 0, ',', '.') . " ({$c['tx_count']} transaksi)";
                }
                return implode("\n", $lines);
            }

            // Pengeluaran terbesar bulan ini / transaksi pengeluaran terbesar
            if (str_contains($p, 'pengeluaran terbesar')) {
                $topTx = $context['transaksi_pengeluaran_terbesar'] ?? null;
                if ($topTx) {
                    $tglTx = date('d F Y', strtotime($topTx['transaction_date']));
                    return "Transaksi pengeluaran terbesar dicatat pada transaksi **{$topTx['transaction_number']}** tanggal **{$tglTx}** untuk keperluan *{$topTx['category_name']}* ({$topTx['description']}) sebesar **Rp " . number_format($topTx['amount'], 0, ',', '.') . "**.";
                }
                return "Belum ditemukan catatan transaksi pengeluaran yang sesuai.";
            }

            // --- C. PEMBAYARAN DAN TAGIHAN ---
            // Tagihan jatuh tempo / overdue (Diprioritaskan sebelum total tagihan umum)
            if (str_contains($p, 'jatuh tempo') || str_contains($p, 'overdue')) {
                return "Tercatat sebanyak **{$tagihan['overdue_count']} tagihan** yang telah melewati tanggal jatuh tempo (status OVERDUE atau belum lunas melampaui tenggat).";
            }

            // Siapa saja mahasiswa yang memiliki tagihan tertunggak
            if (str_contains($p, 'siapa') && (str_contains($p, 'menunggak') || str_contains($p, 'tertunggak') || str_contains($p, 'tunggakan')) || str_contains($p, 'daftar mahasiswa menunggak')) {
                $unpaidList = $context['mahasiswa_menunggak_list'] ?? [];
                if (empty($unpaidList)) {
                    return "Kabar baik! Saat ini tidak ada mahasiswa yang memiliki tagihan tertunggak (seluruh tagihan aktif telah lunas).";
                }
                $lines = ["Tercatat sebanyak **" . count($unpaidList) . " mahasiswa** yang memiliki tagihan tertunggak:"];
                $no = 1;
                foreach ($unpaidList as $mhs) {
                    $lines[] = "{$no}. **{$mhs['full_name']}** (NIM: {$mhs['nim']}, {$mhs['study_program']}): sisa tunggakan **Rp " . number_format($mhs['total_tunggakan'], 0, ',', '.') . "** ({$mhs['jlh_tagihan']} tagihan)";
                    $no++;
                }
                return implode("\n", $lines);
            }

            // Total tagihan yang tercatat
            if (str_contains($p, 'total tagihan yang tercatat') || str_contains($p, 'total tagihan tercatat') || (str_contains($p, 'total tagihan') && !str_contains($p, 'belum'))) {
                return "Total tagihan yang tercatat di sistem saat ini adalah sebanyak **{$tagihan['total_tercatat_count']} tagihan** dengan total nilai kewajiban sebesar **Rp " . number_format($tagihan['total_tercatat_nominal'], 0, ',', '.') . "**.";
            }

            // Tagihan yang sudah lunas
            if (str_contains($p, 'sudah lunas') || str_contains($p, 'tagihan lunas')) {
                return "Jumlah tagihan yang sudah lunas tercatat sebanyak **{$tagihan['lunas_count']} tagihan** dengan total nilai pembayaran sebesar **Rp " . number_format($tagihan['lunas_nominal'], 0, ',', '.') . "**.";
            }

            // Tagihan yang belum dibayar / belum lunas
            if (str_contains($p, 'belum dibayar') || str_contains($p, 'belum lunas') || str_contains($p, 'tagihan belum')) {
                return "Jumlah tagihan yang belum lunas (status UNPAID, PARTIAL, atau OVERDUE) adalah sebanyak **{$tagihan['belum_lunas_count']} tagihan** dengan total akumulasi sisa tunggakan sebesar **Rp " . number_format($tagihan['belum_lunas_nominal'], 0, ',', '.') . "**.";
            }

            // Total pembayaran yang berhasil diterima
            if (str_contains($p, 'pembayaran yang berhasil') || str_contains($p, 'pembayaran berhasil')) {
                return "Total pembayaran yang berhasil diterima dan diverifikasi di sistem (seluruh kuitansi pembayaran sah) adalah sebesar **Rp " . number_format($context['total_pembayaran_berhasil'], 0, ',', '.') . "**.";
            }

            // Total pembayaran parsial
            if (str_contains($p, 'parsial') || str_contains($p, 'cicilan')) {
                return "Fitur pembayaran parsial (cicilan) mencatat sebanyak **{$tagihan['parsial_count']} tagihan** berstatus PARTIAL, dengan total dana yang telah berhasil dibayarkan sebagian sebesar **Rp " . number_format($tagihan['parsial_paid'], 0, ',', '.') . "**.";
            }


            // Tunggakan Mahasiswa (Pertanyaan Umum)
            if (str_contains($p, 'tunggakan') || str_contains($p, 'menunggak')) {
                $count = (int)($context['mahasiswa_memiliki_tunggakan'] ?? 0);
                $nom = (float)($context['total_nominal_tunggakan'] ?? 0);
                return "Saat ini tercatat sebanyak **{$count} mahasiswa** yang masih memiliki tunggakan tagihan aktif, dengan total akumulasi kewajiban belum dibayar sebesar **Rp " . number_format($nom, 0, ',', '.') . "**.";
            }

            // --- D. RINCIAN TRANSAKSI ---
            // Transaksi masuk terbaru
            if (str_contains($p, 'transaksi masuk terbaru') || (str_contains($p, 'masuk') && str_contains($p, 'terbaru'))) {
                $txList = $context['transaksi_masuk_terbaru'] ?? [];
                if (empty($txList)) {
                    return "Belum ditemukan catatan transaksi masuk terbaru.";
                }
                $lines = ["Berikut daftar transaksi penerimaan kas (masuk) terbaru:"];
                foreach ($txList as $tx) {
                    $tglTx = date('d/m/Y H:i', strtotime($tx['transaction_date']));
                    $lines[] = "• **{$tx['transaction_number']}** ({$tglTx}) - Rp " . number_format($tx['amount'], 0, ',', '.') . " [{$tx['category_name']}]: {$tx['description']}";
                }
                return implode("\n", $lines);
            }

            // Transaksi keluar terbaru
            if (str_contains($p, 'transaksi keluar terbaru') || (str_contains($p, 'keluar') && str_contains($p, 'terbaru'))) {
                $txList = $context['transaksi_keluar_terbaru'] ?? [];
                if (empty($txList)) {
                    return "Belum ditemukan catatan transaksi pengeluaran (keluar) terbaru.";
                }
                $lines = ["Berikut daftar transaksi pengeluaran kas (keluar) terbaru:"];
                foreach ($txList as $tx) {
                    $tglTx = date('d/m/Y H:i', strtotime($tx['transaction_date']));
                    $lines[] = "• **{$tx['transaction_number']}** ({$tglTx}) - Rp " . number_format($tx['amount'], 0, ',', '.') . " [{$tx['category_name']}]: {$tx['description']}";
                }
                return implode("\n", $lines);
            }

            // Transaksi gagal, dibatalkan, atau masih menunggu
            if (str_contains($p, 'gagal') || str_contains($p, 'dibatalkan') || str_contains($p, 'batal') || str_contains($p, 'voided') || str_contains($p, 'refund')) {
                $batal = $context['transaksi_bermasalah'] ?? [];
                $vCash = $batal['voided_cash'] ?? 0;
                $refPay = $batal['refunded_payments'] ?? 0;
                $canBill = $batal['cancelled_bills'] ?? 0;
                return "Berdasarkan catatan sistem:\n• Transaksi kas dibatalkan (voided): **{$vCash} transaksi**\n• Pembayaran di-refund: **{$refPay} transaksi**\n• Tagihan dibatalkan (cancelled): **{$canBill} tagihan**\nPerhitungan keuangan sistem hanya mencakup transaksi yang berstatus sah (completed).";
            }

            // Kapan transaksi terakhir tercatat / transaksi terakhir
            if (str_contains($p, 'transaksi terakhir')) {
                $tx = $context['transaksi_terakhir'] ?? null;
                if ($tx) {
                    $tglTx = date('d F Y, H:i', strtotime($tx['transaction_date'])) . ' WIB';
                    $typeLabel = ($tx['type'] === 'income') ? 'Pemasukan' : 'Pengeluaran';
                    return "Transaksi terakhir tercatat pada tanggal **{$tglTx}** dengan nomor **{$tx['transaction_number']}** senilai **Rp " . number_format($tx['amount'], 0, ',', '.') . "** [{$typeLabel} - {$tx['category_name']}] ({$tx['description']}).";
                }
                return "Belum ditemukan riwayat transaksi di Buku Kas.";
            }

            // Transaksi terbaru umum
            if (str_contains($p, 'transaksi terbaru')) {
                $txIn = $context['transaksi_masuk_terbaru'] ?? [];
                $txOut = $context['transaksi_keluar_terbaru'] ?? [];
                $lines = ["Daftar transaksi kas terbaru:"];
                foreach (array_slice($txIn, 0, 3) as $tx) {
                    $lines[] = "• [Masuk] **{$tx['transaction_number']}** - Rp " . number_format($tx['amount'], 0, ',', '.') . " ({$tx['description']})";
                }
                foreach (array_slice($txOut, 0, 2) as $tx) {
                    $lines[] = "• [Keluar] **{$tx['transaction_number']}** - Rp " . number_format($tx['amount'], 0, ',', '.') . " ({$tx['description']})";
                }
                return implode("\n", $lines);
            }

            // --- E. RINGKASAN KEUANGAN ---
            if (str_contains($p, 'ringkasan keuangan') || str_contains($p, 'rekap keuangan') || str_contains($p, 'ikhtisar keuangan') || str_contains($p, 'ringkasan arus kas')) {
                $topCatName = $context['kategori_pengeluaran_terbesar_bulan_ini']['kategori'] ?? (!empty($context['kategori_pengeluaran_all_time']) ? $context['kategori_pengeluaran_all_time'][0]['name'] : 'Belum ada pengeluaran');
                $mTx = $context['transaksi_bulan_ini_count'] ?? 0;
                $surplusBulan = $mIn - $mOut;
                $posisiSurplus = ($surplusBulan >= 0) ? "Surplus Rp " . number_format($surplusBulan, 0, ',', '.') : "Defisit Rp " . number_format(abs($surplusBulan), 0, ',', '.');

                return "**Ringkasan Keuangan Smart Campus Cash ({$periode}):**\n• **Total Pemasukan**: Rp " . number_format($mIn, 0, ',', '.') . "\n• **Total Pengeluaran**: Rp " . number_format($mOut, 0, ',', '.') . "\n• **Saldo Kas Bersih Saat Ini**: Rp " . number_format($saldo, 0, ',', '.') . "\n• **Jumlah Transaksi Bulan Ini**: {$mTx} transaksi\n• **Pengeluaran Terbesar**: {$topCatName}\n• **Tagihan Belum Lunas**: {$tagihan['belum_lunas_count']} tagihan (sisa tunggakan Rp " . number_format($tagihan['belum_lunas_nominal'], 0, ',', '.') . ")\n• **Posisi Arus Kas**: {$posisiSurplus} pada bulan berjalan.\nPerhitungan ini mencakup transaksi yang berstatus sah (completed) sesuai sistem.";
            }

            // Saran / Rekomendasi Keuangan
            if (str_contains($p, 'saran') || str_contains($p, 'rekomendasi') || str_contains($p, 'pengelolaan keuangan') || str_contains($p, 'tips')) {
                $tunggakanNom = (float)($context['total_nominal_tunggakan'] ?? 0);
                $topCat = $context['kategori_pengeluaran_terbesar_bulan_ini']['kategori'] ?? 'operasional';

                return "Berdasarkan data keuangan aktual saat ini:\n• Saldo Kas: **Rp " . number_format($saldo, 0, ',', '.') . "**\n• Pemasukan Bulan Ini: **Rp " . number_format($mIn, 0, ',', '.') . "**\n• Pengeluaran Bulan Ini: **Rp " . number_format($mOut, 0, ',', '.') . "** (Terbesar pada pos: *{$topCat}*)\n• Akumulasi Tunggakan: **Rp " . number_format($tunggakanNom, 0, ',', '.') . "**\n\n**Saran Pengelolaan:** Disarankan untuk memprioritaskan penagihan sisa kewajiban mahasiswa dan mengevaluasi pos pengeluaran terbesar guna memastikan stabilitas likuiditas kas operasional kampus.";
            }

            // Pembayaran / Setoran Hari Ini
            if (str_contains($p, 'hari ini') || str_contains($p, 'setoran')) {
                $today = (float)($context['total_pembayaran_hari_ini'] ?? 0);
                return "Total pembayaran mahasiswa yang diterima di loket kasir hari ini adalah sebesar **Rp " . number_format($today, 0, ',', '.') . "**.";
            }
        }

        // Default Fallback
        return self::getOffTopicRefusalResponse();
    }

    /**
     * Catat interaksi pertanyaan ke database
     */
    protected static function logQuery(int $userId, string $role, string $prompt, string $response): void
    {
        try {
            $pdo = Database::getConnection();
            if (!$pdo) return;

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $pdo->prepare("INSERT INTO ai_query_logs (user_id, role, prompt, response, ip_address, created_at)
                                   VALUES (:uid, :role, :prompt, :resp, :ip, NOW())");
            $stmt->execute([
                'uid' => $userId,
                'role' => $role,
                'prompt' => $prompt,
                'resp' => $response,
                'ip' => $ip
            ]);

            // Catat juga ke audit_logs
            AuditLogger::log('AI_QUERY', 'ai_assistant', null, null, [
                'prompt' => $prompt,
                'role' => $role
            ]);
        } catch (Exception $e) {
            error_log("Failed to log AI query: " . $e->getMessage());
        }
    }
}
