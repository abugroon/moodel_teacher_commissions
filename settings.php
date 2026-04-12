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

    // ── Main settings page ───────────────────────────────────────────────────
    $settings = new admin_settingpage(
        'local_teacher_commissions',
        get_string('pluginname', 'local_teacher_commissions')
    );

    // Default commission percentage.
    $settings->add(new admin_setting_configtext(
        'local_teacher_commissions/default_commission_percent',
        get_string('default_commission_percent',      'local_teacher_commissions'),
        get_string('default_commission_percent_desc', 'local_teacher_commissions'),
        '10',
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

    // ── Category to group all plugin pages under Local plugins ───────────────
    $ADMIN->add('localplugins', new admin_category(
        'local_teacher_commissions_cat',
        get_string('pluginname', 'local_teacher_commissions')
    ));

    // Settings page goes inside the category.
    $ADMIN->add('local_teacher_commissions_cat', $settings);

    // ── Navigation links ─────────────────────────────────────────────────────
    $ADMIN->add('local_teacher_commissions_cat', new admin_externalpage(
        'local_teacher_commissions_dashboard',
        get_string('nav_admin_dashboard', 'local_teacher_commissions'),
        new moodle_url('/local/teacher_commissions/admin/index.php'),
        'local/teacher_commissions:viewadmindashboard'
    ));

    $ADMIN->add('local_teacher_commissions_cat', new admin_externalpage(
        'local_teacher_commissions_reports',
        get_string('nav_reports', 'local_teacher_commissions'),
        new moodle_url('/local/teacher_commissions/admin/reports.php'),
        'local/teacher_commissions:viewreports'
    ));

    $ADMIN->add('local_teacher_commissions_cat', new admin_externalpage(
        'local_teacher_commissions_commission_settings',
        get_string('commission_settings_title', 'local_teacher_commissions'),
        new moodle_url('/local/teacher_commissions/admin/commission_settings.php'),
        'local/teacher_commissions:managecommissions'
    ));
}
