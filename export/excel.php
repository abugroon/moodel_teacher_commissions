<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Excel (CSV) export for commission transactions.
 *
 * Accessible by:
 *  - Admins with viewreports capability (can pass teacherid param).
 *  - Teachers with viewowncommissions (forced to their own id).
 *
 * URL params (all optional):
 *   teacherid  Filter by teacher.
 *   courseid   Filter by course.
 *   datefrom   Unix timestamp.
 *   dateto     Unix timestamp.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\report_manager;

require_login();
$syscontext = context_system::instance();

$isadmin   = has_capability('local/teacher_commissions:viewreports',        $syscontext);
$isteacher = has_capability('local/teacher_commissions:viewowncommissions',  $syscontext);

if (!$isadmin && !$isteacher) {
    throw new \moodle_exception('error_nopermission', 'local_teacher_commissions');
}

// Build filters.
$filters = [];
if ($isadmin) {
    $tid = optional_param('teacherid', 0, PARAM_INT);
    if ($tid) $filters['teacherid'] = $tid;
} else {
    // Teachers only see their own data.
    $filters['teacherid'] = $USER->id;
}

$cid = optional_param('courseid', 0, PARAM_INT);
if ($cid) $filters['courseid'] = $cid;

$from = optional_param('datefrom', 0, PARAM_INT);
$to   = optional_param('dateto',   0, PARAM_INT);
if ($from) $filters['datefrom'] = $from;
if ($to)   $filters['dateto']   = $to;

// Fetch all rows (no pagination for export).
$result = report_manager::get_filtered_transactions($filters, 0, 99999);
$rows   = $result['records'];

// Stream as CSV.
$filename = 'teacher_commissions_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens the file correctly.
fwrite($out, "\xEF\xBB\xBF");

// Header row.
fputcsv($out, [
    get_string('date',               'local_teacher_commissions'),
    get_string('teacher',            'local_teacher_commissions'),
    get_string('course',             'local_teacher_commissions'),
    get_string('student',            'local_teacher_commissions'),
    get_string('sale_amount',        'local_teacher_commissions'),
    get_string('commission_percent', 'local_teacher_commissions'),
    get_string('commission_earned',  'local_teacher_commissions'),
    get_string('currency',           'local_teacher_commissions'),
    get_string('status',             'local_teacher_commissions'),
]);

foreach ($rows as $r) {
    fputcsv($out, [
        userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        $r->teacher_firstname . ' ' . $r->teacher_lastname,
        $r->coursename,
        $r->student_firstname . ' ' . $r->student_lastname,
        number_format($r->saleamount, 2),
        number_format($r->commission_percent, 2),
        number_format($r->commissionamount, 2),
        $r->currency,
        $r->status,
    ]);
}

fclose($out);
exit;
