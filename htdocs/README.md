# 📋 TaskFlow — Project Management System

A complete, production-style **SaaS project management application** built with **HTML, CSS, JavaScript, PHP (PDO)** and **MySQL**. No frameworks, no React, no Node.js — just clean vanilla code.

![TaskFlow](https://img.shields.io/badge/PHP-8.x-777BB4) ![MySQL](https://img.shields.io/badge/MySQL-8.x%20%7C%20MariaDB-4479A1) ![JS](https://img.shields.io/badge/Vanilla-JS-F7DF1E) ![License](https://img.shields.io/badge/license-MIT-green)

---

## ✨ Features

| Category | Details |
|---|---|
| **Auth** | Login, Register, Logout, session-based, bcrypt password hashing, CSRF protection |
| **Roles** | Admin · Manager · Employee — with granular permission checks on every action |
| **Dashboard** | KPI cards, line / doughnut / bar charts (Chart.js), recent activity, deadlines |
| **Projects** | Create, edit, delete, status, priority, start/deadline, budget vs. spent, progress, team members |
| **Tasks** | CRUD, assign to members, Kanban + List views, To‑Do → In Progress → Review → Completed |
| **Task priorities** | Low · Medium · High · Urgent |
| **Collaboration** | Task comments, file attachments (with type/size validation) |
| **Calendar** | Monthly deadline calendar for tasks & projects with overdue highlighting |
| **Notifications** | In-app notifications, unread badges, mark read / mark all |
| **Activity logs** | Full audit trail (admin/manager) |
| **Reports** | Overview + Projects/Tasks/Team tables, **CSV export**, **print** |
| **Search** | Global live search across projects, tasks & people |
| **Profile & Settings** | Edit profile, avatar upload, change password, theme preference |
| **Themes** | 🌙 Dark / ☀️ Light mode — persisted per user |
| **Responsive** | Fully mobile-friendly with collapsible sidebar |

---

## 🔐 Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@taskflow.com` | `admin123` |
| Manager | `manager@taskflow.com` | `manager123` |
| Employee | `employee@taskflow.com` | `employee123` |

---

## 🚀 Installation

### Requirements
- PHP 8.0+ (with `pdo_mysql`, `gd`, `mbstring`, `fileinfo`)
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache/Nginx) **or** PHP's built-in server

### Steps

**1. Copy files** to your web root (e.g. `/var/www/taskflow`).

**2. Create the database** and import the schema:
```bash
mysql -u root -p < database.sql
```
This creates the `taskflow` database, all tables and seed data (including the 3 demo users above).

**3. Configure the database connection** in `config/config.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'taskflow');
define('DB_USER', 'root');        // your MySQL user
define('DB_PASS', 'yourpassword'); // your MySQL password
```

**4. Set permissions** on the uploads folder:
```bash
chmod -R 775 uploads/
```

**5. Run it** — either point Apache/Nginx at the folder, or use PHP's dev server:
```bash
php -S localhost:8000 -t /path/to/taskflow
```
Then open **http://localhost:8000** and log in with a demo account.

---

## 🗂️ Project Structure

```
taskflow/
├── database.sql              # Full schema + seed data
├── index.php                 # Entry redirect
├── login.php / register.php / logout.php
├── dashboard.php             # Stats + charts
├── projects.php              # Project grid + filters
├── project_view.php          # Kanban + team + details
├── tasks.php                 # Tasks list + board
├── task_view.php             # Task detail, comments, attachments
├── team.php                  # Member management
├── calendar.php              # Deadline calendar
├── notifications.php
├── activity_logs.php         # Admin audit trail
├── reports.php               # Reports + CSV export + print
├── profile.php / settings.php
├── config/
│   ├── config.php            # App + DB config
│   └── database.php          # PDO connection (singleton)
├── includes/
│   ├── auth.php              # Sessions, login, RBAC
│   ├── functions.php         # Helpers (labels, dates, pagination, CSRF…)
│   ├── header.php            # Layout: sidebar + navbar
│   ├── footer.php            # Layout: scripts
│   ├── _project_modal.php    # Shared project modal
│   └── _task_modal.php       # Shared task modal
├── api/                      # AJAX/JSON endpoints
│   ├── project_save.php / project_delete.php / project_get.php
│   ├── task_save.php / task_delete.php / task_status.php / task_get.php
│   ├── comment_save.php / comment_delete.php
│   ├── attachment_upload.php / attachment_delete.php
│   ├── notification_read.php
│   ├── user_save.php / user_delete.php
│   └── search.php
├── assets/
│   ├── css/style.css         # Full design system + dark mode
│   └── js/
│       ├── main.js           # Interactivity (modals, AJAX, theme, search)
│       └── chart.min.js      # Chart.js (bundled, CDN fallback)
└── uploads/                  # User-uploaded files (avatars, attachments)
```

---

## 🔒 Security

- **Passwords** hashed with `password_hash()` (bcrypt).
- **PDO prepared statements** everywhere — no SQL injection.
- **CSRF tokens** on every state-changing request (forms + AJAX).
- **Role-based access control** enforced server-side on every page & API endpoint.
- **HttpOnly / SameSite** session cookies.
- **Upload validation**: allowed extensions whitelist + size limits + randomized filenames.

---

## 🎨 Customization

All colors, spacing and theme variables live as CSS custom properties at the top of `assets/css/style.css` (`:root` and `[data-theme="dark"]`). Change `--primary` to re-brand instantly.

---

## 📜 License

MIT — free to use, modify and distribute.
