<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Live Support | Agent Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" type="image/png" href="https://bpoc.in/wfm/images/bpoc-icon.png">
<link rel="shortcut icon" href="https://bpoc.in/wfm/images/bpoc-icon.png">


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Inter,Segoe UI,Tahoma,sans-serif;
}

body{

    height:100vh;
    overflow:hidden;

    background:
    radial-gradient(circle at top left,#5b8cff 0%,transparent 30%),
    radial-gradient(circle at bottom right,#00d4ff 0%,transparent 30%),
    linear-gradient(135deg,#09111f,#16253f,#0b1325);

    display:flex;
    justify-content:center;
    align-items:center;
}

/* Animated Background */

body::before{

    content:'';
    position:absolute;
    width:700px;
    height:700px;
    border-radius:50%;
    background:rgba(255,255,255,.04);
    top:-250px;
    right:-250px;
    animation:move1 10s infinite alternate;
}

body::after{

    content:'';
    position:absolute;
    width:600px;
    height:600px;
    border-radius:50%;
    background:rgba(0,212,255,.05);
    bottom:-250px;
    left:-250px;
    animation:move2 12s infinite alternate;
}

@keyframes move1{

from{
transform:translateY(0);
}

to{
transform:translateY(80px);
}

}

@keyframes move2{

from{
transform:translateX(0);
}

to{
transform:translateX(120px);
}

}

.login-card{

    position:relative;
    z-index:10;

    width:430px;

    background:rgba(255,255,255,.08);

    backdrop-filter:blur(18px);

    border:1px solid rgba(255,255,255,.15);

    border-radius:22px;

    padding:45px;

    box-shadow:
    0 20px 60px rgba(0,0,0,.45);

}

.logo{

    width:80px;
    height:80px;

    margin:auto;

    border-radius:18px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:linear-gradient(135deg,#4f7cff,#00d4ff);

    color:#fff;
    font-size:36px;

    box-shadow:0 15px 40px rgba(0,150,255,.35);

}

.title{

    color:#fff;
    text-align:center;
    font-size:20px;
    font-weight:700;
    margin-top:25px;

}
.title h5{
    margin:0;
    font-size:20px;
    font-weight:800;
    letter-spacing:2px;

  background:linear-gradient(
        135deg,
        #4F8CFF,
        #8B5CF6,
        #00D4AA
    );

-webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    color: transparent;

  }


.subtitle{

    color:rgba(255,255,255,.75);
    text-align:center;
    margin-bottom:35px;
    font-size:15px;

}

.form-floating{

    margin-bottom:22px;

}

.form-control{

    background:rgba(255,255,255,.08)!important;

    border:1px solid rgba(255,255,255,.18);

    color:#fff;

    height:58px;

    border-radius:14px;

}

.form-control:focus{

    border-color:#58b7ff;

    box-shadow:0 0 0 .20rem rgba(88,183,255,.25);

    color:#fff;

}

.form-floating label{

    color:#c7d4ea;

}

.form-control::placeholder{

    color:#ccc;

}

.input-group{

    position:relative;

}

.password-toggle{

    position:absolute;
    right:18px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:#fff;
    z-index:20;
}

.login-btn{

    height:56px;

    border:none;

    border-radius:14px;

    font-size:17px;

    font-weight:600;

    background:linear-gradient(135deg,#2d7ff9,#00c8ff);

    transition:.35s;

    box-shadow:
    0 15px 35px rgba(0,160,255,.35);

}

.login-btn:hover{

    transform:translateY(-2px);

    box-shadow:
    0 20px 45px rgba(0,160,255,.45);

}

.footer{

    margin-top:30px;

    text-align:center;

    color:rgba(255,255,255,.65);

    font-size:13px;

}

.version{

    margin-top:6px;

    color:#8ebdff;

    font-size:12px;

}

.alert{

border-radius:12px;

}

@media(max-width:520px){

.login-card{

width:92%;
padding:30px;

}

.title{

font-size:26px;

}

}

.brand-logo1{
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    width: 100%;
}

.brand-logo1 img{
    display: block;
    width: 70px;
    height: auto;
}


</style>

</head>

<body>

<div class="login-card">

<div class="brand-logo1">
    <img src="https://bpoc.in/wfm/images/bpoc-icon.png"
         alt="B-CAI Logo"
         width="40" align="center">

</div>


<div class="title">

Live Chat Support <h5>Tata Capitals<h5>

</div>

<div class="subtitle">

Secure Agent Console Login

</div>

<?php if(session()->getFlashdata('error')): ?>

<div class="alert alert-danger">

<?= session()->getFlashdata('error') ?>

</div>

<?php endif; ?>

<form method="post" action="<?= site_url('login') ?>">

<div class="form-floating">

<input
type="text"
class="form-control"
id="username"
name="username"
placeholder="Username"
required>

<label for="username">

<i class="bi bi-person-fill me-2"></i>
Username

</label>

</div>

<div class="input-group">

<div class="form-floating w-100">

<input
type="password"
class="form-control"
id="password"
name="password"
placeholder="Password"
required>

<label for="password">

<i class="bi bi-lock-fill me-2"></i>
Password

</label>

</div>

<span
class="password-toggle"
onclick="togglePassword()">

<i
class="bi bi-eye-fill"
id="eyeIcon"></i>

</span>

</div>

<div class="d-flex justify-content-between align-items-center mb-4">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
id="remember">

<label
class="form-check-label text-white"
for="remember">

Remember me

</label>

</div>

<a
href="#"
class="text-info text-decoration-none">

Forgot Password?

</a>

</div>

<button
type="submit"
class="btn login-btn w-100">

<i class="bi bi-box-arrow-in-right me-2"></i>

Sign In

</button>

</form>

<div class="footer">

&copy; <?= date('Y') ?> BPO Convergence Pvt. Ltd

<div class="version">

 Enterprise Live Support

</div>

</div>

</div>

<script>

function togglePassword(){

let input=document.getElementById("password");

let icon=document.getElementById("eyeIcon");

if(input.type==="password"){

input.type="text";

icon.classList.remove("bi-eye-fill");

icon.classList.add("bi-eye-slash-fill");

}else{

input.type="password";

icon.classList.remove("bi-eye-slash-fill");

icon.classList.add("bi-eye-fill");

}

}

</script>

</body>
</html>