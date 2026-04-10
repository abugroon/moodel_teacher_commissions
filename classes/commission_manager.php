<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Commission manager — core business logic.
 *
 * Responsibilities:
 *  - Resolve effective commission rate for a teacher.
 *  - Create commission transaction records.
 *  - Aggregate dashboard summary data.
 *  - Manage per-teacher commission settings.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions;

defined('MOODLE_INTERNAL') || die();

/**
 * Commission manager class.
 */
class commission_manager {

    /** @var int userid=0 row in local_tc_settings holds the global default */
    const GLOBAL_USERID = 0;

    // -------------------------------------------------------------------------
    // Commission rate management
    // -------------------------------------------------------------------------

    /**
     * Return the effective commission percentage for a teacher.
     *
     * Priority: individual override > global default > plugin setting > 0.
     *
     * @param int $teacherid
     * @return float
     */
    public static function get_effective_rate(int $teacherid): float {
        global $DB;

        // 1. Per-teacher override.
        $row = $DB->get_record('local_tc_settings', ['userid' => $teacherid]);
        if ($row) {
            return (float) $row->commission_percent;
        }

        // 2. Global DB row (userid=0).
        $global = $DB->get_record('local_tc_settings', ['userid' => self::GLOBAL_USERID]);
        if ($global) {
            return (float) $global->commission_percent;
        }

        // 3. Admin config setting.
        $cfg = get_config('local_teacher_commissions', 'default_commission_percent');
        if ($cfg !== false && $cfg !== '') {
            return (float) $cfg;
        }

        return 0.0;
    }

    /**
     * Save (insert or update) the commission rate for a teacher or the global default.
     *
     * @param int   $userid  Teacher userid, or 0 for global default.
     * @param float $percent Commission percentage (0–100).
     * @return void
     */
    public static function save_rate(int $userid, float $percent): void {
        global $DB, $USER;

        $now = time();
        $existing = $DB->get_record('local_tc_settings', ['userid' => $userid]);

        if ($existing) {
            $existing->commission_percent = $percent;
            $existing->timemodified       = $now;
            $DB->update_record('local_tc_settings', $existing);
        } else {
            $record = (object) [
                'userid'             => $userid,
                'commission_percent' => $percent,
                'createdby'          => $USER->id,
                'timecreated'        => $now,
                'timemodified'       => $now,
            ];
            $DB->insert_record('local_tc_settings', $record);
        }
    }

    /**
     * Return per-teacher settings (or null).
     *
     * @param int $userid
     * @return \stdClass|null
     */
    public static function get_teacher_setting(int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record('local_tc_settings', ['userid' => $userid]) ?: null;
    }

    // -------------------------------------------------------------------------
    // Transaction creation
    // -------------------------------------------------------------------------

    /**
     * Create a commission transaction for a paid enrollment.
     *
     * Idempotent: if a transaction already exists for the same userenrolmentid
     * it will not be duplicated (unique index in DB).
     *
     * @param int    $teacherid       Teacher user id.
     * @param int    $courseid        Course id.
     * @param int    $studentid       Student user id.
     * @param int    $enrolid         mdl_enrol.id.
     * @param int    $userenrolmentid mdl_user_enrolments.id.
     * @param float  $saleamount      Amount paid by student.
     * @param string $currency        ISO 4217 code.
     * @param string $notes           Optional notes.
     * @return int   New transaction id (or 0 if already exists).
     */
    public static function create_transaction(
        int $teacherid,
        int $courseid,
        int $studentid,
        int $enrolid,
        int $userenrolmentid,
        float $saleamount,
        string $currency = 'USD',
        string $notes = ''
    ): int {
        global $DB;

        // Guard: no duplicate.
        if ($DB->record_exists('local_tc_transactions', ['userenrolmentid' => $userenrolmentid])) {
            return 0;
        }

        $percent    = self::get_effective_rate($teacherid);
        $commission = round($saleamount * $percent / 100, 2);
        $now        = time();

        $record = (object) [
            'teacherid'          => $teacherid,
            'courseid'           => $courseid,
            'studentid'          => $studentid,
            'enrolid'            => $enrolid,
            'userenrolmentid'    => $userenrolmentid,
            'saleamount'         => $saleamount,
            'commission_percent' => $percent,
            'commissionamount'   => $commission,
            'currency'           => $currency,
            'status'             => 'pending',
            'payoutid'           => null,
            'notes'              => $notes,
            'timecreated'        => $now,
            'timemodified'       => $now,
        ];

        return $DB->insert_record('local_tc_transactions', $record);
    }

