<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Live Agent Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" type="image/png" href="https://bpoc.in/wfm/images/bpoc-icon.png">
<link rel="shortcut icon" href="https://bpoc.in/wfm/images/bpoc-icon.png">

<!-- IMPORTANT: jQuery + Bootstrap JS loaded here in <head>, before anything else -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
/*==========================================================
    ENTERPRISE LIVE CHAT CONSOLE
    Design tokens
==========================================================*/

@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');

:root {

    /* ink / console */
    --ink-900:#071A2F;
    --ink-800:#0B2745;
    --ink-700:#123A5F;
    --ink-line:rgba(255,255,255,.09);

    /* surfaces */
    --surface:#F3F7FB;
    --card:#FFFFFF;

    /* Tata Capital inspired theme */
    --accent:#00529B;
    --accent-dark:#003B73;
    --accent-soft:#EAF3FA;
    --accent-soft-line:#B8D6EC;

    /* secondary orange accent */
    --orange:#F58220;
    --orange-dark:#D9680C;
    --orange-soft:#FFF3E8;

    /* status */
    --success:#1E9E5A;
    --success-soft:#E4F7EC;

    --warning:#C17F0A;
    --warning-soft:#FBF1DD;

    --danger:#D64545;
    --danger-soft:#FBEAEA;

    /* text */
    --text-900:#102A43;
    --text-600:#486581;
    --text-400:#829AB1;

    /* borders */
    --border:#D9E2EC;

    /* shadows */
    --shadow-sm:
        0 1px 2px rgba(16,42,67,.04),
        0 1px 1px rgba(16,42,67,.03);

    --shadow-md:
        0 8px 20px rgba(16,42,67,.08);

    --shadow-lg:
        0 22px 50px rgba(16,42,67,.14);

    /* radius */
    --radius-lg:16px;
    --radius-md:12px;
    --radius-sm:8px;

    /* animation */
    --ease:cubic-bezier(.4,0,.2,1);
}
/*==========================================================
BASE
==========================================================*/

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{

    height:100%;

    overflow:hidden;

    font-family:'Inter',sans-serif;

    background:var(--surface);

    color:var(--text-900);

    -webkit-font-smoothing:antialiased;
}

.mono{
    font-family:'JetBrains Mono',monospace;
}

@media (prefers-reduced-motion: reduce){
    *{ animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; }
}

:focus-visible{
    outline:2px solid var(--accent);
    outline-offset:2px;
    border-radius:6px;
}

/*==========================================================
SCROLLBAR
==========================================================*/

::-webkit-scrollbar{ width:7px; height:7px; }

::-webkit-scrollbar-track{ background:transparent; }

