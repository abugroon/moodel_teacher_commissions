<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin library / hook functions for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns true if the current user has teacher-level access to the commissions plugin.
 *
 * Teachers are assigned the editingteacher/teacher role at CONTEXT_COURSE level, not at
 * CONTEXT_SYSTEM.  A plain has_capability($syscontext) check therefore always fails for
 * them.  This function falls back to checking whether the user holds the capability in
 * any course context, which is the correct approach for course-level role assignments.
 *
 * @return bool
 */
function local_teacher_commissions_has_teacher_access(): bool {
    global $USER;

    $syscontext = context_system::instance();

    // Fast path: system-level assignment (site admins, managers who were explicitly
    // granted the role at system context).
    if (has_capability('local/teacher_commissions:viewowncommissions', $syscontext)) {
        return true;
    }

    // Course-level path: get_user_capability_course() returns all courses where the
    // user effectively has the capability.  For an editingteacher/teacher archetype,
    // Moodle grants CAP_ALLOW in every course where that role is assigned.
    // We only need to know if at least one such course exists, hence limit=1.
    $courses = get_user_capability_course(
        'local/teacher_commissions:viewowncommissions',
        $USER->id,
        true,   // $doanything — respect siteadmin overrides
        'id',   // fields to return
        '',     // no sort needed
        1       // limit — we only need one result
    );

    return !empty($courses);
}

// ---------------------------------------------------------------------------
// In-page navigation helpers
// ---------------------------------------------------------------------------

/**
 * Render a Bootstrap nav-tabs bar for the given links array.
 *
 * @param array  $links  Associative array of key => ['label' => string, 'url' => moodle_url]
 * @param string $active Key of the currently active tab (empty string = none active)
 * @return string HTML
 */
function local_teacher_commissions_render_nav(array $links, string $active): string {
    $html = html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-4']);
    foreach ($links as $key => $item) {
        $isactive = ($key === $active);
        $aattrs   = [
            'class' => 'nav-link' . ($isactive ? ' active' : ''),
            'href'  => $item['url']->out(false),
        ];
        if ($isactive) {
            $aattrs['aria-current'] = 'page';
        }
        $html .= html_writer::start_tag('li', ['class' => 'nav-item']);
        $html .= html_writer::tag('a', $item['label'], $aattrs);
        $html .= html_writer::end_tag('li');
    }
    $html .= html_writer::end_tag('ul');
    return $html;
}

/**
 * Return the admin navigation bar HTML.
 *
 * @param string $active One of: 'dashboard', 'reports', 'settings', or '' for none.
 * @return string HTML
 */
