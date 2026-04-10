<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Event observer subscriptions for local_teacher_commissions.
 *
 * We listen to two events:
 *   1. user_enrolment_created  — for instant-payment gateways (PayPal, Stripe, enrol_fee …)
 *      that activate the enrolment immediately (status=0).
 *   2. user_enrolment_updated  — for bank-transfer plugins that first create a pending
 *      enrolment (status=1) and activate it later once the receipt is approved (status=0).
 *
 * create_transaction() is idempotent (unique index on userenrolmentid), so calling it from
 * both handlers is safe — whichever fires first wins; the second call is silently skipped.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Instant-payment gateways: enrolment is active (status=0) on creation.
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_teacher_commissions\event\observer::user_enrolment_created',
        'priority'    => 200,
        'internal'    => false,
    ],
    // Bank-transfer / manual-approval flow: enrolment switches from pending to active.
    [
        'eventname'   => '\core\event\user_enrolment_updated',
        'callback'    => '\local_teacher_commissions\event\observer::user_enrolment_updated',
        'priority'    => 200,
        'internal'    => false,
    ],
];
