<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form: commission settings (per-teacher or global default).
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Commission settings form.
 */
class commission_settings extends \moodleform {

    /**
     * Define form elements.
     */
    public function definition(): void {
        $mform = $this->_form;

        $teacherid   = $this->_customdata['teacherid']   ?? 0;
        $teachername = $this->_customdata['teachername'] ?? get_string('global_default', 'local_teacher_commissions');

        $mform->addElement('hidden', 'teacherid', $teacherid);
        $mform->setType('teacherid', PARAM_INT);

        $mform->addElement('header', 'commissionhdr',
            get_string('commission_settings_title', 'local_teacher_commissions'));

        if ($teacherid > 0) {
            $mform->addElement('static', 'teacherinfo', get_string('teacher', 'local_teacher_commissions'), $teachername);
        }

        $mform->addElement(
            'text',
            'commission_percent',
            get_string('commission_rate_label', 'local_teacher_commissions'),
            ['size' => 8, 'placeholder' => '0.00']
        );
        $mform->setType('commission_percent', PARAM_FLOAT);
        $mform->addRule('commission_percent', null, 'required', null, 'client');
        $mform->addRule('commission_percent', null, 'numeric',  null, 'client');
        $mform->addHelpButton('commission_percent', 'commission_rate_label', 'local_teacher_commissions');

        $this->add_action_buttons(true, get_string('save_settings', 'local_teacher_commissions'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $pct = (float) ($data['commission_percent'] ?? -1);
        if ($pct < 0 || $pct > 100) {
            $errors['commission_percent'] = get_string('error', 'moodle') . ': must be 0–100.';
        }

        return $errors;
    }
}
