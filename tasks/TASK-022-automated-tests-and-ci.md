# TASK-022: Automated Tests and GitHub Actions CI

**Priority:** High  
**Reference:** Code Quality & Regression Prevention

## Scope
Introduce a lightweight automated test suite covering core PHP components and API endpoints, and configure a GitHub Actions workflow to run PHP syntax checks and tests on every push and pull request.

## Affected Files
- `composer.json` (or `site/composer.json`)
- `tests/` (new directory)
- `.github/workflows/ci.yml` (new workflow)

## Implementation Checklist
1. **Unit Tests (`tests/unit/`)**
   - Test `Pgn::parse()` and `PGNParser` with valid, minimal, and malformed PGN inputs.
   - Test `Board::is_correct_epd()` / `Diagram` validation for valid FENs, missing ranks, invalid piece letters, and invalid color options.
   - Test `\mc\sql\query` SQL generation (`SELECT`, `WHERE`, `ORDER BY`, `LIMIT`) and `\mc\sql\database` CRUD operations against an in-memory SQLite database.
2. **API Smoke Tests (`tests/integration/`)**
   - Verify `api/?q=data` filtering (author, piece counts, date range, stipulation, cook, sorting) and `api/?q=pgn/<id>`.
   - Verify `/?q=pgn/<id>` returns `text/plain` PGN content and `modules/diagram/?fen=...` returns a valid PNG image.
3. **CI Workflow (`.github/workflows/ci.yml`)**
   - Run `php -l` across all `.php` files in `site/`.
   - Run the PHPUnit test suite on PHP 8.1+ with `gd` and `pdo_sqlite` extensions enabled.

## Acceptance Criteria
- [x] Running the test suite locally executes unit and API smoke tests cleanly without mutating the production/sample SQLite file.
- [x] GitHub Actions CI passes on `main` and fails on PHP syntax errors or test regressions.
