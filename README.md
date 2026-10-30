# FitTrack

A fitness tracking web application built with native PHP and PostgreSQL as a school project.

## Purpose

FitTrack aims to bring weight, workout, and meal records into one place so users can
review their fitness habits over time. Development starts with recording and editing
data, then adds account-based access and analysis built on those records.

## Development journey

The existing Git history records the foundation of this project:

| Stage | Work completed | Commit references |
| --- | --- | --- |
| Implementation approach | Started with a Laravel scaffold, then switched to native PHP | `3038a94`, `4d1182e`, `c016a8d` |
| Data foundation | Excluded local database credentials, added the initial schema and shared connection | `5e1343d`, `0d7ad06`, `eb04068` |
| Application structure | Added pages and shared navigation, then clarified the nutrition page structure | `de5d705`, `a11d529`, `c02320b` |
| First recording feature | Added weight recording, history, and editing | `fee111f` |

The next milestones extend this foundation. See the [development roadmap](docs/ROADMAP.md)
for their purpose, dependencies, and completion criteria.

## Project status

Work in progress. Features are being developed and published incrementally.
The committed foundation includes the database schema, page structure, shared navigation,
and weight recording with history and editing. The account milestone adds registration,
login, logout, and a private profile with editable fitness goals.

## Account milestone

- Register an account and sign in using a password hash stored in PostgreSQL.
- View your profile and update starting weight, goal weight, and goal dates.
- Protect profile access with a shared session check and form submissions with CSRF tokens.
- Rotate the session ID after authentication and clear the session on sign-out.

Login currently opens the profile. The earlier weight page and placeholder feature pages
are not part of the authenticated account flow yet; their integration is the next milestone.
This development version is not ready for public deployment: login rate limiting,
password recovery, and email verification are not implemented.

## Next milestones

Additional implementation exists in the local working tree and is awaiting review and
feature-focused commits. These milestones are not yet marked as released:

1. Weight tracking connected to the signed-in user
2. Training records and duration summaries
3. Nutrition records, followed by convenience-store product selection
4. Personal dashboards with weight, training, and nutrition analysis
5. Community analytics and a documented demonstration

## Technology stack

- PHP (native PHP; no framework)
- PostgreSQL
- HTML, CSS, and JavaScript

## Project structure

```text
database/      Database schema
includes/      Shared PHP code and database connection
pages/         Application pages
public/        Stylesheets and other browser assets
tests/         HTTP integration checks and test router
index.php      Redirects to sign-in
```

## Local setup

1. Install PHP 8+ with the PDO PostgreSQL and mbstring extensions and start PostgreSQL.
2. Create a development database and apply `database/schema.sql`.
3. Create `config/database.php` with a PDO connection assigned to `$pdo`.
   This file is excluded from Git because it contains local credentials.
4. From the project root, run `php -S localhost:8000`.
5. Open `http://localhost:8000/` and select **Create Account**.

The account flow works with the existing `users` table; no schema change is required
for this milestone. The initial weight page still uses a development user ID in the
committed version and will be connected to authentication in the next milestone.

## Verification

Run `php tests/auth_http_test.php` against your local development database.
The test requires permission to create a disposable PostgreSQL schema and starts a
short-lived HTTP server on a loopback port. It checks registration, password hashing,
login, session rotation, profile access, validation, CSRF rejection, account isolation,
logout, and invalidated accounts. Test data and sessions are removed afterwards;
existing application tables are not modified.

HTTP checks and PHP syntax checks have passed. Browser layout verification remains pending.

## Development approach

Changes are published in small, feature-focused commits. Each update should include
the PHP, styles, and database changes it needs, with checks performed before publishing.
Milestones follow functional dependencies rather than a daily posting schedule.
Each completed milestone documents the problem addressed, the resulting behavior,
and the checks performed. Existing commits remain part of the development history.
