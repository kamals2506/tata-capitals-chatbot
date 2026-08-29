<!DOCTYPE html>
<html lang="en" id="html-root">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="/trainingnew/images/icon.png">

<style>
@media (max-width:768px){

    .chat-container{
        width:100%;
        max-width:100%;
        height:100vh;
        margin:0;
        border-radius:0;
    }

    .chat-header{
        padding:10px;
    }

    .chat-header .title{
        font-size:15px;
    }

    .chat-header .subtitle{
        font-size:11px;
    }

    .bubble{
        max-width:92%;
        font-size:14px;
    }

    .input-bar{
        padding:8px;
    }

    .icon-btn{
        width:38px;
        height:38px;
    }

    #user-input{
        font-size:14px;
    }

    .lang-card{
        width:92%;
        max-width:300px;
        padding:18px;
    }

    .lang-btns{
        flex-direction:column;
        gap:8px;
    }
}

@media (max-width:480px){

    .chat-header .subtitle{
        display:none;
    }

    .lang-card{
        max-width:280px;
        padding:14px;
    }

    .lang-card h1{
        font-size:16px;
    }

    .bubble{
        max-width:95%;
    }
}

</style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-CAI AllCargo Support Assistant</title>
    <style>

#lang-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:99999;
}

.lang-card{
    width:320px;              /* Reduced from 400+ */
    padding:20px 18px;        /* Reduced padding */
    background:#fff;
    border-radius:16px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

.logo img{
    width:36px !important;
    height:36px !important;
    margin-bottom:6px;
}

.lang-card h1{
    font-size:20px;
    margin:4px 0;
    line-height:1.2;
}

.lang-card p{
    font-size:13px;
    color:#666;
    margin:10px 0 16px;
    line-height:1.4;
}

.lang-btns{
    display:flex;
    gap:10px;
}

.lang-btn{
    flex:1;
    padding:8px 8px;
    border:1px solid #ddd;
    border-radius:10px;
    background:#fff;
    cursor:pointer;
    transition:.2s;
}

.lang-btn:hover{
    border-color:#E60012;
    transform:translateY(-2px);
}

.flag{
    display:block;
    font-size:20px;
    margin-bottom:4px;
}

.lang-name{
    display:block;
    font-size:11px;
    color:#777;
    margin-top:2px;
}

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
            display: flex; flex-direction: column; height: 100vh;
            max-width: 900px; margin: 0 auto; background: #fff;
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

        /* Escalation card */
        .escalation-card {
            background:#fff8e1;border:2px solid #ff8f00;
            border-radius:12px;padding:14px 16px;max-width:80%;font-size:.9rem;
        }
        .escalation-card h4 { color:#e65100;margin-bottom:8px;font-size:1rem; }
        .escalation-card .contact-item { margin:6px 0;display:flex;gap:8px;align-items:center; }

        /* Quick-reply chips */
        .quick-replies { display:flex;flex-wrap:wrap;gap:8px;margin-top:10px; }
        .chip {
            background:#e8f0fe;border:1px solid #4a90d9;color:#0052cc;
            border-radius:20px;padding:5px 14px;font-size:.82rem;
            cursor:pointer;transition:background .2s;white-space:nowrap;
        }
        .chip:hover { background:#c9d9f8; }

        /* Replay btn */
        .replay-btn {
            background:none;border:none;cursor:pointer;font-size:1rem;
            padding:2px 6px;opacity:.65;transition:opacity .2s;vertical-align:middle;
        }
        .replay-btn:hover { opacity:1; }

        /* Typing indicator */
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

        /* Input bar */
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

        @media(max-width:600px){
            .bubble{max-width:88%;font-size:.88rem;}
            .lang-btns{flex-direction:column;}
        }

        /* ── Live Chat ──────────────────────────────── */
        #mobile-collection-form { margin-bottom: 8px; }
        #mobile-collection-form input:focus { outline: 2px solid #0077e6; }
        .live-queue-badge {
            background:#fff3cd;border:1px solid #ffc107;border-radius:12px;
            padding:6px 12px;font-size:13px;margin:4px 0;display:inline-block;
        }
        #agent-typing-label { font-size:.82rem; color:#888; margin-top:4px; display:none; }
    </style>
</head>
<body>

<!-- ── Language Picker Overlay ───────────────────────── -->
<div id="lang-overlay">
    <div class="lang-card">
        <div class="logo">📡</div>
<div class="logo">
    <img src="https://bpoc.in/Allcargo/public/images/logo_icon.png" alt="BPOC Logo"  style="width:40px;height:40px;">
</div>

	<h1>B-CAI</h1>
        <h1>AllCargo Support</h1>
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
<div class="chat-container" id="chat-app" style="display:none;">
    <div class="chat-header">
        <div class="logo">📡</div>
        <div>
            <div class="title" id="header-title">Allcargo Support Assistant</div>
            <div class="subtitle" id="header-sub">AI-Powered • 24/7</div>
        </div>
        <div class="online-dot" title="Online"></div>
        <button id="lang-switch-btn" onclick="switchLanguage()" title="Change language"></button>
    </div>

    <div id="chat-messages" role="log" aria-live="polite">
        <div id="typing-indicator" role="status">
           <div class="avatar bot">&#129302;</div> 
            <div class="typing-dots"><span></span><span></span><span></span></div>
        </div>
        <div id="agent-typing-label">Agent is typing…</div>
    </div>

    <div class="input-bar">
        <button id="voice-toggle" class="icon-btn" aria-pressed="true">🔊</button>
        <textarea id="user-input" rows="1" autocomplete="off" aria-label="Message input"></textarea>
        <button id="mic-btn" class="icon-btn">🎤</button>
        <button id="send-btn" class="icon-btn" aria-label="Send">&#10148;</button>
    </div>
</div>

<script>
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Translations
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
const T = {
    en: {
        headerTitle   : 'Allcargo Logistics Support Assistant',
        headerSub     : 'AI-Powered Customer Support \u2022 24/7',
        placeholder   : 'Type your message here...',
        switchBtn     : '\ud83c\uddee\ud83c\uddf3 \u0939\u093f\u0902\u0926\u0940',
        micTitle      : 'Click to speak',
        micLang       : 'en-IN',
        muteTitle     : 'Mute voice',
        unmuteTitle   : 'Unmute voice',
        networkErr    : 'A network error occurred. Please try again or call 1860-123-4284.',
        welcome       : "Hello! I'm your Allcargo Logistics Support Assistant. How can I help you today?",
        chips: [
            { label:'\ud83d\udce6 Track Shipment',     text:'I want to track my shipment' },
            { label:'\ud83d\udcb0 Get Estimate',       text:'I want to get a shipping rate estimate' },
            { label:'\ud83d\ude9a Book Pickup',        text:'I want to book a pickup for my shipment' },
            { label:'\ud83d\udccd Branch Locator',     text:'Help me find the nearest Allcargo branch' },
            { label:'\ud83d\udccb Raise Complaint',    text:'I want to register a complaint' },
            { label:'\ud83e\uddfe Track Complaint',    text:'I want to track my complaint or claim status' },
            { label:'\ud83d\udc64 Human Agent',        text:'Connect me to a human agent' },
        ],
        escalTitle    : '\ud83d\udcde Connect to a Human Agent',
        escalSub      : 'You will be connected to an Allcargo Logistics specialist shortly.',
        helplineLabel : 'Helpline (Toll-Free):',
        websiteLabel  : 'Website:',
        emailLabel    : 'Email Support:',
        emailNote     : 'customerservices@allcargologistics.com',
    },
    hi: {
        headerTitle   : 'Allcargo Logistics \u0938\u0939\u093e\u092f\u0915',
        headerSub     : 'AI \u0917\u094d\u0930\u093e\u0939\u0915 \u0938\u0947\u0935\u093e \u2022 24/7',
        placeholder   : '\u092f\u0939\u093e\u0901 \u0905\u092a\u0928\u093e \u0938\u0935\u093e\u0932 \u0932\u093f\u0916\u0947\u0902...',
        switchBtn     : '\ud83c\uddec\ud83c\udde7 English',
        micTitle      : '\u092c\u094b\u0932\u0915\u0930 \u0938\u0935\u093e\u0932 \u092a\u0942\u091b\u0947\u0902',
        micLang       : 'hi-IN',
        muteTitle     : '\u0906\u0935\u093e\u091c\u093c \u092c\u0902\u0926 \u0915\u0930\u0947\u0902',
        unmuteTitle   : '\u0906\u0935\u093e\u091c\u093c \u091a\u093e\u0932\u0942 \u0915\u0930\u0947\u0902',
        networkErr    : '\u0928\u0947\u091f\u0935\u0930\u094d\u0915 \u0924\u094d\u0930\u0941\u091f\u093f \u0939\u0941\u0908\u0964 \u0915\u0943\u092a\u092f\u093e \u092a\u0941\u0928\u0903 \u092a\u094d\u0930\u092f\u093e\u0938 \u0915\u0930\u0947\u0902 \u092f\u093e 1860-123-4284 \u092a\u0930 \u0915\u0949\u0932 \u0915\u0930\u0947\u0902\u0964',
        welcome       : '\u0928\u092e\u0938\u094d\u0924\u0947! \u092e\u0948\u0902 Allcargo Logistics \u0938\u0939\u093e\u092f\u0915 \u0939\u0942\u0901\u0964 \u0906\u091c \u092e\u0948\u0902 \u0906\u092a\u0915\u0940 \u0915\u0948\u0938\u0947 \u092e\u0926\u0926 \u0915\u0930 \u0938\u0915\u0924\u093e \u0939\u0942\u0901?',
        chips: [
            { label:'\ud83d\udce6 \u0936\u093f\u092a\u092e\u0947\u0902\u091f \u091f\u094d\u0930\u0948\u0915 \u0915\u0930\u0947\u0902',   text:'\u092e\u0941\u091d\u0947 \u0905\u092a\u0928\u093e \u0936\u093f\u092a\u092e\u0947\u0902\u091f \u091f\u094d\u0930\u0948\u0915 \u0915\u0930\u0928\u093e \u0939\u0948' },
            { label:'\ud83d\udcb0 \u090f\u0938\u094d\u091f\u093f\u092e\u0947\u091f \u0932\u0947\u0902',          text:'\u092e\u0941\u091d\u0947 \u0936\u093f\u092a\u093f\u0902\u0917 \u0930\u0947\u091f \u0915\u093e \u090f\u0938\u094d\u091f\u093f\u092e\u0947\u091f \u091a\u093e\u0939\u093f\u090f' },
            { label:'\ud83d\ude9a \u092a\u093f\u0915\u0905\u092a \u092c\u0941\u0915 \u0915\u0930\u0947\u0902',        text:'\u092e\u0941\u091d\u0947 \u0905\u092a\u0928\u0947 \u0936\u093f\u092a\u092e\u0947\u0902\u091f \u0915\u0947 \u0932\u093f\u090f \u092a\u093f\u0915\u0905\u092a \u092c\u0941\u0915 \u0915\u0930\u0928\u093e \u0939\u0948' },
            { label:'\ud83d\udccd \u092c\u094d\u0930\u093e\u0902\u091a \u0932\u094b\u0915\u0947\u091f\u0930',         text:'\u092e\u0941\u091d\u0947 \u0928\u091c\u093c\u0926\u0940\u0915\u0940 Allcargo \u092c\u094d\u0930\u093e\u0902\u091a \u0916\u094b\u091c\u0928\u0947 \u092e\u0947\u0902 \u092e\u0926\u0926 \u0915\u0930\u0947\u0902' },
            { label:'\ud83d\udccb \u0936\u093f\u0915\u093e\u092f\u0924 \u0926\u0930\u094d\u091c \u0915\u0930\u0947\u0902',      text:'\u092e\u0941\u091d\u0947 \u0936\u093f\u0915\u093e\u092f\u0924 \u0926\u0930\u094d\u091c \u0915\u0930\u0928\u0940 \u0939\u0948' },
            { label:'\ud83e\uddfe \u0936\u093f\u0915\u093e\u092f\u0924 \u091f\u094d\u0930\u0948\u0915 \u0915\u0930\u0947\u0902',     text:'\u092e\u0941\u091d\u0947 \u0905\u092a\u0928\u0940 \u0936\u093f\u0915\u093e\u092f\u0924 \u092f\u093e \u0915\u094d\u0932\u0947\u092e \u0915\u0940 \u0938\u094d\u0925\u093f\u0924\u093f \u091c\u093e\u0928\u0928\u0940 \u0939\u0948' },
            { label:'\ud83d\udc64 Human Agent',          text:'\u092e\u0941\u091d\u0947 human agent \u0938\u0947 \u092c\u093e\u0924 \u0915\u0930\u0928\u0940 \u0939\u0948' },
        ],
        escalTitle    : '\ud83d\udcde \u092e\u093e\u0928\u0935 \u090f\u091c\u0947\u0902\u091f \u0938\u0947 \u091c\u0941\u0921\u093c\u0947\u0902',
        escalSub      : '\u0906\u092a\u0915\u094b \u091c\u0932\u094d\u0926 \u0939\u0940 \u090f\u0915 Allcargo Logistics \u0935\u093f\u0936\u0947\u0937\u091c\u094d\u091e \u0938\u0947 \u091c\u094b\u0921\u093c\u093e \u091c\u093e\u090f\u0917\u093e\u0964',
        helplineLabel : '\u0939\u0947\u0932\u094d\u092a\u0932\u093e\u0907\u0928 (\u0928\u093f\u0903\u0936\u0941\u0932\u094d\u0915):',
        websiteLabel  : '\u0935\u0947\u092c\u0938\u093e\u0907\u091f:',
        emailLabel    : '\u0908\u092e\u0947\u0932 \u0938\u092a\u094b\u0930\u094d\u091f:',
        emailNote     : 'customerservices@allcargologistics.com',
    },
};

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// State
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
let sessionId    = null;
let lang         = 'en';
let voiceEnabled = true;
let audioCache   = {};
let recognition  = null;
let failCount    = 0;
let humanMode    = false;

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Language selection
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
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
    // Show language overlay again to let user pick
    document.getElementById('chat-app').style.display     = 'none';
    document.getElementById('lang-overlay').style.display = 'flex';
    sessionId = null;
    audioCache = {};
    failCount  = 0;
    // Clear messages (except typing indicator)
    const msgs = document.getElementById('chat-messages');
    const ti   = document.getElementById('typing-indicator');
    while (msgs.firstChild && msgs.firstChild !== ti) msgs.removeChild(msgs.firstChild);
    if (ti.parentNode !== msgs) msgs.appendChild(ti);
}

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Session
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
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
        console.error('Error Message:', e.message);
        console.error('Error Stack:', e.stack);

        appendBot(
            lang === 'hi'
                ? 'Allcargo Logistics \u0938\u0947 \u0915\u0928\u0947\u0915\u094d\u091f \u0928\u0939\u0940\u0902 \u0939\u094b \u092a\u093e\u092f\u093e\u0964 \u0915\u0943\u092a\u092f\u093e \u092a\u0947\u091c \u0930\u0940\u092b\u094d\u0930\u0947\u0936 \u0915\u0930\u0947\u0902 \u092f\u093e 1860-123-4284 \u092a\u0930 \u0915\u0949\u0932 \u0915\u0930\u0947\u0902\u0964'
                : 'Unable to connect. Please refresh the page or call 1860-123-4284.'
        );
    }
}

