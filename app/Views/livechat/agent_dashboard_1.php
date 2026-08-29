
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Live Agent Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

html,
body{
    height:100%;
    margin:0;
    padding:0;
    overflow:hidden;
    background:#eef1f5;
    font-family:Arial,Helvetica,sans-serif;
}

/*=================================================
    MAIN LAYOUT
=================================================*/

.wrapper{
    height:100vh;
    display:flex;
    flex-direction:column;
}

.topbar{

    height:65px;

    background:#075E54;

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 20px;

    box-shadow:0 2px 8px rgba(0,0,0,.15);

    z-index:1000;

}

.topbar h4{

    margin:0;

    font-size:22px;

    font-weight:600;

}

.agent-info{

    font-size:15px;

    display:flex;
    align-items:center;
    gap:8px;

}

.ws-online-dot{
    width:10px;height:10px;background:#28a745;border-radius:50%;display:inline-block;flex-shrink:0;
}
.ws-reconnecting{
    font-size:12px;background:#ffc107;color:#333;padding:2px 8px;border-radius:10px;
}

/*=================================================
    PANELS
=================================================*/

.left-panel,
.middle-panel{

    background:#fff;

    border-right:1px solid #ddd;

    overflow-y:auto;

    padding:0;

}

.right-panel{

    display:flex;

    flex-direction:column;

    background:#efeae2;

    background-image:
    linear-gradient(
    rgba(255,255,255,.25),
    rgba(255,255,255,.25)
    );

    padding:0;

}

/*=================================================
    PANEL HEADER
=================================================*/

.panel-header{

    background:#f8f9fa;

    padding:15px;

    font-weight:bold;

    border-bottom:1px solid #ddd;

    position:sticky;

    top:0;

    z-index:99;

}

/*=================================================
    CHAT LIST
=================================================*/

.chat-item{

    display:flex;

    align-items:center;

    padding:12px;

    cursor:pointer;

    border-bottom:1px solid #eee;

    transition:.2s;

}

.chat-item:hover{

    background:#f4f7ff;

}

.chat-item.active{

    background:#d9fdd3;

}

.chat-avatar{

    width:48px;

    height:48px;

    border-radius:50%;

    background:#0d6efd;

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:18px;

    font-weight:bold;

    margin-right:12px;

    flex-shrink:0;

}

.chat-details{

    flex:1;

}

.customer-name{

    font-weight:bold;

    color:#222;

    margin-bottom:2px;

}

.customer-mobile{

    font-size:13px;

    color:#777;

}

.last-message{

    font-size:13px;

    color:#666;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}

.chat-meta{

    text-align:right;

    min-width:60px;

}

.chat-time{

    font-size:11px;

    color:#999;

}

.unread{

    display:inline-block;

    min-width:20px;

    height:20px;

    line-height:20px;

    border-radius:20px;

    background:#25D366;

    color:#fff;

    font-size:11px;

    text-align:center;

    margin-top:4px;

}

/*=================================================
    CHAT HEADER
=================================================*/

#chatHeader{

    display:flex;

    align-items:center;

    background:#f0f2f5;

    padding:15px;

    border-bottom:1px solid #ddd;

}

.header-avatar{

    width:45px;

    height:45px;

    border-radius:50%;

    background:#198754;

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:18px;

    font-weight:bold;

    margin-right:12px;

}

.header-info{

    flex:1;

}

.header-name{

    font-weight:bold;

    font-size:16px;

}

.header-status{

    font-size:12px;

    color:#198754;

}

/*=================================================
    CHAT BODY
=================================================*/

.chat-body{

    flex:1;

    overflow-y:auto;

    padding:20px;

}

.msg{

    display:flex;

    margin-bottom:15px;

}

.msg.customer{

    justify-content:flex-start;

}

.msg.agent{

    justify-content:flex-end;

}

.msg.system{

    justify-content:center;

}

.bubble{

    max-width:75%;

    padding:10px 14px;

    border-radius:12px;

    position:relative;

    word-break:break-word;

    box-shadow:0 1px 3px rgba(0,0,0,.12);

}

/* Customer */

.customer .bubble{

    background:#ffffff;

    color:#222;

    border-top-left-radius:0;

}

/* Agent */

.agent .bubble{

    background:#DCF8C6;

    color:#111;

    border-top-right-radius:0;

}

/* System */

.system .bubble{

    background:#fff3cd;

    color:#444;

}

/*=================================================
    TIME
=================================================*/

.time{

    display:block;

    text-align:right;

    font-size:10px;

    color:#777;

    margin-top:5px;

}

/*=================================================
    FOOTER
=================================================*/

.chat-footer{

    background:#f0f2f5;

    border-top:1px solid #ddd;

    padding:12px;

}

