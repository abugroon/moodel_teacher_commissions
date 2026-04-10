<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Payout manager — handles payout creation and transaction status updates.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions;

defined('MOODLE_INTERNAL') || die();

/**
 * Payout manager class.
 */
class payout_manager {

    /**
     * Process a payout for a teacher.
     *
     * Steps:
     *  1. Validate the amount does not exceed outstanding balance.
     *  2. Insert a row in local_tc_payouts.
     *  3. Mark pending transactions as paid (oldest-first) until the
     *     payout amount is exhausted or all pending are covered.
     *
     * All operations run inside a transaction.
     *
     * @param int    $teacherid  Teacher user id.
     * @param float  $amount     Amount to pay.
     * @param string $currency   ISO 4217 code.
     * @param string $notes      Admin notes.
     * @return \stdClass  The created payout record.
     * @throws \moodle_exception  If validation fails.
     */
    public static function process_payout(
        int $teacherid,
        float $amount,
        string $currency = 'USD',
        string $notes = ''
    ): \stdClass {
        global $DB, $USER;

        // Validate amount.
        if ($amount <= 0) {
            throw new \moodle_exception('payout_error_zero', 'local_teacher_commissions');
        }

        $summary = commission_manager::get_teacher_summary($teacherid);
        if ($amount > $summary->balance + 0.01) {  // 0.01 tolerance for float.
            throw new \moodle_exception(
                'payout_error_exceed',
                'local_teacher_commissions',
                '',
                number_format($summary->balance, 2)
            );
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $now = time();

            // 1. Insert payout record.
            $payout = (object) [
                'teacherid'   => $teacherid,
                'amount'      => $amount,
                'currency'    => $currency,
                'notes'       => $notes,
                'adminid'     => $USER->id,
                'timecreated' => $now,
            ];
            $payout->id = $DB->insert_record('local_tc_payouts', $payout);

            // 2. Mark pending transactions as paid (oldest first).
            $pending = $DB->get_records_select(
                'local_tc_transactions',
                "teacherid = :tid AND status = 'pending'",
                ['tid' => $teacherid],
                'timecreated ASC'
            );

            $remaining = $amount;
            foreach ($pending as $tx) {
                if ($remaining <= 0) {
                    break;
                }
                $DB->set_field('local_tc_transactions', 'status', 'paid', ['id' => $tx->id]);
                $DB->set_field('local_tc_transactions', 'payoutid', $payout->id, ['id' => $tx->id]);
                $DB->set_field('local_tc_transactions', 'timemodified', $now, ['id' => $tx->id]);
                $remaining -= (float) $tx->commissionamount;
            }

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }

        return $payout;
    }

    /**
     * Retrieve payout history for a teacher (newest first).
     *
     * @param int $teacherid
     * @return array of \stdClass records with admin user info joined.
     */
    public static function get_payouts(int $teacherid): array {
        global $DB;

        $sql = "SELECT p.*,
                       u.firstname AS admin_firstname,
                       u.lastname  AS admin_lastname
                  FROM {local_tc_payouts} p
                  JOIN {user} u ON u.id = p.adminid
                 WHERE p.teacherid = :tid
              ORDER BY p.timecreated DESC";

        return array_values($DB->get_records_sql($sql, ['tid' => $teacherid]));
    }

    /**
     * Get all payout records (admin reports).
     *
     * @param array $filters  Keys: teacherid, datefrom, dateto.
     * @return array
     */
    public static function get_all_payouts(array $filters = []): array {
        global $DB;

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['teacherid'])) {
            $where[]             = 'p.teacherid = :tid';
            $params['tid']       = (int) $filters['teacherid'];
        }
        if (!empty($filters['datefrom'])) {
            $where[]             = 'p.timecreated >= :datefrom';
            $params['datefrom']  = (int) $filters['datefrom'];
        }
        if (!empty($filters['dateto'])) {
            $where[]             = 'p.timecreated <= :dateto';
            $params['dateto']    = (int) $filters['dateto'];
        }

        $wheresql = implode(' AND ', $where);

        $sql = "SELECT p.*,
                       t.firstname AS teacher_firstname,
                       t.lastname  AS teacher_lastname,
                       a.firstname AS admin_firstname,
                       a.lastname  AS admin_lastname
                  FROM {local_tc_payouts} p
                  JOIN {user} t ON t.id = p.teacherid
                  JOIN {user} a ON a.id = p.adminid
                 WHERE {$wheresql}
              ORDER BY p.timecreated DESC";

        return array_values($DB->get_records_sql($sql, $params));
    }
}