::-webkit-scrollbar-thumb{ background:#CBD5E1; border-radius:50px; }

::-webkit-scrollbar-thumb:hover{ background:#94A3B8; }

/*==========================================================
WRAPPER
==========================================================*/

.wrapper{
    height:100vh;
    display:flex;
    flex-direction:column;
}

/*==========================================================
TOPBAR - command console
==========================================================*/

.topbar{

    height:64px;

    background:var(--ink-900);

    background-image:
 radial-gradient(1200px 180px at 15% -60%, rgba(244, 130, 32, 0.35), transparent),
    linear-gradient(135deg, #003b73, #00529b);

    color:#5f94e3;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 24px;

    position:relative;

    z-index:999;

    border-bottom:1px solid var(--ink-line);
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
}

.brand-mark {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: linear-gradient(155deg, #00529B, #003B73);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    box-shadow: 0 6px 16px rgba(0, 82, 155, 0.40);
    flex-shrink: 0;
}

.brand-text{
    display:flex;
    flex-direction:column;
    line-height:1.2;
}

.brand-text .name{
    font-family:'Space Grotesk',sans-serif;
    font-weight:600;
    font-size:15.5px;
    letter-spacing:.1px;
    color:#fff;
}

.brand-text .sub{
    font-size:11px;
    color:#8894AE;
    letter-spacing:.4px;
    text-transform:uppercase;
    font-family:'JetBrains Mono',monospace;
}

/*==========================================================
LIVE OPS READOUT - signature element
==========================================================*/

.ops-strip{
    display:flex;
    align-items:center;
    gap:22px;
    padding:6px 18px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--ink-line);
    border-radius:30px;
}

.ops-item{
    display:flex;
    align-items:center;
    gap:8px;
    font-family:'JetBrains Mono',monospace;
    font-size:12px;
    color:#B7C1D6;
    white-space:nowrap;
}

.ops-item .ops-label{
    color:#71809C;
    text-transform:uppercase;
    letter-spacing:.5px;
    font-size:10.5px;
}

.ops-item .ops-value{
    color:#fff;
    font-weight:600;
}

.ops-divider{
    width:1px;
    height:16px;
    background:var(--ink-line);
}

/*==========================================================
AGENT IDENTITY
==========================================================*/

.agent-info{
    display:flex;
    align-items:center;
    gap:14px;
}

.agent-chip{
    display:flex;
    align-items:center;
    gap:10px;
    padding:6px 8px 6px 6px;
    border-radius:30px;
    background:rgba(255,255,255,.05);
    border:1px solid var(--ink-line);
}

.agent-avatar{
    width:30px;
    height:30px;
    border-radius:50%;
    background:linear-gradient(150deg,#2C3A5C,#1B2740);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:600;
    color:#EDF1F7;
    font-family:'Space Grotesk',sans-serif;
}

.agent-meta{
    line-height:1.15;
}

.agent-meta .agent-label{
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:#71809C;
}

.agent-meta .agent-name{
    font-size:13px;
    font-weight:600;
    color:#fff;
}

/*==========================================================
WEBSOCKET STATUS DOT
==========================================================*/

.ws-online-dot{

    width:9px;

    height:9px;

    background:var(--success);

    border-radius:50%;

    box-shadow:0 0 0 3px rgba(30,158,90,.22);

    animation:pulse 2.2s infinite;

    flex-shrink:0;
}

@keyframes pulse{

    0%{ box-shadow:0 0 0 0 rgba(30,158,90,.35); }

    70%{ box-shadow:0 0 0 8px rgba(30,158,90,0); }

    100%{ box-shadow:0 0 0 0 rgba(30,158,90,0); }
}

.ws-reconnecting{

    background:var(--warning);

    color:#241902;

    padding:4px 12px;

    border-radius:30px;

    font-family:'JetBrains Mono',monospace;

    font-size:11px;

    font-weight:600;

    letter-spacing:.3px;
}

/*==========================================================
CONTENT AREA
==========================================================*/

.content{
    flex:1;
    padding:16px;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height:0;
}
.content .row{ height:100%; min-height:0; }

.content .row > [class*="col-"]{ min-height:0; }

/*==========================================================
PANELS
==========================================================*/

.left-panel,
.middle-panel,
.right-panel,
.assist-panel{
    height:100%;
    min-height:0;

    display:flex;
    flex-direction:column;

    overflow:hidden;

    border-radius:var(--radius-lg);
    background:var(--card);
    border:2px solid var(--border);
    box-shadow:var(--shadow-sm);
}

.left-panel:hover,
.middle-panel:hover,
.right-panel:hover,
.assist-panel:hover{
    box-shadow:var(--shadow-md);
}

.left-panel,
.middle-panel,
.right-panel{
    margin-right:14px;
}

.right-panel{
    background:
        linear-gradient(var(--card),var(--card)) padding-box;
height: 640px;              /* Change to your required height */
    display: flex;
    flex-direction: column;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}

/*==========================================================
AI ASSIST PANEL (embeds https://bpoc.in/TataPlayAssist/)
==========================================================*/

.assist-panel{
    border:2px solid var(--accent-soft-line);
}

.assist-panel .panel-header{
    background:var(--accent-soft);
}

.assist-panel .panel-title i{
    color:var(--accent-dark);
}

#assistFrameWrap{
    flex:1;
    min-height:0;
    position:relative;
}

#assistFrame{
    width:100%;
    height:100%;
    border:none;
    display:block;
}

.assist-refresh-btn{
    border:1px solid var(--accent-soft-line);
    background:#fff;
    color:var(--accent-dark);
    border-radius:8px;
    width:30px;
    height:30px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    cursor:pointer;
    transition:.2s;
}

.assist-refresh-btn:hover{
    background:var(--accent-soft);
}

.assist-live-pill{
    display:flex;
    align-items:center;
    gap:6px;
    font-family:'JetBrains Mono',monospace;
    font-size:10.5px;
    font-weight:600;
    color:var(--accent-dark);
    background:#fff;
    border:1px solid var(--accent-soft-line);
    padding:4px 9px;
    border-radius:30px;
}

.assist-live-pill .dot{
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--success);
    box-shadow:0 0 6px var(--success);
    flex-shrink:0;
}

/*==========================================================
PANEL HEADER
==========================================================*/

.panel-header{

    flex-shrink:0;

    padding:16px 18px;

    background:var(--card);

    border-bottom:1px solid var(--border);

    display:flex;

    justify-content:space-between;

    align-items:center;

    position:sticky;

    top:0;

    z-index:10;

    gap:10px;
}

.panel-title{
    display:flex;
    align-items:center;
    gap:9px;
    font-family:'Space Grotesk',sans-serif;
    font-weight:600;
    font-size:13.5px;
    letter-spacing:.3px;
    text-transform:uppercase;
    color:var(--text-600);
}

.panel-title i{
    color:var(--accent);
    font-size:15px;
}

/*==========================================================
COUNT BADGES
==========================================================*/

.queue-count,
.active-count{
    font-family:'JetBrains Mono',monospace;
    border-radius:8px;
    padding:3px 9px;
    font-size:12px;
    font-weight:600;
    border:1px solid transparent;
}

.queue-count{
    background:var(--warning-soft);
    color:var(--warning);
    border-color:#F0DFB6;
}

.active-count{
    background:var(--success-soft);
    color:var(--success);
    border-color:#BFE7CE;
}

/*==========================================================
LIST CONTAINERS
==========================================================*/

#waitingQueue,
#activeChats{
    flex:1;
    min-height:0;
    overflow-y:auto;
    overscroll-behavior:contain;
    padding:12px;
    scroll-behavior:smooth;
    height: 200px;   
    overflow-x: hidden;
}

/*==========================================================
EMPTY STATE
==========================================================*/

.empty-state{

    height:80%;

    display:flex;

    justify-content:center;

    align-items:center;

    flex-direction:column;

    color:var(--text-400);

    font-size:13.5px;

    gap:10px;

    text-align:center;

    padding:0 24px;
}

.empty-state i{
    font-size:34px;
    color:#D4DAE5;
}

.empty-state h5{
    font-family:'Space Grotesk',sans-serif;
    font-size:15px;
    color:var(--text-600);
    font-weight:600;
}

/*==========================================================
CHAT ITEM CARDS (queue + active list)
==========================================================*/

.chat-item{

    display:flex;
    align-items:flex-start;
    gap:12px;

    padding:10px;

    margin-bottom:3px;

    background:var(--card);

    border:1px solid var(--border);

    border-radius:var(--radius-md);

    cursor:pointer;

    transition:border-color .2s var(--ease), transform .2s var(--ease), box-shadow .2s var(--ease);

    position:relative;
}

.chat-item:last-child{ margin-bottom:0; }

