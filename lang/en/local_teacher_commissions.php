<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English language strings for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Teacher Commissions';

// Navigation.
$string['nav_admin_dashboard']   = 'Commission Dashboard';
$string['nav_reports']           = 'Commission Reports';
$string['nav_teacher_dashboard'] = 'My Commissions';
$string['nav_teacher_ledger']    = 'My Statement';

// Admin dashboard.
$string['admin_dashboard_title']   = '💰 Commission Dashboard';
$string['teacher']                 = 'Teacher';
$string['courses_owned']           = 'Courses Owned';
$string['paid_enrollments']        = 'Paid Enrolments';
$string['total_sales']             = 'Total Sales';
$string['commission_percent']      = 'Commission %';
$string['total_commission_earned'] = 'Commission Earned';
$string['total_commission_paid']   = 'Commission Paid';
$string['balance_unpaid']          = 'Unpaid Balance';
$string['actions']                 = 'Actions';
$string['view_ledger']             = 'View Ledger';
$string['pay_commission']          = 'Pay Commission';
$string['edit_commission']         = 'Edit Rate';
$string['no_teachers']             = 'No teachers found.';

// Commission settings.
$string['commission_settings_title']   = 'Commission Settings';
$string['global_default']              = 'Global Default';
$string['global_commission_percent']   = 'Default commission percentage';
$string['global_commission_percent_desc'] = 'Applied to all teachers who do not have an individual rate set.';
$string['teacher_commission_override'] = 'Per-Teacher Commission Rate';
$string['override_commission_percent'] = 'Commission percentage for this teacher';
$string['save_settings']              = 'Save Settings';
$string['settings_saved']             = 'Commission settings saved successfully.';
$string['commission_rate_label']       = 'Commission Rate (%)';
$string['back_to_dashboard']           = 'Back to Dashboard';

// Payout form.
$string['payout_title']       = 'Process Payout for {$a}';
$string['payout_amount']      = 'Payout Amount';
$string['payout_notes']       = 'Notes (optional)';
$string['payout_submit']      = 'Confirm Payout';
$string['payout_success']     = 'Payout of {$a->amount} {$a->currency} recorded successfully.';
$string['payout_error_zero']  = 'Payout amount must be greater than zero.';
$string['payout_error_exceed']= 'Payout amount cannot exceed outstanding balance of {$a}.';
$string['available_balance']  = 'Available balance';
$string['currency']           = 'Currency';

// Reports.
$string['reports_title']      = 'Commission Reports';
$string['filter_teacher']     = 'Filter by Teacher';
$string['filter_course']      = 'Filter by Course';
$string['filter_date_from']   = 'Date From';
$string['filter_date_to']     = 'Date To';
$string['filter_period']      = 'Period';
$string['period_all']         = 'All time';
$string['period_this_month']  = 'This month';
$string['period_last_month']  = 'Last month';
$string['period_this_year']   = 'This year';
$string['period_last_year']   = 'Last year';
$string['period_custom']      = 'Custom range';
$string['apply_filters']      = 'Apply Filters';
$string['reset_filters']      = 'Reset';
$string['export_pdf']         = 'Export PDF';
$string['export_excel']       = 'Export Excel';
$string['no_records']         = 'No records match your filters.';
$string['summary']            = 'Summary';
$string['total_records']      = 'Total Records';
$string['grand_total_sales']  = 'Grand Total Sales';
$string['grand_total_commissions'] = 'Grand Total Commissions';
$string['monthly_summary']    = 'Monthly Summary';
$string['yearly_summary']     = 'Yearly Summary';

// Transaction ledger.
$string['ledger_title']       = 'Commission Statement: {$a}';
$string['date']               = 'Date';
$string['student']            = 'Student';
$string['course']             = 'Course';
$string['sale_amount']        = 'Sale Amount';
$string['commission_earned']  = 'Commission Earned';
$string['status']             = 'Status';
$string['status_pending']     = 'Pending';
$string['status_paid']        = 'Paid';
$string['payout_history']     = 'Payout History';
$string['payout_date']        = 'Payout Date';
$string['payout_amount_col']  = 'Amount Paid';
$string['processed_by']       = 'Processed By';
$string['notes']              = 'Notes';
$string['current_balance']    = 'Current Outstanding Balance';
$string['no_transactions']    = 'No commission transactions recorded.';
$string['no_payouts']         = 'No payouts recorded.';

// Teacher self-service portal.
$string['teacher_dashboard_title'] = 'My Commission Summary';
$string['my_courses']              = 'My Courses';
$string['my_paid_enrollments']     = 'Paid Enrolments';
$string['my_total_sales']          = 'Total Sales';
$string['my_earned_commissions']   = 'Total Earned';
$string['my_paid_commissions']     = 'Amount Paid';
$string['my_outstanding_balance']  = 'Outstanding Balance';
$string['view_full_statement']     = 'View Full Statement';

