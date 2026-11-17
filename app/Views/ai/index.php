<?php
/**
 * AI Financial Assistant - Smart Campus Cash
 * View: ai/index.php
 */
$roleLabel = match($role ?? 'mahasiswa') {
    'admin'     => 'Administrator',
    'keuangan'  => 'Bagian Keuangan',
    'kasir'     => 'Kasir Loket',
    'mahasiswa' => 'Mahasiswa',
    default     => 'Pengguna'
};
$roleIcon = match($role ?? 'mahasiswa') {
    'admin'     => 'bi-shield-fill-check',
    'keuangan'  => 'bi-building',
    'kasir'     => 'bi-cash-stack',
    'mahasiswa' => 'bi-person-badge',
    default     => 'bi-person'
};
?>
<div class="container-fluid px-2 px-md-4 py-3">
    <div class="row g-4">

        <!-- Header -->
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-1">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:48px;height:48px;font-size:1.4rem;">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0">AI Financial Assistant</h1>
                    <p class="text-muted small mb-0">
                        <i class="<?= e($roleIcon) ?> me-1"></i><?= e($roleLabel) ?> &bull; Data terisolasi sesuai hak akses Anda
                    </p>
                </div>
            </div>
            <hr class="my-3">
        </div>

        <!-- Left: Chat Area -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100" style="min-height:520px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-semibold text-dark small">
                        <i class="bi bi-chat-left-dots-fill text-primary me-2"></i>Percakapan
                    </span>
                    <button class="btn btn-outline-danger btn-sm" id="btnClearHistory" title="Bersihkan riwayat chat">
                        <i class="bi bi-trash3"></i> <span class="d-none d-md-inline">Bersihkan</span>
                    </button>
                </div>

                <!-- Chat Messages -->
                <div class="card-body p-3 p-md-4 overflow-auto chat-messages-container" id="chatMessages" style="max-height:420px;min-height:320px;">
                    <!-- Welcome message -->
                    <div class="d-flex align-items-start gap-2 mb-3" id="welcomeMsg">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white flex-shrink-0" style="width:34px;height:34px;font-size:0.95rem;">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div class="bg-white rounded-3 p-3 shadow-sm" style="max-width:85%;">
                            <p class="mb-1 fw-semibold small text-primary">AI Financial Assistant</p>
                            <div class="mb-0 text-dark ai-response-text" style="font-size:0.9rem; line-height: 1.6;">
                                <?php
                                $actualName = trim($user['full_name'] ?? '');
                                $greeting = !empty($actualName) ? "Halo, " . e($actualName) . "!" : "Halo!";
                                ?>
                                <p class="mb-2"><?= $greeting ?> Saya adalah AI Assistant Smart Campus Cash, asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem.</p>
                                <p class="mb-0">Silakan ajukan pertanyaan seputar keuangan kampus yang ingin Anda ketahui.</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $h): ?>
                            <!-- User message -->
                            <div class="d-flex align-items-start gap-2 mb-2 flex-row-reverse">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white flex-shrink-0" style="width:34px;height:34px;font-size:0.85rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="bg-primary text-white rounded-3 p-3" style="max-width:80%;">
                                    <p class="mb-0" style="font-size:0.9rem;"><?= e($h['prompt']) ?></p>
                                    <p class="mb-0 mt-1 small opacity-75"><?= e($h['time'] ?? '') ?></p>
                                </div>
                            </div>
                            <!-- AI response -->
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white flex-shrink-0" style="width:34px;height:34px;font-size:0.95rem;">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <div class="bg-white rounded-3 p-3 shadow-sm" style="max-width:85%;">
                                    <p class="mb-1 fw-semibold small text-primary">AI Financial Assistant</p>
                                    <div class="mb-0 text-dark ai-response-text" style="font-size:0.9rem;"><?= nl2br(e($h['response'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input Area -->
                <div class="card-footer bg-white border-top p-3">
                    <div id="aiTypingIndicator" class="text-muted small mb-2 d-none">
                        <i class="bi bi-hourglass-split text-primary me-1"></i> AI sedang memproses jawaban...
                    </div>
                    <form id="aiChatForm" class="d-flex gap-2" autocomplete="off">
                        <input type="hidden" id="aiCsrfToken" value="<?= e(csrf_token()) ?>">
                        <input
                            type="text"
                            id="aiPromptInput"
                            class="form-control"
                            placeholder="Ketik pertanyaan keuangan Anda..."
                            maxlength="500"
                            required
                            autofocus
                        >
                        <button type="submit" class="btn btn-primary px-3" id="btnSendAi" title="Kirim pertanyaan">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.75rem;">
                        <i class="bi bi-shield-lock me-1 text-success"></i>
                        AI hanya mengakses data sesuai hak akses role <strong><?= e($roleLabel) ?></strong>. API key tidak pernah dikirim ke browser.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Suggestions & Info -->
        <div class="col-12 col-lg-4">
            <!-- Quick Suggestions -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <span class="fw-semibold text-dark small">
                        <i class="bi bi-lightbulb-fill text-warning me-2"></i>Pertanyaan Cepat
                    </span>
                </div>
                <div class="card-body p-3">
                    <?php foreach ($suggestions as $s): ?>
                        <button class="btn btn-light btn-sm d-block w-100 text-start mb-2 ai-suggestion-btn py-2 px-3" style="font-size:0.85rem;border:1px solid #dee2e6;">
                            <i class="bi bi-arrow-right-circle text-primary me-2"></i><?= e($s) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2">
                    <span class="fw-semibold text-dark small">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>Tentang AI Assistant
                    </span>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Data dari database real-time</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>RBAC ketat sesuai role Anda</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Tidak mengarang angka (grounded)</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Riwayat chat tersimpan di sesi</li>
                        <li class="mb-0"><i class="bi bi-shield-check-fill text-primary me-2"></i>API key aman di server</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('aiChatForm');
    const promptInput = document.getElementById('aiPromptInput');
    const typingIndicator = document.getElementById('aiTypingIndicator');
    const sendBtn = document.getElementById('btnSendAi');
    const clearBtn = document.getElementById('btnClearHistory');
    const csrfToken = document.getElementById('aiCsrfToken').value;
    const baseUrl = '<?= url('') ?>';

    // Auto-scroll to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    // Append message bubble
    function appendUserMessage(text) {
        const d = document.createElement('div');
        d.className = 'd-flex align-items-start gap-2 mb-2 flex-row-reverse fade-in';
        d.innerHTML = `
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white flex-shrink-0" style="width:34px;height:34px;font-size:0.85rem;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="bg-primary text-white rounded-3 p-3" style="max-width:80%;">
                <p class="mb-0" style="font-size:0.9rem;">${escHtml(text)}</p>
            </div>`;
        chatMessages.appendChild(d);
        scrollToBottom();
    }

    function appendAiMessage(text, timestamp) {
        // Simple bold and line break rendering
        const formatted = escHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        const d = document.createElement('div');
        d.className = 'd-flex align-items-start gap-2 mb-3 fade-in';
        d.innerHTML = `
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white flex-shrink-0" style="width:34px;height:34px;font-size:0.95rem;">
                <i class="bi bi-stars"></i>
            </div>
            <div class="bg-white rounded-3 p-3 shadow-sm" style="max-width:85%;">
                <p class="mb-1 fw-semibold small text-primary">AI Financial Assistant</p>
                <div class="mb-0 text-dark" style="font-size:0.9rem;">${formatted}</div>
                ${timestamp ? `<p class="mb-0 mt-1 text-muted" style="font-size:0.75rem;">${escHtml(timestamp)}</p>` : ''}
            </div>`;
        chatMessages.appendChild(d);
        scrollToBottom();
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function setLoading(state) {
        sendBtn.disabled = state;
        promptInput.disabled = state;
        typingIndicator.classList.toggle('d-none', !state);
    }

    // Handle form submit
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const prompt = promptInput.value.trim();
        if (!prompt) return;

        appendUserMessage(prompt);
        promptInput.value = '';
        setLoading(true);

        try {
            const resp = await fetch(`${baseUrl}/ai-assistant/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: `prompt=${encodeURIComponent(prompt)}&_csrf=${encodeURIComponent(csrfToken)}`
            });

            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();

            if (data.success) {
                appendAiMessage(data.response, data.timestamp);
            } else {
                appendAiMessage('Maaf, terjadi kesalahan: ' + (data.message || 'Tidak diketahui'), null);
            }
        } catch (err) {
            appendAiMessage('Gagal terhubung ke server AI. Periksa koneksi Anda.', null);
        } finally {
            setLoading(false);
            promptInput.focus();
        }
    });

    // Suggestion buttons
    document.querySelectorAll('.ai-suggestion-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            promptInput.value = this.textContent.replace(/^\s*[›→]\s*/, '').trim();
            promptInput.focus();
        });
    });

    // Clear history
    clearBtn.addEventListener('click', async function() {
        if (!confirm('Hapus seluruh riwayat percakapan?')) return;
        try {
            await fetch(`${baseUrl}/ai-assistant/clear`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `_csrf=${encodeURIComponent(csrfToken)}`
            });
            // Remove all chat messages except the welcome message
            const msgs = chatMessages.querySelectorAll('.d-flex.fade-in');
            msgs.forEach(m => m.remove());
        } catch(e) {}
    });
})();
</script>
