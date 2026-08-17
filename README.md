# SIB-K — Guidance & Counseling Information System

**Read this in other languages:** **English** · [Bahasa Indonesia](README.id.md)

SIB-K (*Sistem Informasi Bimbingan dan Konseling*) is a web-based application for
managing school guidance and counseling (BK) services: consultations and reports,
guidance and counseling sessions, parent collaboration, home visits, case
conferences, assessments, career/higher-education information, internal messaging,
notifications, staff task assignment, dashboards, and reporting.

The application is built for **MA Persis 31 Banjaran** but is configurable for other
schools through the in-app settings page.

> **Confidentiality is the core design rule.** Counseling details, confidential notes,
> and individual assessment results are visible only to the handling counselor and the
> BK coordinator. Other roles see, at most, schedules or a safe summary. Please keep
> this rule intact when modifying the code.

---

## Table of contents

- [Features](#features)
- [User roles](#user-roles)
- [Requirements](#requirements)
- [Local installation](#local-installation)
- [Production deployment](#production-deployment)
- [Accounts and credentials](#accounts-and-credentials)
- [Database and migrations](#database-and-migrations)
- [Backup](#backup)
- [Security notes](#security-notes)
- [Repository layout](#repository-layout)
- [License](#license)

---

## Features

| Area | Description |
|---|---|
| Consultations & Reports | Students, parents, and homeroom teachers submit consultations, complaints, or requests; BK staff review, process, and can convert them into a service record. |
| Five BK services | Guidance, Counseling, Parent Collaboration, Home Visits, and Case Conferences — one shared service-record backbone with participants, notes, and follow-ups. |
| Assessments | Online questionnaires: build questions, publish, assign to students or classes, grade, and export results. |
| Career & Higher-Education Info | Career options and university information, with a per-item shown/hidden status and a list of items students saved. |
| Task Assignment | The coordinator assigns work to counselors and tracks status history. |
| Internal Messaging | Chat-style messaging with attachments, restricted by a per-role recipient matrix. |
| Notifications | System-generated notifications with a per-role category matrix configured by the admin. |
| Dashboards & Reports | Role-specific dashboards, plus multi-feature PDF/Excel reports scoped to each role's access. |
| Student & Parent Import | Bulk-create students and their parent accounts from an Excel file. |
| Administration | Users, roles/permissions, academic years, classes, students, app settings, password-reset requests, trash/restore, and a guarded full data reset. |

## User roles

1. **Admin** — accounts, roles, master data, and application settings. No access to confidential BK content.
2. **BK Coordinator** — full access to BK service data, assigns counselors, school-wide reporting.
3. **Counselor (Guru BK)** — services for assigned classes/students; sees confidential detail only for their own cases.
4. **Homeroom Teacher** — own class only; general information, no confidential counseling detail.
5. **Student** — submits consultations, fills assessments, sees own schedule and results.
6. **Parent/Guardian** — child's schedule and general summary, consultations with counselors.

Access is enforced at the route level (role + permission filters) and again in the
controllers and views. A full access matrix ships with the internal user handbook.

---

## Requirements

| Component | Version / note |
|---|---|
| PHP | 8.1 or newer (tested up to 8.4) |
| PHP extensions | `intl`, `mbstring`, `mysqli`, `gd`, `zip` |
| Database | MySQL 8.x or an equivalent MariaDB, `utf8mb4` |
| Web server | Apache with `mod_rewrite`, or Nginx with an equivalent rewrite rule |
| Composer | 2.x |
| Framework | CodeIgniter 4.6 (installed via Composer) |

---

## Local installation

The steps below assume a local stack such as Laragon, XAMPP, or a plain
PHP + MySQL setup.

### 1. Get the code and dependencies

```bash
git clone https://github.com/Rekasl2002/sib-k-main.git
cd sib-k-main
composer install
```

### 2. Create the environment file

The repository ships a template named `env` (no leading dot, no credentials in it).
Copy it and fill in your own values — **never commit the resulting `.env`.**

```bash
cp env .env          # Windows PowerShell: Copy-Item env .env
```

Edit `.env` and set at minimum:

```ini
CI_ENVIRONMENT = development          # use "production" on a public server

app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = <your_database_name>
database.default.username = <your_database_user>
database.default.password = <your_database_password>
```

Then generate the encryption key (this writes `encryption.key` into `.env`):

```bash
php spark key:generate
```

### 3. Create the database and run the migration

Create an empty `utf8mb4` database that matches `database.default.database`, then:

```bash
php spark migrate
```

### 4. Seed the initial data

```bash
php spark db:seed DatabaseSeeder
```

This loads the required baseline data (roles, permissions, default accounts,
academic year, classes, settings) plus sample records for every feature, with
sample schedules shifted to around the seeding date.

To load only the required baseline without sample records, run
`php spark db:seed InitialDataSeeder`.

### 5. Serve the application

Point the web server's document root at the **`public/`** directory — never at the
project root. With the built-in server:

```bash
php spark serve
```

Then open the `app.baseURL` you configured and sign in with the credentials your
administrator provided (see [Accounts and credentials](#accounts-and-credentials)).

---

## Production deployment

Server installation — including document root, file ownership and permissions,
HTTPS, update procedure, and troubleshooting — is documented separately:

- **[DEPLOYMENT.md](DEPLOYMENT.md)** (English)
- **[DEPLOYMENT.id.md](DEPLOYMENT.id.md)** (Bahasa Indonesia)

Minimum production checklist:

- [ ] `CI_ENVIRONMENT = production` in `.env` (development mode exposes stack traces and server paths to visitors).
- [ ] Document root points at `public/`.
- [ ] `php spark key:generate` has been run on the server.
- [ ] `writable/` is writable by the web-server user; `.env` is readable only by its owner (`chmod 640`).
- [ ] HTTPS enabled, then `app.forceGlobalSecureRequests = true` and `cookie.secure = true`.
- [ ] Every default account password changed, and unused sample accounts removed.

---

## Accounts and credentials

The seeder creates a set of starter accounts for each role so the application is
usable immediately after installation.

**Their usernames and passwords are deliberately not published in this repository.**
They are distributed with the internal administrator handbook. The same applies to
the naming and initial-password convention used by the student/parent import
feature — that convention is documented only in the internal handbook, because
publishing it would make freshly imported accounts guessable.

After installation:

1. Sign in as an administrator.
2. Change **every** default password immediately.
3. Delete or deactivate any sample accounts you do not need.
4. Tell imported students and parents to change their initial password at first login.

There is no self-service email password reset. A user who forgets their password
submits a request from the login page; an administrator issues a new password and
marks the request as resolved. Homeroom teachers can additionally reset the
passwords of students in their own class.

---

## Database and migrations

The whole schema lives in a **single migration**, `CreateSibkSchema`. All BK tables
use soft deletes (`deleted_at`) so service history stays auditable.

> ### ⚠️ Never run `php spark migrate:rollback` on a server
> Because the schema is a single migration, its `down()` **drops every application
> table**. Always take a database dump before any migration or reset operation.

Schema changes after the initial release should be added as **new** migration files
under `app/Database/Migrations/`, then applied with `php spark migrate`.

An in-app **Settings → Reset Application Data** page (admin only, guarded by typing
`RESET` plus the admin's own password) wipes all data and uploads and reloads the
baseline plus sample data. It cannot be undone.

---

## Backup

Back up all three of these together:

```bash
mysqldump -u <db_user> -p <db_name> > backup_$(date +%F).sql
```

- the database dump,
- `writable/uploads/`,
- `public/uploads/`.

Application logs are written to `writable/logs/`.

---

## Security notes

- **Never commit `.env` or any credentials.** This repository is public. The tracked
  `env` file is a credential-free template only; `.env` and `.env.*` are ignored by Git.
- Run public servers with `CI_ENVIRONMENT = production`.
- Keep `.env` at `chmod 640` and owned by the web-server user; keep `writable/` at
  `775`. Never use `777`.
- CSRF protection is session-based and enabled on every form. A form left idle for a
  long time will be rejected — this is intended behavior; reload the page and resubmit.
- Sessions expire after an idle timeout configurable in the application settings.
- Research material (`backupNInformasi/`, `bahan lain/`) contains real student data
  and is intentionally **not tracked by Git**. Do not add it to the repository or
  upload it to the server.

---

## Repository layout

```
app/          Application code (Controllers, Models, Views, Services, Libraries, Migrations, Seeds)
public/       Web root — the document root must point here
system/       CodeIgniter framework
writable/     Runtime files: cache, logs, sessions, uploads (not tracked)
env           Credential-free .env template
DEPLOYMENT.md Server installation and troubleshooting guide
```

`vendor/`, `writable/*`, uploaded files, and `.env` are not tracked and are recreated
by `composer install`, by the application at runtime, or by you.

---

## License

See [LICENSE](LICENSE).
