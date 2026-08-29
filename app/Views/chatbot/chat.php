<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-CAI Tata Capital Support Assistant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="/trainingnew/images/icon.png">
    <style>
        /* ── Responsive Styles ────────────────────────── */
        @media (max-width: 768px) {
            .chat-container {
                width: 100%;
                max-width: 100%;
                height: 100vh;
                margin: 0;
                border-radius: 0;
            }
            .chat-header { padding: 10px; }
            .chat-header .title { font-size: 15px; }
            .chat-header .subtitle { font-size: 11px; }
            .bubble { max-width: 92%; font-size: 14px; }
            .input-bar { padding: 8px; }
            .icon-btn { width: 38px; height: 38px; }
            #user-input { font-size: 14px; }
            .lang-card { width: 92%; max-width: 300px; padding: 18px; }
            .lang-btns { flex-direction: column; gap: 8px; }
        }

        @media (max-width: 480px) {
            .chat-header .subtitle { display: none; }
            .lang-card { max-width: 280px; padding: 14px; }
            .lang-card h1 { font-size: 16px; }
            .bubble { max-width: 95%; }
        }

        /* ── Base Styles ────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; }

        /* ── Language Picker Overlay ────────────────── */
        #lang-overlay {
            position: fixed; inset: 0; z-index: 999;
            background: linear-gradient(135deg, #003d99, #0077e6);
            display: flex; align-items: center; justify-content: center;
        }
        .lang-card {
            background: #fff; border-radius: 20px; padding: 40px 36px;
            text-align: center; max-width: 400px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .lang-card .logo { font-size: 3rem; margin-bottom: 12px; }
        .lang-card .logo img { width: 40px; height: 40px; }
        .lang-card h1 { font-size: 1.4rem; color: #0052cc; margin-bottom: 6px; }
        .lang-card p  { color: #555; font-size: .92rem; margin-bottom: 28px; line-height: 1.5; }
        .lang-btns { display: flex; gap: 16px; justify-content: center; }
        .lang-btn {
            flex: 1; padding: 14px 10px; border: 2.5px solid #0077e6;
            border-radius: 14px; background: #fff; cursor: pointer;
            font-size: 1rem; font-weight: 600; color: #0052cc;
            transition: all .2s; display: flex; flex-direction: column;
            align-items: center; gap: 6px;
        }
        .lang-btn:hover { background: #0077e6; color: #fff; transform: translateY(-2px); }
        .lang-btn .flag { font-size: 2rem; }
        .lang-btn .lang-name { font-size: .88rem; font-weight: 500; }

        /* ── Chat Layout ────────────────────────────── */
        .chat-container {
            display: none;
            flex-direction: column;
            height: 100vh;
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,.12);
        }

        /* ── Header ─────────────────────────────────── */
        .chat-header {
            background: linear-gradient(135deg, #0052cc, #0077e6);
            color: #fff; padding: 13px 18px;
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        .chat-header .logo { font-size: 1.6rem; }
        .chat-header .title    { font-size: 1.1rem; font-weight: 700; }
        .chat-header .subtitle { font-size: .76rem; opacity: .85; margin-top: 2px; }
        .online-dot { width:10px;height:10px;background:#4caf50;border-radius:50%;flex-shrink:0;margin-left:auto; }
        #lang-switch-btn {
            margin-left: 10px; background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.4); color:#fff;
            border-radius: 20px; padding: 4px 12px; font-size:.78rem;
            cursor: pointer; transition: background .2s;
        }
        #lang-switch-btn:hover { background: rgba(255,255,255,.35); }

        /* ── Messages ───────────────────────────────── */
        #chat-messages {
            flex:1; overflow-y:auto; padding:18px 16px;
            display:flex; flex-direction:column; gap:12px;
        }
        .bubble-row { display:flex; align-items:flex-end; gap:8px; }
        .bubble-row.user      { flex-direction:row-reverse; }
        .bubble-row.assistant { flex-direction:row; }
        .avatar {
            width:34px;height:34px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:1rem;flex-shrink:0;
        }
        .avatar.bot     { background:#0052cc;color:#fff; }
        .avatar.user-av { background:#e0e0e0;color:#333; }
        .bubble {
            max-width:72%;padding:10px 14px;border-radius:16px;
            font-size:.93rem;line-height:1.6;word-wrap:break-word;
        }
        .bubble.user-bubble { background:#0077e6;color:#fff;border-bottom-right-radius:4px; }
        .bubble.bot-bubble  { background:#f1f3f5;color:#1a1a1a;border-bottom-left-radius:4px; }
        .bubble ol,.bubble ul { padding-left:18px;margin-top:6px; }
        .bubble li { margin-bottom:4px; }
        .bubble p  { margin-bottom:4px; }
        .bubble a  { color:#0052cc; }

        /* ── Chatbot Options (Quick Reply Buttons) ── */
        .chatbot-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            padding: 0 4px;
        }
        .chatbot-option-btn {
            background: #e8f0fe;
            border: 1.5px solid #4a90d9;
            color: #0052cc;
            border-radius: 20px;
            padding: 6px 16px;
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            font-family: inherit;
            background: #ffffff;
        }
        .chatbot-option-btn:hover {
            background: #c9d9f8;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 82, 204, 0.15);
        }
        .chatbot-option-btn:active {
            transform: translateY(0px);
            background: #b0c4de;
        }

        /* ── Escalation card ───────────────────────── */
        .escalation-card {
            background:#fff8e1;border:2px solid #ff8f00;
            border-radius:12px;padding:14px 16px;max-width:80%;font-size:.9rem;
        }
        .escalation-card h4 { color:#e65100;margin-bottom:8px;font-size:1rem; }
        .escalation-card .contact-item { margin:6px 0;display:flex;gap:8px;align-items:center; }

        /* ── Quick-reply chips (legacy) ────────────── */
        .quick-replies { display:flex;flex-wrap:wrap;gap:8px;margin-top:10px; }
        .chip {
            background:#e8f0fe;border:1px solid #4a90d9;color:#0052cc;
            border-radius:20px;padding:5px 14px;font-size:.82rem;
            cursor:pointer;transition:background .2s;white-space:nowrap;
        }
        .chip:hover { background:#c9d9f8; }

        /* ── Replay btn ────────────────────────────── */
        .replay-btn {
            background:none;border:none;cursor:pointer;font-size:1rem;
            padding:2px 6px;opacity:.65;transition:opacity .2s;vertical-align:middle;
        }
        .replay-btn:hover { opacity:1; }

        /* ── Typing indicator ───────────────────────── */
        #typing-indicator { display:none;align-items:center;gap:8px;padding:4px 0; }
        #typing-indicator.active { display:flex; }
        .typing-dots { display:flex;gap:4px; }
        .typing-dots span {
            width:8px;height:8px;background:#888;border-radius:50%;
            animation:bounce 1.2s infinite;
        }
        .typing-dots span:nth-child(2){animation-delay:.2s;}
        .typing-dots span:nth-child(3){animation-delay:.4s;}
        @keyframes bounce{0%,80%,100%{transform:scale(.6);opacity:.5;}40%{transform:scale(1);opacity:1;}}

        /* ── Input bar ────────────────────────────────── */
        .input-bar {
            border-top:1px solid #e0e0e0;padding:10px 12px;
            display:flex;align-items:center;gap:8px;
            background:#fff;flex-shrink:0;
        }
        #user-input {
            flex:1;border:1.5px solid #ccc;border-radius:22px;
            padding:9px 16px;font-size:.93rem;outline:none;
            transition:border .2s;font-family:inherit;
            height:42px;max-height:120px;resize:none;overflow:hidden;
        }
        #user-input:focus{border-color:#0077e6;}
        #user-input:disabled { background: #f5f5f5; cursor: not-allowed; }
        .icon-btn {
            width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            font-size:1.2rem;transition:background .2s;flex-shrink:0;
        }
        #send-btn  {background:#0077e6;color:#fff;}
        #send-btn:hover{background:#0052cc;}
        #send-btn:disabled{background:#b0c4de;cursor:not-allowed;}
        #mic-btn   {background:#f0f2f5;color:#444;}
        #mic-btn:hover{background:#e0e0e0;}
        #mic-btn.recording{background:#ff5252;color:#fff;animation:pulse 1s infinite;}
        #mic-btn:disabled{opacity:.4;cursor:not-allowed;}
        #voice-toggle{background:#f0f2f5;color:#444;font-size:1.1rem;}
        #voice-toggle.muted{color:#bbb;}
        @keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,82,82,.4);}50%{box-shadow:0 0 0 8px rgba(255,82,82,0);}}

        /* ── Live Chat Queue Badge ──────────────────── */
        .live-queue-badge {
            background: #ff8f00;
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .85rem;
            font-weight: 600;
            display: inline-block;
        }

        /* ── Mobile Collection Form ──────────────────── */
        #mobile-collection-form input {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1.5px solid #ccc;
            font-size: .9rem;
            transition: border .2s;
            font-family: inherit;
        }
        #mobile-collection-form input:focus {
            border-color: #0077e6;
            outline: none;
        }
        #mobile-collection-form button {
            background: #0077e6;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 20px;
            cursor: pointer;
            width: 100%;
            font-size: .95rem;
            font-weight: 600;
            transition: background .2s;
        }
        #mobile-collection-form button:hover {
            background: #0052cc;
        }
        #livechat-form-errors {
            color: #d32f2f;
            font-size: 13px;
            margin-bottom: 6px;
            min-height: 20px;
        }
        #agent-typing-label {
            display: none;
            font-size: .78rem;
            color: #888;
            padding: 4px 12px;
            font-style: italic;
        }

        @media(max-width:600px){
            .bubble{max-width:88%;font-size:.88rem;}
            .lang-btns{flex-direction:column;}
        }
    </style>
