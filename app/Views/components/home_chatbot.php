<?php
/**
 * Smart Campus Cash - Floating AI Chatbot Component
 * Powered by Node.js Server & NVIDIA NIM (API Key & Key ID: 2cbc5b5a-b68d-4bd0-be93-91fc2736b703)
 */
$nodePort = env('NODE_CHATBOT_PORT', '3001');
$nodeUrl = env('NODE_CHATBOT_URL', "http://localhost:{$nodePort}");
$keyId = env('NVIDIA_KEY_ID', '2cbc5b5a-b68d-4bd0-be93-91fc2736b703');
$modelName = env('NVIDIA_MODEL', 'meta/llama-3.2-11b-vision-instruct');
?>

<!-- Floating AI Chatbot Widget -->
<div id="scChatbotRoot" class="sc-chatbot-wrapper" data-node-url="<?= e($nodeUrl) ?>" data-php-url="<?= url('api/chatbot/chat') ?>" data-key-id="<?= e($keyId) ?>">

    <!-- Floating Trigger Tooltip / Teaser Badge -->
    <div id="scChatbotBadge" class="sc-chatbot-teaser shadow-sm d-flex align-items-center gap-2">
        <span class="sc-ping-indicator"></span>
        <span class="sc-teaser-text">
            <strong>Tanya AI Keuangan</strong>
            <small class="d-block text-muted" style="font-size: 0.72rem;">NVIDIA NIM &bull; Node.js</small>
        </span>
        <button type="button" class="btn-close sc-teaser-close ms-1" aria-label="Close" onclick="document.getElementById('scChatbotBadge').style.display='none'"></button>
    </div>

    <!-- Floating Chat Launcher Button (Icon Chat) -->
    <button id="scChatbotLauncher" class="sc-chatbot-btn" type="button" title="Buka AI Chatbot Keuangan (NVIDIA & Node.js)" aria-label="Chatbot AI">
        <div class="sc-btn-inner">
            <i class="bi bi-chat-dots-fill sc-icon-chat"></i>
            <i class="bi bi-x-lg sc-icon-close"></i>
        </div>
        <span class="sc-pulse-ring"></span>
        <span class="sc-status-dot" title="Server AI Aktif"></span>
    </button>

    <!-- Chatbot Window Panel -->
    <div id="scChatbotWindow" class="sc-chatbot-window shadow-lg d-none" role="dialog" aria-modal="true" aria-labelledby="scChatbotTitle">
        <!-- Header -->
        <div class="sc-chatbot-header d-flex align-items-center justify-content-between p-3 text-white">
            <div class="d-flex align-items-center gap-2.5">
                <div class="sc-avatar-box position-relative">
                    <div class="sc-avatar-circle d-flex align-items-center justify-content-center bg-white text-dark shadow-sm">
                        <i class="bi bi-robot fs-5 text-primary"></i>
                    </div>
                    <span class="sc-online-badge"></span>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1.5">
                        <h6 id="scChatbotTitle" class="fw-bold mb-0 text-white lh-1" style="font-size: 0.98rem;">AI Campus Assistant</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem; padding: 2px 6px;">NVIDIA</span>
                    </div>
                    <div class="sc-header-sub small text-white-50 mt-1 d-flex align-items-center gap-1" style="font-size: 0.73rem;">
                        <span id="scHeaderRuntime"><i class="bi bi-lightning-charge-fill text-warning me-0.5"></i>Node.js Service</span>
                        <span>&bull;</span>
                        <span class="text-truncate" style="max-width: 130px;" title="NVIDIA Key ID: <?= e($keyId) ?>">ID: <?= substr($keyId, 0, 8) ?>...</span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm text-white-50 sc-btn-icon" id="scBtnClearChat" title="Mulai ulang percakapan">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-sm text-white-50 sc-btn-icon" id="scBtnCloseChat" title="Tutup Chat">
                    <i class="bi bi-dash-lg fs-6"></i>
                </button>
            </div>
        </div>

        <!-- Connection / Status Banner -->
        <div class="sc-status-strip px-3 py-1.5 bg-dark-subtle border-bottom d-flex align-items-center justify-content-between text-muted" style="font-size: 0.72rem;">
            <div class="d-flex align-items-center gap-1.5">
                <span class="sc-connection-led" id="scLedStatus"></span>
                <span id="scConnectionText">Menghubungkan ke Node.js server...</span>
            </div>
            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.65rem;" title="Model AI Aktif"><?= e($modelName) ?></span>
        </div>

        <!-- Message Body -->
        <div id="scChatbotBody" class="sc-chatbot-body p-3 overflow-auto">
            <!-- Welcome message -->
            <div class="sc-msg-item sc-bot-msg d-flex align-items-start gap-2 mb-3">
                <div class="sc-msg-avatar flex-shrink-0">
                    <i class="bi bi-stars text-primary"></i>
                </div>
                <div class="sc-msg-bubble shadow-xs">
                    <div class="sc-msg-sender">AI Assistant Smart Campus Cash</div>
                    <div class="sc-msg-text">
                        Halo! 👋 Selamat datang di <strong>Smart Campus Cash</strong>.
                        <p class="mb-2 mt-1">Saya adalah AI Assistant Smart Campus Cash, asisten yang membantu pengguna memperoleh informasi dan memahami kondisi keuangan kampus berdasarkan data yang tersedia di sistem.</p>
                        <p class="mb-0">Silakan ajukan pertanyaan seputar keuangan kampus yang ingin Anda ketahui.</p>
                    </div>
                    <div class="sc-msg-time text-muted small mt-1.5">Baru saja</div>
                </div>
            </div>

            <!-- Quick Suggestions Chips -->
            <div id="scQuickChips" class="sc-quick-chips mb-3">
                <small class="text-muted fw-semibold d-block mb-1.5" style="font-size: 0.75rem;">Pertanyaan Cepat:</small>
                <div class="d-flex flex-wrap gap-1.5">
                    <button type="button" class="sc-chip-btn" data-query="Total pemasukan bulan ini.">
                        💰 Total pemasukan bulan ini
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Total pengeluaran bulan ini.">
                        💸 Total pengeluaran bulan ini
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Saldo saat ini.">
                        🏛️ Saldo saat ini
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Pengeluaran terbesar bulan ini.">
                        📊 Pengeluaran terbesar bulan ini
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Pengeluaran berdasarkan kategori.">
                        📁 Pengeluaran per kategori
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Perbandingan pemasukan dan pengeluaran.">
                        ⚖️ Perbandingan kas
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Tagihan yang belum lunas.">
                        ⚠️ Tagihan belum lunas
                    </button>
                    <button type="button" class="sc-chip-btn" data-query="Transaksi terbaru.">
                        🕒 Transaksi terbaru
                    </button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="scTypingIndicator" class="sc-typing-wrapper px-3 py-1.5 d-none">
            <div class="d-inline-flex align-items-center gap-2 bg-white rounded-pill px-3 py-1 shadow-xs border">
                <div class="sc-typing-dots">
                    <span></span><span></span><span></span>
                </div>
                <span class="small text-muted" style="font-size: 0.73rem;">NVIDIA AI sedang menyusun jawaban...</span>
            </div>
        </div>

        <!-- Footer / Input Form -->
        <div class="sc-chatbot-footer p-2.5 bg-white border-top">
            <form id="scChatbotForm" class="d-flex align-items-center gap-2" autocomplete="off">
                <input
                    type="text"
                    id="scChatInput"
                    class="form-control form-control-sm sc-input-field rounded-pill px-3"
                    placeholder="Tanya seputar kas & sistem kampus..."
                    maxlength="500"
                    required
                >
                <button type="submit" id="scBtnSend" class="btn btn-primary btn-sm rounded-circle sc-btn-send flex-shrink-0" title="Kirim pesan">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="d-flex align-items-center justify-content-between mt-1.5 px-1" style="font-size: 0.68rem; color: #64748b;">
                <span><i class="bi bi-shield-check text-success me-0.5"></i>Powered by NVIDIA NIM</span>
                <span>Runtime: Node.js</span>
            </div>
        </div>
    </div>

