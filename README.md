# Southeastern Archdeaconry — Episcopal Church of Liberia

Hybrid informational + CMS-driven website for the Southeastern Archdeaconry (PHP 8+, MySQL, Bootstrap 5).

## Status

- **Phase 1 (done):** Database schema (`database/schema.sql`), seed data (`database/seed.sql`), PDO config (`config/`).
- **Phase 2 (done):** Public site shell — header/footer/nav partials, helpers, session-based auth scaffolding, theme CSS/JS.
- **Phase 3 (done):** Landing page (`public/index.php`) — hero carousel, live stats, "Today in the Church", featured story, clergy spotlight, testimonials, upcoming events, newsletter signup.
- **Phase 4 (done):** All public pages — leadership pages, churches/organizations directories + single pages, clergy registry, blog, letters & documents, events + liturgical calendar, newsletter archive, gallery, give, contact (with map), search, 404.
- **Phase 5 (done):** Admin dashboard — session auth with lockout, RBAC across 5 roles, dashboard home with Chart.js graphs, and CRUD modules for every content type (see below).
- **Phase 6 (in progress):** SEO basics (sitemap.php, robots.txt), `.htaccess` hardening, Composer manifest. Still open: a hands-on mobile/accessibility pass in a real browser, and supplying real photography/logo assets.

## Requirements

- PHP 8.2+ with the `pdo_mysql` extension
- MySQL 8 or MariaDB
- Composer (for PHPMailer — only needed to actually send email; forms degrade gracefully without it)
- A way to serve PHP locally (PHP's built-in server is enough for development)

## Setup

1. **Create the database:**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```
2. **Configure environment:** copy `.env.example` to `.env` and fill in your DB and SMTP credentials:
   ```bash
   cp .env.example .env
   ```
3. **Install PHPMailer** (needed for the contact form and newsletter sending — both endpoints work and store data without it, they just won't email anyone until this is installed and SMTP_* is set in `.env`):
   ```bash
   composer install
   ```
4. **Default admin login:** the seed creates `admin@southeasternarchdeaconry.org`. Its seeded password hash is a placeholder — before using the admin dashboard, generate a real hash and update the row:
   ```bash
   php -r "echo password_hash('YourNewPassword!', PASSWORD_BCRYPT), PHP_EOL;"
   ```
   ```sql
   UPDATE users SET password_hash = '<paste hash>' WHERE email = 'admin@southeasternarchdeaconry.org';
   ```
5. **Run the dev server** from the `public/` directory (this is the webroot):
   ```bash
   php -S localhost:8000 -t public
   ```
   Visit `http://localhost:8000` for the public site, `http://localhost:8000/admin/login.php` for the dashboard.
6. **Uploads folder permissions:** ensure `public/uploads/**` is writable by the webserver process (`chmod -R 775` on Linux, or grant the IIS/Apache service account write access on Windows).
7. **Production web server:** point the document root at `public/` only — `config/`, `database/`, `admin/`, and `vendor/` should sit *outside* the web-servable root, or be blocked at the server level, so `.env` and the schema files are never fetchable over HTTP. `public/.htaccess` and `public/uploads/.htaccess` are included for Apache; if deploying to Nginx or IIS, translate their rules (deny PHP execution under `/uploads/`, custom 404 page) into the equivalent server config.

## Admin dashboard modules

All under `/admin/modules/`, each gated by role (see `MODULE_ROLES` in `admin/includes/admin-functions.php`):

| Module | Roles (besides SuperAdmin, which sees everything) |
|---|---|
| Bishops, Archdeacons, Letters | Bishop's Office |
| Clergy, Churches, Organizations | Registrar |
| Blog, Static Pages | Communications, Editor |
| Newsletters, Events, Media, Testimonials | Communications |
| Employees, Users & Roles, Settings, Activity Log | SuperAdmin only |

Notes on a few modules:
- **Events** also manages the liturgical calendar (`lectionary.php`/`lectionary-form.php` alongside `index.php`/`form.php`).
- **Newsletters** sends to confirmed subscribers via PHPMailer and tracks opens through a pixel at `public/pages/newsletter-track-open.php` (public, unauthenticated by design — it's loaded by email clients).
- **Activity Log** is intentionally read-only — no edit/delete UI, since an editable audit trail isn't one.
- **Letters** are served through `public/pages/letter-download.php`, which re-checks `visibility` server-side on every request — the raw file path is never linked directly, so `clergy_only`/`staff_only` documents can't be fetched by guessing the upload URL.

## Folder structure

```
public/            Web root — index.php, pages/, includes/, assets/, uploads/
admin/              Admin dashboard — modules/, includes/ (auth guard, layout, RBAC)
config/             .env loader, PDO connection, app bootstrap
database/           schema.sql, seed.sql
vendor/             Composer dependencies (PHPMailer) after `composer install`
```

## Notes

- All SQL uses PDO prepared statements — never concatenate user input into queries.
- The Suffragan Bishop page renders from `dioceses_bishops WHERE type='suffragan' AND is_current=1` — no bishop's name is hardcoded in template code. The admin Bishops module auto-archives (`is_current = 0`) the previous holder of the same type when a new one is marked current, same pattern as the Pro-Cathedral flag on Churches.
- `public/assets/images/logo.png` and `placeholder.jpg` are referenced but not yet supplied — add real assets before going live.
- Bootstrap, Quill, Chart.js, Leaflet, and Bootstrap Icons are loaded via CDN — the production server needs outbound internet access for those requests, or you should vendor them locally if deploying somewhere offline/air-gapped.
- No PHP interpreter was available in the environment this was built in, so files were written carefully against a single vetted reference module (`admin/modules/churches/`) but have not been execution-tested. Run through the golden paths (login, create/edit/delete in each module, public form submissions) in a real PHP environment before considering this production-ready.
