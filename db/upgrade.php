<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin upgrade steps for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute plugin upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_teacher_commissions_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024010102) {
        $table = new xmldb_table('local_tc_payouts');
        $field = new xmldb_field('receipt_file', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'notes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024010102, 'local', 'teacher_commissions');
    }

    // =========================================================================
    // 2024010103 — Add marketer tracking to transactions;
    //              create teacher withdrawal requests table.
    // =========================================================================
    if ($oldversion < 2024010103) {

        // --- local_tc_transactions: add referralmarketerid ---
        $tx_table = new xmldb_table('local_tc_transactions');

        if (!$dbman->field_exists($tx_table, new xmldb_field('referralmarketerid'))) {
            $f = new xmldb_field(
                'referralmarketerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'notes'
            );
            $dbman->add_field($tx_table, $f);
        }

        // --- local_tc_transactions: add mainmarketerid ---
        if (!$dbman->field_exists($tx_table, new xmldb_field('mainmarketerid'))) {
            $f = new xmldb_field(
                'mainmarketerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'referralmarketerid'
            );
            $dbman->add_field($tx_table, $f);
        }

        // Add indexes on new columns (idempotent).
        $ref_idx = new xmldb_index('idx_referralmarketerid', XMLDB_INDEX_NOTUNIQUE, ['referralmarketerid']);
        if (!$dbman->index_exists($tx_table, $ref_idx)) {
            $dbman->add_index($tx_table, $ref_idx);
        }

        $main_idx = new xmldb_index('idx_mainmarketerid', XMLDB_INDEX_NOTUNIQUE, ['mainmarketerid']);
        if (!$dbman->index_exists($tx_table, $main_idx)) {
            $dbman->add_index($tx_table, $main_idx);
        }

        // Backfill referralmarketerid + mainmarketerid for existing transactions.
        // Use a PHP loop for cross-DB compatibility (no JOIN-in-UPDATE).
        $ref_users_tbl   = new xmldb_table('local_ref_users');
        $ref_profile_tbl = new xmldb_table('local_ref_marketer_profile');

        if ($dbman->table_exists($ref_users_tbl) && $dbman->table_exists($ref_profile_tbl)) {
            $tx_rows = $DB->get_records_select(
                'local_tc_transactions',
                'referralmarketerid IS NULL',
                [],
                '',
                'id, studentid'
            );
            foreach ($tx_rows as $tx) {
                $refuser = $DB->get_record('local_ref_users', ['userid' => $tx->studentid], 'marketerid', IGNORE_MISSING);
                if (!$refuser) {
                    continue;
                }
                $mp = $DB->get_record(
                    'local_ref_marketer_profile',
                    ['userid' => $refuser->marketerid],
                    'userid, type, parent_userid',
                    IGNORE_MISSING
                );
                if (!$mp) {
                    continue;
                }
                $refid  = (int)$mp->userid;
                $mainid = (!empty($mp->parent_userid) && ($mp->type ?? '') === 'sub')
                    ? (int)$mp->parent_userid
                    : (int)$mp->userid;

                $DB->set_field('local_tc_transactions', 'referralmarketerid', $refid,  ['id' => $tx->id]);
                $DB->set_field('local_tc_transactions', 'mainmarketerid',     $mainid, ['id' => $tx->id]);
            }
        }

        // --- Create local_tc_withdrawal_requests table ---
        $wr_table = new xmldb_table('local_tc_withdrawal_requests');
        if (!$dbman->table_exists($wr_table)) {
            $wr_table->add_field('id',             XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $wr_table->add_field('teacherid',      XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, null, '0');
            $wr_table->add_field('mainmarketerid', XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, null, '0');
            $wr_table->add_field('amount',         XMLDB_TYPE_NUMBER,  '10,2',  null, XMLDB_NOTNULL, null, '0.00');
            $wr_table->add_field('currency',       XMLDB_TYPE_CHAR,    '3',     null, XMLDB_NOTNULL, null, 'SDG');
            $wr_table->add_field('status',         XMLDB_TYPE_CHAR,    '20',    null, XMLDB_NOTNULL, null, 'pending');
            $wr_table->add_field('notes',          XMLDB_TYPE_TEXT,    null,    null, null,           null, null);
            $wr_table->add_field('createdby',      XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, null, '0');
            $wr_table->add_field('approvedby',     XMLDB_TYPE_INTEGER, '10',    null, null,           null, null);
            $wr_table->add_field('timecreated',    XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, null, '0');
            $wr_table->add_field('timemodified',   XMLDB_TYPE_INTEGER, '10',    null, XMLDB_NOTNULL, null, '0');

            $wr_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $wr_table->add_index('idx_teacherid',      XMLDB_INDEX_NOTUNIQUE, ['teacherid']);
            $wr_table->add_index('idx_mainmarketerid', XMLDB_INDEX_NOTUNIQUE, ['mainmarketerid']);
            $wr_table->add_index('idx_status',         XMLDB_INDEX_NOTUNIQUE, ['status']);
            $wr_table->add_index('idx_timecreated',    XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($wr_table);
        }

        upgrade_plugin_savepoint(true, 2024010103, 'local', 'teacher_commissions');
    }

    // =========================================================================
    // 2024010105 — Add receipt_file column to withdrawal requests table.
    // =========================================================================
    if ($oldversion < 2024010105) {
        $wr_table = new xmldb_table('local_tc_withdrawal_requests');
        $field    = new xmldb_field('receipt_file', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'notes');
        if (!$dbman->field_exists($wr_table, $field)) {
            $dbman->add_field($wr_table, $field);
        }
        upgrade_plugin_savepoint(true, 2024010105, 'local', 'teacher_commissions');
    }

    return true;
}