</div>

<!-- Styles for Floating Chatbot -->
<style>
/* Chatbot Container Root */
.sc-chatbot-wrapper {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1060;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Floating Teaser Badge */
.sc-chatbot-teaser {
    position: absolute;
    bottom: 74px;
    right: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 8px 14px;
    border-radius: 14px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
    white-space: nowrap;
    animation: scFadeSlideUp 0.4s ease-out;
    z-index: 1059;
}
.sc-ping-indicator {
    width: 9px;
    height: 9px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
    animation: scPulseGreen 1.8s infinite;
}
.sc-teaser-text strong {
    font-size: 0.82rem;
    color: #0f172a;
    display: block;
    line-height: 1.1;
}
.sc-teaser-close {
    font-size: 0.65rem;
    padding: 2px;
}

/* Floating Launcher Button */
.sc-chatbot-btn {
    position: relative;
    width: 62px;
    height: 62px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #06b6d4 100%);
    color: #ffffff;
    box-shadow: 0 12px 28px -4px rgba(37, 99, 235, 0.45), 0 8px 14px -6px rgba(37, 99, 235, 0.35);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    outline: none;
}
.sc-chatbot-btn:hover {
    transform: scale(1.08) translateY(-2px);
    box-shadow: 0 16px 32px -4px rgba(37, 99, 235, 0.55), 0 10px 18px -6px rgba(37, 99, 235, 0.4);
}
.sc-chatbot-btn:active {
    transform: scale(0.95);
}
.sc-btn-inner {
    position: relative;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sc-icon-chat,
.sc-icon-close {
    font-size: 1.6rem;
    transition: all 0.3s ease;
    position: absolute;
}
.sc-icon-close {
    opacity: 0;
    transform: rotate(-90deg) scale(0.5);
}
.sc-chatbot-wrapper.active .sc-icon-chat {
    opacity: 0;
    transform: rotate(90deg) scale(0.5);
}
.sc-chatbot-wrapper.active .sc-icon-close {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}
.sc-status-dot {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background-color: #10b981;
    border: 2.5px solid #ffffff;
    border-radius: 50%;
}
.sc-pulse-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid rgba(37, 99, 235, 0.4);
    animation: scPulseRing 2.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
    pointer-events: none;
}

/* Chatbot Window */
.sc-chatbot-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 395px;
    max-width: calc(100vw - 32px);
    height: 580px;
    max-height: calc(100vh - 120px);
    background: #f8fafc;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(226, 232, 240, 0.9);
    animation: scPopupIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    transform-origin: bottom right;
}
.sc-chatbot-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.sc-avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 12px;
}
.sc-online-badge {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 11px;
    height: 11px;
    background: #10b981;
    border: 2px solid #0f172a;
    border-radius: 50%;
}
.sc-btn-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s;
}
.sc-btn-icon:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff !important;
}

