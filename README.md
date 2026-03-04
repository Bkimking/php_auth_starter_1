# 🔐 PHP Auth Starter

A clean, beginner-friendly authentication project built in **plain PHP 8.0+** with MySQL, PHPMailer, and zero frameworks. Designed to teach backend fundamentals through real, working code.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Folder Structure](#folder-structure)
3. [How Authentication Works](#how-authentication-works)
4. [How Password Reset Works](#how-password-reset-works)
5. [Installation Guide](#installation-guide)
6. [Environment Variables](#environment-variables)
7. [PHP & PHPMailer Compatibility](#php--phpmailer-compatibility)
8. [Security Features](#security-features)
9. [Future Improvements](#future-improvements)

---

## Project Overview

This project implements:

- **User registration** with role selection (admin / user)
- **Login** with session-based authentication
- **Logout** with full session destruction
- **Forgot password** flow with time-limited email tokens
- **Role-based dashboards** — admins and users see different pages
- **CSRF protection** on every form
- **Prepared statements** (PDO) on every database query

The code is heavily commented so you can read it top-to-bottom and understand every decision.

---

## Folder Structure

```
/project-root
│
├── index.php               ← Landing page (redirects if logged in)
├── composer.json           ← PHPMailer dependency definition
├── .env                    ← Your local secrets (never commit this!)
├── .env.example            ← Template — copy this to .env
├── README.md               ← You are here
│
├── config/
│   ├── env.php             ← Reads .env file into PHP environment
│   ├── database.php        ← Creates PDO database connection
│   └── mail.php            ← Configures PHPMailer + reset email function
│
├── auth/
│   ├── register.php        ← Registration form + handler
│   ├── login.php           ← Login form + handler
│   ├── logout.php          ← Destroys session, redirects
│   ├── forgot-password.php ← Email input form
│   └── reset-password.php  ← New password form (token required)
│
├── dashboards/
│   ├── admin.php           ← Admin-only dashboard (role check)
│   └── user.php            ← Regular user dashboard
│
├── includes/
│   ├── header.php          ← <head>, navbar, flash message output
│   ├── footer.php          ← </body></html> closing tags
│   ├── auth-middleware.php ← requireLogin() / requireRole() functions
│   └── functions.php       ← CSRF, sanitize, flash, session helpers
│
├── assets/
│   └── css/style.css       ← All styles (no CSS framework required)
│
├── sql/
│   └── schema.sql          ← Database + users table + sample data
│
└── vendor/                 ← Created by Composer (don't edit)
```

---

## How Authentication Works

### Registration
1. User fills out the form (name, email, password, role)
2. Server validates every field (length, format, password strength)
3. Server checks the email isn't already registered
4. Password is hashed with `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`
   - Bcrypt is a **one-way hash** — once hashed, the original password is gone
   - Even if the database is stolen, attackers can't reverse the hash
5. A new row is inserted into `users` using a PDO prepared statement
6. Session variables are set (`user_id`, `name`, `email`, `role`)
7. User is redirected to their dashboard

### Login
1. User submits email + password
2. Server looks up the user by email
3. `password_verify($submitted, $storedHash)` checks the credentials
   - We use the **same error message** whether email OR password is wrong (prevents user enumeration)
4. `session_regenerate_id(true)` creates a new session ID (prevents session fixation)
5. Session is populated and user is redirected

### Session-Based Auth
- PHP's `$_SESSION` array is stored server-side
- The browser only holds a session ID cookie
- `requireLogin()` in `auth-middleware.php` checks `$_SESSION['user_id']` on protected pages
- `requireRole('admin')` additionally checks `$_SESSION['role']`

### Logout
1. `$_SESSION = []` clears all session data
2. The session cookie is expired in the browser
3. `session_destroy()` deletes the server-side session file

---

## How Password Reset Works

```
User                     Server                      Email
 │                          │                           │
 ├─ Submits email ─────────►│                           │
 │                          ├─ Generate random token    │
 │                          ├─ Store token + expiry ───►│ (in database)
 │                          ├─ Send reset email ───────►│
 │◄─ "Check your inbox" ────┤                           │
 │                          │                           │
 ├─ Clicks link in email ──►│ (token in URL param)      │
 │                          ├─ Validate token           │
 │                          ├─ Check expiry (1 hour)    │
 │◄─ New password form ─────┤                           │
 │                          │                           │
 ├─ Submits new password ──►│                           │
 │                          ├─ Hash new password        │
 │                          ├─ UPDATE users SET         │
 │                          │    password = hash,       │
 │                          │    reset_token = NULL     │
 │◄─ Redirect to login ─────┤                           │
```

Key security decisions:
- **Token is random**: `bin2hex(random_bytes(32))` = 64 cryptographically random hex chars
- **Token expires**: `reset_token_expiry > NOW()` — invalid after 1 hour
- **Token is single-use**: cleared from the database after the password is updated
- **Same response for missing emails**: prevents attackers from discovering valid email addresses

---

## Installation Guide
### Prerequisites
Make sure these are installed on your computer:

| Tool | Version | Download |
|------|---------|----------|
| XAMPP | 8.2+ | https://www.apachefriends.org/download.html |
| Composer | latest | https://getcomposer.org |

### Step 1 — Clone or Download the Project
```bash
git clone https://github.com/your-username/auth-project.git
cd auth-project
```
Or just download the ZIP and extract it.

### Step 2 — Install PHPMailer via Composer
```bash
composer require phpmailer/phpmailer
```
This downloads PHPMailer into the `vendor/` folder and creates `vendor/autoload.php`.

### Step 3 — Configure Your Environment
```bash
cp .env.example .env
```
Open `.env` in any text editor and fill in your values:

```env
APP_ENV=development

DB_HOST=localhost
DB_NAME=auth_project_1
DB_USER=root
DB_PASS=your_mysql_password

MAIL_HOST=smtp.mail
MAIL_PORT=2525
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
MAIL_FROM=no-reply@example.com

APP_URL=http://localhost:8000
```

### Step 4 — Create the Database
Open MySQL and run the schema file:

```bash
mysql -u root -p < sql/schema.sql
```

Or open MySQL Workbench / phpMyAdmin and paste the contents of `sql/schema.sql`.

This creates:
- The `auth_project_1` database
- The `users` table
- Two demo accounts (admin and user, both with password `Password123!`)

### Step 5 — Configure SMTP (for password reset emails)

**For local testing** — use [Mailtrap](https://mailtrap.io):
1. Create a free account at mailtrap.io
2. Go to Inboxes → SMTP Settings
3. Copy the Host, Port, Username, and Password into your `.env`

**For production** — use a real SMTP provider:
- [SendGrid](https://sendgrid.com) (free tier: 100 emails/day)
- [Mailgun](https://mailgun.com)
- [Postmark](https://postmarkapp.com)

### Step 6 — Start the Development Server
```bash
php -S localhost:8000
```

Open your browser and visit: **http://localhost:8000**

### Demo Accounts
To create users, navigate to the registration page (`/auth/register.php`) in your browser. You can select 'admin' or 'user' roles during registration.

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | Password123! |
| User | user@example.com | Password123! |

---

## Environment Variables

### Why `.env` Matters

Imagine you build this project and push it to GitHub. If your database password and SMTP credentials are hard-coded in your PHP files, **anyone can read them**.

Instead, we:
1. Put secrets in `.env` (which is in `.gitignore`)
2. Read them at runtime with `getenv()`
3. Commit only `.env.example` (with empty values) so other developers know what to fill in

This is the **twelve-factor app** methodology — widely used in professional development.

### How `.env` Loading Works

In `config/env.php`, the `loadEnv()` function:
1. Reads every line of the `.env` file
2. Skips blank lines and comments (`#`)
3. Splits each line on the first `=` sign
4. Calls `putenv()` and sets `$_ENV[]` for each key-value pair

Every page calls `require_once '../config/env.php'` at the top, so variables are always loaded.

---

## PHP & PHPMailer Compatibility

### PHP Version
| PHP Version | Support |
|-------------|---------|
| 8.2+ | ✅ Fully supported |
| 8.1 | ✅ Fully supported |
| 8.0 | ✅ Minimum required |
| 7.x | ❌ Not supported (uses PHP 8 features) |

This project uses:
- `str_starts_with()` — added in PHP 8.0
- `str_contains()` — added in PHP 8.0
- Named arguments in `array_filter()` — PHP 8.0
- Constructor property promotion (not used, but available)

### PHPMailer Version
**PHPMailer 6.x** is required. It supports PHP 5.5 through PHP 8.x.

```bash
composer require phpmailer/phpmailer
# Installs: phpmailer/phpmailer ^6.8
```

**Why PHPMailer instead of `mail()`?**
- PHP's `mail()` doesn't support SMTP authentication
- `mail()` emails often end up in spam
- PHPMailer supports HTML emails, attachments, CC, BCC
- PHPMailer provides real error messages when things go wrong
- PHPMailer is used by millions of PHP projects (battle-tested)

---

## Security Features

| Feature | Where | Why |
|---------|-------|-----|
| Password hashing (bcrypt) | `register.php`, `reset-password.php` | Hashes can't be reversed |
| `password_verify()` | `login.php` | Constant-time comparison |
| Prepared statements | All DB queries | Prevents SQL injection |
| CSRF tokens | All forms | Prevents cross-site request forgery |
| `session_regenerate_id()` | `login.php` | Prevents session fixation |
| `htmlspecialchars()` | `sanitize()` helper | Prevents XSS output |
| Input validation | All form handlers | Rejects bad data early |
| Generic error messages | `login.php` | Prevents user enumeration |
| Token expiry (1 hour) | `forgot-password.php` | Limits reset window |
| Single-use tokens | `reset-password.php` | Token cleared after use |

---

## Future Improvements

Once you're comfortable with this codebase, here are things to add:

1. **Profile editing** — Let users change their name and email
2. **Change password** — Authenticated password update (verify old password first)
3. **Remember me** — Persistent login via a secure cookie
4. **Email verification** — Verify email on registration before allowing login
5. **Login rate limiting** — Block brute-force attacks (e.g., 5 failed attempts → 15 min lockout)
6. **Two-factor authentication (2FA)** — TOTP via Google Authenticator
7. **Admin user management** — Admins can edit/delete/suspend users
8. **Audit log** — Record login events with IP address and timestamp
9. **Password strength meter** — JavaScript real-time feedback
10. **OAuth** — "Log in with Google" via a library like OAuth 2.0 Client
11. **Namespaced classes** — Refactor into proper OOP with PSR-4 autoloading
12. **Unit tests** — PHPUnit tests for auth functions

---

## Contributing

This is a learning project — issues and PRs welcome!

---

## License

MIT — use it however you like.