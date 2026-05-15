<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher self-service commission dashboard.
 *
 * Shows overall summary + commissions grouped by main marketer source
 * with per-group withdrawal request buttons.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\withdrawal_manager;
use local_teacher_commissions\output\teacher_dashboard;

require_login();
$syscontext = context_system::instance();

if (!local_teacher_commissions_has_teacher_access()) {
    throw new required_capability_exception(
        $syscontext,
        'local/teacher_commissions:viewowncommissions',
        'nopermissions',
        ''
    );
}

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/teacher/dashboard.php'));
$PAGE->set_title(get_string('teacher_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('teacher_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('standard');

$summary = commission_manager::get_teacher_summary($USER->id);
$summary->firstname = $USER->firstname;
$summary->lastname  = $USER->lastname;

// Grouped balances by main marketer.
$grouped = withdrawal_manager::get_grouped_balances($USER->id);

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
echo local_teacher_commissions_teacher_nav('dashboard');

// --- Overall summary (existing renderable) ---
$renderable = new teacher_dashboard($summary);
echo $renderer->render_teacher_dashboard($renderable);

// --- Grouped commission cards by marketer (only groups with available balance) ---
$groups_with_balance = array_filter($grouped, fn($g) => $g->has_balance);

if (!empty($groups_with_balance)) {
    $withdraw_url_base = new moodle_url('/local/teacher_commissions/teacher/withdrawals.php');

    echo '<div class="tc-marketer-cards-wrap" style="max-width:960px;margin:24px auto 0;">';
    echo '<h4 style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:14px;">'
         . get_string('commissions_by_marketer', 'local_teacher_commissions') . '</h4>';
    echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">';

    foreach ($groups_with_balance as $mid => $grp) {
        $wd_url = (clone $withdraw_url_base);
        $wd_url->param('mainmarketerid', $mid);

        // Sub-marketer breakdown (who contributed to this main marketer group).
        $sub_breakdown  = withdrawal_manager::get_sub_marketer_breakdown($USER->id, $mid);
        $show_breakdown = count($sub_breakdown) > 1
            || (count($sub_breakdown) === 1 && (int)$sub_breakdown[0]->referralmarketerid !== $mid);

        echo '
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
                    box-shadow:0 2px 8px rgba(0,0,0,.07);">

            <!-- Card header: main marketer name -->
            <div style="background:linear-gradient(135deg,#1d4ed8,#2563eb);padding:16px 20px;">
                <div style="font-size:.62rem;font-weight:700;color:rgba(255,255,255,.65);
                            text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">
                    ' . get_string('main_marketer', 'local_teacher_commissions') . '
                </div>
                <div style="font-size:1.1rem;font-weight:800;color:#fff;line-height:1.2;">
                    ' . s($grp->marketername) . '
                </div>
            </div>

            <div style="padding:18px 20px;">';

        // Sub-marketer source list (collapsible).
        if ($show_breakdown) {
            $card_id = 'tc_sub_' . (int)$mid;
            echo '
                <div style="margin-bottom:14px;background:#f0f9ff;border:1px solid #bae6fd;
                            border-radius:8px;overflow:hidden;">
                    <button onclick="(function(btn){
                                var el=document.getElementById(\'' . $card_id . '\');
                                var open=el.style.display!==\'none\';
                                el.style.display=open?\'none\':\'block\';
                                btn.querySelector(\'span\').textContent=open?\'+\':\'-\';
                            })(this)"
                            style="width:100%;background:none;border:none;padding:8px 12px;
                                   display:flex;justify-content:space-between;align-items:center;
                                   font-size:.76rem;color:#0369a1;font-weight:700;cursor:pointer;">
                        <span style="text-align:left;">' . get_string('show_sub_marketers', 'local_teacher_commissions') . ' (' . count($sub_breakdown) . ')</span>
                        <span style="font-size:1rem;font-weight:700;">+</span>
                    </button>
                    <div id="' . $card_id . '" style="display:none;">';

            foreach ($sub_breakdown as $sub) {
                echo '
                        <div style="display:flex;justify-content:space-between;align-items:center;
                                    padding:7px 12px;border-top:1px solid #e0f2fe;font-size:.78rem;">
                            <span style="color:#334155;font-weight:600;">' . s($sub->name) . '</span>
                            <span style="color:#059669;font-weight:700;">'
                                . number_format($sub->earned, 2) . ' ' . s($sub->currency) . '</span>
                        </div>';
            }

            echo '
                    </div>
                </div>';
        }

        // Earned / Paid / Balance stats.
        echo '
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;
                            gap:0;margin-bottom:16px;border:1px solid #e2e8f0;border-radius:9px;overflow:hidden;">
                    <div style="text-align:center;padding:10px 6px;background:#f8fafc;">
                        <div style="font-size:.58rem;font-weight:700;color:#64748b;text-transform:uppercase;
                                    letter-spacing:.04em;margin-bottom:3px;">'
                            . get_string('total_earned', 'local_teacher_commissions') . '</div>
                        <div style="font-size:.95rem;font-weight:800;color:#1e293b;">'
                            . number_format($grp->earned, 2) . '</div>
                    </div>
                    <div style="text-align:center;padding:10px 6px;background:#f8fafc;
                                border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
                        <div style="font-size:.58rem;font-weight:700;color:#64748b;text-transform:uppercase;
                                    letter-spacing:.04em;margin-bottom:3px;">'
                            . get_string('paid_amount', 'local_teacher_commissions') . '</div>
                        <div style="font-size:.95rem;font-weight:800;color:#6366f1;">'
                            . number_format($grp->paid, 2) . '</div>
                    </div>
                    <div style="text-align:center;padding:10px 6px;background:#f0fdf4;">
                        <div style="font-size:.58rem;font-weight:700;color:#166534;text-transform:uppercase;
                                    letter-spacing:.04em;margin-bottom:3px;">'
                            . get_string('remaining_balance', 'local_teacher_commissions') . '</div>
                        <div style="font-size:.95rem;font-weight:800;color:#059669;">'
                            . number_format($grp->balance, 2)
                            . ' <small style="font-size:.5em;color:#6b7280;">' . s($grp->currency) . '</small></div>
                    </div>
                </div>

                <!-- Withdrawal button (only shown when balance > 0) -->
                <a href="' . $wd_url->out(false) . '"
                   style="display:block;text-align:center;background:#059669;color:#fff;
                          padding:10px 16px;border-radius:9px;font-size:.86rem;font-weight:700;
                          text-decoration:none;letter-spacing:.02em;transition:opacity .15s;"
                   onmouseover="this.style.opacity=\'.85\'" onmouseout="this.style.opacity=\'1\'">
                    &#x1F4B8;&nbsp; ' . get_string('request_withdrawal', 'local_teacher_commissions') . '
                </a>
            </div>
        </div>';
    }

    echo '</div></div>';
}

// --- Own withdrawal request history ---
$my_requests = withdrawal_manager::get_teacher_requests($USER->id);
if (!empty($my_requests)) {
    $status_labels = [
        'pending'  => [get_string('withdrawal_status_pending',  'local_teacher_commissions'), '#92400e', '#fef3c7'],
        'approved' => [get_string('withdrawal_status_approved', 'local_teacher_commissions'), '#065f46', '#d1fae5'],
        'rejected' => [get_string('withdrawal_status_rejected', 'local_teacher_commissions'), '#991b1b', '#fee2e2'],
        'paid'     => [get_string('withdrawal_status_paid',     'local_teacher_commissions'), '#1d4ed8', '#dbeafe'],
    ];
    echo '
    <div style="max-width:960px;margin:24px auto 40px;">
        <h4 style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:14px;">'
            . get_string('my_withdrawal_requests', 'local_teacher_commissions') . '</h4>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 14px;font-size:.68rem;font-weight:700;color:#64748b;
                               text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0;text-align:right;">'
                        . get_string('date', 'local_teacher_commissions') . '</th>
                    <th style="padding:10px 14px;font-size:.68rem;font-weight:700;color:#64748b;
                               text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0;">'
                        . get_string('marketer_source', 'local_teacher_commissions') . '</th>
                    <th style="padding:10px 14px;font-size:.68rem;font-weight:700;color:#64748b;
                               text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0;">'
                        . get_string('payout_amount', 'local_teacher_commissions') . '</th>
                    <th style="padding:10px 14px;font-size:.68rem;font-weight:700;color:#64748b;
                               text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0;">'
                        . get_string('status', 'local_teacher_commissions') . '</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($my_requests as $req) {
        [$slbl, $stxt, $sbg] = $status_labels[$req->status] ?? [$req->status, '#475569', '#f1f5f9'];
        $mname = '';
        if (!empty($req->mainmarketerid) && isset($grouped[(int)$req->mainmarketerid])) {
            $mname = $grouped[(int)$req->mainmarketerid]->marketername;
        }
        echo '
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 14px;font-size:.82rem;color:#64748b;text-align:right;">'
                        . userdate($req->timecreated, '%d/%m/%Y') . '</td>
                    <td style="padding:10px 14px;font-size:.82rem;color:#334155;font-weight:600;">'
                        . s($mname ?: '—') . '</td>
                    <td style="padding:10px 14px;font-size:.9rem;font-weight:800;color:#059669;">'
                        . number_format($req->amount, 2) . ' ' . s($req->currency) . '</td>
                    <td style="padding:10px 14px;">
                        <span style="display:inline-block;padding:2px 9px;border-radius:20px;
                                     font-size:.7rem;font-weight:700;
                                     background:' . $sbg . ';color:' . $stxt . ';">'
                            . $slbl . '</span>
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
        </div>
    </div>';
}

echo $OUTPUT->footer();
