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
     * Handles instant-payment gateways (PayPal, Stripe, enrol_fee …) that activate
     * the enrolment immediately.  Bank-transfer enrolments start as pending (status=1)
     * and are handled by user_enrolment_updated() instead.
     *
     * @param \core\event\user_enrolment_created $event
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        global $DB;

        $userenrolmentid = $event->objectid;  // mdl_user_enrolments.id

        $ue = $DB->get_record('user_enrolments', ['id' => $userenrolmentid], '*', IGNORE_MISSING);
        if (!$ue) {
            return;
        }

        // Skip pending enrolments (status=1) — bank-transfer plugins create them this way.
        // They will be picked up by user_enrolment_updated() once the receipt is approved.
        if ((int) $ue->status !== 0) {
            return;
        }

        self::maybe_create_commission($ue, $event->courseid, $event->relateduserid);
    }

    /**
     * Triggered when a user enrolment is updated.
     *
     * Handles the bank-transfer approval flow: the enrolment is created as pending
     * (status=1) and activated (status=0) once the admin confirms the receipt.
     *
     * create_transaction() is idempotent, so if the enrolment was already active on
     * creation (non-bank gateways) this handler simply does nothing new.
     *
     * @param \core\event\user_enrolment_updated $event
     * @return void
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event): void {
        global $DB;

        $userenrolmentid = $event->objectid;

        $ue = $DB->get_record('user_enrolments', ['id' => $userenrolmentid], '*', IGNORE_MISSING);
        if (!$ue) {
            return;
        }

        // Only act when the enrolment has just become active (approved by admin).
        if ((int) $ue->status !== 0) {
            return;
        }

        self::maybe_create_commission($ue, $event->courseid, $event->relateduserid);
    }

    /**
     * Core logic: read enrol cost and create a commission transaction if applicable.
     *
     * Safe to call from both created and updated handlers because create_transaction()
     * has a unique index on userenrolmentid — duplicate calls are silently ignored.
     *
     * @param \stdClass $ue   Row from mdl_user_enrolments.
     * @param int       $courseid
     * @param int       $studentid
     * @return void
     */
    private static function maybe_create_commission(\stdClass $ue, int $courseid, int $studentid): void {
        global $DB;

        $enrol = $DB->get_record('enrol', ['id' => $ue->enrolid], '*', IGNORE_MISSING);
        if (!$enrol) {
            return;
        }

        // Supports: enrol_paypal, enrol_fee, enrol_bank, and any plugin storing cost in enrol.cost.
        $cost     = isset($enrol->cost) ? (float) $enrol->cost : 0.0;
        $currency = isset($enrol->currency) ? $enrol->currency
                  : (get_config('local_teacher_commissions', 'default_currency') ?: 'USD');

        if ($cost <= 0) {
            return;
        }

        $teacherid = commission_manager::get_course_teacher($courseid);
        if (!$teacherid) {
            return;
        }

        commission_manager::create_transaction(
            $teacherid,
            $courseid,
            $studentid,
            (int) $ue->enrolid,
            (int) $ue->id,
            $cost,
            $currency,
            'Auto-generated from enrolment #' . $ue->id
        );
    }
}
