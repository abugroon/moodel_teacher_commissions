<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin: view and manage teacher withdrawal requests.
 *
 * @package     local_teacher_commissions
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\withdrawal_manager;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:approvewithdrawal', $syscontext);

// Handle status-update POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $requestid = required_param('requestid', PARAM_INT);
    $newstatus = required_param('newstatus', PARAM_ALPHA);
    $notes     = optional_param('notes', '', PARAM_TEXT);

    try {
        withdrawal_manager::update_status($requestid, $newstatus, $notes);
        redirect(
            new moodle_url('/local/teacher_commissions/admin/withdrawals.php'),
            get_string('withdrawal_status_updated', 'local_teacher_commissions')
        );
    } catch (\moodle_exception $e) {
        $update_error = $e->getMessage();
    }
}

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/withdrawals.php'));
$PAGE->set_title(get_string('nav_admin_withdrawals', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('nav_admin_withdrawals', 'local_teacher_commissions'));
$PAGE->set_pagelayout('admin');

$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);
$PAGE->navbar->add(get_string('nav_admin_withdrawals', 'local_teacher_commissions'));

// Filter param.
$filter_status = optional_param('status', '', PARAM_ALPHA);

// Fetch all requests (optionally filtered by status).
$sql_where  = '';
$sql_params = [];
if ($filter_status !== '') {
    $sql_where          = 'WHERE wr.status = :status';
    $sql_params['status'] = $filter_status;
}

$sql = "SELECT wr.*,
               t.firstname   AS teacher_firstname,
               t.lastname    AS teacher_lastname,
               m.firstname   AS marketer_firstname,
               m.lastname    AS marketer_lastname
          FROM {local_tc_withdrawal_requests} wr
     LEFT JOIN {user} t ON t.id = wr.teacherid
     LEFT JOIN {user} m ON m.id = wr.mainmarketerid
         $sql_where
      ORDER BY wr.timecreated DESC";

$requests = $DB->get_records_sql($sql, $sql_params);

