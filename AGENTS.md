# AGENTS.md — Teacher Commissions Plugin (local_teacher_commissions)

## Plugin Overview

**Name:** Teacher Commissions  
**Component:** `local_teacher_commissions`  
**Version:** 1.0.0 (Release: 2024010101)  
**Requirements:** Moodle 5.0+, PHP 8.2+, MySQL / MariaDB / PostgreSQL  
**License:** GNU GPL v3 or later

A Moodle local plugin that automatically tracks and manages teacher commission earnings from paid course enrollments. It provides admins with a dashboard to view earnings, configure commission rates, process payouts, and export reports — plus a read-only self-service portal for teachers to view their own earnings.

---

## Current Features

### Admin Features

| Feature | Description | File |
|---------|-------------|------|
| Commission Dashboard | Summary table of all teachers showing earned / paid / balance amounts | `admin/index.php` |
| Commission Settings | Configure a global default rate or a per-teacher override (0–100%) | `admin/commission_settings.php` |
| Teacher Ledger | Full transaction history for any teacher (pending / paid) | `admin/ledger.php` |
| Process Payouts | Record payout transactions; marks pending transactions as paid (oldest-first) | `admin/payout.php` |
| Reports | Filterable transaction records with grand totals and monthly / yearly summaries | `admin/reports.php` |
| CSV / Excel Export | Export data as CSV with UTF-8 BOM encoding | `export/excel.php` |
| PDF Export | Export via TCPDF or a printable HTML fallback (Moodle 5.0+) | `export/pdf.php` |
| Quick Navigation | Per-teacher nav card on all admin pages for seamless switching | `lib.php` |

### Teacher Features

| Feature | Description | File |
|---------|-------------|------|
| Teacher Dashboard | Personal summary showing courses, paid enrollments, total sales, earned / paid / balance | `teacher/dashboard.php` |
| Statement / Ledger | Full read-only transaction and payout history | `teacher/ledger.php` |
| Personal Data Export | Teachers can export their own data as CSV or PDF | `export/excel.php`, `export/pdf.php` |

### Automatic Features

| Feature | Description | File |
|---------|-------------|------|
| Commission on Enrollment | Listens to `user_enrolment_created` and creates a commission automatically | `classes/event/observer.php` |
| Bank Transfer Support | Listens to `user_enrolment_updated` to handle enrollments that start as pending | `classes/event/observer.php` |
| Duplicate Prevention | Unique index on `userenrolmentid` prevents double-counting | `db/install.xml` |
| Commission Rate Priority | Per-teacher override → DB global default → config setting → 0% | `classes/commission_manager.php` |
| Payment Plugin Support | Works with `enrol_paypal`, `enrol_fee`, `enrol_bank`, and any plugin storing cost in `enrol.cost` | `classes/event/observer.php` |

---

## Database Tables

### `local_tc_settings` — Commission Rate Settings

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| userid | INT | Teacher user ID; 0 = global default (UNIQUE) |
| commission_percent | DECIMAL(5,2) | Commission percentage (0–100) |
| createdby | INT | Admin who created the record |
| timecreated | INT | Unix timestamp |
| timemodified | INT | Unix timestamp |

### `local_tc_transactions` — Transaction Log

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| teacherid | INT | Teacher user ID (indexed) |
| courseid | INT | Course ID (indexed) |
| studentid | INT | Student user ID (indexed) |
| enrolid | INT | `mdl_enrol.id` |
| userenrolmentid | INT | `mdl_user_enrolments.id` (UNIQUE — prevents duplicates) |
| saleamount | DECIMAL(10,2) | Amount paid by the student |
| commission_percent | DECIMAL(5,2) | Rate applied at transaction time |
| commissionamount | DECIMAL(10,2) | Calculated commission (saleamount × percent / 100) |
| currency | CHAR(3) | ISO 4217 currency code |
| status | CHAR(20) | `pending` or `paid` (indexed) |
| payoutid | INT | FK to `local_tc_payouts.id` (null if pending) |
| notes | TEXT | Optional notes |
| timecreated | INT | Unix timestamp (indexed) |
| timemodified | INT | Unix timestamp |

### `local_tc_payouts` — Payout Records

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| teacherid | INT | Teacher user ID (indexed) |
| amount | DECIMAL(10,2) | Total amount paid out |
| currency | CHAR(3) | ISO 4217 currency code |
| notes | TEXT | Admin notes |
| adminid | INT | Admin who processed the payout |
| timecreated | INT | Unix timestamp (indexed) |

---

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `viewadmindashboard` | View the admin commission dashboard | Manager, Admin |
| `managecommissions` | Edit commission rate settings | Manager, Admin |
| `processpayout` | Process and record payouts | Manager, Admin |
| `viewreports` | View filtered reports | Manager, Admin |
| `viewowncommissions` | View own commission statement | Teacher, EditingTeacher |

