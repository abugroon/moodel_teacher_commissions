<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form: process payout for a teacher.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Payout form.
 */
class payout extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $teacherid   = $this->_customdata['teacherid']   ?? 0;
        $teachername = $this->_customdata['teachername'] ?? '';
        $balance     = $this->_customdata['balance']     ?? 0.0;
        $currency    = $this->_customdata['currency']    ?? 'SDG';

        $mform->addElement('hidden', 'teacherid', $teacherid);
        $mform->setType('teacherid', PARAM_INT);

        $mform->addElement('header', 'payouthdr',
            get_string('payout_title', 'local_teacher_commissions', $teachername));

        // Show current balance (read-only info).
        $mform->addElement('static', 'balance_display',
            get_string('available_balance', 'local_teacher_commissions'),
            number_format($balance, 2) . ' ' . $currency);

        // Amount.
        $mform->addElement('text', 'amount',
            get_string('payout_amount', 'local_teacher_commissions'),
            ['size' => 12, 'placeholder' => number_format($balance, 2)]);
        $mform->setType('amount', PARAM_FLOAT);
        $mform->addRule('amount', null, 'required', null, 'client');
        $mform->setDefault('amount', number_format($balance, 2, '.', ''));

        // Currency.
        $mform->addElement('text', 'currency',
            get_string('currency', 'local_teacher_commissions'),
            ['size' => 5, 'maxlength' => 3]);
        $mform->setType('currency', PARAM_ALPHA);
        $mform->addRule('currency', null, 'required', null, 'client');
        $mform->setDefault('currency', $currency);

        // Notes.
        $mform->addElement('textarea', 'notes',
            get_string('payout_notes', 'local_teacher_commissions'),
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('notes', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('payout_submit', 'local_teacher_commissions'));
    }

    public function validation($data, $files): array {
        $errors  = parent::validation($data, $files);
        $amount  = (float) ($data['amount'] ?? 0);
        $balance = (float) ($this->_customdata['balance'] ?? 0);

        if ($amount <= 0) {
            $errors['amount'] = get_string('payout_error_zero', 'local_teacher_commissions');
        } elseif ($amount > $balance + 0.01) {
            $errors['amount'] = get_string(
                'payout_error_exceed',
                'local_teacher_commissions',
                number_format($balance, 2)
            );
        }

        return $errors;
    }
}