.chat-item:hover{

    transform:translateY(-2px);

    box-shadow:var(--shadow-md);

    border-color:var(--accent-soft-line);
}

.chat-item.active{
    background:var(--accent-soft);
    border-color:var(--accent-soft-line);
}

/*==========================================================
AVATAR
==========================================================*/

.item-avatar{

    width:40px;
    height:40px;

    border-radius:10px;

    background:linear-gradient(155deg,var(--ink-700),var(--ink-900));

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-family:'Space Grotesk',sans-serif;

    font-weight:600;

    font-size:14px;

    flex-shrink:0;
}

/*==========================================================
CHAT ITEM DETAILS
==========================================================*/

.chat-item-body{
    flex:1;
    min-width:0;
}

.customer-name{

    font-size:14px;

    font-weight:600;

    color:var(--text-900);

    margin-bottom:2px;

    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.customer-mobile{

    font-family:'JetBrains Mono',monospace;

    font-size:11.5px;

    color:var(--text-400);

    margin-bottom:8px;
}

.last-message{
    color:var(--text-600);
    font-size:12.5px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.item-row-bottom{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-top:2px;
}

/*==========================================================
UNREAD BADGE
==========================================================*/

.unread{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-width:20px;

    height:20px;

    padding:0 6px;

    border-radius:30px;

    background:var(--danger);

    color:#fff;

    font-family:'JetBrains Mono',monospace;

    font-size:11px;

    font-weight:600;

    box-shadow:0 4px 10px rgba(214,69,69,.35);
}

/*==========================================================
ACCEPT BUTTON
==========================================================*/

.chat-item .btn-success{
    background:var(--accent);
    border-color:var(--accent);
    border-radius:8px;
    font-size:12.5px;
    font-weight:600;
    padding:7px 10px;
}

.chat-item .btn-success:hover{
    background:var(--accent-dark);
    border-color:var(--accent-dark);
}

.chat-item .badge.bg-warning{
    background:var(--warning-soft) !important;
    color:var(--warning) !important;
    font-family:'JetBrains Mono',monospace;
    font-weight:600;
    font-size:11px;
    border:1px solid #F0DFB6;
    border-radius:6px;
    padding:5px 8px;
}

/*==========================================================
CHAT HEADER (conversation panel)
==========================================================*/

#chatHeader{

    flex-shrink:0;

    display:flex;

    align-items:center;

    padding:16px 20px;

    background:var(--card);

    border-bottom:1px solid var(--border);
}

.header-avatar{

    width:44px;

    height:44px;

    border-radius:10px;
background: linear-gradient(155deg, var(--accent), var(--accent-dark));
color: #fff;
    display:flex;

    align-items:center;

    justify-content:center;

    font-size:18px;

    font-weight:600;

    margin-right:14px;

    flex-shrink:0;
}

.header-info{ flex:1; min-width:0; }

.header-name{
    font-family:'Space Grotesk',sans-serif;
    font-size:15.5px;
    font-weight:600;
    color:var(--text-900);
}

.header-status{
    color:var(--success);
    font-size:12px;
    margin-top:2px;
    font-family:'JetBrains Mono',monospace;
}

/*==========================================================
CHAT BODY
==========================================================*/

.chat-body{
    flex:1;
    overflow-y:auto;
    min-height:0;
}

/*==========================================================
MESSAGES
==========================================================*/

.msg{
    display:flex;
    margin-bottom:16px;
    animation:fadeUp .25s var(--ease);
}

.customer{ justify-content:flex-start; }

.agent{ justify-content:flex-end; }

.system{ justify-content:center; }

.bubble{

    max-width:70%;

    padding:12px 16px;

    border-radius:14px;

    font-size:13.5px;

    line-height:1.55;

    position:relative;

    word-break:break-word;

    box-shadow:var(--shadow-sm);
}

.customer .bubble{
    background:var(--card);
    color:var(--text-900);
    border:1px solid var(--border);
    border-bottom-left-radius:4px;
}

.agent .bubble{
    background:var(--accent);
    color:#fff;
    border-bottom-right-radius:4px;
}

.system .bubble{
    background:var(--warning-soft);
    color:#7A5205;
    font-weight:600;
    font-size:12.5px;
    box-shadow:none;
    border:1px solid #F0DFB6;
}

.time{
    display:block;
    margin-top:6px;
    text-align:right;
    font-size:10.5px;
    font-family:'JetBrains Mono',monospace;
    opacity:.65;
}

/*==========================================================
FOOTER / COMPOSER
==========================================================*/

.chat-footer{
    flex-shrink:0;
    padding:18px 20px;
    background:#fff;
    border-top:1px solid var(--border);
    margin-bottom:50px;
}

#messageBox{

    height:48px;

    border-radius:14px;

    border:1px solid var(--border);

    padding:12px 16px;

    resize:none;

    font-size:13.5px;

    transition:border-color .2s, box-shadow .2s;

    background:var(--surface);
}

#messageBox.assist-filled{
    border-color:var(--accent);
    box-shadow:0 0 0 3px var(--accent-soft);
    background:var(--card);
}

#messageBox:focus{

    border-color:var(--accent);

    box-shadow:0 0 0 3px var(--accent-soft);

    outline:none;

    background:var(--card);
}

#sendBtn{

    height:48px;

    width:48px;

    border:none;

    border-radius:14px;

    background:var(--accent);

    color:#fff;

    transition:background .2s, transform .2s;
}

#sendBtn:hover{
    background:var(--accent-dark);
    transform:translateY(-1px);
}

#closeBtn{

    border-radius:10px;

    padding:3px 10px;

    font-weight:600;

    font-size:12.5px;

    color:var(--danger);

    border-color:#F0C9C9;
}