function local_teacher_commissions_admin_nav(string $active = ''): string {
    $links = [
        'dashboard' => [
            'label' => get_string('nav_admin_dashboard', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/index.php'),
        ],
        'withdrawals' => [
            'label' => get_string('nav_admin_withdrawals', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/withdrawals.php'),
        ],
        'reports' => [
            'label' => get_string('nav_reports', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/reports.php'),
        ],
        'settings' => [
            'label' => get_string('commission_settings_title', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/commission_settings.php'),
        ],
    ];
    return local_teacher_commissions_render_nav($links, $active);
}

/**
 * Return a teacher-context quick-links bar for admin pages.
 *
 * Shows links to all pages related to a specific teacher so admins can
 * jump between dashboard, ledger, settings, payout and reports without
 * navigating back and forth through the main nav.
 *
 * @param int    $teacherid   The target teacher's user id.
 * @param string $teachername Display name shown in the bar heading.
 * @param string $active      Active page key: 'ledger', 'settings', 'payout', or '' for none.
 * @param float  $balance     Teacher's current unpaid balance (shows Payout link only when > 0).
 * @return string HTML
 */
function local_teacher_commissions_admin_teacher_nav(
    int $teacherid,
    string $teachername,
    string $active = '',
    float $balance = 0.0
): string {
    if ($teacherid <= 0) {
        return '';
    }

    $links = [
        'dashboard' => [
            'label' => get_string('nav_admin_dashboard', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/index.php'),
            'icon'  => 'fa-tachometer',
            'class' => 'btn-outline-secondary',
        ],
        'ledger' => [
            'label' => get_string('view_ledger', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/ledger.php', ['id' => $teacherid]),
            'icon'  => 'fa-list',
            'class' => 'btn-outline-primary',
        ],
        'settings' => [
            'label' => get_string('edit_commission', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/commission_settings.php', ['id' => $teacherid]),
            'icon'  => 'fa-cog',
            'class' => 'btn-outline-dark',
        ],
    ];

    if ($balance > 0) {
        $links['payout'] = [
            'label' => get_string('pay_commission', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/admin/payout.php', ['id' => $teacherid]),
            'icon'  => 'fa-money',
            'class' => 'btn-outline-success',
        ];
    }

    $links['reports'] = [
        'label' => get_string('nav_reports', 'local_teacher_commissions'),
        'url'   => new moodle_url('/local/teacher_commissions/admin/reports.php', ['teacherid' => $teacherid]),
        'icon'  => 'fa-bar-chart',
        'class' => 'btn-outline-info',
    ];

    $html  = html_writer::start_div('card mb-3 border-secondary');
    $html .= html_writer::start_div('card-header bg-light py-2 d-flex align-items-center gap-2');
    $html .= html_writer::tag('i', '', ['class' => 'fa fa-user-circle text-secondary']);
    $html .= html_writer::tag('strong', $teachername);
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('card-body py-2');
    $html .= html_writer::start_div('d-flex flex-wrap gap-2');

    foreach ($links as $key => $item) {
        $isactive = ($key === $active);
        $classes  = 'btn btn-sm ' . ($isactive
            ? str_replace('btn-outline-', 'btn-', $item['class']) . ' disabled'
            : $item['class']);
        $attrs = ['href' => $item['url']->out(false), 'class' => $classes];
        if ($isactive) {
            $attrs['aria-current'] = 'page';
        }
        $icon  = html_writer::tag('i', '', ['class' => 'fa ' . $item['icon'] . ' me-1']);
        $html .= html_writer::tag('a', $icon . $item['label'], $attrs);
    }

    $html .= html_writer::end_div(); // d-flex
    $html .= html_writer::end_div(); // card-body
    $html .= html_writer::end_div(); // card
    return $html;
}

/**
 * Return the teacher navigation bar HTML.
 *
 * @param string $active One of: 'dashboard', 'ledger', or '' for none.
 * @return string HTML
 */
function local_teacher_commissions_teacher_nav(string $active = ''): string {
    $links = [
        'dashboard' => [
            'label' => get_string('nav_teacher_dashboard', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/teacher/dashboard.php'),
        ],
        'ledger' => [
            'label' => get_string('nav_teacher_ledger', 'local_teacher_commissions'),
            'url'   => new moodle_url('/local/teacher_commissions/teacher/ledger.php'),
        ],
    ];
    return local_teacher_commissions_render_nav($links, $active);
}

// ---------------------------------------------------------------------------

/**
 * Inject a commission link into the Moodle top navbar.
 * Shown to admins and to any user who has teacher-level access.
 *
 * @param \renderer_base $renderer
 * @return string HTML
 */
function local_teacher_commissions_render_navbar_output(\renderer_base $renderer): string {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $syscontext = context_system::instance();

    // Admin path — link to admin dashboard.
    if (has_capability('local/teacher_commissions:viewadmindashboard', $syscontext)) {
        $url   = new moodle_url('/local/teacher_commissions/admin/index.php');
        $label = get_string('nav_admin_dashboard', 'local_teacher_commissions');
        return local_teacher_commissions_navbar_link($url->out(false), '&#x1F4B0;', $label);
    }

    // Teacher path — only for users who actually have courses.
    if (local_teacher_commissions_has_teacher_access()) {
        $url   = new moodle_url('/local/teacher_commissions/teacher/dashboard.php');
        $label = get_string('nav_teacher_dashboard', 'local_teacher_commissions');
        return local_teacher_commissions_navbar_link($url->out(false), '&#x1F4B0;', $label);
    }

    return '';
}

/**
 * Helper: build a styled navbar anchor tag.
 *
 * @param string $url
 * @param string $icon  HTML entity / emoji
 * @param string $label Plain text label
 * @return string HTML
 */
function local_teacher_commissions_navbar_link(string $url, string $icon, string $label): string {
    global $PAGE;
    $active = strpos($PAGE->url->out(false), '/local/teacher_commissions/') !== false;
    $style  = 'display:inline-flex;align-items:center;gap:5px;white-space:nowrap;'
            . 'font-weight:600;font-size:.88rem;text-decoration:none;'
            . 'padding:6px 12px;border-radius:8px;transition:background .15s;'
            . ($active
                ? 'background:rgba(37,99,235,.12);color:#1d4ed8;'
                : 'color:inherit;');
    return '<a href="' . s($url) . '" style="' . $style . '">'
         . $icon . ' ' . s($label)
         . '</a>';
}

/**
 * Serve files from local_teacher_commissions file areas.
 */
function local_teacher_commissions_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();
    if ($filearea !== 'payout_receipts') {
        return false;
    }
    require_capability('local/teacher_commissions:viewadmindashboard', context_system::instance());
    $itemid   = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? ('/' . implode('/', $args) . '/') : '/';
    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'local_teacher_commissions', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Inject commission navigation items into Moodle's navigation tree.
 *
 * @param global_navigation $nav
 */
function local_teacher_commissions_extend_navigation(global_navigation $nav): void {
    $syscontext = context_system::instance();

    // Admin navigation node.
    if (has_capability('local/teacher_commissions:viewadmindashboard', $syscontext)) {
        $nav->add(
            get_string('nav_admin_dashboard', 'local_teacher_commissions'),
            new moodle_url('/local/teacher_commissions/admin/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tc_admin_dashboard',
            new pix_icon('i/report', '')
        );
        return; // Admins don't also need the teacher node.
    }

    // Teacher navigation node: use the extended check so course-level roles work.
    if (local_teacher_commissions_has_teacher_access()) {
        $nav->add(
            get_string('nav_teacher_dashboard', 'local_teacher_commissions'),
            new moodle_url('/local/teacher_commissions/teacher/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tc_teacher_dashboard',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Inject commission navigation items into the flat navigation (Boost theme).
 *
 * @param flat_navigation $nav
 */
function local_teacher_commissions_extend_navigation_user_settings(\navigation_node $nav): void {
    // Intentionally empty — we use extend_navigation above.
}
