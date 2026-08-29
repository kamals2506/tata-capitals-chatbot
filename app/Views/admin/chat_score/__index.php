<table class="table table-bordered table-hover">

    <thead>

    <tr>

        <th>Customer</th>

        <th>Mobile</th>

        <th>Agent</th>

        <th>Vocabulary</th>

        <th>Grammar</th>

        <th>Relevance</th>

        <th>Fluency</th>

        <th>Final</th>

        <th>Action</th>

    </tr>

    </thead>

    <tbody>

    <?php foreach($rows as $r): ?>

    <tr>

        <td><?= esc($r->customer_name) ?></td>

        <td><?= esc($r->customer_mobile) ?></td>

        <td><?= esc($r->agent_name) ?></td>

        <td><?= $r->vocabulary ?></td>

        <td><?= $r->grammar ?></td>

        <td><?= $r->relevance ?></td>

        <td><?= $r->fluency ?></td>

        <td>

            <span class="badge bg-success">

                <?= $r->final_score ?>

            </span>

        </td>

        <td>

            <a href="<?= site_url('admin/chat-score/evaluate/'.$r->id) ?>"
               class="btn btn-primary btn-sm">

                Evaluate

            </a>

        </td>

    </tr>

    <?php endforeach; ?>

    </tbody>

</table>