#messageBox{

    height:46px;

    resize:none;

    border-radius:25px;

    padding:10px 15px;

}

#sendBtn{

    height:46px;

    border-radius:25px;

}

#closeBtn{

    border-radius:20px;

}

/*=================================================
    SCROLLBAR
=================================================*/

::-webkit-scrollbar{

    width:8px;

}

::-webkit-scrollbar-thumb{

    background:#c8c8c8;

    border-radius:20px;

}

/*=================================================
    STATUS BADGES
=================================================*/

.online-dot{

    width:10px;

    height:10px;

    background:#28a745;

    border-radius:50%;

    display:inline-block;

    margin-right:5px;

}

.waiting-dot{

    width:10px;

    height:10px;

    background:#ffc107;

    border-radius:50%;

    display:inline-block;

    margin-right:5px;

}

/*=================================================
    MOBILE
=================================================*/

@media(max-width:992px){

.left-panel{

    height:220px;

}

.middle-panel{

    height:220px;

}

.right-panel{

    height:calc(100vh - 500px);

}

}

@media(max-width:768px){

.topbar h4{

    font-size:18px;

}

.chat-avatar{

    width:40px;

    height:40px;

}

.header-avatar{

    width:40px;

    height:40px;

}

.bubble{

    max-width:90%;

}

}


/*=========================
Typing
=========================*/

.typing-box{

    display:inline-block;

    background:#fff;

    padding:12px 18px;

    border-radius:18px;

    margin:10px;

    box-shadow:0 2px 6px rgba(0,0,0,.12);

}

.typing-dot{

    width:8px;

    height:8px;

    background:#777;

    border-radius:50%;

    display:inline-block;

    margin-right:4px;

    animation:typing 1.3s infinite;

}

.typing-dot:nth-child(2){

animation-delay:.2s;

}

.typing-dot:nth-child(3){

animation-delay:.4s;

}

@keyframes typing{

0%{

opacity:.3;
transform:translateY(0);

}

50%{

opacity:1;
transform:translateY(-5px);

}

100%{

opacity:.3;
transform:translateY(0);

}

}

/*=========================
Fade Animation
=========================*/

.msg{

animation:fadeMessage .25s ease;

}

@keyframes fadeMessage{

from{

opacity:0;
transform:translateY(15px);

}

to{

opacity:1;
transform:translateY(0);

}

}
</style>

</head>

<body>


<div class="wrapper">

<div class="topbar">

<div>

<h4>

<i class="bi bi-headset"></i>

Live Agent Dashboard

</h4>

</div>

<div class="agent-info">

Welcome,

<b><?= session()->get('name') ?></b>

<span class="ws-online-dot" id="agent-status-dot" title="WebSocket connected"></span>

</div>

</div>

<div class="container-fluid content">

<div class="row h-100">

<!-- Waiting Queue -->

<div class="col-lg-3 col-md-4 left-panel">

<div class="panel-header">

<i class="bi bi-hourglass-split"></i>

Waiting Queue

<span

class="badge bg-danger queue-count"

id="queueCount"

>

0

</span>

</div>

<div id="waitingQueue">

<!-- Ajax -->

</div>

</div>

<!-- Active Chats -->

<div class="col-lg-3 col-md-4 middle-panel">

<div class="panel-header">

<i class="bi bi-chat-dots-fill"></i>

Active Chats

<span

class="badge bg-success active-count"

id="activeCount"

>

0

</span>

</div>

<div id="activeChats">

<!-- Ajax -->

</div>

</div>

<!-- Chat Window -->

<div class="col-lg-6 col-md-4 right-panel">

<div

class="panel-header"

id="chatHeader"

>

No Chat Selected

</div>

<div

class="chat-body"

id="chatMessages"

>

<div

class="text-center text-muted mt-5"

>

Select a customer to begin chatting.

</div>

</div>

<div class="chat-footer">

<div class="row">

<div class="col-10">

<textarea

id="messageBox"

class="form-control"

placeholder="Type message..."

disabled

></textarea>

</div>

<div class="col-2 d-grid">

<button

class="btn btn-primary"

id="sendBtn"

disabled

>

<i class="bi bi-send-fill"></i>

</button>

</div>

</div>

<div class="mt-2">

<button

class="btn btn-danger btn-sm"

id="closeBtn"

disabled

>

<i class="bi bi-x-circle"></i>

Close Chat

</button>

</div>

</div>

</div>

</div>

</div>

</div>

<script>
const BASE_URL = "<?= base_url(); ?>";
</script>

<script>

let currentChatId=0;

let lastMessageId=0;

</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>

