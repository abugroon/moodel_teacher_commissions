<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * PDF export for commission transactions.
 *
 * Uses Moodle's bundled TCPDF library (available in Moodle 3.9+).
 * Falls back to an HTML printable page if TCPDF is unavailable.
 *
 * URL params: same as export/excel.php
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\payout_manager;
use local_teacher_commissions\report_manager;

require_login();
$syscontext = context_system::instance();

$isadmin   = has_capability('local/teacher_commissions:viewreports',       $syscontext);
$isteacher = has_capability('local/teacher_commissions:viewowncommissions', $syscontext);

if (!$isadmin && !$isteacher) {
    throw new \moodle_exception('error_nopermission', 'local_teacher_commissions');
}

// Build filters.
$filters = [];
if ($isadmin) {
    $tid = optional_param('teacherid', 0, PARAM_INT);
    if ($tid) $filters['teacherid'] = $tid;
} else {
    $filters['teacherid'] = $USER->id;
}

$cid  = optional_param('courseid', 0, PARAM_INT);
$from = optional_param('datefrom', 0, PARAM_INT);
$to   = optional_param('dateto',   0, PARAM_INT);
if ($cid)  $filters['courseid']  = $cid;
if ($from) $filters['datefrom']  = $from;
if ($to)   $filters['dateto']    = $to;

// Fetch data.
$result = report_manager::get_filtered_transactions($filters, 0, 99999);
$rows   = $result['records'];
$totals = $result['totals'];

// ---- Try to use TCPDF (bundled with Moodle ≤ 4.x; removed in Moodle 5.0) ----
// In Moodle 5.0+, TCPDF is no longer bundled. The file_exists() check below
// will return false and execution falls through to the HTML printable fallback.
$tcpdfpath = $CFG->libdir . '/tcpdf/tcpdf.php';

if (file_exists($tcpdfpath)) {
    require_once($tcpdfpath);

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(get_string('pluginname', 'local_teacher_commissions'));
    $pdf->SetTitle(get_string('reports_title', 'local_teacher_commissions'));
    $pdf->SetHeaderData('', 0, get_string('pluginname', 'local_teacher_commissions'),
        get_string('reports_title', 'local_teacher_commissions') . ' — ' . date('Y-m-d'));
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Title.
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, get_string('reports_title', 'local_teacher_commissions'), 0, 1, 'C');
    $pdf->Ln(3);

    // Totals.
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6,
        get_string('total_records', 'local_teacher_commissions') . ': ' . $result['total'] . '    ' .
        get_string('grand_total_sales', 'local_teacher_commissions') . ': ' . number_format((float) $totals->total_sales, 2) . '    ' .
        get_string('grand_total_commissions', 'local_teacher_commissions') . ': ' . number_format((float) $totals->total_commissions, 2),
        0, 1);
    $pdf->Ln(3);

    // Table header.
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(66, 134, 244);
    $pdf->SetTextColor(255);
    $pdf->SetLineWidth(0.3);
    $cols = [35, 38, 55, 38, 22, 16, 22, 14, 20];
    $heads = [
        get_string('date',               'local_teacher_commissions'),
        get_string('teacher',            'local_teacher_commissions'),
        get_string('course',             'local_teacher_commissions'),
        get_string('student',            'local_teacher_commissions'),
        get_string('sale_amount',        'local_teacher_commissions'),
        '%',
        get_string('commission_earned',  'local_teacher_commissions'),
        get_string('currency',           'local_teacher_commissions'),
        get_string('status',             'local_teacher_commissions'),
    ];
    foreach ($heads as $i => $h) {
        $pdf->Cell($cols[$i], 6, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Rows.
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0);
    $fill = false;
    foreach ($rows as $r) {
        $pdf->SetFillColor($fill ? 230 : 255, $fill ? 240 : 255, $fill ? 255 : 255);
        $pdf->Cell($cols[0], 5, userdate($r->timecreated, get_string('strftimedate', 'langconfig')), 1, 0, 'L', true);
        $pdf->Cell($cols[1], 5, mb_strimwidth($r->teacher_firstname . ' ' . $r->teacher_lastname, 0, 20, '…'), 1, 0, 'L', true);
        $pdf->Cell($cols[2], 5, mb_strimwidth($r->coursename, 0, 35, '…'), 1, 0, 'L', true);
        $pdf->Cell($cols[3], 5, mb_strimwidth($r->student_firstname . ' ' . $r->student_lastname, 0, 20, '…'), 1, 0, 'L', true);
        $pdf->Cell($cols[4], 5, number_format($r->saleamount, 2), 1, 0, 'R', true);
        $pdf->Cell($cols[5], 5, number_format($r->commission_percent, 1) . '%', 1, 0, 'C', true);
        $pdf->Cell($cols[6], 5, number_format($r->commissionamount, 2), 1, 0, 'R', true);
        $pdf->Cell($cols[7], 5, $r->currency, 1, 0, 'C', true);
        $pdf->Cell($cols[8], 5, $r->status, 1, 1, 'C', true);
        $fill = !$fill;
    }

    $pdf->Output('teacher_commissions_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}

// ---- Fallback: HTML printable page ----
$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/export/pdf.php'));
$PAGE->set_title(get_string('reports_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('print');

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('reports_title', 'local_teacher_commissions'));
echo html_writer::tag('p',
    get_string('total_records', 'local_teacher_commissions') . ': ' . $result['total'] . ' | ' .
    get_string('grand_total_sales', 'local_teacher_commissions') . ': ' . number_format((float) $totals->total_sales, 2) . ' | ' .
    get_string('grand_total_commissions', 'local_teacher_commissions') . ': ' . number_format((float) $totals->total_commissions, 2)
);

$table             = new html_table();
$table->head       = [
    get_string('date',               'local_teacher_commissions'),
    get_string('teacher',            'local_teacher_commissions'),
    get_string('course',             'local_teacher_commissions'),
    get_string('student',            'local_teacher_commissions'),
    get_string('sale_amount',        'local_teacher_commissions'),
    '%',
    get_string('commission_earned',  'local_teacher_commissions'),
    get_string('currency',           'local_teacher_commissions'),
    get_string('status',             'local_teacher_commissions'),
];
$table->attributes['class'] = 'generaltable';

foreach ($rows as $r) {
    $table->data[] = [
        userdate($r->timecreated, get_string('strftimedate', 'langconfig')),
        $r->teacher_firstname . ' ' . $r->teacher_lastname,
        $r->coursename,
        $r->student_firstname . ' ' . $r->student_lastname,
        number_format($r->saleamount, 2),
        number_format($r->commission_percent, 2) . '%',
        number_format($r->commissionamount, 2),
        $r->currency,
        $r->status,
    ];
}
echo html_writer::table($table);
echo html_writer::tag('script', 'window.print();');
echo $OUTPUT->footer();
