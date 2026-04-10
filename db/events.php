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
 * We listen to user_enrolment_created so that commissions are generated
 * automatically whenever a paid enrollment is recorded in Moodle, regardless
 * of which payment gateway (PayPal, Stripe, etc.) processed the transaction.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_teacher_commissions\event\observer::user_enrolment_created',
        'priority'    => 200,
        'internal'    => false,
    ],
];
