<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy API implementation for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider — declares all user data stored by this plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    // -------------------------------------------------------------------------
    // Metadata
    // -------------------------------------------------------------------------

    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'local_tc_settings',
            [
                'userid'             => 'privacy:metadata:local_tc_settings:userid',
                'commission_percent' => 'privacy:metadata:local_tc_settings:commission_percent',
                'timecreated'        => 'privacy:metadata:local_tc_settings:timecreated',
                'timemodified'       => 'privacy:metadata:local_tc_settings:timemodified',
            ],
            'privacy:metadata:local_tc_settings'
        );

        $collection->add_database_table(
            'local_tc_transactions',
            [
                'teacherid'          => 'privacy:metadata:local_tc_transactions:teacherid',
                'courseid'           => 'privacy:metadata:local_tc_transactions:courseid',
                'studentid'          => 'privacy:metadata:local_tc_transactions:studentid',
                'saleamount'         => 'privacy:metadata:local_tc_transactions:saleamount',
                'commissionamount'   => 'privacy:metadata:local_tc_transactions:commissionamount',
                'status'             => 'privacy:metadata:local_tc_transactions:status',
                'timecreated'        => 'privacy:metadata:local_tc_transactions:timecreated',
            ],
            'privacy:metadata:local_tc_transactions'
        );

        $collection->add_database_table(
            'local_tc_payouts',
            [
                'teacherid'   => 'privacy:metadata:local_tc_payouts:teacherid',
                'amount'      => 'privacy:metadata:local_tc_payouts:amount',
                'adminid'     => 'privacy:metadata:local_tc_payouts:adminid',
                'notes'       => 'privacy:metadata:local_tc_payouts:notes',
                'timecreated' => 'privacy:metadata:local_tc_payouts:timecreated',
            ],
            'privacy:metadata:local_tc_payouts'
        );

        $collection->add_database_table(
            'local_tc_withdrawal_requests',
            [
                'teacherid'      => 'privacy:metadata:local_tc_withdrawal_requests:teacherid',
                'mainmarketerid' => 'privacy:metadata:local_tc_withdrawal_requests:mainmarketerid',
                'amount'         => 'privacy:metadata:local_tc_withdrawal_requests:amount',
                'status'         => 'privacy:metadata:local_tc_withdrawal_requests:status',
                'timecreated'    => 'privacy:metadata:local_tc_withdrawal_requests:timecreated',
            ],
            'privacy:metadata:local_tc_withdrawal_requests'
        );

        return $collection;
    }

    // -------------------------------------------------------------------------
    // Context list
    // -------------------------------------------------------------------------

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // All data is at system context.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :level
                   AND (
                       EXISTS (SELECT 1 FROM {local_tc_settings}              WHERE userid    = :uid1)
                    OR EXISTS (SELECT 1 FROM {local_tc_transactions}           WHERE teacherid = :uid2)
                    OR EXISTS (SELECT 1 FROM {local_tc_transactions}           WHERE studentid = :uid3)
                    OR EXISTS (SELECT 1 FROM {local_tc_payouts}                WHERE teacherid = :uid4)
                    OR EXISTS (SELECT 1 FROM {local_tc_withdrawal_requests}    WHERE teacherid = :uid5)
                   )";

        $contextlist->add_from_sql($sql, [
            'level' => CONTEXT_SYSTEM,
            'uid1'  => $userid,
            'uid2'  => $userid,
            'uid3'  => $userid,
            'uid4'  => $userid,
            'uid5'  => $userid,
        ]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!($context instanceof \context_system)) {
            return;
        }

        $userlist->add_from_table('local_tc_settings',             'userid');
        $userlist->add_from_table('local_tc_transactions',          'teacherid');
        $userlist->add_from_table('local_tc_transactions',          'studentid');
        $userlist->add_from_table('local_tc_payouts',               'teacherid');
        $userlist->add_from_table('local_tc_withdrawal_requests',   'teacherid');
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Commission settings.
        $settings = $DB->get_records('local_tc_settings', ['userid' => $userid]);
        if ($settings) {
            writer::with_context(\context_system::instance())
                ->export_data(['Commission Settings'], (object) ['settings' => array_values($settings)]);
        }

        // Transactions as teacher.
        $txs = $DB->get_records('local_tc_transactions', ['teacherid' => $userid]);
        if ($txs) {
            writer::with_context(\context_system::instance())
                ->export_data(['Commission Transactions (Teacher)'], (object) ['transactions' => array_values($txs)]);
        }

        // Transactions as student.
        $stx = $DB->get_records('local_tc_transactions', ['studentid' => $userid]);
        if ($stx) {
            writer::with_context(\context_system::instance())
                ->export_data(['Commission Transactions (Student)'], (object) ['transactions' => array_values($stx)]);
        }

        // Payouts.
        $payouts = $DB->get_records('local_tc_payouts', ['teacherid' => $userid]);
        if ($payouts) {
            writer::with_context(\context_system::instance())
                ->export_data(['Commission Payouts'], (object) ['payouts' => array_values($payouts)]);
        }

        // Withdrawal requests.
        $wrequests = $DB->get_records('local_tc_withdrawal_requests', ['teacherid' => $userid]);
        if ($wrequests) {
            writer::with_context(\context_system::instance())
                ->export_data(['Withdrawal Requests'], (object) ['requests' => array_values($wrequests)]);
        }
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!($context instanceof \context_system)) {
            return;
        }
        // We do not delete financial records by policy — they are audit trails.
        // Anonymise instead if required by your data-retention policy.
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // Same policy: financial audit records are retained.
        // If you must delete, anonymise teacher/student ids here.
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        // Same policy as above.
    }
}
