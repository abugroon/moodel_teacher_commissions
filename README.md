# Teacher Commissions — Moodle Local Plugin

A Moodle local plugin that automatically tracks and manages teacher commission earnings from paid course enrollments. Admins can configure commission rates per teacher, view real-time dashboards, process payouts, and export reports. Teachers have a read-only self-service portal to view their own earnings.

---

## Requirements

| Requirement | Version |
|---|---|
| Moodle | 5.0 or higher (build 2025041400+) |
| PHP | 8.2 or higher (follows Moodle 5.0 requirements) |
| Database | MySQL / MariaDB / PostgreSQL (standard Moodle support) |

> **Tested on:** Moodle 5.0.4 (Build: 20251219)

---

## Installation

### 1. Copy the plugin files

Place the plugin folder inside your Moodle installation's `local/` directory:

```
/path/to/moodle/local/teacher_commissions/
```

The folder name **must** be `teacher_commissions` (not `local_teacher_commissions`).

```bash
# Example on Linux
cp -r local_teacher_commissions/ /var/www/html/moodle/local/teacher_commissions/
```

On **Windows (XAMPP/LocalServer)**:
```
Copy folder to: C:\xampp\htdocs\moodle\local\teacher_commissions\
```

### 2. Run the Moodle upgrade

Log in as a Moodle **admin**, then visit:

```
https://your-moodle-site.com/admin/index.php
```

Moodle will detect the new plugin and prompt you to install it. Click **Upgrade Moodle database now**.

Alternatively, via CLI:

```bash
php admin/cli/upgrade.php
```

### 3. Verify installation

Go to: **Site administration → Plugins → Local plugins**

You should see **Teacher Commissions** listed and active.

---

## Configuration

After installation, configure the plugin at:

**Site administration → Plugins → Local plugins → Teacher Commissions**

| Setting | Description | Default |
|---|---|---|
| Default commission percentage | Applied to all teachers without an individual rate | `10` |
| Default currency | ISO 4217 code (e.g. USD, EUR, SAR) | `USD` |

---

## How It Works

### Automatic commission tracking

The plugin listens to Moodle's `user_enrolment_created` event. When a student enrolls in a **paid** course (via `enrol_paypal`, `enrol_fee`, or any plugin that stores a cost in `mdl_enrol.cost`), a commission transaction is automatically created for the course's primary editing teacher.

**Commission calculation:**
```
Commission Amount = Sale Amount × Commission Rate / 100
```

**Commission rate priority** (highest to lowest):
1. Individual teacher override (set by admin)
2. Global default row in database
3. Plugin admin setting
4. 0% (fallback)

### Commission status lifecycle

```
Enrollment paid → Transaction created (status: pending)
                        ↓
              Admin processes payout
                        ↓
              Transactions marked (status: paid)
```

---

## Roles & Permissions

All capabilities are applied at the **System context**.

| Capability | Assigned To | Description |
|---|---|---|
| `viewadmindashboard` | Manager | View all teachers' commission data |
| `managecommissions` | Manager | Set global or per-teacher commission rates |
| `processpayout` | Manager | Record payouts and mark transactions as paid |
| `viewreports` | Manager | Access filtered reports and exports |
| `viewowncommissions` | Teacher, Editing Teacher | View their own earnings only |

---

## Admin Interface

### Dashboard — `/local/teacher_commissions/admin/index.php`

Shows a summary table for every teacher with an active course role:

- Courses owned
- Number of paid enrollments
- Total sales amount
- Commission rate (%)
- Total earned / paid / outstanding balance
- Quick actions: View Ledger, Process Payout, Edit Rate

### Commission Settings — `/local/teacher_commissions/admin/commission_settings.php`

- **Global default** (`?id=0`): sets the fallback rate for all teachers
- **Per-teacher** (`?id={userid}`): overrides the global rate for a specific teacher

### Ledger — `/local/teacher_commissions/admin/ledger.php?id={userid}`

Full transaction history for a teacher including:
- Every paid enrollment (date, student, course, sale amount, commission)
- Transaction status (pending / paid)
- Payout history (amount, date, processed by)

### Process Payout — `/local/teacher_commissions/admin/payout.php?id={userid}`

- Validates amount does not exceed outstanding balance
- Inserts a payout record in `local_tc_payouts`
- Marks pending transactions as paid (oldest-first) until the payout amount is exhausted
- The entire operation runs inside a database transaction (safe rollback on failure)

### Reports — `/local/teacher_commissions/admin/reports.php`

Filterable report with:

| Filter | Options |
|---|---|
| Teacher | Any teacher or all |
| Course | Any course or all |
| Period | All time / This month / Last month / This year / Last year / Custom range |

Includes:
- Grand totals (records, total sales, total commissions)
- Transaction table (paginated, 50 per page)
- Monthly summary table
- Yearly summary table
- Export buttons (CSV/Excel and PDF)

---

## Teacher Self-Service Portal

### Dashboard — `/local/teacher_commissions/teacher/dashboard.php`

Read-only summary card showing:
- Number of courses
- Number of paid enrollments
- Total sales
- Total earned / amount paid / outstanding balance
- Link to full statement

### Statement — `/local/teacher_commissions/teacher/ledger.php`

Full personal transaction ledger (read-only).

---

## Export

### CSV / Excel — `/local/teacher_commissions/export/excel.php`

- Downloads a UTF-8 BOM CSV file (opens correctly in Excel)
- Admins can filter by teacher, course, and date range
- Teachers can only export their own data
- Filename format: `teacher_commissions_YYYY-MM-DD.csv`

