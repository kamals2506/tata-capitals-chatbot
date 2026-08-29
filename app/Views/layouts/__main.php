<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Panel' ?></title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root {
            --grad-start: #4f46e5;
            --grad-end: #7c3aed;
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: #2d2a5e;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f4f5fa; margin: 0; }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 250px;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 22px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-brand .logo-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 16px;
        }
        .sidebar-brand span {
            color: #fff; font-weight: 700; font-size: 17px;
        }
        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-nav .nav-section-title {
            color: rgba(255,255,255,0.35);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 12px 6px;
        }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.7);
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 14.5px;
            font-weight: 500;
            margin-bottom: 4px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .sidebar-nav .nav-link i {
            font-size: 17px;
            width: 20px;
            text-align: center;
        }
        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
            color: #fff;
            box-shadow: 0 2px 8px rgba(79,70,229,0.4);
        }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-footer .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-footer .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 600; font-size: 13px;
        }
        .sidebar-footer .user-info small {
            color: rgba(255,255,255,0.5);
            display: block;
            font-size: 11px;
        }
        .sidebar-footer .user-info span {
            color: #fff;
            font-size: 13.5px;
            font-weight: 600;
        }

        /* Main content */
        .main-wrapper {
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #fff;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .topbar .page-title {
            font-weight: 700;
            color: #1e1b4b;
            font-size: 18px;
            margin: 0;
        }
        .content-area {
            padding: 28px;
            flex: 1;
        }

        /* Alerts */
        .alert { border-radius: 10px; border: none; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.2s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo-icon">B</div>
        <span>BPOC Admin</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>

        <a href="<?= site_url('livechat/dashboard1') ?>"
           class="nav-link <?= uri_string() === 'dashboard1' || uri_string() === '' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Management</div>

        <a href="<?= site_url('agents/') ?>"
           class="nav-link <?= (uri_string() === 'agents' || strpos(uri_string(), 'agents/') === 0) ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Agent Management</span>
        </a>
    </nav>

<div class="sidebar-footer">
    <div class="user-chip">
        <div class="avatar">
            <?= strtoupper(substr(session()->get('username') ?? 'A', 0, 1)) ?>
        </div>

        <div class="user-info">
            <span><?= esc(session()->get('username') ?? 'Admin') ?></span>
            <small>Administrator</small>
        </div>
    </div>

    <a href="<?= site_url('logout') ?>" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</div>
</aside>

<!-- Main content -->
<div class="main-wrapper">
 
    <div class="content-area">
        <?= $this->renderSection('content') ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<?= $this->renderSection('scripts') ?>

</body>
</html>