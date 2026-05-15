<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin: commission reports with filters and summary tables.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\report_manager;
use local_teacher_commissions\form\report_filter;
use local_teacher_commissions\withdrawal_manager;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:viewreports', $syscontext);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/reports.php'));
$PAGE->set_title(get_string('reports_title', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('reports_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('admin');

$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);
$PAGE->navbar->add(get_string('nav_reports', 'local_teacher_commissions'));

// ---- Build filter ----
$form    = new report_filter($PAGE->url);
$filters = [];

if ($fdata = $form->get_data()) {
    // Map period shortcut to date range.
    if (!empty($fdata->period) && $fdata->period !== 'custom') {
        [$from, $to] = report_manager::period_to_dates($fdata->period);
        if ($from) $filters['datefrom'] = $from;
        if ($to)   $filters['dateto']   = $to;
    } else {
        if (!empty($fdata->datefrom)) $filters['datefrom'] = (int) $fdata->datefrom;
        if (!empty($fdata->dateto))   $filters['dateto']   = (int) $fdata->dateto + 86399; // end of day
    }
    if (!empty($fdata->teacherid))      $filters['teacherid']      = (int) $fdata->teacherid;
    if (!empty($fdata->courseid))       $filters['courseid']       = (int) $fdata->courseid;
    if (!empty($fdata->mainmarketerid)) $filters['mainmarketerid'] = (int) $fdata->mainmarketerid;
}

// ---- Export shortcuts ----
$exporttype = optional_param('export', '', PARAM_ALPHA);
if ($exporttype === 'excel') {
    $url = new moodle_url('/local/teacher_commissions/export/excel.php', $filters);
    redirect($url);
}
if ($exporttype === 'pdf') {
    $url = new moodle_url('/local/teacher_commissions/export/pdf.php', $filters);
    redirect($url);
}

// ---- Fetch data ----
$page    = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$result   = report_manager::get_filtered_transactions($filters, $page, $perpage);
$monthly  = report_manager::get_monthly_summary($filters);
$yearly   = report_manager::get_yearly_summary($filters);
$bymarket = report_manager::get_marketer_grouped_summary($filters);

// ---- Render ----
echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('reports');

$form->display();

echo $OUTPUT->heading(get_string('summary', 'local_teacher_commissions'), 3);

// Grand totals.
echo html_writer::start_div('row mb-3');
    echo html_writer::start_div('col-md-4');
        echo html_writer::div(
            html_writer::tag('strong', get_string('total_records', 'local_teacher_commissions')) . ': ' . $result['total'],
            'alert alert-secondary'
        );
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
        echo html_writer::div(
            html_writer::tag('strong', get_string('grand_total_sales', 'local_teacher_commissions'))
            . ': ' . number_format((float) $result['totals']->total_sales, 2),
            'alert alert-info'
        );
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
        echo html_writer::div(
            html_writer::tag('strong', get_string('grand_total_commissions', 'local_teacher_commissions'))
            . ': ' . number_format((float) $result['totals']->total_commissions, 2),
            'alert alert-success'
        );
    echo html_writer::end_div();
echo html_writer::end_div();

// Export buttons.
$exportparams = array_merge($filters, ['export' => 'pdf']);
echo $OUTPUT->single_button(
    new moodle_url($PAGE->url, array_merge($filters, ['export' => 'pdf'])),
    get_string('export_pdf', 'local_teacher_commissions'),
    'get',
    ['class' => 'btn-danger me-1']
);
echo $OUTPUT->single_button(
    new moodle_url($PAGE->url, array_merge($filters, ['export' => 'excel'])),
    get_string('export_excel', 'local_teacher_commissions'),
    'get',
    ['class' => 'btn-success']
);

echo html_writer::empty_tag('br');

// ---- Transactions table ----
if (empty($result['records'])) {
    echo $OUTPUT->notification(get_string('no_records', 'local_teacher_commissions'), 'info');
} else {
    echo $OUTPUT->heading(get_string('nav_reports', 'local_teacher_commissions'), 3);

    $table               = new html_table();
    $table->head         = [
        get_string('date',            'local_teacher_commissions'),
        get_string('teacher',         'local_teacher_commissions'),
        get_string('course',          'local_teacher_commissions'),
        get_string('student',         'local_teacher_commissions'),
        get_string('sale_amount',     'local_teacher_commissions'),
        get_string('commission_percent', 'local_teacher_commissions'),
        get_string('commission_earned','local_teacher_commissions'),
        get_string('status',          'local_teacher_commissions'),
    ];
    $table->attributes['class'] = 'generaltable table table-bordered table-sm';

    foreach ($result['records'] as $r) {
        $statusbadge = html_writer::tag(
            'span',
            get_string('status_' . $r->status, 'local_teacher_commissions'),
            ['class' => 'badge text-bg-' . ($r->status === 'paid' ? 'success' : 'warning')]
        );
        $table->data[] = [
            userdate($r->timecreated, get_string('strftimedatetime', 'langconfig')),
            $r->teacher_firstname . ' ' . $r->teacher_lastname,
            $r->coursename,
            $r->student_firstname . ' ' . $r->student_lastname,
            number_format($r->saleamount, 2) . ' ' . $r->currency,
            number_format($r->commission_percent, 2) . '%',
            number_format($r->commissionamount, 2) . ' ' . $r->currency,
            $statusbadge,
        ];
    }
    echo html_writer::table($table);

    echo $OUTPUT->paging_bar($result['total'], $page, $perpage, $PAGE->url);
}

// ---- Monthly Summary ----
if (!empty($monthly)) {
    echo $OUTPUT->heading(get_string('monthly_summary', 'local_teacher_commissions'), 3);

    $mt             = new html_table();
    $mt->head       = ['Month', get_string('total_records', 'local_teacher_commissions'),
        get_string('grand_total_sales', 'local_teacher_commissions'),
        get_string('grand_total_commissions', 'local_teacher_commissions')];
    $mt->attributes['class'] = 'generaltable table table-bordered table-sm';

    foreach ($monthly as $m) {
        $mt->data[] = [
            $m->label,
            $m->record_count,
            number_format($m->total_sales, 2),
            number_format($m->total_commissions, 2),
        ];
    }
    echo html_writer::table($mt);
}

// ---- Yearly Summary ----
if (!empty($yearly)) {
    echo $OUTPUT->heading(get_string('yearly_summary', 'local_teacher_commissions'), 3);

    $yt             = new html_table();
    $yt->head       = ['Year', get_string('total_records', 'local_teacher_commissions'),
        get_string('grand_total_sales', 'local_teacher_commissions'),
        get_string('grand_total_commissions', 'local_teacher_commissions')];
    $yt->attributes['class'] = 'generaltable table table-bordered table-sm';

    foreach ($yearly as $y) {
        $yt->data[] = [
            $y->year,
            $y->record_count,
            number_format($y->total_sales, 2),
            number_format($y->total_commissions, 2),
        ];
    }
    echo html_writer::table($yt);
}

// ---- By-Marketer Summary ----
if (!empty($bymarket)) {
    echo $OUTPUT->heading(get_string('commission_by_marketer', 'local_teacher_commissions'), 3);

    $bt             = new html_table();
    $bt->head       = [
        get_string('marketer_source',         'local_teacher_commissions'),
        get_string('total_records',           'local_teacher_commissions'),
        get_string('grand_total_sales',       'local_teacher_commissions'),
        get_string('grand_total_commissions', 'local_teacher_commissions'),
    ];
    $bt->attributes['class'] = 'generaltable table table-bordered table-sm';

    foreach ($bymarket as $bm) {
        $bt->data[] = [
            s($bm->marketername),
            $bm->record_count,
            number_format((float)$bm->total_sales, 2),
            number_format((float)$bm->total_commissions, 2),
        ];
    }
    echo html_writer::table($bt);
}

echo $OUTPUT->footer();