#closeBtn:hover{
    background:var(--danger-soft);
    border-color:var(--danger);
    color:var(--danger);
}

/*==========================================================
TYPING INDICATOR
==========================================================*/

.typing-box{
    background:var(--card);
    padding:12px 16px;
    border-radius:14px;
    border:1px solid var(--border);
    box-shadow:var(--shadow-sm);
}

.typing-dot{
    width:6px;
    height:6px;
    border-radius:50%;
    background:var(--text-400);
    display:inline-block;
    margin-right:4px;
    animation:typing 1.2s infinite;
}

.typing-dot:nth-child(2){ animation-delay:.2s; }

.typing-dot:nth-child(3){ animation-delay:.4s; }

@keyframes typing{
    0%,100%{ opacity:.3; transform:translateY(0); }
    50%{ opacity:1; transform:translateY(-4px); }
}

/*==========================================================
MOTION
==========================================================*/

@keyframes fadeUp{
    from{ opacity:0; transform:translateY(10px); }
    to{ opacity:1; transform:translateY(0); }
}

/*==========================================================
RESPONSIVE
==========================================================*/

@media(max-width:992px){

    .left-panel,
    .middle-panel{
        margin-right:0;
        margin-bottom:14px;
        height:280px;
    }

    .right-panel{
        height:calc(100vh - 610px);
    }

    .assist-panel{
        height:360px;
        margin-top:14px;
    }

    .ops-strip{ display:none; }
}

@media(max-width:768px){

    .topbar{ padding:0 14px; }

    .brand-text .sub{ display:none; }

    .item-avatar{ width:34px; height:34px; font-size:12px; }

    .header-avatar{ width:38px; height:38px; font-size:15px; }

    .bubble{ max-width:90%; }

    .chat-body{ padding:16px; }

    #messageBox{ height:44px; }

    #sendBtn{ height:44px; width:44px; }
}

.middle-panel {
    height: 650px;              /* Or: calc(100vh - 120px) */
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.panel-header {
    flex-shrink: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

#activeChats {
    flex: 1;
    overflow-y: auto;
    padding: 3px;
    min-height: 0;
}

/* Nice scrollbar */
#activeChats::-webkit-scrollbar {
    width: 8px;
}

#activeChats::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#activeChats::-webkit-scrollbar-thumb {
    background: #b5b5b5;
    border-radius: 10px;
}

#activeChats::-webkit-scrollbar-thumb:hover {
    background: #888;
}

</style>

</head>

<body>

<div class="wrapper">

    <!-- ===================== TOPBAR ===================== -->

    <header class="topbar">

        <div class="brand">

            <div class="brand-mark">
                <i class="bi bi-headset"></i>
            </div>

            <div class="brand-text">
                <span class="name">Enterprise Live Support</span>
                <span class="sub">Agent Console</span>
            </div>

        </div>

        <div class="ops-strip">

            <div class="ops-item">
                <span class="ops-label">Queue</span>
                <span class="ops-value" id="opsQueueValue">0</span>
            </div>

            <div class="ops-divider"></div>

            <div class="ops-item">
                <span class="ops-label">Active</span>
                <span class="ops-value" id="opsActiveValue">0</span>
            </div>

            <div class="ops-divider"></div>

            <div class="ops-item">
                <span id="agent-status-dot" class="ws-online-dot" title="Connected"></span>
                <span class="ops-label">Realtime</span>
            </div>

        </div>

      <div class="agent-info d-flex align-items-center gap-3">

    <div class="agent-chip d-flex align-items-center">

        <div class="agent-avatar">
            <?= strtoupper(substr(session()->get('name'), 0, 1)) ?>
        </div>

        <div class="agent-meta ms-2">
            <div class="agent-label">Signed in</div>
            <div class="agent-name"><?= esc(session()->get('name')) ?></div>
        </div>

    </div>

    <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm rounded-pill">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

