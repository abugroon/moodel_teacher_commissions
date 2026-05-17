<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher commission withdrawal manager.
 *
 * Handles teacher withdrawal requests grouped by main marketer.
 * Each request is routed to the main marketer (identified by userid) who
 * generated the student referrals behind the commission group.
 *
 * @package     local_teacher_commissions
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions;

defined('MOODLE_INTERNAL') || die();

/**
 * Withdrawal manager.
 */
class withdrawal_manager {

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID     = 'paid';

    // -------------------------------------------------------------------------
    // Balance helpers
    // -------------------------------------------------------------------------

    /**
     * Return commission balances for a teacher, grouped by mainmarketerid.
     *
     * Each row contains:
     *   mainmarketerid, marketername, earned, paid, pending_requests, balance
     *
     * @param int $teacherid
     * @return array  Indexed by mainmarketerid (0 = no referral).
     */
    public static function get_grouped_balances(int $teacherid): array {
        global $DB;

        $dbman = $DB->get_manager();

        // Check if mainmarketerid column exists (added in upgrade 2024010103).
        $tx_table        = new \xmldb_table('local_tc_transactions');
        $has_main_col    = $dbman->field_exists($tx_table, new \xmldb_field('mainmarketerid'));

        if ($has_main_col) {
            $sql = "SELECT
                        COALESCE(t.mainmarketerid, 0)                        AS mainmarketerid,
                        COALESCE(SUM(t.commissionamount), 0)                 AS earned,
                        COALESCE(SUM(CASE WHEN t.status = 'paid'
                                         THEN t.commissionamount ELSE 0 END), 0) AS paid,
                        MAX(t.currency)                                       AS currency
                      FROM {local_tc_transactions} t
                     WHERE t.teacherid = :tid
                  GROUP BY t.mainmarketerid";
        } else {
            // Fallback: treat all transactions as a single "no referral" group.
            $sql = "SELECT
                        0                                                     AS mainmarketerid,
                        COALESCE(SUM(t.commissionamount), 0)                 AS earned,
                        COALESCE(SUM(CASE WHEN t.status = 'paid'
                                         THEN t.commissionamount ELSE 0 END), 0) AS paid,
                        MAX(t.currency)                                       AS currency
                      FROM {local_tc_transactions} t
                     WHERE t.teacherid = :tid";
        }

        $rows = $DB->get_records_sql($sql, ['tid' => $teacherid]);

        // Subtract pending/approved withdrawal requests (not yet paid) per group.
        $wr_rows = [];
        if ($dbman->table_exists(new \xmldb_table('local_tc_withdrawal_requests'))) {
            $wr_sql = "SELECT mainmarketerid,
                              COALESCE(SUM(amount), 0) AS reserved
                         FROM {local_tc_withdrawal_requests}
                        WHERE teacherid = :tid
                          AND status NOT IN ('rejected', 'paid')
                     GROUP BY mainmarketerid";
            $wr_rows = $DB->get_records_sql($wr_sql, ['tid' => $teacherid]);
        }

        // Resolve marketer names if local_referral plugin is present.
        $profile_exists = $DB->get_manager()->table_exists(new \xmldb_table('local_ref_marketer_profile'));

        $result = [];
        foreach ($rows as $row) {
            $mid      = (int)$row->mainmarketerid;
            $earned   = (float)$row->earned;
            $paid     = (float)$row->paid;
            $reserved = isset($wr_rows[$mid]) ? (float)$wr_rows[$mid]->reserved : 0.0;
            $balance  = round($earned - $paid - $reserved, 2);

            $marketername = get_string('no_referral_marketer', 'local_teacher_commissions');
            if ($mid > 0 && $profile_exists) {
                $profile = $DB->get_record('local_ref_marketer_profile', ['userid' => $mid], 'userid', IGNORE_MISSING);
                if ($profile) {
                    $u = $DB->get_record('user', ['id' => $profile->userid], 'firstname, lastname', IGNORE_MISSING);
                    if ($u) {
                        $marketername = fullname($u);
                    }
                }
            }

            $result[$mid] = (object)[
                'mainmarketerid' => $mid,
                'marketername'   => $marketername,
                'earned'         => $earned,
                'paid'           => $paid,
                'balance'        => $balance,
                'currency'       => $row->currency ?: (get_config('local_teacher_commissions', 'default_currency') ?: 'SDG'),
                'has_balance'    => $balance > 0.01,
            ];
        }

        return $result;
    }