</head>
<body>

<!-- ── Language Picker Overlay ───────────────────────── -->
<div id="lang-overlay">
    <div class="lang-card">
        <div class="logo">📡</div>
        <div class="logo">
            <img src="https://bpoc.in/tata_capitals_chatboot/public/images/logo_icon.png" alt="BPOC Logo">
        </div>
        <h1>B-CAI</h1>
        <h1>Tata Capital Support</h1>
        <p>Please select your preferred language to continue.<br>
           कृपया अपनी पसंदीदा भाषा चुनें।</p>
        <div class="lang-btns">
            <button class="lang-btn" onclick="selectLanguage('en')">
                <span class="flag">🇬🇧</span>
                <span>English</span>
                <span class="lang-name">English</span>
            </button>
            <button class="lang-btn" onclick="selectLanguage('hi')">
                <span class="flag">🇮🇳</span>
                <span>हिंदी</span>
                <span class="lang-name">Hindi</span>
            </button>
        </div>
    </div>
</div>

<!-- ── Chat UI ────────────────────────────────────────── -->
<div class="chat-container" id="chat-app">
    <div class="chat-header">
        <div class="logo">📡</div>
        <div>
            <div class="title" id="header-title">Tata Capital Support Assistant</div>
            <div class="subtitle" id="header-sub">AI-Powered • 24/7</div>
        </div>
        <div class="online-dot" title="Online"></div>
        <button id="lang-switch-btn" onclick="switchLanguage()" title="Change language"></button>
    </div>

    <div id="chat-messages" role="log" aria-live="polite">
        <!-- Typing indicator -->
        <div id="typing-indicator" role="status">
            <div class="avatar bot">🤖</div> 
            <div class="typing-dots"><span></span><span></span><span></span></div>
        </div>
        <!-- Agent typing label (live chat) -->
        <div id="agent-typing-label">Agent is typing...</div>
    </div>

    <div class="input-bar">
        <button id="voice-toggle" class="icon-btn" aria-pressed="true">🔊</button>
        <textarea id="user-input" rows="1" autocomplete="off" aria-label="Message input" placeholder="Type your message here..."></textarea>
        <button id="mic-btn" class="icon-btn" title="Click to speak">🎤</button>
        <button id="send-btn" class="icon-btn" aria-label="Send">➡</button>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════