function loadQueue()
{
$.get(BASE_URL + "/agent/livechat/queue", function(res){

        if(!res.success) return;

        $("#queueCount").html(res.total);

        let html='';

        if(res.total==0)
        {
            html=`
            <div class="p-3 text-center text-muted">
                No waiting customer
            </div>`;
        }

        res.queue.forEach(function(chat){

            html+=`

            <div class="chat-item">

                <div class="customer-name">

                    ${chat.customer_name}

                </div>

                <div class="customer-mobile">

                    ${chat.customer_mobile}

                </div>

                <div class="mt-2">

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

        let html='';

        if(res.total==0)
        {

            html=`
            <div class="p-3 text-center text-muted">

                No Active Chats

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

                <div class="customer-name">

                    ${chat.customer_name}

                </div>

                <div class="customer-mobile">

                    ${chat.customer_mobile}

                </div>

                <small class="text-muted">

                    ${chat.last_message}

                </small>

                ${badgeHtml}

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

                loadQueue();
                loadActiveChats();

                openChat(chatId, name, mobile);

            }else{

                alert(res.message);

            }

        }
    });
}

// Initial load — DashboardWS will keep panels fresh via WebSocket events
loadQueue();
loadActiveChats();



</script>
<script>

function openChat(chatId,name,mobile)
{

    currentChatId=chatId;

    lastMessageId=0;

    // Reset unread badge for this chat (Task 12.4)
    unreadCounts[chatId] = 0;
    $('#chat-item-unread-' + chatId).text('').hide();

    $("#chatHeader").html(

        "<b>"+name+"</b><br><small>"+mobile+"</small>"

    );

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

// pollMessages is now driven by DashboardWS onmessage — no setInterval needed

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

                pollMessages();

            }
            else{

                alert(res.message);

            }

        }

    });

}

$("#closeBtn").click(function(){

    if(currentChatId==0)
        return;

    if(!confirm("Close this chat?"))
        return;

    $.ajax({

url:BASE_URL + "/agent/livechat/close",

        type:"POST",

        contentType:"application/json",

        data:JSON.stringify({

            chat_id:currentChatId,

            closed_by:"agent"

        }),

        success:function(res){

            if(res.success){

                $("#chatMessages").append(

                    '<div class="msg system"><div class="bubble">Conversation Closed.</div></div>'

                );

                $("#messageBox").prop("disabled",true);

                $("#sendBtn").prop("disabled",true);

                $("#closeBtn").prop("disabled",true);

                loadQueue();

                loadActiveChats();

            }

        }

    });

});

function escapeHtml(text)
{

    return $("<div>").text(text).html();

}


</script>
<script>
// ── Task 12.4: per-chat unread counters ─────────────────
const unreadCounts = {};   // chat_id → count

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

// ── Task 12.1 / 12.2 / 12.3 / 12.5 / 12.6: DashboardWS ─
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
            // Re-fetch panels after reconnect
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

    // ── Task 12.1: handle incoming WS events ────────────
    onMessage(event) {
        let data;
        try { data = JSON.parse(event.data); } catch(e) { return; }

        switch (data.type) {

            case 'message':
                if (data.chat_id == currentChatId) {
                    // Currently open chat — append directly
                    if (data.id && data.id > lastMessageId) lastMessageId = data.id;
                    appendMessage(data);
                } else {
                    // Background chat — badge + audio + browser notification
                    if (!unreadCounts[data.chat_id]) unreadCounts[data.chat_id] = 0;
                    unreadCounts[data.chat_id]++;
                    const badge = document.getElementById('chat-item-unread-' + data.chat_id);
                    if (badge) { badge.textContent = unreadCounts[data.chat_id]; badge.style.display = 'inline-block'; }
                    playNotification();
                    // Task 12.5 — browser notification when window not focused
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

            // Task 12.3 — customer typing indicator
            case 'typing_start':
                if (data.sender === 'customer' && data.chat_id == currentChatId) showTyping();
                break;

            case 'typing_stop':
                if (data.sender === 'customer' && data.chat_id == currentChatId) hideTyping();
                break;
        }
    },

    // ── Task 12.2: reconnect with status badge ──────────
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

    // ── Task 12.6: update topbar status dot ─────────────
    setStatusDot(online, reconnecting = false) {
        const dot = document.getElementById('agent-status-dot');
        if (!dot) return;
        if (online) {
            dot.className       = 'ws-online-dot';
            dot.title           = 'WebSocket connected';
            dot.textContent     = '';
            dot.style.background = '#28a745';
        } else if (reconnecting) {
            dot.className       = 'ws-reconnecting';
            dot.textContent     = 'Reconnecting…';
        } else {
            dot.className       = 'ws-online-dot';
            dot.style.background = '#dc3545';
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

// ── Task 12.3: agent typing events from messageBox ──────
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
</script>

<audio id="notifySound" preload="auto">
    <source src="https://actions.google.com/sounds/v1/cartoon/pop.ogg" type="audio/ogg">
</audio>


</body>

</html>