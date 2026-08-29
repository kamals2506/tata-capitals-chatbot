<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.bootstrap5.css">
<link rel="icon" type="image/png" href="https://bpoc.in/wfm/images/bpoc-icon.png">
<link rel="shortcut icon" href="https://bpoc.in/wfm/images/bpoc-icon.png">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

:root{

    --primary:#2563eb;
    --primary2:#4f46e5;
    --success:#10b981;
    --danger:#ef4444;
    --warning:#f59e0b;
    --info:#06b6d4;

    --bg:#f4f7fb;
    --card:#ffffff;

    --text:#1e293b;
    --muted:#64748b;

    --border:#e5e7eb;

}

body{

    background:var(--bg);
    font-family:Inter,Segoe UI,sans-serif;
    color:var(--text);

}

/*==========================
HEADER
==========================*/

.page-header{

    background:linear-gradient(135deg,#2563eb,#4338ca);

    color:#fff;

    border-radius:12px;

    padding:5px 10px;

    margin-bottom:10px;

    box-shadow:0 20px 40px rgba(37,99,235,.25);

}

.page-header h2{

    font-weight:700;

    margin-bottom:5px;

}

.page-header p{

    opacity:.85;

    margin:0;

}

/*==========================
KPI CARDS
==========================*/

.kpi-card{

    background:#fff;

    border:none;

    border-radius:18px;

    padding:10px;

    position:relative;

    overflow:hidden;

    box-shadow:0 10px 30px rgba(15,23,42,.08);

    transition:.35s;

}

.kpi-card:hover{

    transform:translateY(-5px);

    box-shadow:0 20px 40px rgba(15,23,42,.15);

}

.kpi-card:before{

    content:'';

    position:absolute;

    left:0;

    top:0;

    width:6px;

    height:100%;

    background:var(--primary);

}

.kpi-success:before{

    background:var(--success);

}

.kpi-danger:before{

    background:var(--danger);

}

.kpi-warning:before{

    background:var(--warning);

}

.kpi-info:before{

    background:var(--info);

}

.kpi-icon{

    width:60px;

    height:60px;

    border-radius:16px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:28px;

    color:#fff;

    margin-bottom:15px;

}

.bg-blue{

    background:linear-gradient(135deg,#2563eb,#3b82f6);

}

.bg-green{

    background:linear-gradient(135deg,#10b981,#34d399);

}

.bg-red{

    background:linear-gradient(135deg,#ef4444,#f87171);

}

.bg-orange{

    background:linear-gradient(135deg,#f59e0b,#fbbf24);

}

.bg-cyan{

    background:linear-gradient(135deg,#06b6d4,#22d3ee);

}

.kpi-title{

    color:var(--muted);

    font-size:13px;

    text-transform:uppercase;

    letter-spacing:1px;

}

.kpi-number{

    font-size:34px;

    font-weight:700;

    margin-top:8px;

}

.kpi-footer{

    color:var(--muted);

    font-size:13px;

}

/*==========================
PANEL
==========================*/

.panel{

    background:#fff;

    border:none;

    border-radius:18px;

    box-shadow:0 10px 30px rgba(15,23,42,.08);

    overflow:hidden;

}

.panel-header{

    padding:18px 22px;

    border-bottom:1px solid var(--border);

    font-size:17px;

    font-weight:600;

}

.panel-body{

    padding:20px;

}

/*==========================
TABLE
==========================*/

.table{

    margin-bottom:0;

}

.table thead{

    background:#f8fafc;

}

.table thead th{

    border:none;

    color:#475569;

    font-size:13px;

    text-transform:uppercase;

    letter-spacing:.5px;

}

.table tbody td{

    border-color:#edf2f7;

    vertical-align:middle;

    padding:15px;

}

.table tbody tr{

    transition:.25s;

}

.table tbody tr:hover{

    background:#f8fbff;

}

/*==========================
AVATAR
==========================*/

.avatar{

    width:45px;

    height:45px;

    border-radius:50%;

    background:linear-gradient(135deg,#2563eb,#4f46e5);

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:700;

}

/*==========================
BADGES
==========================*/

.badge{

    padding:8px 14px;

    border-radius:30px;

    font-size:12px;

    font-weight:600;

}

/*==========================
BUTTONS
==========================*/

.btn-primary{

    background:linear-gradient(135deg,#2563eb,#4338ca);

    border:none;

    border-radius:10px;

}

.btn-primary:hover{

    background:linear-gradient(135deg,#1d4ed8,#3730a3);

}

/*==========================
AGENT LIST
==========================*/

.agent-item{

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:15px;

    border-bottom:1px solid #edf2f7;

}

.agent-item:last-child{

    border-bottom:none;

}

.agent-name{

    font-weight:600;

}

.agent-chat{

    color:var(--muted);

    font-size:13px;

}

/*==========================
CHAT MODAL
==========================*/

.chat-box{
    max-height:550px;
    overflow-y:auto;
    background:#f4f7fb;
    padding:20px;
}

.customer-msg{
    display:inline-block;
    max-width:70%;
    background:#ffffff;
    border-radius:18px 18px 18px 5px;
    padding:12px 16px;
    margin-bottom:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.06);
    word-wrap:break-word;
    clear:both;
    float:left;
}

.agent-msg{
    display:inline-block;
    max-width:70%;
    background:linear-gradient(135deg,#2563eb,#4338ca);
    color:#fff;
    border-radius:18px 18px 5px 18px;
    padding:12px 16px;
    margin-bottom:15px;
    box-shadow:0 5px 15px rgba(37,99,235,.25);
    word-wrap:break-word;
    clear:both;
    float:right;
}

.chat-time{
    display:block;
    margin-top:8px;
    font-size:11px;
    opacity:.7;
}

.chat-box::after{
    content:"";
    display:block;
    clear:both;
}

/*==========================
SCROLLBAR
==========================*/

::-webkit-scrollbar{

    width:8px;

}

::-webkit-scrollbar-thumb{

    background:#cbd5e1;

    border-radius:20px;

}

/*==========================
CHART
==========================*/

canvas{

    max-height:380px;

}

/*==========================
RESPONSIVE
==========================*/

@media(max-width:992px){

.kpi-number{

font-size:26px;

}

.customer-msg,.agent-msg{

width:95%;

}

.page-header{

text-align:center;

}

}

.dataTables_wrapper{

padding:20px;

}

.dataTables_filter input{

border-radius:10px !important;

border:1px solid #dbe4ef !important;

padding:8px 12px !important;

box-shadow:none;

}

.dataTables_length select{

border-radius:10px !important;

}

.dt-buttons{

margin-bottom:10px;

}

.dt-button{

margin-right:5px !important;

}

.page-link{

border-radius:8px !important;

margin:0 3px;

}

.page-item.active .page-link{

background:#2563eb;

border-color:#2563eb;

}

table.dataTable tbody tr:hover{

background:#f3f8ff;

}

.chat-row{
    display:flex;
    margin-bottom:15px;
}

.chat-row.customer{
    justify-content:flex-start;
}

.chat-row.agent{
    justify-content:flex-end;
}

.customer-msg,
.agent-msg{
    max-width:65%;
}

.logout-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 20px;
    border-radius:50px;
    text-decoration:none;
    color:#fff;
    font-weight:600;
    background:linear-gradient(135deg,#4F8CFF,#8B5CF6,#00D4AA);
    transition:.3s ease;
    box-shadow:0 8px 20px rgba(79,140,255,.25);
}

.logout-btn:hover{
    color:#fff;
    transform:translateY(-2px);
    box-shadow:0 12px 28px rgba(79,140,255,.35);
}
</style>
</head>

<body class="bg-light">

<div class="container-fluid py-4">

    <!-- ================= HEADER ================= -->

  <div class="page-header">
    <div class="row align-items-center">

        <div class="col-lg-8">
            <h4>
                <i class="bi bi-headset"></i>
                Live Chat Command Center
            </h4>

            <p>
                Monitor live conversations, agent productivity and customer engagement in real-time.
            </p>
        </div>

      <div class="col-lg-2 ms-auto text-end">
    <h4 id="clock" class="mb-0"></h4>
    <small><?= date('d M Y') ?></small>
</div>

    </div>
</div>



    <!-- ================= KPI ================= -->

    <div class="row g-4">

        <div class="col-xl-2 col-md-4">

            <div class="kpi-card">

                <div class="kpi-icon bg-blue">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>

                <div class="kpi-title">Total Chats</div>

                <div class="kpi-number"><?= $totalChats ?></div>

                <div class="kpi-footer">
                    All customer conversations
                </div>

            </div>

        </div>



        <div class="col-xl-2 col-md-4">

            <div class="kpi-card kpi-success">

                <div class="kpi-icon bg-green">

                    <i class="bi bi-lightning-charge-fill"></i>

                </div>

                <div class="kpi-title">Active</div>

                <div class="kpi-number"><?= $activeChats ?></div>

                <div class="kpi-footer">

                    Currently Live

                </div>

            </div>

        </div>



        <div class="col-xl-2 col-md-4">

            <div class="kpi-card kpi-warning">

                <div class="kpi-icon bg-orange">

                    <i class="bi bi-clock-history"></i>

                </div>

                <div class="kpi-title">Waiting</div>

                <div class="kpi-number"><?= $waitingChats ?></div>

                <div class="kpi-footer">

                    In Queue

                </div>

            </div>

        </div>



        <div class="col-xl-2 col-md-4">

            <div class="kpi-card kpi-danger">

                <div class="kpi-icon bg-red">

                    <i class="bi bi-check-circle-fill"></i>

                </div>

                <div class="kpi-title">Closed</div>

                <div class="kpi-number"><?= $closedChats ?></div>

                <div class="kpi-footer">

                    Successfully Resolved

                </div>

            </div>

        </div>



        <div class="col-xl-2 col-md-4">

            <div class="kpi-card kpi-info">

                <div class="kpi-icon bg-cyan">

                    <i class="bi bi-person-check-fill"></i>

                </div>

                <div class="kpi-title">Online Agents</div>

                <div class="kpi-number"><?= $onlineAgents ?></div>

                <div class="kpi-footer">

                    Available Now

                </div>

            </div>

        </div>



        <div class="col-xl-2 col-md-4">

            <div class="kpi-card">

                <div class="kpi-icon bg-blue">

                    <i class="bi bi-envelope-fill"></i>

                </div>

                <div class="kpi-title">Messages Today</div>

                <div class="kpi-number"><?= $todayMessages ?></div>

                <div class="kpi-footer">

                    Today's Activity

                </div>

            </div>

        </div>

    </div>



    <!-- ================= CHART + AGENTS ================= -->

    <div class="row mt-4">

        <div class="col-lg-8">

            <div class="panel">

                <div class="panel-header">

                    <i class="bi bi-graph-up-arrow"></i>

                    Chat Activity Trend

                </div>

                <div class="panel-body">

                    <canvas id="chart"></canvas>

                </div>

            </div>

        </div>



        <div class="col-lg-4">

            <div class="panel">

                <div class="panel-header">

                    <i class="bi bi-people-fill"></i>

                    Agent Performance

                </div>

                <div class="panel-body p-0">

                    <?php foreach($agentPerformance as $a): ?>

                    <div class="agent-item">

                        <div>

                            <div class="agent-name">

                                <?= $a->agent_name ?>

                            </div>

                            <div class="agent-chat">

                                <?= $a->total_chats ?> Chats

                            </div>

                        </div>

                        <div>

                            <?php if($a->is_online): ?>

                                <span class="badge bg-success">

                                    Online

                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">

                                    Offline

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </div>



    <!-- ================= RECENT CHAT ================= -->

    <div class="panel mt-4">

        <div class="panel-header">

            <i class="bi bi-chat-left-text-fill"></i>

            Recent Conversations

        </div>



        <div class="table-responsive">

<table id="chatTable" class="table table-hover align-middle w-100">

                <thead>

                <tr>

                    <th>Customer</th>

                    <th>Mobile</th>

                    <th>Agent</th>

                    <th>Status</th>

                    <th>Messages</th>

                    <th>Last Activity</th>

                    <th width="100">Action</th>

                </tr>

                </thead>



                <tbody>

                <?php foreach($recentChats as $r): ?>



                    <tr>



                        <td>

                            <div class="d-flex align-items-center">

                                <div class="avatar">

                                    <?= strtoupper(substr($r->customer_name,0,1)) ?>

                                </div>



                                <div class="ms-3">

                                    <strong>

                                        <?= $r->customer_name ?>

                                    </strong>

                                </div>

                            </div>

                        </td>



                        <td>

                            <?= $r->customer_mobile ?>

                        </td>



                        <td>

                            <?= $r->agent_name ?>

                        </td>



                        <td>

                            <?php

                            $badge='secondary';



                            if($r->status=="Active")

                                $badge='success';



                            if($r->status=="Waiting")

                                $badge='warning';



                            if($r->status=="Closed")

                                $badge='danger';

                            ?>



                            <span class="badge bg-<?= $badge ?>">

                                <?= $r->status ?>

                            </span>



                        </td>



                        <td>

                            <?= $r->total_messages ?>

                        </td>



                        <td>

                            <?= $r->last_message ?>

                        </td>



                        <td>



                            <button

                                    class="btn btn-primary btn-sm viewHistory"

                                    data-session="<?= $r->id ?>">



                                <i class="bi bi-eye"></i>

                                View



                            </button>



                        </td>



                    </tr>



                <?php endforeach; ?>



                </tbody>



            </table>



        </div>



    </div>



</div>



<!-- ================= CHAT HISTORY MODAL ================= -->

<div class="modal fade"

     id="historyModal"

     tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">

                <h5>

                    <i class="bi bi-chat-left-dots-fill"></i>

                    Conversation History

                </h5>

                <button

                        class="btn-close"

                        data-bs-dismiss="modal">

                </button>

            </div>



            <div class="modal-body">

                <div id="chatHistory"

                     class="chat-box">

                </div>

            </div>



        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.js"></script>

<script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>

<script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.bootstrap5.js"></script>

<script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.js"></script>

<script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.print.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>


$(function(){

$('#chatTable').DataTable({

responsive:true,

pageLength:10,

lengthMenu:[
[10,25,50,100,-1],
[10,25,50,100,"All"]
],

order:[[5,"desc"]],

searching:true,

paging:true,

info:true,

ordering:true,

autoWidth:false,

dom:

"<'row mb-3'<'col-md-6'B><'col-md-6'f>>"+

"rt"+

"<'row mt-3'<'col-md-5'i><'col-md-7'p>>",

buttons:[

{
extend:'excel',
className:'btn btn-success btn-sm',
text:'<i class="bi bi-file-earmark-excel"></i> Excel'
},

{
extend:'csv',
className:'btn btn-info btn-sm',
text:'CSV'
},

{
extend:'print',
className:'btn btn-dark btn-sm',
text:'Print'
}

],

language:{

search:"",

searchPlaceholder:"Search Customer / Mobile / Agent...",

lengthMenu:"Show _MENU_ records",

info:"Showing _START_ to _END_ of _TOTAL_ Chats",

paginate:{
previous:"&laquo;",
next:"&raquo;"
},

emptyTable:"No chats available."

}

});

});


let historyModal = new bootstrap.Modal(document.getElementById('historyModal'));

/*==========================================
LIVE CLOCK
==========================================*/

function updateClock(){

    const now=new Date();

    document.getElementById('clock').innerHTML=
        now.toLocaleTimeString('en-IN',{
            hour:'2-digit',
            minute:'2-digit',
            second:'2-digit'
        });

}

updateClock();

setInterval(updateClock,1000);


/*==========================================
CHAT ACTIVITY CHART
==========================================*/

const ctx=document.getElementById('chart');

new Chart(ctx,{

    type:'line',

    data:{

        labels:<?= $chartLabels ?>,

        datasets:[{

            label:'Messages',

            data:<?= $chartValues ?>,

            borderColor:'#2563eb',

            backgroundColor:'rgba(37,99,235,.10)',

            fill:true,

            borderWidth:3,

            tension:.35,

            pointRadius:4,

            pointHoverRadius:6

        }]

    },

    options:{

        responsive:true,

        maintainAspectRatio:false,

        plugins:{

            legend:{

                display:false

            }

        },

        scales:{

            x:{

                grid:{
                    display:false
                }

            },

            y:{

                beginAtZero:true,

                ticks:{
                    precision:0
                }

            }

        }

    }

});


/*==========================================
VIEW CHAT HISTORY
==========================================*/

document.querySelectorAll('.viewHistory').forEach(function(btn){

    btn.addEventListener('click',function(){

        let session=this.dataset.session;

        let history=document.getElementById('chatHistory');

        history.innerHTML=`

            <div class="text-center py-5">

                <div class="spinner-border text-primary"></div>

                <div class="mt-3">

                    Loading Conversation...

                </div>

            </div>

        `;

        historyModal.show();

        fetch("<?= base_url('livechat/history1') ?>/"+session)

        .then(response=>response.json())

        .then(function(data){

            let html='';

            if(data.length==0){

                html=`

                <div class="alert alert-warning">

                    No conversation found.

                </div>

                `;

            }

            data.forEach(function(row){

                if(row.sender.toLowerCase()=="customer"){

                   html += `
<div class="chat-row customer">
    <div class="customer-msg">
        <strong><i class="bi bi-person-circle"></i> Customer</strong>
        <div class="mt-2">${row.message}</div>
        <div class="chat-time">${row.created_at}</div>
    </div>
</div>
`;

                }

                else{

                   html += `
<div class="chat-row agent">
    <div class="agent-msg">
        <strong><i class="bi bi-headset"></i> ${row.agent_name ?? 'Agent'}</strong>
        <div class="mt-2">${row.message}</div>
        <div class="chat-time">${row.created_at}</div>
    </div>
</div>
`;
                }

            });

            history.innerHTML=html;

            history.scrollTop=history.scrollHeight;

        })

        .catch(function(){

            history.innerHTML=`

            <div class="alert alert-danger">

                Unable to load conversation.

            </div>

            `;

        });

    });

});


/*==========================================
AUTO REFRESH DASHBOARD
==========================================*/

setInterval(function(){

    location.reload();

},30000);


/*==========================================
TABLE ROW HOVER EFFECT
==========================================*/

document.querySelectorAll("tbody tr").forEach(function(row){

    row.addEventListener("mouseenter",function(){

        this.style.transform="scale(1.002)";

        this.style.transition=".25s";

    });

    row.addEventListener("mouseleave",function(){

        this.style.transform="scale(1)";

    });

});


/*==========================================
KPI CARD ANIMATION
==========================================*/

window.addEventListener("load",function(){

    document.querySelectorAll(".kpi-card").forEach(function(card,index){

        card.style.opacity="0";

        card.style.transform="translateY(20px)";

        setTimeout(function(){

            card.style.transition=".5s";

            card.style.opacity="1";

            card.style.transform="translateY(0px)";

        },index*120);

    });

});


/*==========================================
PAGE LOADER FADE
==========================================*/

window.onload=function(){

    document.body.style.opacity=1;

};

</script>


<?= $this->endSection() ?>
