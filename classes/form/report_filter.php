<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form: report filter for the reports page.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Report filter form.
 */
class report_filter extends \moodleform {

    public function definition(): void {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'filterhdr',
            get_string('reports_title', 'local_teacher_commissions'));

        // --- Teacher filter ---
        $teachers = [0 => get_string('all_teachers', 'local_teacher_commissions')];
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE r.shortname IN ('editingteacher','teacher')
                   AND ctx.contextlevel = :lvl
                   AND u.deleted = 0
              ORDER BY u.lastname, u.firstname";
        $rows = $DB->get_records_sql($sql, ['lvl' => CONTEXT_COURSE]);
        foreach ($rows as $r) {
            $teachers[$r->id] = fullname($r);
        }
        $mform->addElement('select', 'teacherid',
            get_string('filter_teacher', 'local_teacher_commissions'), $teachers);
        $mform->setType('teacherid', PARAM_INT);

        // --- Course filter ---
        $courses = [0 => get_string('all_courses', 'local_teacher_commissions')];
        $courserows = $DB->get_records_menu('course', ['visible' => 1], 'fullname ASC', 'id,fullname');
        $courses = $courses + $courserows;
        $mform->addElement('select', 'courseid',
            get_string('filter_course', 'local_teacher_commissions'), $courses);
        $mform->setType('courseid', PARAM_INT);

        // --- Period shortcut ---
        $periods = [
            'all'        => get_string('period_all',        'local_teacher_commissions'),
            'this_month' => get_string('period_this_month', 'local_teacher_commissions'),
            'last_month' => get_string('period_last_month', 'local_teacher_commissions'),
            'this_year'  => get_string('period_this_year',  'local_teacher_commissions'),
            'last_year'  => get_string('period_last_year',  'local_teacher_commissions'),
            'custom'     => get_string('period_custom',     'local_teacher_commissions'),
        ];
        $mform->addElement('select', 'period',
            get_string('filter_period', 'local_teacher_commissions'), $periods);
        $mform->setType('period', PARAM_ALPHA);
        $mform->setDefault('period', 'all');

        // --- Custom date range ---
        $mform->addElement('date_selector', 'datefrom',
            get_string('filter_date_from', 'local_teacher_commissions'),
            ['optional' => true]);
        $mform->disabledIf('datefrom', 'period', 'neq', 'custom');

        $mform->addElement('date_selector', 'dateto',
            get_string('filter_date_to', 'local_teacher_commissions'),
            ['optional' => true]);
        $mform->disabledIf('dateto', 'period', 'neq', 'custom');

        // Buttons.
        $buttonarray = [
            $mform->createElement('submit', 'submitbtn', get_string('apply_filters', 'local_teacher_commissions')),
            $mform->createElement('reset',  'resetbtn',  get_string('reset_filters', 'local_teacher_commissions')),
        ];
        $mform->addGroup($buttonarray, 'btngrp', '', ' ', false);
    }
}
