<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings page integration for local_teacher_commissions.
 *
 * Adds a settings page under Site administration → Plugins → Local plugins.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_teacher_commissions',
        get_string('pluginname', 'local_teacher_commissions')
    );

    // Link to the full admin dashboard.
    $settings->add(new admin_setting_heading(
        'local_teacher_commissions/heading',
        get_string('settings_heading', 'local_teacher_commissions'),
        html_writer::link(
            new moodle_url('/local/teacher_commissions/admin/index.php'),
            get_string('nav_admin_dashboard', 'local_teacher_commissions'),
            ['class' => 'btn btn-primary btn-sm']
        )
    ));

    // Default commission percentage.
    $settings->add(new admin_setting_configtext(
        'local_teacher_commissions/default_commission_percent',
        get_string('default_commission_percent',      'local_teacher_commissions'),
        get_string('default_commission_percent_desc', 'local_teacher_commissions'),
        '10',   // Default value: 10 %.
        PARAM_FLOAT
    ));

    // Default currency.
    $settings->add(new admin_setting_configtext(
        'local_teacher_commissions/default_currency',
        get_string('default_currency',      'local_teacher_commissions'),
        get_string('default_currency_desc', 'local_teacher_commissions'),
        'USD',
        PARAM_ALPHA
    ));

    $ADMIN->add('localplugins', $settings);
}