// Capabilities (shown in role assignment UI).
$string['local/teacher_commissions:viewadmindashboard'] = 'View admin commission dashboard';
$string['local/teacher_commissions:managecommissions']  = 'Manage commission settings';
$string['local/teacher_commissions:processpayout']      = 'Process commission payouts';
$string['local/teacher_commissions:viewreports']        = 'View commission reports';
$string['local/teacher_commissions:viewowncommissions'] = 'View own commission statement';

// Settings page.
$string['settings_heading']           = 'Teacher Commissions Settings';
$string['default_commission_percent'] = 'Default commission percentage';
$string['default_commission_percent_desc'] = 'System-wide default commission percentage applied when no individual rate is set for a teacher.';
$string['default_currency']           = 'Default currency';
$string['default_currency_desc']      = 'ISO 4217 currency code (e.g. SDG, USD, EUR, GBP).';

// Reports — marketer filter.
$string['filter_marketer']       = 'Filter by Marketer';
$string['all_marketers']         = 'All Marketers';
$string['commission_by_marketer']= 'Commission Summary by Marketer';

// Errors / misc.
$string['error_nopermission']  = 'You do not have permission to access this page.';
$string['error_invalidteacher']= 'Invalid or non-existent teacher.';
$string['error_invalidpayout'] = 'Invalid payout request.';
$string['confirm_payout']      = 'Are you sure you want to process this payout?';
$string['all_teachers']        = 'All Teachers';
$string['all_courses']         = 'All Courses';

// Privacy API metadata strings.
$string['privacy:metadata:local_tc_settings']                        = 'Stores commission percentage settings per teacher.';
$string['privacy:metadata:local_tc_settings:userid']                 = 'The ID of the teacher user (0 = global default).';
$string['privacy:metadata:local_tc_settings:commission_percent']     = 'The commission percentage assigned.';
$string['privacy:metadata:local_tc_settings:timecreated']            = 'The time this setting was created.';
$string['privacy:metadata:local_tc_settings:timemodified']           = 'The time this setting was last modified.';

$string['privacy:metadata:local_tc_transactions']                    = 'Stores individual commission transactions linked to paid enrollments.';
$string['privacy:metadata:local_tc_transactions:teacherid']          = 'The ID of the teacher who earned the commission.';
$string['privacy:metadata:local_tc_transactions:courseid']           = 'The ID of the course involved.';
$string['privacy:metadata:local_tc_transactions:studentid']          = 'The ID of the student who made the purchase.';
$string['privacy:metadata:local_tc_transactions:saleamount']         = 'The total sale amount paid by the student.';
$string['privacy:metadata:local_tc_transactions:commissionamount']   = 'The commission amount earned by the teacher.';
$string['privacy:metadata:local_tc_transactions:status']             = 'The payment status of the commission (pending/paid).';
$string['privacy:metadata:local_tc_transactions:timecreated']        = 'The time this transaction was recorded.';

$string['privacy:metadata:local_tc_payouts']                         = 'Records payout transactions processed by admins.';
$string['privacy:metadata:local_tc_payouts:teacherid']               = 'The ID of the teacher who received the payout.';
$string['privacy:metadata:local_tc_payouts:amount']                  = 'The amount paid out.';
$string['privacy:metadata:local_tc_payouts:adminid']                 = 'The ID of the admin who processed the payout.';
$string['privacy:metadata:local_tc_payouts:notes']                   = 'Optional notes added by the admin.';
$string['privacy:metadata:local_tc_payouts:timecreated']             = 'The time this payout was processed.';

// Payout receipt.
$string['receipt']                = 'Receipt';
$string['download_receipt']       = 'Download Receipt';
$string['receipt_file']           = 'Payment Receipt (PDF/JPG/PNG, max 5 MB)';
$string['no_receipt']             = '—';

// Help strings.
$string['commission_rate_label_help'] = 'Enter the commission percentage (0–100) to apply to all sales for this teacher. For example: 15 means 15% of each course sale goes to the teacher.';

// Marketer-grouped commission cards (teacher dashboard).
$string['commissions_by_marketer']     = 'Commissions by Marketer';
$string['marketer_source']             = 'Marketer';
$string['main_marketer']               = 'Main Marketer';
$string['show_sub_marketers']          = 'View contributing marketers';
$string['total_earned']                = 'Earned';
$string['paid_amount']                 = 'Paid';
$string['remaining_balance']           = 'Balance';
$string['request_withdrawal']          = 'Request Withdrawal';
$string['no_balance_to_withdraw']      = 'No balance available to withdraw.';
$string['no_referral_marketer']        = 'Direct / No referral';

// Withdrawal request page.
$string['submit_withdrawal_request']   = 'Submit Withdrawal Request';
$string['withdrawal_request_sent']     = 'Withdrawal request submitted successfully. It has been routed to the marketer for review.';
$string['max_amount']                  = 'Maximum';
$string['notes_optional']              = 'Optional notes…';
$string['my_withdrawal_requests']      = 'My Withdrawal Request History';