### PDF — `/local/teacher_commissions/export/pdf.php`

- Supports same filters as Excel export
- **Moodle 5.0+ note:** TCPDF was removed from Moodle core in 5.0. The export automatically falls back to a browser-printable HTML page. A proper PDF download requires a third-party PDF library or a future upgrade to Moodle's new PDF API.

---

## Database Schema

### `local_tc_settings`
Stores commission rate per teacher. `userid = 0` = global default.

| Column | Type | Description |
|---|---|---|
| id | INT | Primary key |
| userid | INT | Teacher user ID; 0 = global default |
| commission_percent | DECIMAL(5,2) | Commission percentage |
| createdby | INT | Admin who created the record |
| timecreated | INT | Unix timestamp |
| timemodified | INT | Unix timestamp |

### `local_tc_transactions`
One row per commission event (one paid enrollment).

| Column | Type | Description |
|---|---|---|
| id | INT | Primary key |
| teacherid | INT | Teacher user ID |
| courseid | INT | Course ID |
| studentid | INT | Student user ID |
| enrolid | INT | `mdl_enrol.id` |
| userenrolmentid | INT | `mdl_user_enrolments.id` (unique — prevents duplicates) |
| saleamount | DECIMAL(10,2) | Amount paid by student |
| commission_percent | DECIMAL(5,2) | Rate applied at time of transaction |
| commissionamount | DECIMAL(10,2) | Calculated commission |
| currency | CHAR(3) | ISO 4217 (e.g. USD) |
| status | CHAR(20) | `pending` or `paid` |
| payoutid | INT | FK → `local_tc_payouts.id` (null if pending) |
| notes | TEXT | Optional notes |
| timecreated | INT | Unix timestamp |
| timemodified | INT | Unix timestamp |

### `local_tc_payouts`
One row per payout processed by an admin.

| Column | Type | Description |
|---|---|---|
| id | INT | Primary key |
| teacherid | INT | Teacher user ID |
| amount | DECIMAL(10,2) | Total amount paid out |
| currency | CHAR(3) | ISO 4217 |
| notes | TEXT | Admin notes |
| adminid | INT | Admin who processed the payout |
| timecreated | INT | Unix timestamp |

---

## Code Structure

```
local/teacher_commissions/
├── version.php                         # Plugin metadata
├── lib.php                             # Navigation hooks (extend_navigation)
├── settings.php                        # Moodle admin settings page
├── renderer.php                        # Plugin renderer (3 render methods)
├── index.php                           # Plugin root redirect
│
├── db/
│   ├── install.xml                     # Database table definitions (XMLDB)
│   ├── events.php                      # Event observer registration
│   ├── access.php                      # Capability definitions
│   └── upgrade.php                     # Database upgrade steps
│
├── classes/
│   ├── commission_manager.php          # Core business logic
│   ├── payout_manager.php              # Payout processing
│   ├── report_manager.php              # Report queries and aggregations
│   ├── event/
│   │   └── observer.php               # Listens to user_enrolment_created
│   ├── form/
│   │   ├── commission_settings.php    # Moodleform: edit commission rate
│   │   ├── payout.php                 # Moodleform: process payout
│   │   └── report_filter.php          # Moodleform: report filters
│   ├── output/
│   │   ├── admin_dashboard.php        # Renderable: admin dashboard
│   │   ├── teacher_dashboard.php      # Renderable: teacher dashboard
│   │   └── teacher_ledger.php         # Renderable: transaction ledger
│   └── privacy/
│       └── provider.php               # GDPR Privacy API
│
├── admin/
│   ├── index.php                       # Admin dashboard page
│   ├── commission_settings.php         # Edit commission rates page
│   ├── ledger.php                      # Teacher ledger (admin view)
│   ├── payout.php                      # Process payout page
│   └── reports.php                     # Reports page
│
├── teacher/
│   ├── dashboard.php                   # Teacher self-service dashboard
│   └── ledger.php                      # Teacher self-service statement
│
├── templates/
│   ├── admin_dashboard.mustache        # Admin dashboard template
│   ├── teacher_dashboard.mustache      # Teacher dashboard template
│   └── teacher_ledger.mustache         # Ledger/statement template
│
├── export/
│   ├── excel.php                       # CSV export
│   └── pdf.php                         # PDF export
│
└── lang/
    └── en/
        └── local_teacher_commissions.php  # English language strings
```

---

## Privacy & GDPR

This plugin implements Moodle's Privacy API (`classes/privacy/provider.php`).

**Data stored:**
- Commission rate settings per teacher (`local_tc_settings`)
- Transaction records linking teachers and students (`local_tc_transactions`)
- Payout records processed by admins (`local_tc_payouts`)

**Deletion policy:** Financial records are retained as audit trails by default. If your data-retention policy requires deletion, anonymize the `teacherid` / `studentid` fields rather than deleting rows.

---

## Navigation

The plugin automatically injects navigation links based on the user's role:

- **Managers/Admins** → "Commission Dashboard" link appears in the navigation
- **Teachers** (without admin capability) → "My Commissions" link appears in the navigation

---

## Supported Payment Plugins

The automatic commission detection works with any enrollment plugin that stores a numeric cost in `mdl_enrol.cost`:

- `enrol_paypal` (built-in Moodle)
- `enrol_fee` (Moodle 4.1+ built-in)
- Any third-party payment plugin that follows the same convention

Free enrollments (`cost = 0` or null) are silently ignored.

---

## License

GNU General Public License v3 or later — http://www.gnu.org/copyleft/gpl.html
