<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Event observer for local_teacher_commissions.
 *
 * Listens to user_enrolment_created and auto-generates a commission record
 * when the enrolment comes from a paid enrolment plugin (enrol_paypal,
 * enrol_stripe, or any plugin that sets a cost on the enrol instance).
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\event;

defined('MOODLE_INTERNAL') || die();

use local_teacher_commissions\commission_manager;

/**
 * Observer class.
 */
class observer {

    /**
     * Triggered when a user enrolment is created.
     *
     * @param \core\event\user_enrolment_created $event
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        global $DB;

        $courseid        = $event->courseid;
        $studentid       = $event->relateduserid;
        $userenrolmentid = $event->objectid;  // mdl_user_enrolments.id

        // Load the user_enrolments record to get the enrolid.
        $ue = $DB->get_record('user_enrolments', ['id' => $userenrolmentid], '*', IGNORE_MISSING);
        if (!$ue) {
            return;
        }

        // Load the enrol instance.
        $enrol = $DB->get_record('enrol', ['id' => $ue->enrolid], '*', IGNORE_MISSING);
        if (!$enrol) {
            return;
        }

        // Determine sale amount.  We support:
        //   a) enrol_paypal  → uses enrol.cost + enrol.currency
        //   b) enrol_fee     → uses enrol.cost + enrol.currency (Moodle 4.1+ built-in)
        //   c) Any plugin that stores a numeric cost in enrol.cost
        $cost     = isset($enrol->cost) ? (float) $enrol->cost : 0.0;
        $currency = isset($enrol->currency) ? $enrol->currency : (get_config('local_teacher_commissions', 'default_currency') ?: 'USD');

        if ($cost <= 0) {
            // Free enrolment — no commission.
            return;
        }

        // Find the primary editingteacher of the course.
        $teacherid = commission_manager::get_course_teacher($courseid);
        if (!$teacherid) {
            // No teacher assigned — nothing to do.
            return;
        }

        // Create commission transaction (idempotent — skips duplicates).
        commission_manager::create_transaction(
            $teacherid,
            $courseid,
            $studentid,
            (int) $ue->enrolid,
            $userenrolmentid,
            $cost,
            $currency,
            'Auto-generated from enrolment #' . $userenrolmentid
        );
    }
}