// Translations
// ═══════════════════════════════════════════════
const T = {
    en: {
        headerTitle   : 'Tata Capital Support Assistant',
        headerSub     : 'AI-Powered Customer Support • 24/7',
        placeholder   : 'Type your message here...',
        switchBtn     : '🇮🇳 हिंदी',
        micTitle      : 'Click to speak',
        micLang       : 'en-IN',
        muteTitle     : 'Mute voice',
        unmuteTitle   : 'Unmute voice',
        networkErr    : 'A network error occurred. Please try again or call 1800-208-6633.',
        welcome       : "Hello! I'm your Tata Capital Support Assistant. How can I help you today?",
        chips: [
            { label:'💰 Loan Enquiry',       text:'I want to know about Tata Capital loan options' },
            { label:'📄 Loan Eligibility',   text:'I want to check my Tata Capital loan eligibility' },
            { label:'💳 EMI Details',       text:'I want to know my Tata Capital EMI details' },
            { label:'📋 Raise Complaint',   text:'I want to register a complaint' },
            { label:'💰 Loan Repayment',    text:'I want to make my Tata Capital loan repayment' },
            { label:'📑 Loan Statement',    text:'I want to get my Tata Capital loan statement' },
            { label:'🔄 Loan Status',       text:'I want to check my Tata Capital loan status' },
            { label:'👤 Human Agent',       text:'Connect me to a human agent' },
        ],
        escalTitle    : '📞 Connect to a Human Agent',
        escalSub      : 'You will be connected to a Tata Capital specialist shortly.',
        helplineLabel : 'Helpline (Toll-Free):',
        websiteLabel  : 'Website:',
        emailLabel    : 'Email:',
    },
    hi: {
        headerTitle   : 'Tata Capital सहायक',
        headerSub     : 'AI ग्राहक सेवा • 24/7',
        placeholder   : 'यहाँ अपना सवाल लिखें...',
        switchBtn     : '🇬🇧 English',
        micTitle      : 'बोलकर सवाल पूछें',
        micLang       : 'hi-IN',
        muteTitle     : 'आवाज़ बंद करें',
        unmuteTitle   : 'आवाज़ चालू करें',
        networkErr    : 'नेटवर्क त्रुटि हुई। कृपया पुनः प्रयास करें या 1800-208-6633 पर कॉल करें।',
        welcome       : 'नमस्ते! मैं Tata Capital सहायक हूँ। आज मैं आपकी कैसे मदद कर सकता हूँ?',
        chips: [
            { label:'💰 लोन की जानकारी',       text:'मुझे Tata Capital के लोन के बारे में जानकारी चाहिए' },
            { label:'📄 लोन पात्रता',           text:'मुझे Tata Capital लोन की eligibility चेक करनी है' },
            { label:'💳 EMI की जानकारी',       text:'मुझे अपनी Tata Capital EMI की जानकारी चाहिए' },
            { label:'📋 शिकायत दर्ज करें',      text:'मुझे शिकायत दर्ज करनी है' },
            { label:'💰 लोन का भुगतान',        text:'मुझे अपने Tata Capital लोन का भुगतान करना है' },
            { label:'📑 लोन स्टेटमेंट',        text:'मुझे अपना Tata Capital लोन स्टेटमेंट चाहिए' },
            { label:'🔄 लोन स्टेटस',           text:'मुझे अपने Tata Capital लोन का स्टेटस चेक करना है' },
            { label:'👤 Human Agent',          text:'मुझे human agent से बात करनी है' },
        ],
        escalTitle    : '📞 मानव एजेंट से जुड़ें',
        escalSub      : 'आपकी बात एक Tata Capital विशेषज्ञ से कराई जाएगी।',
        helplineLabel : 'हेल्पलाइन (निःशुल्क):',
        websiteLabel  : 'वेबसाइट:',
        emailLabel    : 'ईमेल:',
    },
};

// ═══════════════════════════════════════════════
// State
// ═══════════════════════════════════════════════
let sessionId    = null;
let lang         = 'en';
let voiceEnabled = true;
let audioCache   = {};
let recognition  = null;
let failCount    = 0;
let humanMode    = false;

// ═══════════════════════════════════════════════
// Language selection
// ═══════════════════════════════════════════════
async function selectLanguage(chosen) {
    lang = chosen;
    document.getElementById('lang-overlay').style.display = 'none';
    document.getElementById('chat-app').style.display     = 'flex';
    applyTranslations();
    initSTT();
    await createSession();
    setupListeners();
}