function showWelcome() {
    appendBot(T[lang].welcome, null, T[lang].chips);
}

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Listeners
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
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
        // Task 11.4 — typing events for live chat
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

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Send message
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550

async function sendMessage(text) {

const site_url = '<?= site_url() ?>';

 if(humanMode){

        LiveChatManager.sendLiveChatMessage(text);

        return;

    }


    appendUser(text);
    showTyping();
    setInputDisabled(true);

    const isEscReq = /(human|agent|operator|live agent|representative|executive|support person|customer care|real person|real agent|help desk|talk to agent|connect me|human support)/i.test(text);

    if (isEscReq) {
        console.log('Escalation requested');
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
        const data = JSON.parse(responseText);


        hideTyping();

        if (data.new_session && data.session_id) {

            sessionId = data.session_id;
        }

        if (data.escalation) {



            showEscalationCard(data.contacts || {});
            failCount = 0;

        } else {



            appendBot(data.message, data.tts_url);

            if (
                data.message &&
                data.message.includes('1860-123-4284') &&
                !data.ticket_number
            ) {

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
        console.error('Error Message:', e.message);
        console.error('Error Stack:', e.stack);

        hideTyping();
        appendBot(T[lang].networkErr);

    } finally {

        setInputDisabled(false);

        const input = document.getElementById('user-input');

        if (input) {
            input.focus();
        }

        console.log('========== END ==========');
    }
}

async function sendLiveMessage(message){
    // Legacy stub — now routed through LiveChatManager.sendLiveChatMessage
    LiveChatManager.sendLiveChatMessage(message);
}

// ══════════════════════════════════════════════════════════
// appendLiveMessage — helper for live chat message bubbles
// ══════════════════════════════════════════════════════════
function appendLiveMessage(msg) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    if (msg.sender === 'customer') {
        row.className = 'bubble-row user';
        row.innerHTML = `<div class="avatar user-av">&#128100;</div>
            <div class="bubble user-bubble">${escHtml(msg.message)}</div>`;
    } else {
        row.className = 'bubble-row assistant';
        const icon = msg.sender === 'agent'
            ? '<i class="bi bi-person-badge-fill"></i>'
            : '&#129302;';
        row.innerHTML = `<div class="avatar bot">${icon}</div>
            <div class="bubble bot-bubble"><strong>${msg.sender === 'agent' ? 'Agent' : 'System'}</strong><br>${formatText(msg.message)}</div>`;
    }
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// ══════════════════════════════════════════════════════════
// LiveChatManager — WebSocket-based real-time live chat
// Tasks 11.2, 11.3, 11.4, 11.5
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

    // ── Task 11.2: open WebSocket connection ─────────
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

    // Send subscribe message after open
    sendSubscribe() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type:    'subscribe',
                role:    'customer',
                chat_id: this.chatId,
                token:   this.wsToken,
            }));
            // Fetch any missed messages after reconnect
            if (this.lastMessageId > 0) {
                this.fetchMissedMessages();
            }
        }
    },

    // ── Task 11.2: handle incoming WS messages ───────
    onWsMessage(event) {
        let data;
        try { data = JSON.parse(event.data); } catch (e) { return; }

        switch (data.type) {
            case 'message':
                if (data.id && data.id > this.lastMessageId) {
                    this.lastMessageId = data.id;
                }
                // Only render non-customer messages — customer already sees theirs via appendUser()
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

    // ── Task 11.2: send a live chat message ──────────
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
            // HTTP fallback
            const siteUrl = '<?= site_url() ?>';
            fetch(siteUrl + 'chatbot/livechat/message', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ session_id: sessionId, sender: 'customer', message: text }),
            }).catch(e => console.warn('LiveChat HTTP send failed:', e));
        }

        // Stop typing indicator after sending
        this.sendTypingEvent('typing_stop');
    },

    // ── Task 11.3: reconnect with infinite retry ─────
    wsReconnect() {
        if (this.reconnectTimer) return;
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.reconnectAttempts++;

            if (this.reconnectAttempts === 6) {
                appendBot('Connection lost. Retrying…');
            }

            // If token might be expired (5 min TTL), refresh it
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

    // Fetch missed messages after reconnect
    fetchMissedMessages() {
        const siteUrl = '<?= site_url() ?>';
        fetch(`${siteUrl}chatbot/livechat/poll/${this.chatId}?last_id=${this.lastMessageId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.messages) {
                    data.messages.forEach(msg => {
                        if (msg.id > this.lastMessageId) this.lastMessageId = msg.id;
                        // Skip customer's own messages
                        if (msg.sender !== 'customer') appendLiveMessage(msg);
                    });
                }
            })
            .catch(e => console.warn('fetchMissedMessages failed:', e));
    },

    // ── Task 11.4: typing event emitter (debounced) ──
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

    // ── Task 11.5: HTTP polling fallback ─────────────
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
                            // Skip customer's own messages — already shown via appendUser()
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

    // ── Task 11.6: check agent availability ──────────
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

    // ── Task 11.1: submit MobileCollectionForm ───────
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

        // Remove form bubble row
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

                // Task 11.5 — choose WS or polling
                if (typeof WebSocket === 'undefined') {
                    this.startPollingFallback();
                } else {
                    this.wsConnect(data.ws_token, data.chat_id);
                    // Also start a status poll every 5 seconds as a safety net
                    // in case the WS broadcast for chat_claimed doesn't arrive
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

    // Safety-net status poll — runs every 4 seconds alongside WebSocket
    // catches chat_claimed/chat_closed if WS broadcast doesn't arrive
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

                // Process any missed messages
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        if (msg.id > this.lastMessageId) {
                            this.lastMessageId = msg.id;
                            // Never re-render customer's own messages — already shown via appendUser()
                            if (msg.sender !== 'customer') appendLiveMessage(msg);
                        }
                    });
                }

                // Detect chat_claimed via HTTP poll
                if (data.chat && data.chat.status === 'active' && !this.isActive) {
                    this.isActive  = true;
                    this.isWaiting = false;
                    document.getElementById('header-sub').textContent = 'Connected to Agent';
                    setInputDisabled(false);
                    const qb = document.getElementById('queue-position-row');
                    if (qb) qb.parentNode.removeChild(qb);
                    appendLiveMessage({ sender: 'system', message: '✅ You are now connected to a live agent.' });
                }

                // Detect chat_closed via HTTP poll
                if (data.chat && data.chat.status === 'closed' && humanMode) {
                    clearInterval(this._statusPollTimer);
                    this._statusPollTimer = null;
                    this.closeChat();
                }

                // Update queue position while waiting
                if (data.chat && data.chat.status === 'waiting' && data.chat.queue_position) {
                    this.updateQueueBubble(data.chat.queue_position);
                }
            } catch(e) {
                // silent
            }
        }, 4000);
    },

    // Update or insert queue position badge
    updateQueueBubble(position) {
        let badge = document.getElementById('queue-position-bubble');
        if (!badge) {
            const msgs = document.getElementById('chat-messages');
            const row  = document.createElement('div');
            row.className = 'bubble-row assistant';
            row.id        = 'queue-position-row';
            row.innerHTML = `<div class="avatar bot">&#129302;</div>
                <div><span class="live-queue-badge" id="queue-position-bubble">Position in queue: ${escHtml(String(position))}</span></div>`;
            msgs.insertBefore(row, document.getElementById('typing-indicator'));
            scrollBottom();
        } else {
            badge.textContent = 'Position in queue: ' + position;
        }
    },

    // Close the chat and return to AI mode
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
// doEscalate — Task 11.6: check availability first
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

    // Task 11.1 — show MobileCollectionForm inline
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';
    row.innerHTML = `<div class="avatar bot">&#129302;</div>
        <div class="bubble bot-bubble" id="mobile-collection-form">
            <p><strong>Connect to a Live Agent</strong></p>
            <p>Please enter your details to connect.</p>
            <div id="livechat-form-errors" style="color:red;font-size:13px;margin-bottom:6px;"></div>
            <input type="text" id="lc-name" placeholder="Your name"
                style="width:100%;padding:6px;margin-bottom:8px;border-radius:6px;border:1px solid #ccc;">
            <input type="tel" id="lc-mobile" placeholder="Mobile number (10 digits)"
                style="width:100%;padding:6px;margin-bottom:8px;border-radius:6px;border:1px solid #ccc;">
            <button onclick="LiveChatManager.submitForm()"
                style="background:#0077e6;color:#fff;border:none;padding:8px 18px;border-radius:20px;cursor:pointer;width:100%;">
                Connect
            </button>
        </div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Rendering
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
function appendUser(text) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row user';
    row.innerHTML = `<div class="avatar user-av">\u{1F464}</div>
        <div class="bubble user-bubble">${escHtml(text)}</div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

function appendBot(text, ttsUrl = null, chips = []) {
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';

    // Only show replay button when there is audio AND voice output is on
    const replay = (ttsUrl && voiceEnabled)
        ? `<button class="replay-btn" onclick="replayAudio('${escAttr(ttsUrl)}')" title="Replay audio">\u{1F50A}</button>` : '';

    const chipsHtml = chips.length
        ? '<div class="quick-replies">' +
          chips.map(c => `<button class="chip" onclick="triggerSend('${escAttr(c.text)}')">${escHtml(c.label)}</button>`).join('') +
          '</div>' : '';
    row.innerHTML = `<div class="avatar bot">\u{1F916}</div>
        <div>
            <div class="bubble bot-bubble">${formatText(text)}${replay}</div>
            ${chipsHtml}
        </div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();

    // Auto-play only when voice is enabled
    if (ttsUrl && voiceEnabled) playAudio(ttsUrl);
}


function showEscalationCard(contacts) {
    const t   = T[lang];
    const msgs = document.getElementById('chat-messages');
    const row  = document.createElement('div');
    row.className = 'bubble-row assistant';
    const helpline = contacts.helpline || '1860-123-4284';
    const website  = contacts.website  || 'https://www.allcargologistics.com/';
    const email    = contacts.email    || 'customerservices@allcargologistics.com';
    row.innerHTML = `<div class="avatar bot">\u{1F916}</div>
        <div class="escalation-card" role="alert">
            <h4>${escHtml(t.escalTitle)}</h4>
            <p style="margin-bottom:10px;color:#555;">${escHtml(t.escalSub)}</p>
            <div class="contact-item"><span>\ud83d\udcf1</span>
                <div><strong>${escHtml(t.helplineLabel)}</strong>
                    &nbsp;<a href="tel:18601234284">${escHtml(helpline)}</a>
                </div>
            </div>
            <div class="contact-item"><span>\ud83c\udf10</span>
                <div><strong>${escHtml(t.websiteLabel)}</strong>
                    &nbsp;<a href="${escHtml(website)}" target="_blank" rel="noopener">${escHtml(website)}</a>
                </div>
            </div>
            <div class="contact-item"><span>\u2709\ufe0f</span>
                <div><strong>${escHtml(t.emailLabel)}</strong>
                    &nbsp;<a href="mailto:${escHtml(email)}">${escHtml(email)}</a>
                </div>
            </div>
        </div>`;
    msgs.insertBefore(row, document.getElementById('typing-indicator'));
    scrollBottom();
}

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Typing indicator
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
function showTyping()  { document.getElementById('typing-indicator').classList.add('active');    scrollBottom(); }
function hideTyping()  { document.getElementById('typing-indicator').classList.remove('active'); }

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Audio
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
let currentAudio = null;

function playAudio(url) {
    // Stop any currently playing audio first
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

    console.log('voiceEnabled:', voiceEnabled);

   const btn = document.getElementById('voice-toggle');
    btn.textContent = voiceEnabled ? '\u{1F50A}' : '\u{1F507}';

    const t = T[lang];


    btn.classList.toggle('muted', !voiceEnabled);
    btn.setAttribute('aria-pressed', String(voiceEnabled));
    btn.title = voiceEnabled ? t.muteTitle : t.unmuteTitle;

    // Stop currently playing audio
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

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// STT \u2014 Web Speech API
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
function initSTT() {
    const micBtn = document.getElementById('mic-btn');
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        micBtn.disabled = true;
        micBtn.title = lang === 'hi'
            ? '\u0935\u0949\u0907\u0938 \u0907\u0928\u092a\u0941\u091f \u0907\u0938 \u092c\u094d\u0930\u093e\u0909\u091c\u093c\u0930 \u092e\u0947\u0902 \u0938\u092e\u0930\u094d\u0925\u093f\u0924 \u0928\u0939\u0940\u0902 \u0939\u0948\u0964'
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
    recognition.onstart = () => { micBtn.classList.add('recording'); micBtn.title = lang === 'hi' ? '\u0938\u0941\u0928 \u0930\u0939\u093e \u0939\u0942\u0901...' : 'Listening...'; };
    recognition.onend   = () => { micBtn.classList.remove('recording'); micBtn.title = T[lang].micTitle; };
    recognition.onerror = (e) => { console.warn('STT:', e.error); micBtn.classList.remove('recording'); };

    micBtn.onclick = () => micBtn.classList.contains('recording') ? recognition.stop() : recognition.start();
}

// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
// Utilities
// \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
function scrollBottom()          { const m = document.getElementById('chat-messages'); m.scrollTop = m.scrollHeight; }
function setInputDisabled(d)     { document.getElementById('user-input').disabled = d; document.getElementById('send-btn').disabled = d; }
function escHtml(s)              { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s)              { return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

function formatText(raw) {
    if (!raw) return '';

    // Remove markdown formatting
    let text = raw
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/__(.*?)__/g, '$1')
        .replace(/\*(.*?)\*/g, '$1')
        .replace(/_(.*?)_/g, '$1')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/#+\s?/g, '');

    // Escape HTML
    text = escHtml(text);

    // -------------------------------------
    // Markdown links
    // [Book Shipment](https://example.com)
    // -------------------------------------
    text = text.replace(
        /\[(.*?)\]\((https?:\/\/[^\s)]+)\)/gi,
        '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    // -------------------------------------
    // URLs inside brackets
    // [https://example.com]
    // -------------------------------------
    text = text.replace(
        /\[(https?:\/\/[^\]\s]+)\]/gi,
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    // -------------------------------------
    // Plain URLs
    // -------------------------------------
    text = text.replace(
        /(^|[\s(>])(https?:\/\/[^\s<]+)/gi,
        function (match, prefix, url) {

            // Remove trailing punctuation
            let trailing = '';

            while (/[.,!?;:\])]+$/.test(url)) {
                trailing = url.slice(-1) + trailing;
                url = url.slice(0, -1);
            }

            return (
                prefix +
                '<a href="' +
                url +
                '" target="_blank" rel="noopener noreferrer">' +
                url +
                '</a>' +
                trailing
            );
        }
    );

    const lines = text.split('\n');

    let html = '';
    let inOl = false;
    let inUl = false;

    function closeLists() {
        if (inOl) {
            html += '</ol>';
            inOl = false;
        }

        if (inUl) {
            html += '</ul>';
            inUl = false;
        }
    }

    lines.forEach(function (line) {

        line = line.trim();

        if (!line) {
            closeLists();
            return;
        }

        // Ordered list
        if (/^\d+[\.\)]\s+/.test(line)) {

            if (inUl) {
                html += '</ul>';
                inUl = false;
            }

            if (!inOl) {
                html += '<ol>';
                inOl = true;
            }

            html += '<li>' + line.replace(/^\d+[\.\)]\s+/, '') + '</li>';
            return;
        }

        // Bullet list
        if (/^[-�*]\s+/.test(line)) {

            if (inOl) {
                html += '</ol>';
                inOl = false;
            }

            if (!inUl) {
                html += '<ul>';
                inUl = true;
            }

            html += '<li>' + line.replace(/^[-�*]\s+/, '') + '</li>';
            return;
        }

        closeLists();
        html += '<p>' + line + '</p>';
    });

    closeLists();

    return html;
}
</script>

</body>
</html>