</div>

    </header>

    <!-- ===================== CONTENT ===================== -->

    <div class="container-fluid content">

        <div class="row g-3 h-100">

            <!-- ==========================================
                 WAITING QUEUE
            ========================================== -->

            <div class="col-xl-2 col-lg-3 col-md-6">

                <div class="left-panel">

                    <div class="panel-header">

                        <div class="panel-title">

                            <i class="bi bi-hourglass-split"></i>

                            Waiting Queue

                        </div>

                        <span
                            class="queue-count"
                            id="queueCount">

                            0

                        </span>

                    </div>

                    <div id="waitingQueue">

                        <div class="empty-state">

                            <i class="bi bi-clock-history"></i>

                            <h5>Queue is clear</h5>

                            <div>No customers waiting right now</div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ==========================================
                 ACTIVE CHATS
            ========================================== -->

            <div class="col-xl-2 col-lg-3 col-md-6">

                <div class="middle-panel">

                    <div class="panel-header">

                        <div class="panel-title">

                            <i class="bi bi-chat-dots-fill"></i>

                            Active Chats

                        </div>

                        <span
                            class="active-count"
                            id="activeCount">

                            0

                        </span>

                    </div>

                    <div id="activeChats">

                        <div class="empty-state">

                            <i class="bi bi-chat-square-text"></i>

                            <h5>No conversations yet</h5>

                            <div>Accepted chats will appear here</div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ==========================================
                 CHAT WINDOW
            ========================================== -->

            <div class="col-xl-5 col-lg-6">

                <div class="right-panel">

                    <!-- CHAT HEADER -->

                    <div id="chatHeader">

                        <div class="header-avatar">

                            <i class="bi bi-person"></i>

                        </div>

                        <div class="header-info">

                            <div class="header-name">

                                Select a customer

                            </div>

                            <div class="header-status">

                                Waiting for chat selection

                            </div>

                        </div>

                    </div>

                    <!-- CHAT BODY -->

                    <div
                        id="chatMessages"
                        class="chat-body">

                        <div class="empty-state">

                            <i class="bi bi-chat-heart"></i>

                            <h5>No conversation selected</h5>

                            <div>

                                Choose a customer from the left panel
                                to start chatting

                            </div>

                        </div>

                    </div>

                    <!-- CHAT FOOTER -->

                    <div class="chat-footer">

                        <div class="row align-items-center g-2">

                            <div class="col">

                                <textarea

                                    id="messageBox"

                                    class="form-control"

                                    rows="1"

                                    placeholder="Type your message..."

                                    disabled>

                                </textarea>

                            </div>

                            <div class="col-auto">

                                <button

                                    id="sendBtn"

                                    class="btn"

                                    disabled>

                                    <i class="bi bi-send-fill"></i>

                                </button>

                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-3">

                            <button

                                id="closeBtn"

                                class="btn btn-outline-danger"

                                disabled>

                                <i class="bi bi-x-circle"></i>

                                Close Conversation

                            </button>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ==========================================
                 AI AGENT ASSIST (embedded)
                 Loads https://bpoc.in/TataPlayAssist/?embedded=1
                 - auto-searches whenever a new customer message
                   arrives in the currently open chat
                 - "Send to Chat" button drops the AI's answer
                   straight into #messageBox for the agent to
                   review, edit, and send
            ========================================== -->

            <div class="col-xl-3 col-lg-12">

                <div class="assist-panel">

                    <div class="panel-header">

                        <div class="panel-title">
                            <i class="bi bi-robot"></i>
                            AI Agent Assist
                        </div>

                        <div class="d-flex align-items-center gap-2">

                            <span class="assist-live-pill" id="assistLivePill">
                                <span class="dot"></span>
                                Live
                            </span>

                            <button
                                id="assistRefreshBtn"
                                class="assist-refresh-btn"
                                title="Reload Assist">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>

                        </div>

                    </div>

                    <div id="assistFrameWrap">
                        <iframe
                            id="assistFrame"
                            src="https://bpoc.in/TataCapitalAssist/?embedded=1"
                            title="Tata Capital Agent Assist"
                            loading="lazy">
                        </iframe>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ===================== DISPOSITION MODAL ===================== -->
<!-- ===================== DISPOSITION MODAL ===================== -->
<div class="modal fade" id="dispositionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;border:none;">

      <div class="modal-header" style="border-bottom:1px solid var(--border);">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-clipboard-check me-1" style="color:var(--accent);"></i>
          Select Disposition to Close Chat
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Disposition <span class="text-danger">*</span></label>
          <select id="dispositionSelect" class="form-select" required>
            <option value="">-- Loading dispositions... --</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Remarks <span class="text-danger">*</span></label>
          <textarea id="dispositionRemarks" class="form-control" rows="2" placeholder="Please add a note..." required></textarea>
        </div>

        <div id="dispositionError" class="text-danger small mt-2" style="display:none;">
          Please fill in all required fields before closing.
        </div>

      </div>

      <div class="modal-footer" style="border-top:1px solid var(--border);">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmCloseBtn" class="btn btn-sm text-white" style="background:var(--accent);">
          <i class="bi bi-x-circle"></i> Close Conversation
        </button>
      </div>

    </div>
  </div>
</div>

<audio id="notifySound" preload="auto">
    <source src="https://actions.google.com/sounds/v1/cartoon/pop.ogg" type="audio/ogg">
</audio>

<script>
const BASE_URL = "<?= base_url(); ?>";
</script>

<script>

let currentChatId=0;

let lastMessageId=0;

function initials(name)
{
    if(!name) return "?";
    let parts = name.trim().split(" ").filter(Boolean);
    if(parts.length===1) return parts[0].substring(0,2).toUpperCase();
    return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
}

</script>

<script>

function loadQueue()
{
$.get(BASE_URL + "/agent/livechat/queue", function(res){

        if(!res.success) return;

        $("#queueCount").html(res.total);
        $("#opsQueueValue").html(res.total);

        let html='';

        if(res.total==0)
        {
            html=`
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                <h5>Queue is clear</h5>
                <div>No customers waiting right now</div>
            </div>`;
        }

        res.queue.forEach(function(chat){

            html+=`

            <div class="chat-item">

                <div class="item-avatar">${initials(chat.customer_name)}</div>

                <div class="chat-item-body">

                    <div class="customer-name">

                        ${chat.customer_name}

                    </div>

                    <div class="customer-mobile">

                        ${chat.customer_mobile}

                    </div>

                    <div class="item-row-bottom">

                        <span class="badge bg-warning">

                            Waiting ${chat.waiting_minutes} min

                        </span>

                    </div>

                    <div class="mt-2">

                        <button

                            class="btn btn-success btn-sm w-100"

onclick="acceptChat(${chat.id}, '${chat.customer_name}', '${chat.customer_mobile}')"

                        >

                            <i class="bi bi-check-circle"></i>

                            Accept Chat

                        </button>

                    </div>

                </div>

            </div>

            `;

        });

        $("#waitingQueue").html(html);

    });

}

