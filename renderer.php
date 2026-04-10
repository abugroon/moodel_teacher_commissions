<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin renderer for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_teacher_commissions\output\admin_dashboard;
use local_teacher_commissions\output\teacher_dashboard;
use local_teacher_commissions\output\teacher_ledger;

/**
 * Plugin renderer.
 */
class local_teacher_commissions_renderer extends plugin_renderer_base {

    /**
     * Render the admin commission dashboard.
     *
     * @param admin_dashboard $page
     * @return string HTML
     */
    public function render_admin_dashboard(admin_dashboard $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_teacher_commissions/admin_dashboard', $data);
    }

    /**
     * Render the teacher self-service dashboard.
     *
     * @param teacher_dashboard $page
     * @return string HTML
     */
    public function render_teacher_dashboard(teacher_dashboard $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_teacher_commissions/teacher_dashboard', $data);
    }

    /**
     * Render the teacher commission ledger / statement.
     *
     * @param teacher_ledger $page
     * @return string HTML
     */
    public function render_teacher_ledger(teacher_ledger $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_teacher_commissions/teacher_ledger', $data);
    }
}