function applyTranslations() {
    const t = T[lang];
    document.getElementById('html-root').lang          = lang === 'hi' ? 'hi' : 'en';
    document.getElementById('header-title').textContent = t.headerTitle;
    document.getElementById('header-sub').textContent   = t.headerSub;
    document.getElementById('user-input').placeholder   = t.placeholder;
    document.getElementById('lang-switch-btn').textContent = t.switchBtn;
    document.getElementById('mic-btn').title            = t.micTitle;
    document.getElementById('voice-toggle').title       = voiceEnabled ? t.muteTitle : t.unmuteTitle;
}

function switchLanguage() {
    document.getElementById('chat-app').style.display     = 'none';
    document.getElementById('lang-overlay').style.display = 'flex';
    sessionId = null;
    audioCache = {};
    failCount  = 0;
    humanMode  = false;
    // Clear messages (except typing indicator and agent-typing-label)
    const msgs = document.getElementById('chat-messages');
    const ti   = document.getElementById('typing-indicator');
    const atl  = document.getElementById('agent-typing-label');
    while (msgs.firstChild && msgs.firstChild !== ti && msgs.firstChild !== atl) {
        msgs.removeChild(msgs.firstChild);
    }
    if (ti.parentNode !== msgs) msgs.appendChild(ti);
    if (atl.parentNode !== msgs) msgs.appendChild(atl);
}

// ═══════════════════════════════════════════════
// Session
// ═══════════════════════════════════════════════
async function createSession() {
    try {
        const site_url = '<?= site_url() ?>';
        const url = site_url + 'chatbot/session';
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ language: lang }),
        });
        const responseText = await res.text();
        const data = JSON.parse(responseText);
        if (data.success) {
            sessionId = data.session_id;
            showWelcome();
        }
    } catch (e) {
        console.error('createSession Error:', e);
        appendBot(
            lang === 'hi'
                ? 'Tata Capital से कनेक्ट नहीं हो पाया। कृपया पेज रीफ्रेश करें या 1800-420-5577 पर कॉल करें।'
                : 'Unable to connect. Please refresh the page or call 1800-420-5577.'
        );
    }
}

function showWelcome() {
    appendBot(T[lang].welcome, null, T[lang].chips);
}

// ═══════════════════════════════════════════════
// Listeners
// ═══════════════════════════════════════════════
function setupListeners() {
    const input   = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    sendBtn.addEventListener('click', () => triggerSend());
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); triggerSend(); }
    });
    input.addEventListener('input', () => {
        input.style.height = '42px';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        if (humanMode) LiveChatManager.sendTypingEvent('typing_start');
    });
    document.getElementById('voice-toggle').addEventListener('click', toggleVoice);
}

function triggerSend(overrideText) {
    const input = document.getElementById('user-input');
    const text  = overrideText !== undefined ? overrideText : input.value.trim();
    if (!text || !sessionId) return;
    if (overrideText === undefined) { input.value = ''; input.style.height = '42px'; }
    sendMessage(text);
}

// ═══════════════════════════════════════════════
// Send message
// ═══════════════════════════════════════════════
async function sendMessage(text) {
    const site_url = '<?= site_url() ?>';

    if (humanMode) {
        LiveChatManager.sendLiveChatMessage(text);
        return;
    }

    appendUser(text);
    showTyping();
    setInputDisabled(true);

    const isEscReq = /(human|agent|operator|live agent|representative|executive|support person|customer care|real person|real agent|help desk|talk to agent|connect me|human support)/i.test(text);

    if (isEscReq) {
        console.log('Escalation requested');
        hideTyping();
        setInputDisabled(false);
        await doEscalate();
        return;
    }

    try {
        const url = site_url.replace(/\/$/, '') + '/chatbot/message';
        const payload = {
            session_id: sessionId,
            message: text,
            voice_enabled: voiceEnabled ? 1 : 0,
        };

        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
        });

        const responseText = await res.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid chatbot response:', responseText);
            throw new Error('Invalid server response');
        }

        hideTyping();

        if (data.new_session && data.session_id) {
            sessionId = data.session_id;
        }

        if (data.escalation) {
            showEscalationCard(data.contacts || {});
            failCount = 0;
        } else {
            // ✅ Use renderBotMessage instead of appendBot
            renderBotMessage(data);
            
            if (data.message && data.message.includes('1860-123-4284') && !data.ticket_number) {
                failCount++;
                if (failCount >= 3) {
                    failCount = 0;
                    await doEscalate();
                    return;
                }
            } else {
                failCount = 0;
            }
        }

    } catch (e) {
        console.error('sendMessage Error:', e);
        hideTyping();
        appendBot(T[lang].networkErr);
    } finally {
        setInputDisabled(false);
        const input = document.getElementById('user-input');
        if (input) input.focus();
        console.log('========== END ==========');
    }
}

// ══════════════════════════════════════════════════════════
// ✅ renderBotMessage — NEW: Handles message + options buttons
// ══════════════════════════════════════════════════════════
function renderBotMessage(response) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';

    // Replay button for audio
    const replay = (response.tts_url && voiceEnabled)
        ? `<button class="replay-btn" onclick="replayAudio('${escAttr(response.tts_url)}')" title="Replay audio">🔊</button>`
        : '';

    // Build message content
    let responseMessage = response.message || '';
    if (response.ticket_number && responseMessage.indexOf(response.ticket_number) === -1) {
        responseMessage += `\n\n🎫 Ticket Number: ${response.ticket_number}`;
    }
    let contentHtml = `<div class="bubble bot-bubble">${formatText(responseMessage)}${replay}</div>`;

    // ── OPTIONS BUTTONS ──────────────────────────────
    // Check for options in the response
    const options = response.options || [];
    if (options.length > 0) {
        const optionsHtml = options.map(opt => 
            `<button class="chatbot-option-btn" data-option-text="${escAttr(opt.label)}">${escHtml(opt.label)}</button>`
        ).join('');

        contentHtml += `<div class="chatbot-options">${optionsHtml}</div>`;
    }

    row.innerHTML = `<div class="avatar bot">🤖</div>
        <div>${contentHtml}</div>`;

    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();

    // ── Attach click events to option buttons ────────
    const optionBtns = row.querySelectorAll('.chatbot-option-btn');
    optionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const text = this.getAttribute('data-option-text') || this.textContent.trim();
            // Remove the options container after click
            const optionsContainer = this.closest('.chatbot-options');
            if (optionsContainer) {
                optionsContainer.remove();
            }
            // Send the selected option text as a message
            triggerSend(text);
        });
    });

    // Auto-play TTS if available
    if (response.tts_url && voiceEnabled) playAudio(response.tts_url);
}