    /**
     * Manually add a commission transaction (from admin UI).
     *
     * @param int    $teacherid
     * @param int    $courseid
     * @param int    $studentid
     * @param float  $saleamount
     * @param float  $commissionpercent  Override percent (or -1 to use effective rate).
     * @param string $currency
     * @param string $notes
     * @return int   New transaction id.
     */
    public static function add_manual_transaction(
        int $teacherid,
        int $courseid,
        int $studentid,
        float $saleamount,
        float $commissionpercent = -1,
        string $currency = 'USD',
        string $notes = ''
    ): int {
        global $DB;

        if ($commissionpercent < 0) {
            $commissionpercent = self::get_effective_rate($teacherid);
        }

        $commission = round($saleamount * $commissionpercent / 100, 2);
        $now        = time();

        $record = (object) [
            'teacherid'          => $teacherid,
            'courseid'           => $courseid,
            'studentid'          => $studentid,
            'enrolid'            => 0,
            'userenrolmentid'    => 0,
            'saleamount'         => $saleamount,
            'commission_percent' => $commissionpercent,
            'commissionamount'   => $commission,
            'currency'           => $currency,
            'status'             => 'pending',
            'payoutid'           => null,
            'notes'              => $notes,
            'timecreated'        => $now,
            'timemodified'       => $now,
        ];

        return $DB->insert_record('local_tc_transactions', $record);
    }

    // -------------------------------------------------------------------------
    // Aggregation helpers
    // -------------------------------------------------------------------------

    /**
     * Return summary stats for one teacher.
     *
     * @param int $teacherid
     * @return \stdClass  {courses_owned, paid_enrollments, total_sales, commission_percent,
     *                     earned, paid, balance, currency}
     */
    public static function get_teacher_summary(int $teacherid): \stdClass {
        global $DB;

        // Courses owned (editingteacher role in any course).
        $sql_courses = "SELECT COUNT(DISTINCT ra.contextid)
                          FROM {role_assignments} ra
                          JOIN {role} r ON r.id = ra.roleid
                          JOIN {context} ctx ON ctx.id = ra.contextid
                         WHERE ra.userid = :uid
                           AND r.shortname IN ('editingteacher','teacher')
                           AND ctx.contextlevel = :ctxlevel";
        $courses_owned = (int) $DB->count_records_sql($sql_courses, [
            'uid'      => $teacherid,
            'ctxlevel' => CONTEXT_COURSE,
        ]);

        // Aggregate commission transactions.
        $sql_agg = "SELECT
                        COUNT(id)                          AS paid_enrollments,
                        COALESCE(SUM(saleamount), 0)       AS total_sales,
                        COALESCE(SUM(commissionamount), 0) AS earned,
                        MAX(currency)                      AS currency
                      FROM {local_tc_transactions}
                     WHERE teacherid = :tid";
        $agg = $DB->get_record_sql($sql_agg, ['tid' => $teacherid]);

        // Already-paid commissions.
        $sql_paid = "SELECT COALESCE(SUM(commissionamount), 0) AS paid
                       FROM {local_tc_transactions}
                      WHERE teacherid = :tid AND status = 'paid'";
        $paid_row = $DB->get_record_sql($sql_paid, ['tid' => $teacherid]);

        $earned  = $agg ? (float) $agg->earned      : 0.0;
        $paid    = $paid_row ? (float) $paid_row->paid : 0.0;
        $balance = round($earned - $paid, 2);

        return (object) [
            'teacherid'         => $teacherid,
            'courses_owned'     => $courses_owned,
            'paid_enrollments'  => $agg ? (int) $agg->paid_enrollments : 0,
            'total_sales'       => $agg ? (float) $agg->total_sales    : 0.0,
            'commission_percent'=> self::get_effective_rate($teacherid),
            'earned'            => $earned,
            'paid'              => $paid,
            'balance'           => $balance,
            'currency'          => ($agg && $agg->currency) ? $agg->currency
                : (get_config('local_teacher_commissions', 'default_currency') ?: 'USD'),
        ];
    }