// Status display config.
$status_cfg = [
    'pending'  => ['label' => get_string('withdrawal_status_pending',  'local_teacher_commissions'), 'badge' => 'warning',   'text' => '#92400e'],
    'approved' => ['label' => get_string('withdrawal_status_approved', 'local_teacher_commissions'), 'badge' => 'success',   'text' => '#065f46'],
    'rejected' => ['label' => get_string('withdrawal_status_rejected', 'local_teacher_commissions'), 'badge' => 'danger',    'text' => '#991b1b'],
    'paid'     => ['label' => get_string('withdrawal_status_paid',     'local_teacher_commissions'), 'badge' => 'primary',   'text' => '#1d4ed8'],
];

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('withdrawals');
?>
<style>
.tc-wr-wrap { max-width:1100px; margin:0 auto 48px; }
.tc-filter-bar {
    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
    padding:14px 18px; margin-bottom:20px;
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.tc-filter-bar label { font-size:.82rem; font-weight:700; color:#374151; }
.tc-filter-bar select {
    padding:6px 12px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.85rem; color:#334155; background:#f8fafc;
}
.tc-filter-bar button {
    padding:7px 16px; background:#2563eb; color:#fff; border:none;
    border-radius:7px; font-size:.83rem; font-weight:700; cursor:pointer;
}
.tc-wr-table { width:100%; border-collapse:collapse; background:#fff;
               border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.tc-wr-table th {
    padding:10px 14px; font-size:.68rem; font-weight:700; color:#64748b;
    text-transform:uppercase; letter-spacing:.04em;
    background:#f8fafc; border-bottom:2px solid #e2e8f0; text-align:right;
}
.tc-wr-table td { padding:10px 14px; font-size:.84rem; color:#334155;
                  border-bottom:1px solid #f1f5f9; vertical-align:middle; text-align:right; }
.tc-wr-table tr:last-child td { border-bottom:none; }
.tc-badge {
    display:inline-block; padding:2px 10px; border-radius:20px;
    font-size:.7rem; font-weight:700;
}
.tc-badge-warning  { background:#fef3c7; color:#92400e; }
.tc-badge-success  { background:#d1fae5; color:#065f46; }
.tc-badge-danger   { background:#fee2e2; color:#991b1b; }
.tc-badge-primary  { background:#dbeafe; color:#1d4ed8; }
.tc-action-btn {
    padding:4px 10px; border:none; border-radius:6px; font-size:.75rem;
    font-weight:700; cursor:pointer; transition:opacity .15s;
}
.tc-action-btn:hover { opacity:.82; }
.tc-btn-approve { background:#059669; color:#fff; }
.tc-btn-reject  { background:#dc2626; color:#fff; }
.tc-btn-pay     { background:#2563eb; color:#fff; }
.tc-notes-form { display:inline; }
.tc-notes-input {
    padding:3px 8px; border:1.5px solid #e2e8f0; border-radius:5px;
    font-size:.78rem; width:120px; margin-left:4px;
}
</style>

<div class="tc-wr-wrap">

<?php if (!empty($update_error)): ?>
<div class="alert alert-danger"><?php echo s($update_error); ?></div>
<?php endif; ?>

<!-- Filter bar -->
<form method="get" action="" class="tc-filter-bar">
    <label for="wr_status"><?php echo get_string('filter_status', 'local_teacher_commissions'); ?></label>
    <select id="wr_status" name="status">
        <option value=""><?php echo get_string('all_statuses', 'local_teacher_commissions'); ?></option>
        <?php foreach ($status_cfg as $val => $cfg): ?>
        <option value="<?php echo $val; ?>" <?php selected($filter_status, $val); ?>>
            <?php echo $cfg['label']; ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit"><?php echo get_string('apply_filters', 'local_teacher_commissions'); ?></button>
    <?php if ($filter_status !== ''): ?>
    <a href="?" style="font-size:.82rem;color:#64748b;"><?php echo get_string('reset_filters', 'local_teacher_commissions'); ?></a>
    <?php endif; ?>
</form>

<?php if (empty($requests)): ?>
<p class="alert alert-info"><?php echo get_string('no_withdrawal_requests', 'local_teacher_commissions'); ?></p>
<?php else: ?>
<table class="tc-wr-table">
    <thead>
        <tr>
            <th>#</th>
            <th><?php echo get_string('teacher', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('marketer_source', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('payout_amount', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('date', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('notes', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('status', 'local_teacher_commissions'); ?></th>
            <th><?php echo get_string('actions', 'local_teacher_commissions'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $req): ?>
        <?php
        $scfg        = $status_cfg[$req->status] ?? ['label' => $req->status, 'badge' => 'secondary', 'text' => '#64748b'];
        $teachername = trim($req->teacher_firstname . ' ' . $req->teacher_lastname) ?: '—';
        $mname       = (int)$req->mainmarketerid > 0
            ? (trim($req->marketer_firstname . ' ' . $req->marketer_lastname) ?: '#' . $req->mainmarketerid)
            : get_string('no_referral_marketer', 'local_teacher_commissions');
        ?>
        <tr>
            <td style="font-size:.75rem;color:#94a3b8;">#<?php echo $req->id; ?></td>
            <td style="font-weight:600;"><?php echo s($teachername); ?></td>
            <td><?php echo s($mname); ?></td>
            <td style="font-weight:800;color:#059669;">
                <?php echo number_format($req->amount, 2); ?> <?php echo s($req->currency); ?>
            </td>
            <td><?php echo userdate($req->timecreated, '%d/%m/%Y %H:%M'); ?></td>
            <td style="font-size:.78rem;color:#64748b;max-width:160px;">
                <?php echo s($req->notes ?: '—'); ?>
            </td>
            <td>
                <span class="tc-badge tc-badge-<?php echo $scfg['badge']; ?>">
                    <?php echo $scfg['label']; ?>
                </span>
            </td>
            <td>
                <?php if ($req->status === 'pending'): ?>
                <!-- Approve -->
                <form method="post" class="tc-notes-form" style="display:inline-block;margin-bottom:4px;">
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',   'value' => sesskey()]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'requestid', 'value' => $req->id]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'newstatus', 'value' => 'approved']); ?>
                    <button type="submit" class="tc-action-btn tc-btn-approve"
                            onclick="return confirm('<?php echo get_string('confirm_approve_withdrawal', 'local_teacher_commissions'); ?>')">
                        <?php echo get_string('withdrawal_approve', 'local_teacher_commissions'); ?>
                    </button>
                </form>
                <!-- Reject -->
                <form method="post" class="tc-notes-form" style="display:inline-block;">
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',   'value' => sesskey()]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'requestid', 'value' => $req->id]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'newstatus', 'value' => 'rejected']); ?>
                    <input type="text" name="notes" class="tc-notes-input"
                           placeholder="<?php echo get_string('rejection_reason', 'local_teacher_commissions'); ?>">
                    <button type="submit" class="tc-action-btn tc-btn-reject"
                            onclick="return confirm('<?php echo get_string('confirm_reject_withdrawal', 'local_teacher_commissions'); ?>')">
                        <?php echo get_string('withdrawal_reject', 'local_teacher_commissions'); ?>
                    </button>
                </form>

                <?php elseif ($req->status === 'approved'): ?>
                <!-- Mark as paid -->
                <form method="post" class="tc-notes-form">
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',   'value' => sesskey()]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'requestid', 'value' => $req->id]); ?>
                    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'newstatus', 'value' => 'paid']); ?>
                    <input type="text" name="notes" class="tc-notes-input"
                           placeholder="<?php echo get_string('payment_reference', 'local_teacher_commissions'); ?>">
                    <button type="submit" class="tc-action-btn tc-btn-pay"
                            onclick="return confirm('<?php echo get_string('confirm_pay_withdrawal', 'local_teacher_commissions'); ?>')">
                        <?php echo get_string('withdrawal_mark_paid', 'local_teacher_commissions'); ?>
                    </button>
                </form>

                <?php else: ?>
                <span style="font-size:.75rem;color:#94a3b8;">—</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</div>
<?php
echo $OUTPUT->footer();
