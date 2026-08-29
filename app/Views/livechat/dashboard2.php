<!doctype html>
<html>

<head>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.kpi-card{
    border:none;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    transition:.3s;
    overflow:hidden;
}

.kpi-card:hover{
    transform:translateY(-4px);
}

.kpi-card .icon{
    width:60px;
    height:60px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}

.kpi-number{
    font-size:34px;
    font-weight:700;
}

.kpi-title{
    color:#6c757d;
    font-size:14px;
}
</style>
</head>

<body class="bg-light">

<div class="container-fluid mt-4">

<div class="row g-3">

<div class="col-md-2">
<div class="card bg-primary text-white">
<div class="card-body">
<h6>Total Chats</h6>
<h2><?= $totalChats ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card bg-success text-white">
<div class="card-body">
<h6>Active</h6>
<h2><?= $activeChats ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card bg-danger text-white">
<div class="card-body">
<h6>Closed</h6>
<h2><?= $closedChats ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card bg-warning">
<div class="card-body">
<h6>Waiting</h6>
<h2><?= $waitingChats ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card bg-info text-white">
<div class="card-body">
<h6>Online Agents</h6>
<h2><?= $onlineAgents ?></h2>
</div>
</div>
</div>

<div class="col-md-2">
<div class="card bg-dark text-white">
<div class="card-body">
<h6>Today's Messages</h6>
<h2><?= $todayMessages ?></h2>
</div>
</div>
</div>

</div>

<div class="row mt-4">

<div class="col-md-8">

<div class="card">

<div class="card-header">
Chat Activity
</div>

<div class="card-body">

<canvas id="chart"></canvas>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card">

<div class="card-header">
Agent Performance
</div>

<table class="table table-hover">

<thead>

<tr>

<th>Agent</th>
<th>Chats</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($agentPerformance as $a): ?>

<tr>

<td><?= $a->agent_name ?></td>

<td><?= $a->total_chats ?></td>

<td>

<?php if($a->is_online): ?>

<span class="badge bg-success">Online</span>

<?php else: ?>

<span class="badge bg-secondary">Offline</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<div class="row mt-4">

<div class="col-md-12">

<div class="card">

<div class="card-header">

Recent Chats

</div>

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>Customer</th>

<th>Mobile</th>

<th>Agent</th>

<th>Status</th>

<th>Total Messages</th>

<th>Last Message</th>

</tr>

</thead>

<tbody>

<?php foreach($recentChats as $r): ?>

<tr>

<td><?= $r->customer_name ?></td>

<td><?= $r->customer_mobile ?></td>

<td><?= $r->agent_name ?></td>

<td>

<?php

$color='secondary';

if($r->status=='Active')
$color='success';

if($r->status=='Closed')
$color='danger';

if($r->status=='Waiting')
$color='warning';

?>

<span class="badge bg-<?= $color ?>">
<?= $r->status ?>
</span>

</td>

<td><?= $r->total_messages ?></td>

<td><?= $r->last_message ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<script>

new Chart(document.getElementById('chart'),{

type:'line',

data:{

labels:<?= $chartLabels ?>,

datasets:[{

label:'Messages',

data:<?= $chartValues ?>,

fill:false,

tension:.3

}]

}

});

</script>

</body>

</html>