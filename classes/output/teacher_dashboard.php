<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderable: teacher self-service commission dashboard.
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
 * Teacher dashboard renderable.
 */
class teacher_dashboard implements renderable, templatable {

    /** @var \stdClass Teacher summary object */
    protected \stdClass $summary;

    public function __construct(\stdClass $summary) {
        $this->summary = $summary;
    }

    public function export_for_template(renderer_base $output): array {
        $s = $this->summary;
        return [
            'courses_owned'     => $s->courses_owned,
            'paid_enrollments'  => $s->paid_enrollments,
            'total_sales'       => number_format($s->total_sales, 2),
            'commission_percent'=> number_format($s->commission_percent, 2),
            'earned'            => number_format($s->earned, 2),
            'paid'              => number_format($s->paid, 2),
            'balance'           => number_format($s->balance, 2),
            'currency'          => $s->currency,
            'url_ledger'        => (new \moodle_url('/local/teacher_commissions/teacher/ledger.php'))->out(false),
        ];
    }
}