function loadActiveChats()
{

   $.get(BASE_URL + "/agent/livechat/active",function(res){

        if(!res.success) return;

        $("#activeCount").html(res.total);
        $("#opsActiveValue").html(res.total);

        let html='';

        if(res.total==0)
        {

            html=`
            <div class="empty-state">
                <i class="bi bi-chat-square-text"></i>
                <h5>No conversations yet</h5>
                <div>Accepted chats will appear here</div>
            </div>`;

        }

        res.chats.forEach(function(chat){

            const unread = unreadCounts[chat.id] || 0;
            const badgeHtml = unread > 0
                ? `<span class="unread" id="chat-item-unread-${chat.id}">${unread}</span>`
                : `<span class="unread" id="chat-item-unread-${chat.id}" style="display:none;">${unread}</span>`;

            html+=`

            <div

                class="chat-item"

                onclick="openChat(${chat.id},
                '${chat.customer_name}',
                '${chat.customer_mobile}')"

            >

                <div class="item-avatar">${initials(chat.customer_name)}</div>

                <div class="chat-item-body">

                    <div class="customer-name">

                        ${chat.customer_name}

                    </div>

                    <div class="customer-mobile">

                        ${chat.customer_mobile}

                    </div>

                    <div class="last-message">

                        ${chat.last_message}

                    </div>

                    <div class="item-row-bottom">

                        <span></span>

                        ${badgeHtml}

                    </div>

                </div>

            </div>

            `;

        });

        $("#activeChats").html(html);

    });

}

function acceptChat(chatId, name, mobile)
{
    $.ajax({
        url: BASE_URL + "/agent/livechat/claim",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            chat_id: chatId
        }),
        success: function(res){

            if(res.success){

                if (DashboardWS.ws && DashboardWS.ws.readyState === WebSocket.OPEN) {
                    DashboardWS.ws.send(JSON.stringify({
                        type: 'join_chat',
                        chat_id: chatId
                    }));
                }

                loadQueue();
                loadActiveChats();

                openChat(chatId, name, mobile);

            }else{

                alert(res.message);

            }

        }
    });
}

loadQueue();
loadActiveChats();

</script>
<script>

function openChat(chatId,name,mobile)
{

    currentChatId=chatId;

    lastMessageId=0;

    if (DashboardWS.ws && DashboardWS.ws.readyState === WebSocket.OPEN) {
        DashboardWS.ws.send(JSON.stringify({
            type: 'join_chat',
            chat_id: chatId
        }));
    }

    unreadCounts[chatId] = 0;
    $('#chat-item-unread-' + chatId).text('').hide();

    $("#chatHeader").html(`

        <div class="header-avatar">${initials(name)}</div>

        <div class="header-info">

            <div class="header-name">${name}</div>

            <div class="header-status mono">${mobile}</div>

        </div>

    `);

    $("#messageBox").prop("disabled",false);

    $("#sendBtn").prop("disabled",false);

    $("#closeBtn").prop("disabled",false);

    $("#chatMessages").html('');

    loadHistory(chatId);

}

</script>
<script>

function appendMessage(msg)
{
    let cls='system';

    if(msg.sender=='customer')
        cls='customer';

    if(msg.sender=='agent')
        cls='agent';

    let time='';

    if(msg.created_at)
        time=msg.created_at;

    let html=`

    <div class="msg ${cls}">

        <div class="bubble">

            ${escapeHtml(msg.message)}

            <span class="time">

                ${time}

            </span>

        </div>

    </div>

    `;

    $("#chatMessages").append(html);

    $("#chatMessages").scrollTop(
        $("#chatMessages")[0].scrollHeight
    );

}

function loadHistory(chatId)
{

    $.get(

        "/agent/livechat/history/"+chatId,

        function(res){

            if(!res.success)
                return;

            $("#chatMessages").html('');

            lastMessageId=0;

            res.messages.forEach(function(msg){

                appendMessage(msg);

                lastMessageId=msg.id;

            });

            const lastCustomerMsg = [...res.messages].reverse().find(m => m.sender === 'customer');
            if (lastCustomerMsg) {
                notifyAssist(lastCustomerMsg.message);
            }

        }

    );

}

function pollMessages()
{

    if(currentChatId==0)
        return;

    $.get(

        "/agent/livechat/poll/"+currentChatId,

        {

            last_id:lastMessageId

        },

        function(res){

            if(!res.success)
                return;

            if(res.messages.length>0)
            {

                res.messages.forEach(function(msg){

                    appendMessage(msg);

                    lastMessageId=msg.id;

                    if (msg.sender === 'customer') {
                        notifyAssist(msg.message);
                    }

                });

            }

            if(res.chat_status=="closed")
            {

                $("#messageBox").prop("disabled",true);

                $("#sendBtn").prop("disabled",true);

                $("#closeBtn").prop("disabled",true);

                $("#chatHeader").append(

                    '<span class="badge bg-danger ms-2">Closed</span>'

                );

            }

        }

    );

}

$("#sendBtn").click(function(){

    sendMessage();

});

$("#messageBox").keypress(function(e){

    if(e.which==13 && !e.shiftKey)
    {

        e.preventDefault();

        sendMessage();

    }

});

function sendMessage()
{

    if(currentChatId==0)
        return;

    let msg=$("#messageBox").val().trim();

    if(msg=="")
        return;

    $.ajax({

url:BASE_URL + "/agent/livechat/reply",

        type:"POST",

        contentType:"application/json",

        data:JSON.stringify({

            chat_id:currentChatId,

            message:msg

        }),

        success:function(res){

            if(res.success){

                $("#messageBox").val('');
                $("#messageBox").removeClass('assist-filled');

                pollMessages();

            }
            else{

                alert(res.message);

            }

        }

    });

}

// ===================================================
// DISPOSITION-BASED CLOSE CONVERSATION FLOW
// ===================================================

function loadDispositions()
{
    $("#dispositionSelect").html('<option value="">-- Loading dispositions... --</option>');

    $.get(BASE_URL + "/admin/chat-score/getDispositions", function(res){

        let options = '<option value="">-- Select Disposition --</option>';

        let list = res.data ? res.data : (Array.isArray(res) ? res : []);

        if(list.length === 0){
            options = '<option value="">-- No dispositions found --</option>';
        } else {
            list.forEach(function(d){
                let id   = d.id ?? d.disposition_id;
                let name = d.name ?? d.disposition_name ?? d.title;
                options += `<option value="${id}">${name}</option>`;
            });
        }

        $("#dispositionSelect").html(options);

    }).fail(function(){
        $("#dispositionSelect").html('<option value="">-- Failed to load, try again --</option>');
    });
}

