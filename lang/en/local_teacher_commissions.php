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
$string['admin_dashboard_title']   = 'Teacher Commission Dashboard';
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
$string['default_currency_desc']      = 'ISO 4217 currency code (e.g. USD, EUR, GBP).';

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
