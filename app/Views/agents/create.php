<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .form-card { background:#fff; border-radius:12px; padding:32px; max-width:600px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    .form-card h2 { font-family:'Inter',sans-serif; color:#1e1b4b; margin-bottom:24px; }
    .form-group label { font-weight:600; color:#374151; margin-bottom:6px; display:block; }
    .form-control { border-radius:8px; border:1px solid #d1d5db; padding:10px 14px; width:100%; margin-bottom:16px; }
    .btn-gradient {
        background: linear-gradient(135deg, #4f46e5, #7c3aed); color:#fff; border:none;
        padding:10px 24px; border-radius:8px; font-weight:600;
    }
    .btn-cancel { padding:10px 24px; border-radius:8px; font-weight:600; background:#f3f4f6; color:#374151; text-decoration:none; margin-right:10px; }
</style>

<div class="form-card">
    <h2>Add New Agent</h2>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form action="<?= site_url('agents/store') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Agent Name</label>
            <input type="text" name="agent_name" class="form-control" value="<?= old('agent_name') ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_online" value="1" checked>
                Active
            </label>
        </div>

        <a href="<?= site_url('agents') ?>" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-gradient">Create Agent</button>
    </form>
</div>

<?= $this->endSection() ?>