// Status labels.
$string['status']                      = 'Status';
$string['date']                        = 'Date';
$string['withdrawal_status_pending']   = 'Pending';
$string['withdrawal_status_approved']  = 'Approved';
$string['withdrawal_status_rejected']  = 'Rejected';
$string['withdrawal_status_paid']      = 'Paid';

// Error strings.
$string['error_withdrawal_zeroamount']     = 'Withdrawal amount must be greater than zero.';
$string['error_withdrawal_exceeds_balance']= 'Requested amount exceeds your available balance for this marketer group.';
$string['error_nopermission']              = 'You do not have permission to perform this action.';

// Capability strings.
$string['local/teacher_commissions:requestwithdrawal'] = 'Submit a commission withdrawal request';
$string['local/teacher_commissions:approvewithdrawal'] = 'Approve teacher commission withdrawal requests';

// Admin withdrawal management.
$string['nav_admin_withdrawals']           = 'Withdrawal Requests';
$string['no_withdrawal_requests']          = 'No withdrawal requests found.';
$string['withdrawal_status_updated']       = 'Withdrawal request status updated successfully.';
$string['withdrawal_approve']              = 'Approve';
$string['withdrawal_reject']              = 'Reject';
$string['withdrawal_mark_paid']            = 'Mark as Paid';
$string['confirm_approve_withdrawal']      = 'Are you sure you want to approve this withdrawal request?';
$string['confirm_reject_withdrawal']       = 'Are you sure you want to reject this withdrawal request?';
$string['confirm_pay_withdrawal']          = 'Are you sure you want to mark this withdrawal as paid?';
$string['rejection_reason']                = 'Reason (optional)…';
$string['payment_reference']               = 'Reference / notes…';
$string['filter_status']                   = 'Filter by Status';
$string['all_statuses']                    = 'All Statuses';

// Receipt attachment on approval.
$string['receipt_attach_optional']     = 'Attach notice / receipt (PDF/JPG/PNG, optional, max 5 MB)';

// Notification message provider.
$string['messageprovider:withdrawal_request'] = 'New teacher withdrawal request';

// Email / notification strings.
$string['email_withdrawal_subject']   = 'New Withdrawal Request – {$a}';
$string['email_withdrawal_small']     = 'New withdrawal request of {$a->amount} from {$a->teacher_name}';
$string['email_withdrawal_plaintext'] = 'Dear {$a->marketer_name},

A teacher has submitted a new withdrawal request that requires your review.

──────────────────────────────
Teacher : {$a->teacher_name}
Amount  : {$a->amount}
Date    : {$a->date}
Notes   : {$a->notes}
──────────────────────────────

Please log in to review and process this request:
{$a->url}

This is an automated notification from the Teacher Commissions system.';
$string['email_withdrawal_html']      = '<p>Dear <strong>{$a->marketer_name}</strong>,</p>
<p>A teacher has submitted a new withdrawal request that requires your review.</p>
<table style="border-collapse:collapse;width:100%;max-width:480px;font-family:sans-serif;font-size:14px;">
  <tr style="background:#f8fafc;">
    <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#374151;">Teacher</td>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;color:#1e293b;">{$a->teacher_name}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#374151;">Amount</td>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;font-size:16px;font-weight:800;color:#059669;">{$a->amount}</td>
  </tr>
  <tr style="background:#f8fafc;">
    <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#374151;">Date</td>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;color:#1e293b;">{$a->date}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#374151;">Notes</td>
    <td style="padding:10px 14px;border:1px solid #e2e8f0;color:#64748b;">{$a->notes}</td>
  </tr>
</table>
<p style="margin-top:18px;">
  <a href="{$a->url}"
     style="display:inline-block;padding:10px 22px;background:#2563eb;color:#fff;
            border-radius:8px;text-decoration:none;font-weight:700;font-size:14px;">
    Review Request
  </a>
</p>
<p style="font-size:12px;color:#94a3b8;">This is an automated notification from the Teacher Commissions system.</p>';

// Privacy — withdrawal requests table.
$string['privacy:metadata:local_tc_withdrawal_requests']              = 'Stores teacher withdrawal requests routed to main marketers.';
$string['privacy:metadata:local_tc_withdrawal_requests:teacherid']    = 'Teacher user ID.';
$string['privacy:metadata:local_tc_withdrawal_requests:mainmarketerid'] = 'Main marketer userid the request is routed to.';
$string['privacy:metadata:local_tc_withdrawal_requests:amount']       = 'Amount requested.';
$string['privacy:metadata:local_tc_withdrawal_requests:status']       = 'Request status.';
$string['privacy:metadata:local_tc_withdrawal_requests:timecreated']  = 'Time the request was submitted.';