---

## File Structure

```
local/teacher_commissions/
├── index.php                          # Entry point — routes to admin or teacher view
├── lib.php                            # Navigation helpers and capability checks
├── settings.php                       # Plugin settings in Moodle admin
├── renderer.php                       # Renderer for mustache templates
├── version.php                        # Plugin metadata
│
├── admin/
│   ├── index.php                      # Admin commission dashboard
│   ├── commission_settings.php        # Manage commission rates
│   ├── ledger.php                     # Teacher transaction ledger (admin view)
│   ├── payout.php                     # Process payouts
│   └── reports.php                    # Filtered reports
│
├── teacher/
│   ├── dashboard.php                  # Teacher self-service dashboard (read-only)
│   └── ledger.php                     # Teacher statement
│
├── export/
│   ├── excel.php                      # CSV / Excel export
│   └── pdf.php                        # PDF export
│
├── classes/
│   ├── commission_manager.php         # Core commission business logic
│   ├── payout_manager.php             # Payout processing logic
│   ├── report_manager.php             # Report queries and aggregations
│   ├── event/
│   │   └── observer.php               # Enrollment event handlers
│   ├── form/
│   │   ├── commission_settings.php    # Commission settings form
│   │   ├── payout.php                 # Payout form
│   │   └── report_filter.php          # Report filter form
│   ├── output/
│   │   ├── admin_dashboard.php        # Renderable for admin dashboard
│   │   ├── teacher_dashboard.php      # Renderable for teacher dashboard
│   │   └── teacher_ledger.php         # Renderable for statement/ledger
│   └── privacy/
│       └── provider.php               # GDPR Privacy API implementation
│
├── db/
│   ├── access.php                     # Capability definitions
│   ├── events.php                     # Enrollment event subscriptions
│   ├── install.xml                    # Database table definitions
│   └── upgrade.php                    # Upgrade steps
│
├── templates/
│   ├── admin_dashboard.mustache       # Admin dashboard template
│   ├── teacher_dashboard.mustache     # Teacher dashboard template
│   └── teacher_ledger.mustache        # Statement / ledger template
│
└── lang/
    └── en/
        └── local_teacher_commissions.php  # UI strings and capability descriptions (150+)
```

---

## Core Business Logic

### `classes/commission_manager.php`
- Manages commission rates globally or per teacher
- Creates commission transactions on enrollment events
- Aggregates earnings summaries (total earned, paid, balance)
- Finds course teachers via Moodle role assignments

### `classes/payout_manager.php`
- Validates that the payout amount does not exceed the available balance
- Creates a new payout record in `local_tc_payouts`
- Updates pending transactions to `paid` (oldest-first) inside an atomic DB transaction

### `classes/report_manager.php`
- Executes filtered transaction queries (by teacher, course, date range)
- Generates monthly and yearly summary aggregations
- Converts period shortcut strings (e.g., `this_month`) into date boundaries

---

## Automated Workflow

```
Student pays to enroll in a course
            ↓
Moodle fires user_enrolment_created
            ↓
observer.php reads enrol.cost from the enrol record
            ↓
commission_manager finds the course teacher
            ↓
Reads commission rate (per-teacher override or global default)
            ↓
Inserts a row into local_tc_transactions (status = pending)
            ↓
Admin reviews earnings in the dashboard
            ↓
Admin processes a payout via payout_manager
            ↓
Transactions updated to paid + row inserted into local_tc_payouts
```

---

## Plugin Settings

| Setting | Default | Description |
|---------|---------|-------------|
| `default_commission_percent` | 10% | Fallback commission rate for all teachers |
| `default_currency` | USD | Default ISO 4217 currency code |

**Location in Moodle:** Site administration → Plugins → Local plugins → Teacher Commissions

---

## Security & Compliance

- **GDPR / Privacy API:** Exports user data on request; financial records are retained by policy (audit trail) rather than deleted.
- **SQL Injection Prevention:** All queries use Moodle's parameterized DML API — no raw SQL interpolation.
- **CSRF Protection:** All POST forms are validated with Moodle session keys.
- **Capability Checks:** Every page calls `require_capability()` before any data access or mutation.
- **Atomic Payouts:** Payout processing uses a DB transaction; any failure rolls back cleanly.

---

## External Integrations

| System | Integration |
|--------|-------------|
| enrol_paypal | Reads `enrol.cost` automatically |
| enrol_fee | Reads `enrol.cost` automatically |
| enrol_bank | Reads `enrol.cost` automatically |
| TCPDF | PDF generation on Moodle ≤4.x; falls back to printable HTML on 5.0+ |
| Moodle Navigation API | Injects commission links into the main nav tree |
| Moodle Role System | Reads course-level teacher role assignments |
| Moodle Privacy API | GDPR export and audit support |
