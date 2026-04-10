<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderable: teacher commission ledger (full statement).
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher_commissions\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Teacher ledger renderable.
 */
class teacher_ledger implements renderable, templatable {

    protected \stdClass $summary;
    protected array     $transactions;
    protected array     $payouts;
    protected bool      $is_admin;

    /**
     * @param \stdClass $summary      Teacher summary.
     * @param array     $transactions Commission transaction rows.
     * @param array     $payouts      Payout rows.
     * @param bool      $is_admin     Whether the viewer is an admin (enables extra links).
     */
    public function __construct(
        \stdClass $summary,
        array $transactions,
        array $payouts,
        bool $is_admin = false
    ) {
        $this->summary      = $summary;
        $this->transactions = $transactions;
        $this->payouts      = $payouts;
        $this->is_admin     = $is_admin;
    }

    public function export_for_template(renderer_base $output): array {
        $s   = $this->summary;
        $txs = [];
        foreach ($this->transactions as $t) {
            $txs[] = [
                'date'          => userdate($t->timecreated, get_string('strftimedatetime', 'langconfig')),
                'coursename'    => $t->coursename,
                'student'       => $t->student_firstname . ' ' . $t->student_lastname,
                'saleamount'    => number_format($t->saleamount, 2),
                'commission_pct'=> number_format($t->commission_percent, 2),
                'commission'    => number_format($t->commissionamount, 2),
                'currency'      => $t->currency,
                'status'        => $t->status,
                'status_label'  => get_string('status_' . $t->status, 'local_teacher_commissions'),
                'is_paid'       => ($t->status === 'paid'),
            ];
        }

        $pouts = [];
        foreach ($this->payouts as $p) {
            $pouts[] = [
                'date'        => userdate($p->timecreated, get_string('strftimedatetime', 'langconfig')),
                'amount'      => number_format($p->amount, 2),
                'currency'    => $p->currency,
                'admin'       => $p->admin_firstname . ' ' . $p->admin_lastname,
                'notes'       => $p->notes ?? '',
            ];
        }

        return [
            'teachername'       => $s->firstname . ' ' . $s->lastname,
            'commission_percent'=> number_format($s->commission_percent, 2),
            'courses_owned'     => $s->courses_owned,
            'paid_enrollments'  => $s->paid_enrollments,
            'total_sales'       => number_format($s->total_sales, 2),
            'earned'            => number_format($s->earned, 2),
            'paid_out'          => number_format($s->paid, 2),
            'balance'           => number_format($s->balance, 2),
            'currency'          => $s->currency,
            'transactions'      => $txs,
            'payouts'           => $pouts,
            'has_transactions'  => !empty($txs),
            'has_payouts'       => !empty($pouts),
            'is_admin'          => $this->is_admin,
            'url_export_pdf'    => (new \moodle_url('/local/teacher_commissions/export/pdf.php', [
                'teacherid' => $s->teacherid]))->out(false),
            'url_export_excel'  => (new \moodle_url('/local/teacher_commissions/export/excel.php', [
                'teacherid' => $s->teacherid]))->out(false),
            'url_payout'        => $this->is_admin ? (new \moodle_url('/local/teacher_commissions/admin/payout.php', [
                'id' => $s->teacherid]))->out(false) : '',
            'url_back'          => $this->is_admin
                ? (new \moodle_url('/local/teacher_commissions/admin/index.php'))->out(false)
                : (new \moodle_url('/local/teacher_commissions/teacher/dashboard.php'))->out(false),
        ];
    }
}
