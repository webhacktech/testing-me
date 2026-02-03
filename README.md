# NaijaCryptoX

A procedural PHP crypto exchange platform tailored for Nigerian users, deployable on cPanel LAMP hosting.

## Setup

1. Create a MySQL database and user in cPanel.
2. Import `database/schema.sql` into your database (phpMyAdmin).
3. Set these environment variables in cPanel (or add them to `includes/config.php` if your host does not support env vars):
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `BASE_URL` (optional, set when hosting in a subdirectory)
   - `SUPPORT_EMAIL`
   - `MAIL_FROM`
   - `MAIL_FROM_NAME`
   - `MAIL_HOST`
   - `MAIL_USERNAME`
   - `MAIL_PASSWORD`
   - `MAIL_PORT`
   - `MAIL_SECURE`
4. Visit `/admin/setup.php` to create the first admin account.
5. Register a user via `/public/register.php` and complete KYC.

## Notes

- All trades and withdrawals require admin approval.
- Update coin rates and USD/NGN rate in the admin dashboard.
- Selfie uploads are saved in `uploads/selfies`.
- Email verification is required before login. Use PHPMailer (via `vendor/autoload.php`) if available, or the PHP `mail()` fallback.