    /**
     * Return summary data for ALL teachers (for admin dashboard).
     *
     * Returns an array of summary objects (same shape as get_teacher_summary)
     * merged with basic user profile data.
     *
     * @return array
     */
    public static function get_all_teacher_summaries(): array {
        global $DB;

        // Find all users who hold teacher/editingteacher role in at least one course.
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.username
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE r.shortname IN ('editingteacher','teacher')
                   AND ctx.contextlevel = :ctxlevel
                   AND u.deleted = 0
              ORDER BY u.lastname, u.firstname";

        $teachers = $DB->get_records_sql($sql, ['ctxlevel' => CONTEXT_COURSE]);

        $summaries = [];
        foreach ($teachers as $teacher) {
            $summary = self::get_teacher_summary($teacher->id);
            $summary->firstname = $teacher->firstname;
            $summary->lastname  = $teacher->lastname;
            $summary->email     = $teacher->email;
            $summary->username  = $teacher->username;
            $summaries[]        = $summary;
        }

        return $summaries;
    }

    /**
     * Return paginated transactions for a teacher, with optional filters.
     *
     * @param int    $teacherid
     * @param array  $filters  Keys: courseid, status, datefrom, dateto.
     * @param int    $page     0-based page number.
     * @param int    $perpage
     * @return array {records: [], total: int}
     */
    public static function get_transactions(
        int $teacherid,
        array $filters = [],
        int $page = 0,
        int $perpage = 50
    ): array {
        global $DB;

        $where  = ['t.teacherid = :tid'];
        $params = ['tid' => $teacherid];

        if (!empty($filters['courseid'])) {
            $where[]              = 't.courseid = :cid';
            $params['cid']        = (int) $filters['courseid'];
        }
        if (!empty($filters['status'])) {
            $where[]              = 't.status = :status';
            $params['status']     = $filters['status'];
        }
        if (!empty($filters['datefrom'])) {
            $where[]              = 't.timecreated >= :datefrom';
            $params['datefrom']   = (int) $filters['datefrom'];
        }
        if (!empty($filters['dateto'])) {
            $where[]              = 't.timecreated <= :dateto';
            $params['dateto']     = (int) $filters['dateto'];
        }

        $wheresql = implode(' AND ', $where);

        $sql = "SELECT t.*,
                       c.fullname  AS coursename,
                       u.firstname AS student_firstname,
                       u.lastname  AS student_lastname
                  FROM {local_tc_transactions} t
                  JOIN {course} c ON c.id = t.courseid
                  JOIN {user}   u ON u.id = t.studentid
                 WHERE {$wheresql}
              ORDER BY t.timecreated DESC";

        $total   = $DB->count_records_sql("SELECT COUNT(*) FROM {local_tc_transactions} t WHERE {$wheresql}", $params);
        $records = array_values($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));

        return ['records' => $records, 'total' => $total];
    }

    /**
     * Find the primary editing teacher of a course.
     *
     * Returns the first editingteacher found, or null.
     *
     * @param int $courseid
     * @return int|null  Teacher user id or null if none found.
     */
    public static function get_course_teacher(int $courseid): ?int {
        global $DB;

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        $sql = "SELECT ra.userid
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid = :ctxid
                   AND r.shortname IN ('editingteacher','teacher')
              ORDER BY r.shortname DESC, ra.id ASC
                 LIMIT 1";

        $row = $DB->get_record_sql($sql, ['ctxid' => $context->id]);
        return $row ? (int) $row->userid : null;
    }
}