    /**
     * Return per-sub-marketer contribution breakdown within a main marketer group.
     *
     * Each row: referralmarketerid, marketer_name, earned, paid
     *
     * @param int $teacherid
     * @param int $mainmarketerid  Use 0 for the no-referral group.
     * @return array
     */
    public static function get_sub_marketer_breakdown(int $teacherid, int $mainmarketerid): array {
        global $DB;

        $dbman    = $DB->get_manager();
        $tx_table = new \xmldb_table('local_tc_transactions');

        // Columns added in upgrade 2024010103 — return empty if not yet migrated.
        if (!$dbman->field_exists($tx_table, new \xmldb_field('referralmarketerid'))
            || !$dbman->field_exists($tx_table, new \xmldb_field('mainmarketerid'))) {
            return [];
        }

        $sql = "SELECT
                    COALESCE(t.referralmarketerid, 0)                        AS referralmarketerid,
                    COALESCE(SUM(t.commissionamount), 0)                     AS earned,
                    COALESCE(SUM(CASE WHEN t.status = 'paid'
                                     THEN t.commissionamount ELSE 0 END), 0) AS paid,
                    MAX(t.currency)                                           AS currency
                  FROM {local_tc_transactions} t
                 WHERE t.teacherid       = :tid
                   AND COALESCE(t.mainmarketerid, 0) = :mid
              GROUP BY t.referralmarketerid";

        $rows = $DB->get_records_sql($sql, ['tid' => $teacherid, 'mid' => $mainmarketerid]);

        $profile_exists = $DB->get_manager()->table_exists(new \xmldb_table('local_ref_marketer_profile'));

        $result = [];
        foreach ($rows as $row) {
            $rid  = (int)$row->referralmarketerid;
            $name = get_string('no_referral_marketer', 'local_teacher_commissions');

            if ($rid > 0 && $profile_exists) {
                $u = $DB->get_record('user', ['id' => $rid], 'firstname, lastname', IGNORE_MISSING);
                if ($u) {
                    $name = fullname($u);
                }
            }

            $result[] = (object)[
                'referralmarketerid' => $rid,
                'name'               => $name,
                'earned'             => (float)$row->earned,
                'paid'               => (float)$row->paid,
                'currency'           => $row->currency,
            ];
        }

        return $result;
    }

    /**
     * Return available balance for a teacher within a specific main marketer group.
     *
     * @param int $teacherid
     * @param int $mainmarketerid  Marketer userid (0 = no-referral group).
     * @return float
     */
    public static function get_group_balance(int $teacherid, int $mainmarketerid): float {
        $groups = self::get_grouped_balances($teacherid);
        return isset($groups[$mainmarketerid]) ? $groups[$mainmarketerid]->balance : 0.0;
    }

    // -------------------------------------------------------------------------
    // Request lifecycle
    // -------------------------------------------------------------------------

