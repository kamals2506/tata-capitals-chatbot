<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
    .page-header h2 { font-family:'Inter',sans-serif; font-weight:700; color:#1e1b4b; margin:0; }
    .btn-gradient {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color:#fff; border:none; padding:10px 20px; border-radius:8px;
        font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
        transition:opacity .2s;
    }
    .btn-gradient:hover { opacity:.9; color:#fff; }
    .card-table { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.08); overflow:hidden; }
    table.dataTable thead th {
        background:#1e1b4b; color:#fff; font-weight:600; border:none; padding:14px 16px;
    }
    table.dataTable tbody td { padding:14px 16px; vertical-align:middle; }
    .badge-status {
        padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600;
    }
    .badge-active { background:#dcfce7; color:#166534; }
    .badge-inactive { background:#fee2e2; color:#991b1b; }
    .action-icons a { margin-right:12px; color:#6366f1; text-decoration:none; }
    .action-icons a.text-danger { color:#dc2626; }
</style>

<div class="page-header">
    <h2>Agent Management</h2>
    <a href="<?= site_url('agents/create') ?>" class="btn-gradient">+ Add New Agent</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card-table">
    <table id="agentsTable" class="table" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Agent Name</th>
                <th>Email</th>
                <th>Account Status</th>
		<th>Online Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agents as $i => $agent): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($agent['agent_name']) ?></td>
                    <td><?= esc($agent['email']) ?></td>
                  <td>
    <span class="badge-status <?= $agent['Active'] ? 'badge-active' : 'badge-inactive' ?>">
        <?= $agent['Active'] ? 'Active' : 'Inactive' ?>
    </span>
</td>

<td>
    <span class="badge-status <?= $agent['is_online'] ? 'badge-active' : 'badge-inactive' ?>">
        <?= $agent['is_online'] ? 'Online' : 'Offline' ?>
    </span>
</td>
                    <td><?= date('d M Y, h:i A', strtotime($agent['created_at'])) ?></td>

                    <td class="action-icons">
                        <a href="<?= site_url('agents/edit/' . $agent['id']) ?>" title="Edit">Edit</a>

                       <a href="<?= site_url('agents/toggle/' . $agent['id']) ?>"
   title="<?= $agent['Active'] ? 'Deactivate' : 'Activate' ?>"
   onclick="return confirm('Are you sure you want to <?= $agent['Active'] ? 'deactivate' : 'activate' ?> this agent?')">
   <?= $agent['Active'] ? 'Deactivate' : 'Activate' ?>
</a>
                </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        $('#agentsTable').DataTable({
            order: [[0, 'asc']]
        });
    });
</script>

<?= $this->endSection() ?>