// ══════════════════════════════════════════════════════════
// appendBot — Kept for backward compatibility 
// (used by welcome, errors, escalations, etc.)
// ══════════════════════════════════════════════════════════
function appendBot(text, ttsUrl = null, chips = []) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';

    const replay = (ttsUrl && voiceEnabled)
        ? `<button class="replay-btn" onclick="replayAudio('${escAttr(ttsUrl)}')" title="Replay audio">🔊</button>`
        : '';

    const chipsHtml = chips.length
        ? '<div class="quick-replies">' +
          chips.map(c => `<button class="chip" onclick="triggerSend('${escAttr(c.text)}')">${escHtml(c.label)}</button>`).join('') +
          '</div>'
        : '';

    row.innerHTML = `<div class="avatar bot">🤖</div>
        <div>
            <div class="bubble bot-bubble">${formatText(text)}${replay}</div>
            ${chipsHtml}
        </div>`;

    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();

    if (ttsUrl && voiceEnabled) playAudio(ttsUrl);
}

// ══════════════════════════════════════════════════════════
// LiveChatManager — WebSocket-based real-time live chat
// ══════════════════════════════════════════════════════════
const LiveChatManager = {
    ws:                null,
    chatId:            null,
    wsToken:           null,
    customerName:      '',
    reconnectAttempts: 0,
    reconnectTimer:    null,
    lastMessageId:     0,
    typingTimer:       null,
    isTypingSent:      false,
    isActive:          false,
    isWaiting:         false,
    agentsAvailable:   false,
    tokenIssuedAt:     0,
    pollingInterval:   null,
    _statusPollTimer:  null,

    wsConnect(token, chatId) {
        this.wsToken       = token;
        this.chatId        = chatId;
        this.tokenIssuedAt = Date.now();

        const proto = location.protocol === 'https:' ? 'wss://' : 'ws://';
        const url   = proto + location.host + '/ws';

        try {
            this.ws = new WebSocket(url);
        } catch (e) {
            console.warn('LiveChat WS open failed:', e);
            this.wsReconnect();
            return;
        }

        this.ws.onopen = () => {
            console.log('LiveChat WS open');
            this.reconnectAttempts = 0;
            this.sendSubscribe();
        };

        this.ws.onmessage = (event) => this.onWsMessage(event);

        this.ws.onclose = () => {
            console.log('LiveChat WS closed');
            if (humanMode) this.wsReconnect();
        };

        this.ws.onerror = (err) => {
            console.warn('LiveChat WS error:', err);
        };
    },

    sendSubscribe() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type:    'subscribe',
                role:    'customer',
                chat_id: this.chatId,
                token:   this.wsToken,
            }));
            if (this.lastMessageId > 0) {
                this.fetchMissedMessages();
            }
        }
    },

    onWsMessage(event) {
        let data;
        try { data = JSON.parse(event.data); } catch (e) { return; }

        switch (data.type) {
            case 'message':
                if (data.id && data.id > this.lastMessageId) {
                    this.lastMessageId = data.id;
                }
                if (data.sender !== 'customer') appendLiveMessage(data);
                break;

            case 'chat_claimed':
                this.isActive  = true;
                this.isWaiting = false;
                document.getElementById('header-sub').textContent = 'Connected to Agent';
                setInputDisabled(false);
                const qb = document.getElementById('queue-position-row');
                if (qb) qb.parentNode.removeChild(qb);
                appendLiveMessage({ sender: 'system', message: '✅ You are now connected to a live agent.' });
                break;

            case 'chat_closed':
                this.closeChat();
                break;

            case 'queue_updated':
                if (data.queue_position !== undefined && data.queue_position !== null) {
                    this.updateQueueBubble(data.queue_position);
                }
                break;

            case 'typing_start':
                if (data.sender !== 'customer') {
                    document.getElementById('agent-typing-label').style.display = 'block';
                }
                break;

            case 'typing_stop':
                if (data.sender !== 'customer') {
                    document.getElementById('agent-typing-label').style.display = 'none';
                }
                break;

            case 'error':
                console.warn('LiveChat WS error msg:', data.message);
                break;
        }
    },

    sendLiveChatMessage(text) {
        appendUser(text);

        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type:    'message',
                chat_id: this.chatId,
                sender:  'customer',
                message: text,
            }));
        } else {
            const siteUrl = '<?= site_url() ?>';
            fetch(siteUrl + 'chatbot/livechat/message', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ session_id: sessionId, sender: 'customer', message: text }),
            }).catch(e => console.warn('LiveChat HTTP send failed:', e));
        }

        this.sendTypingEvent('typing_stop');
    },

    wsReconnect() {
        if (this.reconnectTimer) return;
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.reconnectAttempts++;

            if (this.reconnectAttempts === 6) {
                appendBot('Connection lost. Retrying…');
            }

            const tokenAge = (Date.now() - this.tokenIssuedAt) / 1000;
            if (tokenAge > 270) {
                const siteUrl = '<?= site_url() ?>';
                fetch(siteUrl + 'chatbot/livechat/start', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ session_id: sessionId }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.ws_token) {
                        this.wsToken       = data.ws_token;
                        this.tokenIssuedAt = Date.now();
                    }
                    this.wsConnect(this.wsToken, this.chatId);
                })
                .catch(() => this.wsConnect(this.wsToken, this.chatId));
            } else {
                this.wsConnect(this.wsToken, this.chatId);
            }
        }, 3000);
    },

    fetchMissedMessages() {
        const siteUrl = '<?= site_url() ?>';
        fetch(`${siteUrl}chatbot/livechat/poll/${this.chatId}?last_id=${this.lastMessageId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.messages) {
                    data.messages.forEach(msg => {
                        if (msg.id > this.lastMessageId) this.lastMessageId = msg.id;
                        if (msg.sender !== 'customer') appendLiveMessage(msg);
                    });
                }
            })
            .catch(e => console.warn('fetchMissedMessages failed:', e));
    },

    sendTypingEvent(type) {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) return;
        if (type === 'typing_start') {
            if (!this.isTypingSent) {
                this.isTypingSent = true;
                this.ws.send(JSON.stringify({ type: 'typing_start', chat_id: this.chatId, sender: 'customer' }));
            }
            clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(() => {
                this.isTypingSent = false;
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.ws.send(JSON.stringify({ type: 'typing_stop', chat_id: this.chatId, sender: 'customer' }));
                }
            }, 2000);
        } else {
            clearTimeout(this.typingTimer);
            if (this.isTypingSent) {
                this.isTypingSent = false;
                this.ws.send(JSON.stringify({ type: 'typing_stop', chat_id: this.chatId, sender: 'customer' }));
            }
        }
    },

    startPollingFallback() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        const siteUrl = '<?= site_url() ?>';
        this.pollingInterval = setInterval(() => {
            fetch(`${siteUrl}chatbot/livechat/poll/${this.chatId}?last_id=${this.lastMessageId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (data.messages) {
                        data.messages.forEach(msg => {
                            if (msg.id > this.lastMessageId) this.lastMessageId = msg.id;
                            if (msg.sender !== 'customer') appendLiveMessage(msg);
                        });
                    }
                    if (data.queue_position !== undefined && data.queue_position !== null) {
                        this.updateQueueBubble(data.queue_position);
                    }
                    if (data.chat && data.chat.status === 'active' && !this.isActive) {
                        this.isActive  = true;
                        this.isWaiting = false;
                        document.getElementById('header-sub').textContent = 'Connected to Agent';
                        setInputDisabled(false);
                    }
                    if (data.chat && data.chat.status === 'closed') {
                        this.closeChat();
                    }
                })
                .catch(e => console.warn('LiveChat poll failed:', e));
        }, 3000);
    },

    async checkAgentAvailability() {
        const siteUrl = '<?= site_url() ?>';
        try {
            const res  = await fetch(siteUrl + 'agent/livechat/agents/status');
            const data = await res.json();
            this.agentsAvailable = !!(data.success && data.available);
        } catch (e) {
            console.warn('Agent status check failed:', e);
            this.agentsAvailable = false;
        }
        return this.agentsAvailable;
    },

    async submitForm() {
        const name   = document.getElementById('lc-name').value.trim();
        const mobile = document.getElementById('lc-mobile').value.trim();
        const errDiv = document.getElementById('livechat-form-errors');
        errDiv.textContent = '';

        if (!name) {
            errDiv.textContent = 'Please enter your name.';
            return;
        }
        const digits = mobile.replace(/\D/g, '').replace(/^91/, '');
        if (!/^[6-9]\d{9}$/.test(digits)) {
            errDiv.textContent = 'Please enter a valid 10-digit Indian mobile number.';
            return;
        }

        const formBubble = document.getElementById('mobile-collection-form');
        if (formBubble) {
            let node = formBubble;
            while (node && !node.classList.contains('bubble-row')) node = node.parentNode;
            if (node) node.parentNode.removeChild(node);
        }

        this.customerName = name;
        appendBot('Connecting you to a live agent. Please wait…');
        setInputDisabled(true);

        const siteUrl = '<?= site_url() ?>';
        try {
            const res  = await fetch(siteUrl + 'chatbot/livechat/start', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    session_id:      sessionId,
                    customer_name:   name,
                    customer_mobile: digits,
                }),
            });
            const data = await res.json();

            if (data.success) {
                humanMode      = true;
                this.chatId    = data.chat_id;
                this.wsToken   = data.ws_token;
                this.isWaiting = true;

                if (data.messages) {
                    data.messages.forEach(msg => {
                        if (msg.id > this.lastMessageId) this.lastMessageId = msg.id;
                    });
                }

                appendBot('You are in the queue. We will connect you shortly.');
                setInputDisabled(false);

                if (typeof WebSocket === 'undefined') {
                    this.startPollingFallback();
                } else {
                    this.wsConnect(data.ws_token, data.chat_id);
                    this.startStatusPoll();
                }
            } else {
                appendBot(data.message || 'Could not start live chat. Please try again.');
                setInputDisabled(false);
            }
        } catch (e) {
            console.error('LiveChat start error:', e);
            appendBot('Unable to connect to live chat. Please call 1860-123-4284.');
            setInputDisabled(false);
        }
    },

    startStatusPoll() {
        if (this._statusPollTimer) return;
        const siteUrl = '<?= site_url() ?>';
        this._statusPollTimer = setInterval(async () => {
            if (!humanMode || !this.chatId) {
                clearInterval(this._statusPollTimer);
                this._statusPollTimer = null;
                return;
            }
            try {
                const res  = await fetch(`${siteUrl}chatbot/livechat/poll/${this.chatId}?last_id=${this.lastMessageId}`);
                const data = await res.json();
                if (!data.success) return;

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        if (msg.id > this.lastMessageId) {
                            this.lastMessageId = msg.id;
                            if (msg.sender !== 'customer') appendLiveMessage(msg);
                        }
                    });
                }

                if (data.chat && data.chat.status === 'active' && !this.isActive) {
                    this.isActive  = true;
                    this.isWaiting = false;
                    document.getElementById('header-sub').textContent = 'Connected to Agent';
                    setInputDisabled(false);
                    const qb = document.getElementById('queue-position-row');
                    if (qb) qb.parentNode.removeChild(qb);
                    appendLiveMessage({ sender: 'system', message: '✅ You are now connected to a live agent.' });
                }

                if (data.chat && data.chat.status === 'closed' && humanMode) {
                    clearInterval(this._statusPollTimer);
                    this._statusPollTimer = null;
                    this.closeChat();
                }

                if (data.chat && data.chat.status === 'waiting' && data.chat.queue_position) {
                    this.updateQueueBubble(data.chat.queue_position);
                }
            } catch(e) {
                // silent
            }
        }, 4000);
    },

    updateQueueBubble(position) {
        let badge = document.getElementById('queue-position-bubble');
        if (!badge) {
            const msgs = document.getElementById('chat-messages');
            const row  = document.createElement('div');
            row.className = 'bubble-row assistant';
            row.id        = 'queue-position-row';
            row.innerHTML = `<div class="avatar bot">🤖</div>
                <div><span class="live-queue-badge" id="queue-position-bubble">Position in queue: ${escHtml(String(position))}</span></div>`;
            msgs.insertBefore(row, document.getElementById('typing-indicator'));
            scrollBottom();
        } else {
            badge.textContent = 'Position in queue: ' + position;
        }
    },

    closeChat() {
        humanMode      = false;
        this.isActive  = false;
        this.isWaiting = false;
        if (this.ws) { try { this.ws.close(); } catch(e){} this.ws = null; }
        if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
        if (this.reconnectTimer)  { clearTimeout(this.reconnectTimer);   this.reconnectTimer  = null; }
        if (this._statusPollTimer){ clearInterval(this._statusPollTimer); this._statusPollTimer = null; }
        document.getElementById('header-sub').textContent  = T[lang].headerSub;
        document.getElementById('agent-typing-label').style.display = 'none';
        appendBot('This chat has ended. You are now back in AI mode. How else can I help you?');
        setInputDisabled(false);
    },
};