    /**
     * Create a withdrawal request for a teacher for a specific marketer group.
     *
     * @param int    $teacherid
     * @param int    $mainmarketerid  Main marketer userid (0 = no referral).
     * @param float  $amount
     * @param string $currency
     * @param string $notes
     * @return int   New request id.
     * @throws \moodle_exception
     */
    public static function create_request(
        int $teacherid,
        int $mainmarketerid,
        float $amount,
        string $currency = 'SDG',
        string $notes = ''
    ): int {
        global $DB, $USER;

        if ($amount <= 0) {
            throw new \moodle_exception('error_withdrawal_zeroamount', 'local_teacher_commissions');
        }

        $available = self::get_group_balance($teacherid, $mainmarketerid);
        if ($amount > $available + 0.01) {
            throw new \moodle_exception('error_withdrawal_exceeds_balance', 'local_teacher_commissions');
        }

        $now = time();
        $record = (object)[
            'teacherid'      => $teacherid,
            'mainmarketerid' => $mainmarketerid,
            'amount'         => round($amount, 2),
            'currency'       => $currency,
            'status'         => self::STATUS_PENDING,
            'notes'          => $notes,
            'createdby'      => $USER->id,
            'approvedby'     => null,
            'timecreated'    => $now,
            'timemodified'   => $now,
        ];

        $transaction = $DB->start_delegated_transaction();
        $id = $DB->insert_record('local_tc_withdrawal_requests', $record);
        $transaction->allow_commit();

        // Notify main marketer of the new request.
        if ($mainmarketerid > 0) {
            try {
                self::notify_marketer_new_request($id, $teacherid, $mainmarketerid, $record->amount, $currency, $notes, $now);
            } catch (\Throwable $e) {
                // Non-fatal — do not fail the whole request if notification fails.
                debugging('teacher_commissions: notification failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $id;
    }

    /**
     * Send Moodle notification + email to the main marketer when a new withdrawal
     * request is submitted.
     */
    private static function notify_marketer_new_request(
        int    $requestid,
        int    $teacherid,
        int    $mainmarketerid,
        float  $amount,
        string $currency,
        string $notes,
        int    $timecreated
    ): void {
        global $DB, $CFG;

        $marketer = $DB->get_record('user', ['id' => $mainmarketerid], '*', IGNORE_MISSING);
        $teacher  = $DB->get_record('user', ['id' => $teacherid],     '*', IGNORE_MISSING);
        if (!$marketer || !$teacher) {
            return;
        }

        $manage_url   = (new \moodle_url('/local/teacher_commissions/admin/withdrawals.php'))->out(false);
        $teacher_name = fullname($teacher);
        $date_str     = userdate($timecreated, '%d/%m/%Y %H:%M');
        $amount_str   = number_format($amount, 2) . ' ' . $currency;
        $notes_str    = $notes !== '' ? $notes : '—';

        $subject   = get_string('email_withdrawal_subject',   'local_teacher_commissions', $teacher_name);
        $plaintext = get_string('email_withdrawal_plaintext', 'local_teacher_commissions', (object)[
            'marketer_name' => fullname($marketer),
            'teacher_name'  => $teacher_name,
            'amount'        => $amount_str,
            'date'          => $date_str,
            'notes'         => $notes_str,
            'url'           => $manage_url,
        ]);
        $html = get_string('email_withdrawal_html', 'local_teacher_commissions', (object)[
            'marketer_name' => fullname($marketer),
            'teacher_name'  => $teacher_name,
            'amount'        => $amount_str,
            'date'          => $date_str,
            'notes'         => s($notes_str),
            'url'           => $manage_url,
        ]);

        // Moodle in-app notification.
        $msg = new \core\message\message();
        $msg->component         = 'local_teacher_commissions';
        $msg->name              = 'withdrawal_request';
        $msg->userfrom          = $teacher;
        $msg->userto            = $marketer;
        $msg->subject           = $subject;
        $msg->fullmessage       = $plaintext;
        $msg->fullmessageformat = FORMAT_HTML;
        $msg->fullmessagehtml   = $html;
        $msg->smallmessage      = get_string('email_withdrawal_small', 'local_teacher_commissions', (object)[
            'teacher_name' => $teacher_name,
            'amount'       => $amount_str,
        ]);
        $msg->notification      = 1;
        $msg->contexturl        = $manage_url;
        $msg->contexturlname    = get_string('nav_admin_withdrawals', 'local_teacher_commissions');
        message_send($msg);

        // Direct email.
        email_to_user($marketer, $teacher, $subject, $plaintext, $html);
    }

    /**
     * Update status of a withdrawal request.
     *
     * Only the assigned main marketer (by userid) or a site admin may approve/reject.
     * Only site admin may mark as paid.
     *
     * @param int    $requestid
     * @param string $newstatus  One of STATUS_* constants.
     * @param string $notes
     * @return void
     * @throws \moodle_exception
     */
    public static function update_status(int $requestid, string $newstatus, string $notes = ''): void {
        global $DB, $USER;

        $allowed = [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_PAID];
        if (!in_array($newstatus, $allowed, true)) {
            throw new \coding_exception('Invalid status: ' . $newstatus);
        }

        $req = $DB->get_record('local_tc_withdrawal_requests', ['id' => $requestid], '*', MUST_EXIST);

        $syscontext = \context_system::instance();
        $isadmin    = has_capability('local/teacher_commissions:approvewithdrawal', $syscontext);

        // Main marketer can approve/reject requests routed to them.
        $ismain = ((int)$req->mainmarketerid === (int)$USER->id);

        if ($newstatus === self::STATUS_PAID && !$isadmin) {
            throw new \moodle_exception('error_nopermission', 'local_teacher_commissions');
        }

        if (!$isadmin && !$ismain) {
            throw new \moodle_exception('error_nopermission', 'local_teacher_commissions');
        }

        $DB->update_record('local_tc_withdrawal_requests', (object)[
            'id'           => $requestid,
            'status'       => $newstatus,
            'notes'        => $notes !== '' ? $notes : $req->notes,
            'approvedby'   => $USER->id,
            'timemodified' => time(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Get withdrawal requests routed to a given main marketer userid.
     *
     * @param int    $mainmarketerid
     * @param string $status  Filter by status ('') = all.
     * @return array
     */
    public static function get_requests_for_main(int $mainmarketerid, string $status = ''): array {
        global $DB;

        $params = ['mid' => $mainmarketerid];
        $where  = 'mainmarketerid = :mid';
        if ($status !== '') {
            $where         .= ' AND status = :status';
            $params['status'] = $status;
        }

        $sql = "SELECT wr.*,
                       u.firstname AS teacher_firstname,
                       u.lastname  AS teacher_lastname
                  FROM {local_tc_withdrawal_requests} wr
             LEFT JOIN {user} u ON u.id = wr.teacherid
                 WHERE {$where}
              ORDER BY wr.timecreated ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get all withdrawal requests for a teacher (their own history).
     *
     * @param int $teacherid
     * @return array
     */
    public static function get_teacher_requests(int $teacherid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_tc_withdrawal_requests'))) {
            return [];
        }
        return $DB->get_records(
            'local_tc_withdrawal_requests',
            ['teacherid' => $teacherid],
            'timecreated DESC'
        );
    }
}