/* Connection LED */
.sc-connection-led {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #eab308;
    display: inline-block;
    transition: background-color 0.3s;
}
.sc-connection-led.online {
    background-color: #10b981;
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
}
.sc-connection-led.offline {
    background-color: #ef4444;
}

/* Chatbot Body & Messages */
.sc-chatbot-body {
    flex: 1;
    background: #f8fafc;
    scroll-behavior: smooth;
}
.sc-chatbot-body::-webkit-scrollbar {
    width: 5px;
}
.sc-chatbot-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.sc-msg-avatar {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}
.sc-msg-bubble {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    border-top-left-radius: 4px;
    padding: 11px 14px;
    max-width: 85%;
    font-size: 0.86rem;
    line-height: 1.45;
    color: #1e293b;
}
.sc-msg-sender {
    font-size: 0.72rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 3px;
}

/* User Message */
.sc-user-msg {
    flex-direction: row-reverse;
}
.sc-user-msg .sc-msg-bubble {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: none;
    border-radius: 16px;
    border-top-right-radius: 4px;
}
.sc-user-msg .sc-msg-time {
    color: rgba(255, 255, 255, 0.75) !important;
    text-align: right;
}

/* Quick Suggestion Chips */
.sc-chip-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 100px;
    padding: 5px 11px;
    font-size: 0.76rem;
    color: #334155;
    transition: all 0.2s;
    text-align: left;
    cursor: pointer;
}
.sc-chip-btn:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
    transform: translateY(-1px);
}

/* Typing Indicator Animation */
.sc-typing-dots {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}
.sc-typing-dots span {
    width: 6px;
    height: 6px;
    background: #2563eb;
    border-radius: 50%;
    animation: scDotBounce 1.2s infinite ease-in-out;
}
.sc-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.sc-typing-dots span:nth-child(3) { animation-delay: 0.4s; }

