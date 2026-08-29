<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    body {
        background: #f3f6ff;
    }

    .qa-panel {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(148, 163, 184, 0.35);
        overflow: hidden;
    }

    .qa-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 22px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        background: linear-gradient(135deg, #0f172a, #1d4ed8);
        color: #fff;
    }

    .qa-panel-title-block {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .qa-icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .qa-panel-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .qa-panel-subtitle {
        font-size: 12px;
        opacity: 0.85;
        margin: 0;
    }

    .qa-panel-badge {
        background: rgba(15, 23, 42, 0.35);
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
    }

    .qa-filter-row {
        padding: 14px 22px 6px 22px;
        background: #f9fafb;
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
    }

    .qa-filter-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .qa-filter-input {
        position: relative;
    }

    .qa-filter-input input[type="date"] {
        padding-left: 34px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 13px;
    }

    .qa-filter-input i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 14px;
    }

    #scoreTable_wrapper {
        padding: 14px 18px 18px 18px;
    }

    table#scoreTable {
        font-size: 13px;
        border-radius: 10px;
        overflow: hidden;
    }

    #scoreTable thead {
        background: #0f172a;
        color: #e5e7eb;
    }

    #scoreTable thead th {
        border-bottom: none;
        font-weight: 600;
        padding-top: 10px;
        padding-bottom: 10px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    #scoreTable tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    #scoreTable tbody tr:hover {
        background: #e5edff !important;
    }

    #scoreTable tbody td {
        vertical-align: middle;
    }

    .btn-primary.btn-sm {
        border-radius: 999px;
        padding: 4px 11px;
        font-size: 12px;
    }

    .btn-success.btn-sm,
    .btn-danger.btn-sm {
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 11px;
    }

    .btn-info.btn-sm {
        border-radius: 999px;
        padding: 4px 11px;
        font-size: 12px;
    }

    .btn-export-csv {
        border-radius: 999px !important;
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
        border: none !important;
        color: #ffffff !important;
        font-size: 12px !important;
        padding: 6px 14px !important;
        box-shadow: 0 4px 10px rgba(34, 197, 94, 0.35);
    }

    .badge {
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .badge.bg-success {
        background: #16a34a !important;
    }

    .badge.bg-danger {
        background: #dc2626 !important;
    }

    .chat-box {
        max-height: 500px;
        overflow-y: auto;
        background: #f8f9fc;
        padding: 20px;
        border-radius: 16px;
    }

    .chat-row {
        display: flex;
        margin-bottom: 16px;
    }

    .chat-row.customer {
        justify-content: flex-start;
    }

    .chat-row.agent {
        justify-content: flex-end;
    }

    .customer-msg,
    .agent-msg {
        max-width: 70%;
        padding: 12px 18px;
        border-radius: 18px;
        word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .customer-msg {
        background: #fff;
        border-bottom-left-radius: 4px;
    }

    .agent-msg {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .chat-time {
        font-size: 11px;
        opacity: 0.6;
        margin-top: 6px;
        display: block;
    }

    .qa-note {
        font-size: 11px;
        color: #6b7280;
    }

    .modal {
        z-index: 1060 !important;
    }

    .modal-backdrop {
        z-index: 1055 !important;
        background-color: rgba(0, 0, 0, 0.6) !important;
    }

    .modal-content {
        z-index: 1061 !important;
        position: relative;
    }

    .modal-dialog {
        z-index: 1061 !important;
        position: relative;
    }

    .modal-backdrop.show {
        opacity: 0.6 !important;
    }

    @media (max-width: 768px) {
        .qa-panel-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        #scoreTable_wrapper {
            padding: 12px 10px 12px 10px;
        }

        .qa-filter-row {
            padding: 10px 12px 4px 12px;
        }
    }
</style>

<div class="qa-panel mb-4">

    <div class="qa-panel-header">
        <div class="qa-panel-title-block">
            <div class="qa-icon-circle">
                <i class="bi bi-clipboard-data-fill"></i>
            </div>
            <div>
                <h5 class="qa-panel-title mb-1">Chat QA Scores</h5>
                <p class="qa-panel-subtitle mb-0">Monitor agent performance, compliance & quality in real time.</p>
            </div>
        </div>

        <div class="qa-panel-badge">
            Live QA Dashboard
        </div>
    </div>

    <div class="qa-filter-row">
        <div class="row g-3 align-items-end">
            <div class="col-md-3 col-6">
                <div class="qa-filter-label">From date</div>
                <div class="qa-filter-input">
                    <i class="bi bi-calendar-event"></i>
                    <input type="date" id="fromDate" class="form-control">
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="qa-filter-label">To date</div>
                <div class="qa-filter-input">
                    <i class="bi bi-calendar-check"></i>
                    <input type="date" id="toDate" class="form-control">
                </div>
            </div>

            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <span class="qa-note">
                    Use date range to filter chat sessions and export filtered results as CSV.
                </span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="scoreTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Agent</th>
                    <th>Score</th>
                    <th>Disposition</th>
                    <th>Action</th>
                    <th>Reply Avg.</th>
                    <th>Compliance</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= esc($r->customer_name) ?></td>
                    <td><?= esc($r->customer_mobile) ?></td>
                    <td><?= esc($r->agent_name) ?></td>
                    <td>
                        <?php if (!empty($r->final_score)): ?>
                            <a href="javascript:void(0)" class="viewScore" data-chat="<?= $r->id ?>" style="text-decoration:none;">
                                <?= $r->final_score ?>%
                            </a>
                        <?php else: ?>
                            Not Evaluated
                        <?php endif; ?>
                    </td>
                    <td><?= esc($r->disposition_name) ?></td>
                    <td>
                        <?php if (empty($r->final_score)): ?>
                            <a href="<?= site_url('admin/chat-score/evaluate/'.$r->id) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square me-1"></i> Evaluate
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary btn-sm viewHistory" data-session="<?= $r->id ?>">
                                <i class="bi bi-chat-dots-fill me-1"></i> Transcript
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-info btn-sm replyHistory" data-chat="<?= $r->id ?>">
                            <?= $r->avg_reply_time ?>
                        </button>
                    </td>
                    <td>
                        <?php if ($r->compliance_status == 'Pending' || empty($r->compliance_status)): ?>
                            <button class="btn btn-success btn-sm complianceBtn" data-id="<?= $r->id ?>" data-value="yes">Yes</button>
                            <button class="btn btn-danger btn-sm complianceBtn" data-id="<?= $r->id ?>" data-value="no">No</button>
                        <?php elseif ($r->compliance_status == 'yes'): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-danger">No</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($r->remarks) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="scoreModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="z-index:1061;">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-star-fill text-warning"></i>
                    Conversation QA Score
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="scoreBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Loading score details...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="z-index:1061;">
            <div class="modal-header">
                <h5>
                    <i class="bi bi-chat-left-dots-fill text-primary"></i>
                    Conversation History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="chatHistory" class="chat-box">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-dots" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                        Select a conversation to view
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Agent Reply Analytics
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="replyHistory">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <div class="mt-2">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {

    var scoreModal = new bootstrap.Modal(document.getElementById('scoreModal'), {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });

    var historyModal = new bootstrap.Modal(document.getElementById('historyModal'), {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });

    var replyModal = new bootstrap.Modal(document.getElementById('replyModal'), {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });

    $('#scoreModal, #historyModal, #replyModal').on('show.bs.modal', function() {
        $('.modal-backdrop').css('z-index', '1055');
        $(this).css('z-index', '1060');
        $('.modal-content', this).css('z-index', '1061');
    });

    $('#scoreModal, #historyModal, #replyModal').on('hidden.bs.modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('overflow', '');
    });

    var table = $('#scoreTable').DataTable({
        responsive: true,
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="bi bi-download me-1"></i> Download CSV',
                className: 'btn btn-export-csv',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            let raw = data ? data.toString() : '';
                            let text = raw.replace(/<[^>]*>/g, '').trim();
                            return text;
                        }
                    }
                }
            }
        ],
        language: {
            search: "",
            searchPlaceholder: "Search by customer, mobile, agent..."
        }
    });

    $('#fromDate, #toDate').on('change', function() {
        loadFilteredData();
    });

    function loadFilteredData() {
        let from = $('#fromDate').val();
        let to = $('#toDate').val();

        if (!from && !to) {
            location.reload();
            return;
        }

        $.ajax({
            url: "<?= base_url('admin/chat-score/filter') ?>",
            type: "GET",
            dataType: "json",
            data: { from: from, to: to },
            success: function(res) {
                if (!Array.isArray(res)) {
                    console.error('Response is not an array');
                    return;
                }

                table.clear();

                res.forEach(function(r) {
                    let complianceUI = '';
                    if (r.compliance_status == 'Pending' || r.compliance_status === null || r.compliance_status === '') {
                        complianceUI = `
                            <button class="btn btn-success btn-sm complianceBtn" data-id="${r.id}" data-value="yes">Yes</button>
                            <button class="btn btn-danger btn-sm complianceBtn" data-id="${r.id}" data-value="no">No</button>
                        `;
                    } else if (r.compliance_status == 'yes') {
                        complianceUI = `<span class="badge bg-success">Yes</span>`;
                    } else {
                        complianceUI = `<span class="badge bg-danger">No</span>`;
                    }

                    let actionUI = '';
                    if (!r.final_score || r.final_score === null || r.final_score === '') {
                        actionUI = `<a href="<?= site_url('admin/chat-score/evaluate/') ?>${r.id}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square me-1"></i> Evaluate</a>`;
                    } else {
                        actionUI = `<button class="btn btn-primary btn-sm viewHistory" data-session="${r.id}"><i class="bi bi-chat-dots-fill me-1"></i> Transcript</button>`;
                    }

                    let scoreUI = '';
                    if (r.final_score) {
                        scoreUI = `<a href="javascript:void(0)" class="viewScore" data-chat="${r.id}" style="text-decoration:none;font-weight:600;color:#2563eb;">${r.final_score}%</a>`;
                    } else {
                        scoreUI = 'Not Evaluated';
                    }

                    let replyUI = `<button class="btn btn-info btn-sm replyHistory" data-chat="${r.id}">${r.avg_reply_time || '-'}</button>`;

                    table.row.add([
                        r.customer_name || '',
                        r.customer_mobile || '',
                        r.agent_name || '',
                        scoreUI,
                        r.disposition_name || '',
                        actionUI,
                        replyUI,
                        complianceUI,
                        r.remarks || ''
                    ]);
                });

                table.draw();
            },
            error: function(xhr, status, error) {
                console.error('Filter error:', status, error);
                alert('Error loading filtered data. Please try again.');
            }
        });
    }

    $(document).on("click", ".complianceBtn", function() {
        let id = $(this).data("id");
        let value = $(this).data("value");
        let parentTd = $(this).closest("td");

        if (!confirm("Are you sure you want to update compliance status?")) return;

        $.ajax({
            url: "<?= base_url('admin/chat-score/update-compliance') ?>",
            type: "POST",
            data: { id: id, status: value },
            success: function(response) {
                if (value === 'yes') {
                    parentTd.html('<span class="badge bg-success">Yes</span>');
                } else {
                    parentTd.html('<span class="badge bg-danger">No</span>');
                }
                alert('Compliance status updated successfully!');
            },
            error: function() {
                alert('Error updating compliance status. Please try again.');
            }
        });
    });

    $(document).on("click", ".viewScore", function(e) {
        e.preventDefault();

        let chat = $(this).data("chat");
        let body = $('#scoreBody');

        body.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading score details...</div>
            </div>
        `);

        scoreModal.show();

        $.ajax({
            url: "<?= site_url('admin/chat-score/details') ?>/" + chat,
            type: "GET",
            dataType: "json",
            success: function(r) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <tr>
                                <th style="width:40%;background:#f8fafc;">Vocabulary</th>
                                <td>${r.vocabulary || '—'}</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc;">Grammar</th>
                                <td>${r.grammar || '—'}</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc;">Relevance</th>
                                <td>${r.relevance || '—'}</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc;">Fluency</th>
                                <td>${r.fluency || '—'}</td>
                            </tr>
                            <tr class="table-success">
                                <th style="background:#d1fae5;"><i class="bi bi-star-fill text-warning me-1"></i> Final Score</th>
                                <td><strong style="font-size:20px;">${r.final_score || '—'}%</strong></td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc;">Feedback</th>
                                <td>${r.feedback || '—'}</td>
                            </tr>
                        </table>
                    </div>
                `;
                body.html(html);
            },
            error: function() {
                body.html(`
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        No score found for this chat yet.
                    </div>
                `);
            }
        });
    });

    $(document).on('click', '.viewHistory', function(e) {
        e.preventDefault();

        let session = $(this).data('session');
        let history = $('#chatHistory');

        history.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading conversation...</div>
            </div>
        `);

        historyModal.show();

        $.ajax({
            url: "<?= base_url('livechat/history1') ?>/" + session,
            type: "GET",
            dataType: "json",
            success: function(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = `
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No conversation found.
                        </div>
                    `;
                } else {
                    data.forEach(function(row) {
                        if (row.sender && row.sender.toLowerCase() == "customer") {
                            html += `
                                <div class="chat-row customer">
                                    <div class="customer-msg">
                                        <strong><i class="bi bi-person-circle me-1"></i> Customer</strong>
                                        <div class="mt-2">${row.message || ''}</div>
                                        <span class="chat-time">${row.created_at || ''}</span>
                                    </div>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="chat-row agent">
                                    <div class="agent-msg">
                                        <strong><i class="bi bi-headset me-1"></i> ${row.agent_name || 'Agent'}</strong>
                                        <div class="mt-2">${row.message || ''}</div>
                                        <span class="chat-time">${row.created_at || ''}</span>
                                    </div>
                                </div>
                            `;
                        }
                    });
                }

                history.html(html);
                history.scrollTop(history[0].scrollHeight);
            },
            error: function() {
                history.html(`
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        Unable to load conversation. Please try again.
                    </div>
                `);
            }
        });
    });

    $(document).on("click", ".replyHistory", function() {

        let chat = $(this).data("chat");

        replyModal.show();

        $("#replyHistory").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <div class="mt-2">Loading...</div>
            </div>
        `);

        $.ajax({
            url: "<?= base_url('admin/chat-score/reply-history') ?>/" + chat,
            type: "GET",
            dataType: "json",
            success: function(res) {

                let html = '';

                html += `
                    <div class="alert alert-info">
                        <strong>Average Reply Time :</strong> ${res.average_text || '-'}
                    </div>
                `;

                if (res.messages && res.messages.length > 0) {
                    $.each(res.messages, function(i, row) {

                        if (row.sender == "customer") {
                            html += `
                                <div class="chat-row customer">
                                    <div class="customer-msg">
                                        <b>Customer</b>
                                        <div class="mt-2">${row.message || ''}</div>
                                        <span class="chat-time">${row.created_at || ''}</span>
                                    </div>
                                </div>
                            `;
                        } else {
                            if (row.reply_seconds) {
                                html += `
                                    <div class="text-center mb-2">
                                        <span class="badge bg-warning">
                                            Agent replied in ${row.reply_text || ''}
                                        </span>
                                    </div>
                                `;
                            }

                            html += `
                                <div class="chat-row agent">
                                    <div class="agent-msg">
                                        <b>Agent</b>
                                        <div class="mt-2">${row.message || ''}</div>
                                        <span class="chat-time">${row.created_at || ''}</span>
                                    </div>
                                </div>
                                <hr>
                            `;
                        }

                    });
                } else {
                    html += `
                        <div class="alert alert-warning mb-0">
                            No reply history found.
                        </div>
                    `;
                }

                $("#replyHistory").html(html);
            },
            error: function() {
                $("#replyHistory").html(`
                    <div class="alert alert-danger mb-0">
                        Unable to load reply history. Please try again.
                    </div>
                `);
            }
        });

    });

    $(document).on('click', '[data-bs-dismiss="modal"]', function() {
        setTimeout(function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('overflow', '');
        }, 300);
    });

});

function formatReplyTime(sec) {
    if (sec == null || sec == 0) return "-";

    let h = Math.floor(sec / 3600);
    let m = Math.floor((sec % 3600) / 60);
    let s = sec % 60;

    if (h > 0) return h + "h " + m + "m " + s + "s";
    if (m > 0) return m + "m " + s + "s";
    return s + " sec";
}
</script>

<?= $this->endSection() ?>