<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Report manager — filter, aggregate, and format commission data.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions;

defined('MOODLE_INTERNAL') || die();

/**
 * Report manager class.
 */
class report_manager {

    /**
     * Fetch filtered transaction rows for the reports page.
     *
     * @param array $filters Keys: teacherid, courseid, datefrom, dateto.
     * @param int   $page    0-based.
     * @param int   $perpage
     * @return array {records: \stdClass[], total: int, totals: \stdClass}
     */
    public static function get_filtered_transactions(
        array $filters = [],
        int $page = 0,
        int $perpage = 100
    ): array {
        global $DB;

        [$wheresql, $params] = self::build_where($filters);

        $countsql = "SELECT COUNT(t.id)
                       FROM {local_tc_transactions} t
                       JOIN {course} c ON c.id = t.courseid
                      WHERE {$wheresql}";
        $total = (int) $DB->count_records_sql($countsql, $params);

        $sql = "SELECT t.*,
                       c.fullname   AS coursename,
                       u.firstname  AS student_firstname,
                       u.lastname   AS student_lastname,
                       tr.firstname AS teacher_firstname,
                       tr.lastname  AS teacher_lastname
                  FROM {local_tc_transactions} t
                  JOIN {course} c  ON c.id  = t.courseid
                  JOIN {user}   u  ON u.id  = t.studentid
                  JOIN {user}   tr ON tr.id = t.teacherid
                 WHERE {$wheresql}
              ORDER BY t.timecreated DESC";

        $records = array_values($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));

        // Grand totals (unaffected by pagination).
        $totsql = "SELECT
                       COALESCE(SUM(t.saleamount), 0)       AS total_sales,
                       COALESCE(SUM(t.commissionamount), 0) AS total_commissions
                     FROM {local_tc_transactions} t
                     JOIN {course} c ON c.id = t.courseid
                    WHERE {$wheresql}";
        $totals = $DB->get_record_sql($totsql, $params);

        return [
            'records' => $records,
            'total'   => $total,
            'totals'  => $totals,
        ];
    }

    /**
     * Monthly summary grouped by year-month.
     *
     * @param array $filters
     * @return array  Each row: {month, total_sales, total_commissions, record_count}
     */
    public static function get_monthly_summary(array $filters = []): array {
        global $DB;

        [$wheresql, $params] = self::build_where($filters);

        // Use database-agnostic approach: fetch all and group in PHP.
        $sql = "SELECT t.timecreated, t.saleamount, t.commissionamount
                  FROM {local_tc_transactions} t
                  JOIN {course} c ON c.id = t.courseid
                 WHERE {$wheresql}
              ORDER BY t.timecreated ASC";

        $rows = $DB->get_records_sql($sql, $params);

        $months = [];
        foreach ($rows as $row) {
            $key = date('Y-m', $row->timecreated);
            if (!isset($months[$key])) {
                $months[$key] = (object) [
                    'month'              => $key,
                    'label'              => date('F Y', $row->timecreated),
                    'total_sales'        => 0.0,
                    'total_commissions'  => 0.0,
                    'record_count'       => 0,
                ];
            }
            $months[$key]->total_sales       += (float) $row->saleamount;
            $months[$key]->total_commissions += (float) $row->commissionamount;
            $months[$key]->record_count++;
        }

        return array_values($months);
    }

    /**
     * Yearly summary grouped by year.
     *
     * @param array $filters
     * @return array
     */
    public static function get_yearly_summary(array $filters = []): array {
        global $DB;

        [$wheresql, $params] = self::build_where($filters);

        $sql = "SELECT t.timecreated, t.saleamount, t.commissionamount
                  FROM {local_tc_transactions} t
                  JOIN {course} c ON c.id = t.courseid
                 WHERE {$wheresql}";

        $rows   = $DB->get_records_sql($sql, $params);
        $years  = [];

        foreach ($rows as $row) {
            $key = date('Y', $row->timecreated);
            if (!isset($years[$key])) {
                $years[$key] = (object) [
                    'year'               => $key,
                    'total_sales'        => 0.0,
                    'total_commissions'  => 0.0,
                    'record_count'       => 0,
                ];
            }
            $years[$key]->total_sales       += (float) $row->saleamount;
            $years[$key]->total_commissions += (float) $row->commissionamount;
            $years[$key]->record_count++;
        }

        krsort($years);
        return array_values($years);
    }

    /**
     * Build WHERE clause and params array from filter array.
     *
     * @param array $filters
     * @return array  [$wheresql, $params]
     */
    private static function build_where(array $filters): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['teacherid'])) {
            $where[]           = 't.teacherid = :tid';
            $params['tid']     = (int) $filters['teacherid'];
        }
        if (!empty($filters['courseid'])) {
            $where[]           = 't.courseid = :cid';
            $params['cid']     = (int) $filters['courseid'];
        }
        if (!empty($filters['datefrom'])) {
            $where[]           = 't.timecreated >= :datefrom';
            $params['datefrom']= (int) $filters['datefrom'];
        }
        if (!empty($filters['dateto'])) {
            // End of day.
            $where[]           = 't.timecreated <= :dateto';
            $params['dateto']  = (int) $filters['dateto'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Convert a period shortname to datefrom/dateto timestamps.
     *
     * @param string $period  all|this_month|last_month|this_year|last_year
     * @return array  [datefrom, dateto]  — 0 means "no limit"
     */
    public static function period_to_dates(string $period): array {
        $now = time();
        switch ($period) {
            case 'this_month':
                return [mktime(0, 0, 0, (int) date('n', $now), 1, (int) date('Y', $now)), $now];
            case 'last_month':
                $y = (int) date('Y', $now);
                $m = (int) date('n', $now) - 1;
                if ($m === 0) { $m = 12; $y--; }
                $from = mktime(0, 0, 0, $m, 1, $y);
                $to   = mktime(23, 59, 59, $m, (int) date('t', $from), $y);
                return [$from, $to];
            case 'this_year':
                return [mktime(0, 0, 0, 1, 1, (int) date('Y', $now)), $now];
            case 'last_year':
                $y = (int) date('Y', $now) - 1;
                return [mktime(0, 0, 0, 1, 1, $y), mktime(23, 59, 59, 12, 31, $y)];
            default: // all.
                return [0, 0];
        }
    }
}