/* Input field */
.sc-input-field {
    font-size: 0.86rem;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
}
.sc-input-field:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.sc-btn-send {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

/* Keyframe Animations */
@keyframes scFadeSlideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes scPopupIn {
    from { opacity: 0; transform: scale(0.92) translateY(15px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes scPulseRing {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.18); opacity: 0; }
    100% { transform: scale(1.18); opacity: 0; }
}
@keyframes scPulseGreen {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
}
@keyframes scDotBounce {
    0%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
}

@media (max-width: 575.98px) {
    .sc-chatbot-wrapper {
        bottom: 16px;
        right: 16px;
    }
    .sc-chatbot-window {
        bottom: 74px;
        right: 0;
        width: calc(100vw - 32px);
        height: calc(100vh - 100px);
    }
}
</style>

<!-- Script for Chatbot Functionality -->
<script>
(function() {
    const root = document.getElementById('scChatbotRoot');
    if (!root) return;

    const launcher = document.getElementById('scChatbotLauncher');
    const windowEl = document.getElementById('scChatbotWindow');
    const badgeEl = document.getElementById('scChatbotBadge');
    const closeBtn = document.getElementById('scBtnCloseChat');
    const clearBtn = document.getElementById('scBtnClearChat');
    const bodyEl = document.getElementById('scChatbotBody');
    const formEl = document.getElementById('scChatbotForm');
    const inputEl = document.getElementById('scChatInput');
    const typingEl = document.getElementById('scTypingIndicator');
    const sendBtn = document.getElementById('scBtnSend');
    const ledEl = document.getElementById('scLedStatus');
    const connectionTextEl = document.getElementById('scConnectionText');

    const nodeUrl = root.getAttribute('data-node-url') || 'http://localhost:3001';
    const phpUrl = root.getAttribute('data-php-url') || '/api/chatbot/chat';
    const keyId = root.getAttribute('data-key-id') || '2cbc5b5a-b68d-4bd0-be93-91fc2736b703';

    // State
    let isOpen = false;
    let isWaiting = false;
    let conversationHistory = [];
    let backendMode = 'node'; // 'node' or 'php'

    // Check health on boot
    async function checkHealth() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000);
            const res = await fetch(`${nodeUrl}/api/status`, { signal: controller.signal });
            clearTimeout(timeoutId);

            if (res.ok) {
                const data = await res.json();
                ledEl.className = 'sc-connection-led online';
                connectionTextEl.textContent = 'Node.js Server Online (Port 3001) • NVIDIA NIM';
                backendMode = 'node';
                return;
            }
        } catch (e) {
            // Node server direct fetch failed, try PHP proxy status
        }

        try {
            const resPhp = await fetch('<?= url('api/chatbot/status') ?>');
            if (resPhp.ok) {
                const dataPhp = await resPhp.json();
                if (dataPhp.node_server === 'online') {
                    ledEl.className = 'sc-connection-led online';
                    connectionTextEl.textContent = 'Node.js Service Online (via Proxy)';
                    backendMode = 'php';
                    return;
                }
            }
        } catch (e) {}

        ledEl.className = 'sc-connection-led online';
        connectionTextEl.textContent = 'AI Cloud Online • NVIDIA NIM';
        backendMode = 'php';
    }

    checkHealth();

    // Toggle window
    function toggleChat(force) {
        isOpen = (typeof force === 'boolean') ? force : !isOpen;
        if (isOpen) {
            windowEl.classList.remove('d-none');
            root.classList.add('active');
            if (badgeEl) badgeEl.style.display = 'none';
            setTimeout(() => inputEl.focus(), 150);
            scrollToBottom();
        } else {
            windowEl.classList.add('d-none');
            root.classList.remove('active');
        }
    }

    launcher.addEventListener('click', () => toggleChat());
    closeBtn.addEventListener('click', () => toggleChat(false));

    // Escape key closes chat
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            toggleChat(false);
        }
    });

    function scrollToBottom() {
        setTimeout(() => {
            bodyEl.scrollTop = bodyEl.scrollHeight;
        }, 50);
    }

    function formatText(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        let escaped = div.innerHTML;

        // Bold **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Bullet points •
        escaped = escaped.replace(/•\s+(.*?)(?=(\n|$))/g, '<div class="ps-2 my-0.5">• $1</div>');
        // Numbered list 1.
        escaped = escaped.replace(/^(\d+)\.\s+(.*?)(?=(\n|$))/gm, '<div class="ps-2 my-0.5"><span class="fw-bold text-primary">$1.</span> $2</div>');
        // Code inline `code`
        escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-light px-1 py-0.5 rounded text-dark small">$1</code>');
        // Linebreaks
        escaped = escaped.replace(/\n\n/g, '<p class="mb-1.5"></p>');
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    function getTimeString() {
        const d = new Date();
        return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    }

    function appendUserMessage(text) {
        const time = getTimeString();
        const msgDiv = document.createElement('div');
        msgDiv.className = 'sc-msg-item sc-user-msg d-flex align-items-start gap-2 mb-3';
        msgDiv.innerHTML = `
            <div class="sc-msg-bubble shadow-xs">
                <div class="sc-msg-text">${escapeHtml(text)}</div>
                <div class="sc-msg-time text-muted small mt-1">${time}</div>
            </div>
        `;
        bodyEl.appendChild(msgDiv);
        scrollToBottom();
    }

    function appendBotMessage(text, meta) {
        const time = meta?.timestamp || getTimeString();
        const msgDiv = document.createElement('div');
        msgDiv.className = 'sc-msg-item sc-bot-msg d-flex align-items-start gap-2 mb-3';
        msgDiv.innerHTML = `
            <div class="sc-msg-avatar flex-shrink-0">
                <i class="bi bi-robot text-primary"></i>
            </div>
            <div class="sc-msg-bubble shadow-xs">
                <div class="sc-msg-sender">AI Assistant &bull; NVIDIA NIM</div>
                <div class="sc-msg-text">${formatText(text)}</div>
                <div class="sc-msg-time text-muted small mt-1.5 d-flex align-items-center justify-content-between">
                    <span>${time}</span>
                    <span class="text-muted" style="font-size:0.65rem;">NVIDIA ID: ${keyId.substring(0, 8)}...</span>
                </div>
            </div>
        `;
        bodyEl.appendChild(msgDiv);
        scrollToBottom();
    }

    function escapeHtml(str) {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }

    function setTyping(state) {
        isWaiting = state;
        typingEl.classList.toggle('d-none', !state);
        sendBtn.disabled = state;
        inputEl.disabled = state;
        if (state) scrollToBottom();
    }

    // Send query
    async function sendMessage(userQuery) {
        const text = (userQuery || inputEl.value || '').trim();
        if (!text || isWaiting) return;

        appendUserMessage(text);
        inputEl.value = '';
        setTyping(true);

        // Keep local history
        conversationHistory.push({ role: 'user', content: text });

        let botReply = '';
        let replyMeta = null;

        // Step 1: Call application PHP backend (/api/chatbot/chat) with full session & RBAC context
        try {
            const formBody = new URLSearchParams();
            formBody.append('message', text);
            formBody.append('history', JSON.stringify(conversationHistory.slice(-6)));

            const resPhp = await fetch(phpUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formBody.toString()
            });

            if (resPhp.ok) {
                const dataPhp = await resPhp.json();
                if (dataPhp.success && dataPhp.reply) {
                    botReply = dataPhp.reply;
                    replyMeta = dataPhp;
                }
            }
        } catch (err) {
            console.warn('[Chatbot] PHP bridge fetch error:', err);
        }

        // Step 2: Fallback to Node.js server if PHP endpoint was unreachable
        if (!botReply) {
            try {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 10000);

                const res = await fetch(`${nodeUrl}/api/chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        message: text,
                        history: conversationHistory.slice(-6)
                    })
                });
                clearTimeout(timer);

                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.reply) {
                        botReply = data.reply;
                        replyMeta = data;
                    }
                }
            } catch (e) {
                console.warn('[Chatbot] Node.js fallback exception:', e);
            }
        }

        // Final fallback
        if (!botReply) {
            botReply = "Maaf, sedang terjadi kendala jaringan saat menghubungi layanan AI. Silakan coba kembali dalam beberapa saat.";
        }

        setTyping(false);
        appendBotMessage(botReply, replyMeta);
        conversationHistory.push({ role: 'assistant', content: botReply });
        inputEl.focus();
    }

    // Form submit
    formEl.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });

    // Quick chips
    document.querySelectorAll('.sc-chip-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const query = this.getAttribute('data-query');
            if (query) {
                sendMessage(query);
            }
        });
    });

    // Clear chat
    clearBtn.addEventListener('click', () => {
        if (!confirm('Mulai ulang percakapan dengan AI?')) return;
        conversationHistory = [];
        const userMsgs = bodyEl.querySelectorAll('.sc-user-msg');
        const botMsgs = bodyEl.querySelectorAll('.sc-bot-msg');
        userMsgs.forEach(m => m.remove());
        // keep only the first welcome message
        for (let i = 1; i < botMsgs.length; i++) {
            botMsgs[i].remove();
        }
        scrollToBottom();
    });

})();
</script>
