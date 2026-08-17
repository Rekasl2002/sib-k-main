# SIB-K — Deployment Guide

**Read this in other languages:** **English** · [Bahasa Indonesia](DEPLOYMENT.id.md)

How to install SIB-K on a Linux server. The examples use **aaPanel**, but every step
maps directly onto a plain LAMP/LEMP server — only the panel-specific clicks differ.

- [1. Prerequisites](#1-prerequisites)
- [2. Placeholders used in this guide](#2-placeholders-used-in-this-guide)
- [3. Install](#3-install)
- [4. File ownership and permissions](#4-file-ownership-and-permissions)
- [5. Post-install checklist](#5-post-install-checklist)
- [6. Updating an installed site](#6-updating-an-installed-site)
- [7. Backup and restore](#7-backup-and-restore)
- [8. Troubleshooting](#8-troubleshooting)
- [9. Command reference](#9-command-reference)

---

## 1. Prerequisites

| Component | Requirement |
|---|---|
| PHP | 8.1+ (8.4 recommended). On aaPanel: *App Store → PHP 8.x → Install*. |
| PHP extensions | `intl`, `mbstring`, `mysqli`, `gd`, `zip`. On aaPanel: *App Store → PHP 8.x → Setting → Install extensions*. |
| Database | MySQL 8.x or equivalent MariaDB. |
| Web server | Nginx or Apache. Rewrites are handled by `public/.htaccess` on Apache, or by a rewrite rule on Nginx (see [3.5](#35-point-the-document-root-at-public)). |
| Composer | Needed unless you upload `vendor/` from your development machine. |
| Git | Recommended, so updates are a `git pull`. |

---

## 2. Placeholders used in this guide

Replace these with your own values. **Do not paste real credentials into any file
that is tracked by Git.**

| Placeholder | Meaning | Typical value |
|---|---|---|
| `<APP_DIR>` | Project directory on the server | `/www/wwwroot/your-domain.example` |
| `<WEB_USER>` | User the web server / PHP-FPM runs as | `www` (aaPanel), `www-data` (Debian/Ubuntu), `apache` (RHEL) |
| `<DOMAIN>` | Site domain | `your-domain.example` |
| `<DB_NAME>` `<DB_USER>` `<DB_PASS>` | Database credentials | created in step 3.1 |

To confirm the web-server user on your machine:

```bash
ps aux | grep -E 'php-fpm|apache2|httpd|nginx' | grep -v grep | head -5
```

---

## 3. Install

### 3.1 Create the site and the database

1. **Website → Add site**, enter `<DOMAIN>`.
2. Set **PHP version** to 8.x. Make sure it is not an older PHP (e.g. 7.4) that may
   also be installed on the server.
3. Create the database from the same form, or from **Databases**. Note `<DB_NAME>`,
   `<DB_USER>`, and `<DB_PASS>` — store them in a password manager, not in a file in
   the project.

Use `utf8mb4` as the database character set.

### 3.2 Put the code in place

```bash
cd <APP_DIR>
rm -f index.html 404.html .htaccess        # remove the panel's placeholder files
git clone https://github.com/Rekasl2002/sib-k-main.git .
```

If you upload a ZIP from your development machine instead, **exclude**:

- any local `.env` / `.env.*` files — create a fresh `.env` on the server;
- research or backup material containing real student data;
- `tests/` and `phpunit.xml.dist`.

### 3.3 Install dependencies

```bash
cd <APP_DIR>
composer install --no-dev
```

If Composer is unavailable on the server, upload the `vendor/` folder from your
development machine instead.

### 3.4 Create and fill `.env`

```bash
cd <APP_DIR>
cp env .env
nano .env        # or edit via the panel's file manager
```

Set at minimum:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://<DOMAIN>/'

database.default.hostname = 127.0.0.1
database.default.database = <DB_NAME>
database.default.username = <DB_USER>
database.default.password = <DB_PASS>
```

Generate the encryption key (writes `encryption.key` into `.env`):

```bash
php spark key:generate
```

After HTTPS is active (step 3.7), also set:

```ini
app.forceGlobalSecureRequests = true
cookie.secure = true
```

> **`CI_ENVIRONMENT = production` is mandatory on a public server.** Development mode
> shows stack traces and server paths to every visitor. If you must temporarily switch
> to `development` to diagnose something, switch it back immediately afterwards.

### 3.5 Point the document root at `public/`

CodeIgniter 4 must be served from `public/` only, so that `app/`, `writable/`, and
`.env` cannot be downloaded.

- **aaPanel:** *Website → click the site → Site directory → Run directory = `/public`* → Save.
- **Apache (manual):** set `DocumentRoot` to `<APP_DIR>/public` and allow `.htaccess`
  (`AllowOverride All`) with `mod_rewrite` enabled.
- **Nginx:** set `root <APP_DIR>/public;` and add the rewrite rule below (aaPanel:
  *URL rewrite* tab). Without it, the home page loads but every other route returns 404.

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3.6 Set ownership and permissions

This is the single most common source of a failed deployment — see
[section 4](#4-file-ownership-and-permissions).

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
```

### 3.7 Run migrations and seed

```bash
cd <APP_DIR>
php spark migrate
php spark db:seed DatabaseSeeder
```

`DatabaseSeeder` loads the required baseline data (roles, permissions, default
accounts, academic year, classes, settings) plus sample records for each feature, with
sample schedules shifted to around the seeding date. For a baseline-only install
without sample records, run `php spark db:seed InitialDataSeeder` instead.

> ### ⚠️ Never run `php spark migrate:rollback` on a server
> The schema is a single migration whose `down()` **drops every application table**.
> Take a dump first — see [section 7](#7-backup-and-restore).

### 3.8 Enable HTTPS

1. *Website → click the site → SSL → Let's Encrypt → select the domain → Apply*.
2. Enable **Force HTTPS**.
3. Return to `.env` and set `app.forceGlobalSecureRequests = true` and
   `cookie.secure = true`.

---

## 4. File ownership and permissions

### Why this matters

Every Linux file has an **owner**, a **group**, and permissions for owner / group /
others: `r` (read) = 4, `w` (write) = 2, `x` (execute or enter a directory) = 1.

| Mode | Meaning | Use for |
|---|---|---|
| `755` | Owner may write; group and others may read and enter | Application code |
| `775` | Owner **and group** may write; others read only | `writable/` |
| `640` | Owner reads and writes, group reads, others nothing | `.env` (holds the database password) |
| `777` | Everyone may write | **Never** — especially not for credentials |

If you clone or upload the code as `root`, every file ends up owned by `root`. With
`755`, only the owner can write — so PHP-FPM, running as `<WEB_USER>`, cannot create
cache, session, log, or upload files, and every page fails with HTTP 500.

### The rule for SIB-K

- The whole project is owned by `<WEB_USER>:<WEB_USER>`.
- `writable/` is `775`.
- `.env` is `640`.

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
```

Notes on the command: the trailing `.` in `chown -R <WEB_USER>:<WEB_USER> .` is the
target (the current directory) and is **required** — without it you get
`missing operand`. `-R` uses a single dash and means recursive.

**Re-run `chown -R <WEB_USER>:<WEB_USER> .` after every `git pull` or upload done as
root**, because the new files will be owned by root again.

---

## 5. Post-install checklist

- [ ] Sign in as an administrator and **change every default account password**.
      Credentials for the seeded accounts are distributed with the internal
      administrator handbook, not published in this repository.
- [ ] Delete or deactivate sample accounts you do not need.
- [ ] Review **Settings**: school name, logo, academic year, the Consultation &
      Reports toggles, and the per-role notification matrix.
- [ ] Verify `CI_ENVIRONMENT = production` and that visiting a bad URL shows a plain
      error page, not a stack trace.
- [ ] Confirm `.env` is not reachable over HTTP (`https://<DOMAIN>/.env` must not
      return the file — with the document root at `public/` it cannot).
- [ ] Log in once per role and confirm menus and access match the intended matrix.
- [ ] Set up a backup routine (section 7).

To wipe and reload data later: **Settings → Reset Application Data** (type `RESET`
plus the admin password). This deletes all data and uploads, reloads baseline and
sample data, and signs everyone out. It cannot be undone.

---

## 6. Updating an installed site

```bash
cd <APP_DIR>
git pull
composer install --no-dev        # if composer.json changed
php spark migrate                # if new migrations were added
chown -R <WEB_USER>:<WEB_USER> . # required: pull ran as root
```

Server-side OPcache means PHP changes may not take effect until PHP-FPM is reloaded
(aaPanel: *App Store → PHP 8.x → Reload*).

---

## 7. Backup and restore

Back up the database and both upload directories together, and always before any
migration or reset:

```bash
mysqldump -u <DB_USER> -p <DB_NAME> > /root/backup_sibk_$(date +%F).sql
tar czf /root/backup_sibk_uploads_$(date +%F).tar.gz \
    <APP_DIR>/writable/uploads <APP_DIR>/public/uploads
```

Restore:

```bash
mysql -u <DB_USER> -p <DB_NAME> < /root/backup_sibk_YYYY-MM-DD.sql
```

Keep backups outside the web root, and remember they contain confidential counseling
data — store and transfer them accordingly.

---

## 8. Troubleshooting

### "Cache unable to write" / session or log write errors / uploads fail

```
CodeIgniter\Cache\Exceptions\CacheException:
Cache unable to write to "<APP_DIR>/writable/cache/".
```

Every page returns HTTP 500, even before login, because CodeIgniter initialises the
cache service on every request.

**Cause:** the project is owned by `root` with mode `755`, while PHP-FPM runs as
`<WEB_USER>` — so the web server can read but not write.

**Diagnose:**

```bash
ls -ld <APP_DIR>/writable/cache
sudo -u <WEB_USER> touch <APP_DIR>/writable/cache/test \
  && echo "WRITABLE" || echo "NOT WRITABLE"
```

**Fix:**

```bash
cd <APP_DIR>
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
```

### The home page works but every other page returns 404

Rewrites are not active, or the run directory is not `/public`. See
[3.5](#35-point-the-document-root-at-public); on Nginx add the `try_files` block.

### HTTP 500 with no detail

Read `writable/logs/log-<date>.log`. If necessary, temporarily set
`CI_ENVIRONMENT = development` in `.env` to see the trace — then set it back to
`production`.

### `open_basedir restriction in effect`

The panel restricts which directories PHP may access. Widen it to include the whole
project directory (not just `public/`): *Website → the site → PHP → open_basedir /
Anti-XSS settings*.

### PHP code changes do not appear

Server-side OPcache is enabled (unlike a typical local setup). Reload PHP-FPM after
deploying.

### The error page mentions the wrong PHP version

The site is using the wrong PHP pool: *Website → the site → PHP version → 8.x*.

### A form is rejected as an expired token after the page sat idle

Intended CSRF behavior. Reload the page and resubmit.

### Database errors after `git pull`

There are probably new migrations: run `php spark migrate`. **Do not** run
`migrate:rollback` — it drops every table. Dump the database first.

### Password reset emails are not delivered

Email-based reset is intentionally disabled. Users request a reset from the login
page, and an administrator issues the new password from
**Admin → Password Reset Requests**.

---

## 9. Command reference

```bash
# ===== FIRST-TIME DEPLOY =====
cd <APP_DIR>
git clone https://github.com/Rekasl2002/sib-k-main.git .
composer install --no-dev
cp env .env
#   -> edit .env: CI_ENVIRONMENT, app.baseURL, database.default.*
php spark key:generate
chown -R <WEB_USER>:<WEB_USER> .
chmod -R 775 writable
chmod 640 .env
php spark migrate
php spark db:seed DatabaseSeeder

# ===== EVERY CODE UPDATE =====
cd <APP_DIR>
git pull
composer install --no-dev          # if composer.json changed
php spark migrate                  # if new migrations exist
chown -R <WEB_USER>:<WEB_USER> .   # required: pull ran as root

# ===== ON "unable to write" ERRORS =====
chown -R <WEB_USER>:<WEB_USER> <APP_DIR>
chmod -R 775 <APP_DIR>/writable

# ===== BACKUP (before anything risky) =====
mysqldump -u <DB_USER> -p <DB_NAME> > /root/backup_sibk_$(date +%F).sql
```

---

See also: [README.md](README.md) for local development setup and project overview.