$("#closeBtn").click(function(){

    if(currentChatId==0)
        return;

    $("#dispositionSelect").val('');
    $("#dispositionRemarks").val('');
    $("#dispositionError").hide();

    loadDispositions();

    let modalEl = document.getElementById('dispositionModal');
    let modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

});

$("#confirmCloseBtn").click(function(){

    if(currentChatId==0)
        return;

    let dispositionId = $("#dispositionSelect").val();
    let remarks        = $("#dispositionRemarks").val().trim();

    // Hide previous error
    $("#dispositionError").hide();

    // Validate both fields
    let errorMessages = [];
    if(!dispositionId){
        errorMessages.push("Please select a disposition.");
    }
    if(!remarks){
        errorMessages.push("Please enter remarks.");
    }

    // Show error if any validation fails
    if(errorMessages.length > 0){
        $("#dispositionError")
            .text(errorMessages.join(" "))
            .show();
        return;
    }

    // Disable button and show spinner
    $(this).prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Closing...');

    $.ajax({

        url: BASE_URL + "/agent/livechat/close",

        type: "POST",

        contentType: "application/json",

        data: JSON.stringify({

            chat_id:         currentChatId,
            closed_by:       "agent",
            disposition_id:  dispositionId,
            remarks:         remarks

        }),

        success: function(res){

            // Reset button
            $("#confirmCloseBtn").prop("disabled", false).html('<i class="bi bi-x-circle"></i> Close Conversation');

            if(res.success){

                // Close modal
                let modalEl = document.getElementById('dispositionModal');
                let modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();

                // Show closed message in chat
                $("#chatMessages").append(
                    '<div class="msg system"><div class="bubble"><strong>Conversation Closed.</strong></div></div>'
                );

                // Disable chat input
                $("#messageBox").prop("disabled",true);
                $("#sendBtn").prop("disabled",true);
                $("#closeBtn").prop("disabled",true);

                // Add closed badge to header if not already there
                if (!$("#chatHeader .badge.bg-danger").length) {
                    $("#chatHeader").append('<span class="badge bg-danger ms-2">Closed</span>');
                }

                // Refresh queues
                loadQueue();
                loadActiveChats();

            } else {
                alert(res.message || "Failed to close chat.");
            }

        },

        error: function(){
            // Reset button on error
            $("#confirmCloseBtn").prop("disabled", false).html('<i class="bi bi-x-circle"></i> Close Conversation');
            alert("Something went wrong while closing the chat.");
        }

    });

});

function escapeHtml(text)
{

    return $("<div>").text(text).html();

}

</script>
<script>
// -- Task 12.4: per-chat unread counters ------------------
const unreadCounts = {};   // chat_id ? count

let typingTimer = null;
let agentTypingTimer = null;

function playNotification()
{
    let audio=document.getElementById("notifySound");
    if(audio) audio.play().catch(()=>{});
}

function showBrowserNotification(title, text)
{
    if(!("Notification" in window)) return;
    if(Notification.permission==="granted")
    {
        new Notification(title, {
            body: text,
            icon: "https://cdn-icons-png.flaticon.com/512/2462/2462719.png"
        });
    }
}

if("Notification" in window) { Notification.requestPermission(); }

function showTyping()
{
    if($("#typingIndicator").length) return;
    $("#chatMessages").append(`
        <div id="typingIndicator" class="msg customer">
            <div class="typing-box">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        </div>`);
    $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
}

function hideTyping() { $("#typingIndicator").remove(); }

