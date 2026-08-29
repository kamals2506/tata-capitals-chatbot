<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= esc($title ?? 'BPOC Admin') ?></title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>

:root{

--sidebar:#1e1b4b;
--sidebar-hover:#302c73;
--primary:#4f46e5;
--primary2:#7c3aed;

}

*{
font-family:'Inter',sans-serif;
}

body{

margin:0;
background:#f5f7fb;

}

/*********************
SIDEBAR
**********************/

.sidebar{

position:fixed;
top:0;
left:0;
bottom:0;
width:250px;

background:var(--sidebar);

display:flex;
flex-direction:column;

z-index:1000;

}

.sidebar-brand{

padding:22px;

display:flex;

align-items:center;

gap:12px;

border-bottom:1px solid rgba(255,255,255,.08);

}

.logo{

width:42px;
height:42px;

border-radius:12px;

background:linear-gradient(135deg,var(--primary),var(--primary2));

display:flex;

align-items:center;

justify-content:center;

font-size:20px;

font-weight:700;

color:#fff;

}

.brand{

font-size:18px;

font-weight:700;

color:#fff;

}

.sidebar-nav{

padding:15px;

flex:1;

overflow:auto;

}

.sidebar-title{

color:rgba(255,255,255,.45);

font-size:11px;

text-transform:uppercase;

margin:10px 12px;

letter-spacing:1px;

}

.sidebar-nav a{

display:flex;

align-items:center;

gap:12px;

padding:12px 14px;

margin-bottom:5px;

border-radius:10px;

text-decoration:none;

color:rgba(255,255,255,.75);

transition:.25s;

font-size:14px;

}

.sidebar-nav a:hover{

background:var(--sidebar-hover);

color:#fff;

}

.sidebar-nav a.active{

background:linear-gradient(135deg,var(--primary),var(--primary2));

color:#fff;

box-shadow:0 8px 20px rgba(79,70,229,.35);

}

.sidebar-nav i{

font-size:18px;

width:20px;

text-align:center;

}

/*********************
FOOTER
**********************/

.sidebar-footer{

padding:18px;

border-top:1px solid rgba(255,255,255,.08);

}

.user{

display:flex;

align-items:center;

gap:10px;

margin-bottom:15px;

}

.avatar{

width:42px;
height:42px;

border-radius:50%;

background:linear-gradient(135deg,var(--primary),var(--primary2));

display:flex;

align-items:center;

justify-content:center;

color:#fff;

font-weight:700;

}

.user-name{

color:#fff;

font-size:14px;

font-weight:600;

}

.user-role{

font-size:12px;

color:#b8bdd3;

}

.logout{

display:block;

text-align:center;

padding:10px;

border-radius:10px;

background:linear-gradient(135deg,#4F8CFF,#8B5CF6,#00D4AA);

color:#fff;

text-decoration:none;

font-weight:600;

transition:.3s;

}

.logout:hover{

color:#fff;

transform:translateY(-2px);

}

/*********************
MAIN
**********************/

.main{

margin-left:250px;

min-height:100vh;

display:flex;

flex-direction:column;

}

.topbar{

background:#fff;

padding:18px 25px;

border-bottom:1px solid #e8edf5;

display:flex;

justify-content:space-between;

align-items:center;

position:sticky;

top:0;

z-index:999;

}

.topbar h4{

margin:0;

font-size:20px;

font-weight:700;

}

.content{

padding:25px;

}

/*********************
FLASH
**********************/

.alert{

border:none;

border-radius:10px;

}

/*********************
RESPONSIVE
**********************/

@media(max-width:768px){

.sidebar{

transform:translateX(-100%);
transition:.3s;

}

.sidebar.show{

transform:translateX(0);

}

.main{

margin-left:0;

}

}

</style>

<?= $this->renderSection('styles') ?>

</head>

<body>

<!-- Sidebar -->

<aside class="sidebar">

<div class="sidebar-brand">

<div class="logo">
B
</div>

<div class="brand">
BPOC Admin
</div>

</div>

<div class="sidebar-nav">

<div class="sidebar-title">
Main
</div>

<a href="<?= site_url('livechat/dashboard1') ?>"
class="<?= strpos(uri_string(),'livechat/dashboard1')===0 ? 'active':'' ?>">

<i class="bi bi-speedometer2"></i>

Dashboard

</a>

<div class="sidebar-title">
Management
</div>

<a href="<?= site_url('agents') ?>"
class="<?= strpos(uri_string(),'agents')===0 ? 'active':'' ?>">

<i class="bi bi-people-fill"></i>

Agent Management

</a>

 
<a href="<?= site_url('admin/chat-score') ?>"
class="<?= strpos(uri_string(),'admin/chat-score')===0 ? 'active':'' ?>">
 
<i class="bi bi-clipboard-data-fill"></i>
 
Chat QA Scores
 
</a>

</div>

<div class="sidebar-footer">

<div class="user">

<div class="avatar">

<?= strtoupper(substr(session()->get('username') ?? 'A',0,1)) ?>

</div>

<div>

<div class="user-name">

<?= esc(session()->get('username') ?? 'Administrator') ?>

</div>

<div class="user-role">

Administrator

</div>

</div>

</div>

<a href="<?= site_url('logout') ?>" class="logout">

<i class="bi bi-box-arrow-right"></i>

Logout

</a>

</div>

</aside>

<!-- Main -->

<div class="main">

<div class="topbar">

<h4>

<?= esc($title ?? 'Dashboard') ?>

</h4>

</div>

<div class="content">

<?php if(session()->getFlashdata('success')): ?>

<div class="alert alert-success">

<?= session()->getFlashdata('success') ?>

</div>

<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>

<div class="alert alert-danger">

<?= session()->getFlashdata('error') ?>

</div>

<?php endif; ?>

<?= $this->renderSection('content') ?>

</div>

</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<?= $this->renderSection('scripts') ?>

</body>
</html>