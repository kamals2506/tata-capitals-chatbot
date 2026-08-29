<!-- Chat Bubble -->
<div id="chatBubble">
    ??
</div>

<!-- Chat Window -->
<div id="chatWindow">
    <div class="chat-header">
        Tata Capital Support
        <span onclick="closeChat()">?</span>
    </div>

    <iframe
        src="https://bpoc.in/tata_capitals_chatboot/chatbot"
        frameborder="0">
    </iframe>
</div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="/trainingnew/images/icon.png">
<style>
#chatBubble{
    position:fixed;
    bottom:25px;
    right:25px;
    width:65px;
    height:65px;
    border-radius:50%;
    background:#E60012;
    color:#fff;
    font-size:30px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow:0 6px 20px rgba(0,0,0,.25);
    z-index:9999;
}

#chatWindow{
    position:fixed;
    bottom:100px;
    right:25px;
    width:380px;
    height:650px;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 10px 35px rgba(0,0,0,.25);
    display:none;
    z-index:9999;
}

.chat-header{
    background:#E60012;
    color:#fff;
    padding:14px 18px;
    font-weight:600;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.chat-header span{
    cursor:pointer;
    font-size:20px;
}

#chatWindow iframe{
    width:100%;
    height:calc(100% - 52px);
    border:none;
}
</style>

<script>
const bubble = document.getElementById('chatBubble');
const chatWindow = document.getElementById('chatWindow');

bubble.onclick = () => {
    chatWindow.style.display = 'block';
    bubble.style.display = 'none';
};

function closeChat() {
    chatWindow.style.display = 'none';
    bubble.style.display = 'flex';
}
</script>