// -- Task 12.1 / 12.2 / 12.3 / 12.5 / 12.6: DashboardWS -
const DashboardWS = {
    ws:                null,
    agentId:           <?= (int) session()->get('user_id') ?>,
    wsToken:           null,
    reconnectAttempts: 0,
    reconnectTimer:    null,

    // Fetch a fresh ws_token then open the WebSocket
    async init() {
        try {
            const res  = await fetch(BASE_URL + '/agent/livechat/ws-token');
            const data = await res.json();
            if (data.success) {
                this.wsToken = data.ws_token;
            }
        } catch(e) {
            console.warn('DashboardWS: could not fetch ws_token, continuing without auth', e);
        }
        this.connect();
    },

    connect() {
        const proto = location.protocol === 'https:' ? 'wss://' : 'ws://';
        const url   = proto + location.host + '/ws';

        try { this.ws = new WebSocket(url); }
        catch(e) { this.reconnect(); return; }

        this.ws.onopen = () => {
            console.log('DashboardWS open');
            this.reconnectAttempts = 0;
            this.setStatusDot(true);
            this.sendSubscribe();
            loadQueue();
            loadActiveChats();
        };

        this.ws.onmessage = (event) => this.onMessage(event);

        this.ws.onclose = () => {
            console.log('DashboardWS closed');
            this.setStatusDot(false);
            this.reconnect();
        };

        this.ws.onerror = (err) => { console.warn('DashboardWS error', err); };
    },

    sendSubscribe() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type:     'subscribe',
                role:     'agent',
                agent_id: this.agentId,
                token:    this.wsToken || '',
            }));
        }
    },

    // -- Task 12.1: handle incoming WS events --------------
    onMessage(event) {
        let data;
        try { data = JSON.parse(event.data); } catch(e) { return; }

        switch (data.type) {

            case 'message':
                if (data.chat_id == currentChatId) {
                    if (data.id && data.id > lastMessageId) lastMessageId = data.id;
                    appendMessage(data);

                    if (data.sender === 'customer') {
                        notifyAssist(data.message);
                    }
                } else {
                    if (!unreadCounts[data.chat_id]) unreadCounts[data.chat_id] = 0;
                    unreadCounts[data.chat_id]++;
                    const badge = document.getElementById('chat-item-unread-' + data.chat_id);
                    if (badge) { badge.textContent = unreadCounts[data.chat_id]; badge.style.display = 'inline-flex'; }
                    playNotification();
                    if (document.hidden) {
                        showBrowserNotification(
                            'New message from ' + (data.customer_name || 'Customer'),
                            data.message || ''
                        );
                    }
                }
                break;

            case 'queue_updated':
                loadQueue();
                loadActiveChats();
                break;

            case 'chat_claimed':
                loadQueue();
                loadActiveChats();
                break;

            case 'chat_closed':
                if (data.chat_id == currentChatId) {
                    $("#chatMessages").append(
                        '<div class="msg system"><div class="bubble"><strong>This chat has been closed.</strong></div></div>'
                    );
                    $("#messageBox").prop("disabled", true);
                    $("#sendBtn").prop("disabled", true);
                    $("#closeBtn").prop("disabled", true);
                    if (!$("#chatHeader .badge.bg-danger").length) {
                        $("#chatHeader").append('<span class="badge bg-danger ms-2">Closed</span>');
                    }
                }
                loadQueue();
                loadActiveChats();
                break;

            // Task 12.3 - customer typing indicator
            case 'typing_start':
                if (data.sender === 'customer' && data.chat_id == currentChatId) showTyping();
                break;

            case 'typing_stop':
                if (data.sender === 'customer' && data.chat_id == currentChatId) hideTyping();
                break;
        }
    },

    // -- Task 12.2: reconnect with status badge --------------
    reconnect() {
        if (this.reconnectTimer) return;
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.reconnectAttempts++;
            if (this.reconnectAttempts >= 5) {
                this.setStatusDot(false, true);
            }
            this.connect();
        }, 3000);
    },

    // -- Task 12.6: update topbar status dot ------------------
    setStatusDot(online, reconnecting = false) {
        const dot = document.getElementById('agent-status-dot');
        if (!dot) return;
        if (online) {
            dot.className       = 'ws-online-dot';
            dot.title           = 'WebSocket connected';
            dot.textContent     = '';
            dot.style.background = '';
        } else if (reconnecting) {
            dot.className       = 'ws-reconnecting';
            dot.textContent     = 'Reconnecting…';
        } else {
            dot.className       = 'ws-online-dot';
            dot.style.background = '#D64545';
            dot.title           = 'Disconnected';
        }
    },

    // Send typing event to WebSocket
    sendTyping(type) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN && currentChatId > 0) {
            this.ws.send(JSON.stringify({ type, chat_id: currentChatId, sender: 'agent' }));
        }
    },
};

// -- Task 12.3: agent typing events from messageBox --------
$(document).on('input', '#messageBox', function() {
    if (currentChatId === 0) return;
    DashboardWS.sendTyping('typing_start');
    clearTimeout(agentTypingTimer);
    agentTypingTimer = setTimeout(function() {
        DashboardWS.sendTyping('typing_stop');
    }, 2000);
});

// Kick off WebSocket connection
DashboardWS.init();

// -- Safety-net polling fallback --------------------------
setInterval(function() {
    loadQueue();
    loadActiveChats();

    if (currentChatId > 0) {
        $.get(BASE_URL + "/agent/livechat/poll/" + currentChatId, { last_id: lastMessageId }, function(res) {
            if (!res.success) return;
            if (res.messages && res.messages.length > 0) {
                res.messages.forEach(function(msg) {
                    if (msg.id > lastMessageId) {
                        lastMessageId = msg.id;
                        appendMessage(msg);

                        if (msg.sender === 'customer') {
                            notifyAssist(msg.message);
                        }
                    }
                });
            }
            if (res.chat_status === "closed") {
                $("#chatMessages").append('<div class="msg system"><div class="bubble"><strong>This chat has been closed.</strong></div></div>');
                $("#messageBox").prop("disabled", true);
                $("#sendBtn").prop("disabled", true);
                $("#closeBtn").prop("disabled", true);
                if (!$("#chatHeader .badge.bg-danger").length) {
                    $("#chatHeader").append('<span class="badge bg-danger ms-2">Closed</span>');
                }
            }
        });
    }
}, 4000);
</script>

<script>
/* ============================================================
   AI AGENT ASSIST INTEGRATION
============================================================ */

let assistReady = false;
let pendingAssistQuery = null;

function notifyAssist(query) {
    if (!query) return;

    const frame = document.getElementById('assistFrame');
    if (!frame || !frame.contentWindow) return;

    if (!assistReady) {
        pendingAssistQuery = query;
        return;
    }

    frame.contentWindow.postMessage({ type: 'agent_assist_query', query: query }, '*');
}

window.addEventListener('message', function (event) {
    const data = event.data;
    if (!data || typeof data !== 'object') return;

    if (data.type === 'agent_assist_ready') {
        assistReady = true;
        if (pendingAssistQuery) {
            notifyAssist(pendingAssistQuery);
            pendingAssistQuery = null;
        }
        return;
    }

    if (data.type === 'agent_assist_answer' && data.answer) {
        $('#messageBox')
            .val(data.answer)
            .addClass('assist-filled')
            .trigger('input')
            .focus();
    }
});

$('#assistRefreshBtn').on('click', function () {
    assistReady = false;
    const frame = document.getElementById('assistFrame');
    if (frame) {
        frame.src = frame.src; // reload
    }
});
</script>

</body>

</html>