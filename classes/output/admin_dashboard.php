<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderable: admin commission dashboard.
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
 * Admin dashboard renderable.
 */
class admin_dashboard implements renderable, templatable {

    /** @var array Teacher summary objects */
    protected array $summaries;

    /**
     * Constructor.
     *
     * @param array $summaries  Array of summary stdClass objects from commission_manager.
     */
    public function __construct(array $summaries) {
        $this->summaries = $summaries;
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $rows = [];
        foreach ($this->summaries as $s) {
            $rows[] = [
                'teacherid'          => $s->teacherid,
                'teachername'        => $s->firstname . ' ' . $s->lastname,
                'email'              => $s->email,
                'courses_owned'      => $s->courses_owned,
                'paid_enrollments'   => $s->paid_enrollments,
                'total_sales'        => number_format($s->total_sales, 2),
                'commission_percent' => number_format($s->commission_percent, 2),
                'earned'             => number_format($s->earned, 2),
                'paid'               => number_format($s->paid, 2),
                'balance'            => number_format($s->balance, 2),
                'currency'           => $s->currency,
                'has_balance'        => $s->balance > 0,
                'url_ledger'         => (new \moodle_url('/local/teacher_commissions/admin/ledger.php', ['id' => $s->teacherid]))->out(false),
                'url_payout'         => (new \moodle_url('/local/teacher_commissions/admin/payout.php', ['id' => $s->teacherid]))->out(false),
                'url_settings'       => (new \moodle_url('/local/teacher_commissions/admin/commission_settings.php', ['id' => $s->teacherid]))->out(false),
            ];
        }

        return [
            'rows'      => $rows,
            'has_rows'  => !empty($rows),
            'url_global_settings' => (new \moodle_url('/local/teacher_commissions/admin/commission_settings.php'))->out(false),
            'url_reports'         => (new \moodle_url('/local/teacher_commissions/admin/reports.php'))->out(false),
        ];
    }
}
