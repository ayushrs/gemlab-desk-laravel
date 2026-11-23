# GemLab Desk — Laravel edition

This is a Laravel 12 rebuild of the supplied Flask GemLab Desk MVP. It preserves the same business workflow and the supplied customer/staff interface, while replacing Flask + SQLite with Laravel + MySQL.

> **Important:** This is an independent demonstration workflow system. It is not an official GTL Jaipur, GJEPC or IIGJ Lab application. Do not use demo output as an official gemological certificate. Scientific methods, terminology, conclusions, certificate wording and approval processes must be validated by qualified laboratory personnel before real-world use.

## Included workflow

- Public customer testing request form
- Customer matching by registered phone number
- Customer and order records
- Sample registration and sample-to-order relationship
- Laboratory method, observation and result records
- Report issuance gated on at least one recorded test
- PDF report with QR verification link
- Public report-number verification and public report download
- Customer order tracking using order number + registered phone number
- Payment recording and payment status
- Dashboard metrics and recent orders
- Role-aware actions for reception, accounts, dispatch, report management and gemmologist roles
- Admin audit log endpoint
- Laravel validation, CSRF protection, session authentication, password hashing and login throttling

## Requirements

- PHP 8.2 or newer
- Composer 2
- MySQL 8 / MariaDB 10.6+
- PHP extensions required by Laravel and its PDF/QR packages (including PDO MySQL, fileinfo, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd or Imagick)

## Local setup (Windows / XAMPP)

1. Extract this project to a folder such as `C:\xampp\htdocs\gemlab-desk-laravel`.
2. Open that folder in VS Code.
3. In terminal, run:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

4. Create a MySQL database in phpMyAdmin named `gemlab_desk` (utf8mb4).
5. Update `.env` with your MySQL username/password and set:

```env
APP_URL=http://127.0.0.1:8000
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gemlab_desk
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations and create your own administrator account:

```bash
php artisan migrate
php artisan gemlab:make-admin admin admin@example.com
```

The command prompts for the administrator's display name and a password of at least 12 characters. There is no hardcoded demo password.

7. Start Laravel:

```bash
php artisan serve
```

8. Open `http://127.0.0.1:8000`. Staff sign-in is at `/login`.

## Main URLs

- `/` — customer request and tracking portal
- `/login` — staff sign-in
- `/staff` — laboratory dashboard
- `/verify` — public report verification

## Database tables

`users`, `customers`, `orders`, `samples`, `tests`, `reports`, `payments`, `shipments`, `audit_logs`.

MySQL stores structured records. Uploaded documents/images, when that feature is added, should be stored on private/persistent server storage and referenced by path in the database rather than stored as base64 blobs.

## API endpoints retained from the Flask version

- `GET /api/me`
- `GET /api/dashboard`
- `GET|POST /api/customers`
- `GET|POST /api/orders`
- `GET|POST /api/samples`
- `GET|POST /api/tests`
- `GET /api/lookups`
- `POST /api/orders/{id}/status`
- `POST /api/orders/{id}/payment`
- `POST /api/samples/{id}/issue`
- `GET /api/reports`
- `GET /api/verify/{reportNo}` (public)
- `GET /api/audit` (Admin)
- `GET /reports/{id}.pdf` (staff)
- `GET /public/reports/{reportNo}.pdf` (public issued report only)

POST routes are in Laravel's web middleware and require a valid CSRF token. The supplied JavaScript includes it automatically.

## cPanel deployment outline

1. Confirm the hosting plan supports the PHP version required by Laravel 12 (PHP 8.2+), Composer or a locally built `vendor` directory, and MySQL.
2. Upload the project outside the public web root if possible.
3. Point the domain/subdomain document root to the Laravel project's `public` directory. Do not expose `.env`, `vendor`, `storage`, or the project root publicly.
4. Set production `.env` values: `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, correct `APP_URL`, and MySQL credentials.
5. Ensure `storage` and `bootstrap/cache` are writable by the PHP process.
6. Run `php artisan migrate --force`, `php artisan storage:link` if/when public storage is used, and `php artisan optimize`.
7. Enforce HTTPS and configure scheduled encrypted backups of the database and private files.

If the host cannot point a domain to the Laravel `public` directory, we should adjust the hosting layout safely rather than moving `.env` or other private files into `public_html`.

## Production checklist before real laboratory use

This is an MVP conversion, not a certified LIMS. Before handling real customer records or valuable stones:

- Add staff account administration, password reset, MFA and granular permissions.
- Add immutable sample custody events, barcode labels, tamper-evident packaging and dual-control handoffs.
- Add report draft/review/approval separation, version history, revocation and digital signatures.
- Add complete shipment management, GST invoices, refunds, reconciliation and notification delivery.
- Add encrypted off-site backups, retention policy, disaster recovery and monitoring.
- Add automated feature tests and a security review.
- Validate all laboratory methods and certificate content with qualified gemmologists.
- Replace demonstration branding and disclaimer only after the organisation has approved the final identity and report language.

## Verification performed for this package

PHP syntax was checked for the generated controllers, models, migrations, routes, commands and configuration files. The Laravel runtime and Composer dependency installation could not be executed in this environment because Composer is not installed here. Run `composer install` and the local smoke tests above before deployment.
