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

Login opens the profile, which now links to personal weight tracking. Weight history,
saving, editing, and deletion are scoped to the signed-in user. Other feature pages
are still awaiting integration.
This development version is not ready for public deployment: login rate limiting,
password recovery, and email verification are not implemented.

## Next milestones

Additional implementation exists in the local working tree and is awaiting review and
feature-focused commits. These milestones are not yet marked as released:

1. Convenience-store product selection for nutrition records
2. Personal dashboards with weight, training, and nutrition analysis
3. Community analytics and a documented demonstration

## Nutrition milestone

Manually record food, date, calories, and optional protein. History and today's summary
are private to the signed-in user. Calories are summed by date; protein totals are shown
only when every entry has a value. Unknown protein is distinct from a recorded zero.
Entry counts count foods, not meals. Values refer to the amount eaten.

Apply `database/migrations/002_nutrition_records.sql` after the base schema.
It preserves existing tables and data and creates the product table needed by the foreign
key; product import and selection are a later milestone.
See [the Japanese nutrition note](docs/NUTRITION_MILESTONE_JA.md) for examples and checks.

## Training milestone

Record personal workout sessions with optional duration. The last-six-month chart and
source table distinguish session count from distinct training days. The monthly recorded
minutes and all-time average exclude unknown duration and show no value when all durations
are missing. Coverage counts explain how many sessions have recorded duration.

Apply `database/migrations/001_training_records.sql` after the base schema, including on
an existing account/weight installation. It preserves existing training records and can
be run again. No migration was applied to real user data during development testing.
See [the Japanese training note](docs/TRAINING_MILESTONE_JA.md) for definitions and checks.

## Personal weight tracking

- Save one weight per user per day; saving that date again replaces its weight.
- Edit or delete your own entries using CSRF-protected forms.
- Keep other daily data when deleting a weight measurement.
- Review history, a trend chart, and change from the first to latest measurement.
- Show no change comparison until at least two dates are recorded.

Inputs accept dates up to today and weights from 1–300 kg with up to two decimal places.
The server date determines today. This is an input rule, not a health assessment.
See the [Japanese development note](docs/WEIGHT_MILESTONE_JA.md) for the calculation
and verification details.

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
2. Create a development database and apply `database/schema.sql`, then the SQL files in
   `database/migrations/` in numbered order.
3. Create `config/database.php` with a PDO connection assigned to `$pdo`.
   This file is excluded from Git because it contains local credentials.
4. From the project root, run `php -S localhost:8000`.
5. Open `http://localhost:8000/` and select **Create Account**.

The account and weight flows use the existing `users` and `daily_records` tables;
no schema migration is required for these milestones.

## Verification

Run `php tests/auth_http_test.php` against your local development database.
The test requires permission to create a disposable PostgreSQL schema and starts a
short-lived HTTP server on a loopback port. It checks registration, password hashing,
login, session rotation, profile access, validation, CSRF rejection, account isolation,
logout, invalidated accounts, personal weight CRUD, training records and summaries,
and nutrition entry with daily totals. It also verifies malformed
input, same-day replacement, cross-user isolation, and preservation of other daily data.
Test data and sessions are removed afterwards;
existing application tables are not modified.

HTTP checks and PHP syntax checks have passed. Browser layout verification remains pending.

## Development approach

Changes are published in small, feature-focused commits. Each update should include
the PHP, styles, and database changes it needs, with checks performed before publishing.
Milestones follow functional dependencies rather than a daily posting schedule.
Each completed milestone documents the problem addressed, the resulting behavior,
and the checks performed. Existing commits remain part of the development history.