// ══════════════════════════════════════════════════════════
// doEscalate — Check availability and show form
// ══════════════════════════════════════════════════════════
async function doEscalate() {
    hideTyping();
    setInputDisabled(false);

    const available = await LiveChatManager.checkAgentAvailability();

    if (!available) {
        appendBot(
            'No agents are currently available. You can leave your details and we will contact you, ' +
            'or call our helpline at 1860-123-4284.'
        );
        return;
    }

    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';
    row.innerHTML = `<div class="avatar bot">🤖</div>
        <div class="bubble bot-bubble" id="mobile-collection-form">
            <p><strong>Connect to a Live Agent</strong></p>
            <p>Please enter your details to connect.</p>
            <div id="livechat-form-errors" style="color:red;font-size:13px;margin-bottom:6px;"></div>
            <input type="text" id="lc-name" placeholder="Your name">
            <input type="tel" id="lc-mobile" placeholder="Mobile number (10 digits)">
            <button onclick="LiveChatManager.submitForm()">
                Connect
            </button>
        </div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// ══════════════════════════════════════════════════════════
// appendLiveMessage — helper for live chat message bubbles
// ══════════════════════════════════════════════════════════
function appendLiveMessage(msg) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    if (msg.sender === 'customer') {
        row.className = 'bubble-row user';
        row.innerHTML = `<div class="avatar user-av">👤</div>
            <div class="bubble user-bubble">${escHtml(msg.message)}</div>`;
    } else {
        row.className = 'bubble-row assistant';
        const icon = msg.sender === 'agent'
            ? '<i class="bi bi-person-badge-fill"></i>'
            : '🤖';
        row.innerHTML = `<div class="avatar bot">${icon}</div>
            <div class="bubble bot-bubble"><strong>${msg.sender === 'agent' ? 'Agent' : 'System'}</strong><br>${formatText(msg.message)}</div>`;
    }
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// ══════════════════════════════════════════════════════════
// Rendering
// ══════════════════════════════════════════════════════════
function appendUser(text) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row user';
    row.innerHTML = `<div class="avatar user-av">👤</div>
        <div class="bubble user-bubble">${escHtml(text)}</div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

function showEscalationCard(contacts) {
    const t   = T[lang];
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';
    const helpline = contacts.helpline || '1800-208-6633';
    const website  = contacts.website  || 'https://www.tatacapital.com/';
    const email    = contacts.email    || 'help@tatacapital.com';
    row.innerHTML = `<div class="avatar bot">🤖</div>
        <div class="escalation-card" role="alert">
            <h4>${escHtml(t.escalTitle)}</h4>
            <p style="margin-bottom:10px;color:#555;">${escHtml(t.escalSub)}</p>
            <div class="contact-item"><span>📱</span>
                <div><strong>${escHtml(t.helplineLabel)}</strong>
                    &nbsp;<a href="tel:18601234284">${escHtml(helpline)}</a>
                </div>
            </div>
            <div class="contact-item"><span>🌐</span>
                <div><strong>${escHtml(t.websiteLabel)}</strong>
                    &nbsp;<a href="${escHtml(website)}" target="_blank" rel="noopener">${escHtml(website)}</a>
                </div>
            </div>
            <div class="contact-item"><span>✉️</span>
                <div><strong>${escHtml(t.emailLabel)}</strong>
                    &nbsp;<a href="mailto:${escHtml(email)}">${escHtml(email)}</a>
                </div>
            </div>
        </div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// ══════════════════════════════════════════════════════════
// Typing indicator
// ══════════════════════════════════════════════════════════
function showTyping()  { document.getElementById('typing-indicator').classList.add('active'); scrollBottom(); }
function hideTyping()  { document.getElementById('typing-indicator').classList.remove('active'); }

// ══════════════════════════════════════════════════════════
// Audio
// ══════════════════════════════════════════════════════════
let currentAudio = null;

function playAudio(url) {
    if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
    }
    if (!audioCache[url]) audioCache[url] = new Audio(url);
    currentAudio = audioCache[url];
    currentAudio.currentTime = 0;
    currentAudio.play().catch(e => console.warn('Audio blocked:', e));
}

function replayAudio(url) { playAudio(url); }

function toggleVoice() {
    voiceEnabled = !voiceEnabled;
    const btn = document.getElementById('voice-toggle');
    btn.textContent = voiceEnabled ? '🔊' : '🔇';
    const t = T[lang];
    btn.classList.toggle('muted', !voiceEnabled);
    btn.setAttribute('aria-pressed', String(voiceEnabled));
    btn.title = voiceEnabled ? t.muteTitle : t.unmuteTitle;

    if (!voiceEnabled) {
        Object.values(audioCache).forEach(audio => {
            try {
                audio.pause();
                audio.currentTime = 0;
            } catch (e) {
                console.error(e);
            }
        });
    }
}

// ══════════════════════════════════════════════════════════
// STT — Web Speech API
// ══════════════════════════════════════════════════════════
function initSTT() {
    const micBtn = document.getElementById('mic-btn');
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        micBtn.disabled = true;
        micBtn.title = lang === 'hi'
            ? 'वॉइस इनपुट इस ब्राउज़र में समर्थित नहीं है।'
            : 'Voice input is not supported in this browser.';
        return;
    }
    micBtn.title = T[lang].micTitle;
    if (recognition) { try { recognition.stop(); } catch(e){} }
    recognition = new SR();
    recognition.lang           = T[lang].micLang;
    recognition.interimResults = false;
    recognition.continuous     = false;

    recognition.onresult = (e) => {
        const t = e.results[0][0].transcript;
        document.getElementById('user-input').value = t;
        triggerSend(t);
    };
    recognition.onstart = () => { micBtn.classList.add('recording'); micBtn.title = lang === 'hi' ? 'सुन रहा हूँ...' : 'Listening...'; };
    recognition.onend   = () => { micBtn.classList.remove('recording'); micBtn.title = T[lang].micTitle; };
    recognition.onerror = (e) => { console.warn('STT:', e.error); micBtn.classList.remove('recording'); };

    micBtn.onclick = () => micBtn.classList.contains('recording') ? recognition.stop() : recognition.start();
}

// ══════════════════════════════════════════════════════════
// Utilities
// ══════════════════════════════════════════════════════════
function scrollBottom()          { const m = document.getElementById('chat-messages'); m.scrollTop = m.scrollHeight; }
function setInputDisabled(d)     { document.getElementById('user-input').disabled = d; document.getElementById('send-btn').disabled = d; }
function escHtml(s)              { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s)              { return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

function formatText(raw) {
    if (!raw) return '';

    let text = raw
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/__(.*?)__/g, '$1')
        .replace(/\*(.*?)\*/g, '$1')
        .replace(/_(.*?)_/g, '$1')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/#+\s?/g, '');

    text = escHtml(text);

    text = text.replace(
        /\[(.*?)\]\((https?:\/\/[^\s)]+)\)/gi,
        '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    text = text.replace(
        /\[(https?:\/\/[^\]\s]+)\]/gi,
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    text = text.replace(
        /(^|[\s(>])(https?:\/\/[^\s<]+)/gi,
        function (match, prefix, url) {
            let trailing = '';
            while (/[.,!?;:\])]+$/.test(url)) {
                trailing = url.slice(-1) + trailing;
                url = url.slice(0, -1);
            }
            return prefix + '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>' + trailing;
        }
    );

    const lines = text.split('\n');
    let html = '';
    let inOl = false;
    let inUl = false;

    function closeLists() {
        if (inOl) { html += '</ol>'; inOl = false; }
        if (inUl) { html += '</ul>'; inUl = false; }
    }

    lines.forEach(function (line) {
        line = line.trim();
        if (!line) { closeLists(); return; }

        if (/^\d+[\.\)]\s+/.test(line)) {
            if (inUl) { html += '</ul>'; inUl = false; }
            if (!inOl) { html += '<ol>'; inOl = true; }
            html += '<li>' + line.replace(/^\d+[\.\)]\s+/, '') + '</li>';
            return;
        }

        if (/^[-•*]\s+/.test(line)) {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (!inUl) { html += '<ul>'; inUl = true; }
            html += '<li>' + line.replace(/^[-•*]\s+/, '') + '</li>';
            return;
        }

        closeLists();
        html += '<p>' + line + '</p>';
    });

    closeLists();
    return html;
}

console.log('Chatbot loaded successfully!');
</script>

</body>
</html>