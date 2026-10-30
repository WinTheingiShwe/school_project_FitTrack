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
and weight recording with history and editing.

## Next milestones

Additional implementation exists in the local working tree and is awaiting review and
feature-focused commits. These milestones are not yet marked as released:

1. User registration, login, and profile management
2. Weight tracking connected to the signed-in user
3. Training records and duration summaries
4. Nutrition records, followed by convenience-store product selection
5. Personal dashboards with weight, training, and nutrition analysis
6. Community analytics and a documented demonstration

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
index.php      Root entry point (currently a placeholder)
```

## Local setup

1. Install PHP with the PDO PostgreSQL extension and start PostgreSQL.
2. Create a development database and apply `database/schema.sql`.
3. Create `config/database.php` with a PDO connection assigned to `$pdo`.
   This file is excluded from Git because it contains local credentials.
4. From the project root, run `php -S localhost:8000`.
5. Open the relevant page under `http://localhost:8000/pages/`.

Authentication and a complete onboarding flow are still part of the planned rollout.
The initial weight page uses a development user ID, so it requires a matching test user
in the local database until the authentication update is published.

## Development approach

Changes are published in small, feature-focused commits. Each update should include
the PHP, styles, and database changes it needs, with checks performed before publishing.
Milestones follow functional dependencies rather than a daily posting schedule.
Each completed milestone documents the problem addressed, the resulting behavior,
and the checks performed. Existing commits remain part of the development history.
