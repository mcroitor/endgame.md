# Bug Fixing Development Tasks

This directory contains development tasks for resolving the bugs documented in `tasks/bugs.md`.

| Task | Title | Priority | Related Bugs |
| :--- | :--- | :--- | :--- |
| [TASK-001](TASK-001-auth-and-permissions.md) | Fix Authentication, Authorization, and Role Schema | Critical | BUG 001, BUG 002, BUG 003, BUG 018 |
| [TASK-002](TASK-002-core-db-and-management.md) | Fix Core CRUD and Module Management | High | BUG 004, BUG 005 |
| [TASK-003](TASK-003-api-and-frontend-search.md) | Fix API Routing, Date Filtering, and SQL Injection | Critical | BUG 006, BUG 007, BUG 008 |
| [TASK-004](TASK-004-pdf-and-diagram-rendering.md) | Fix PDF Export SQL Injection, Diagram Validation, and Rendering | High | BUG 009, BUG 011, BUG 012, BUG 013 |
| [TASK-005](TASK-005-pgn-upload-and-analytics.md) | Fix Attic, PGN Parser, Endgame Upload, and Analytics | Medium | BUG 014, BUG 015, BUG 016, BUG 017, BUG 019 |
| [TASK-006](TASK-006-cli-tools.md) | Fix CLI Password Reset and PGN Export | Medium | BUG 010, BUG 020 |
| [TASK-007](TASK-007-core-db-and-admin-ui.md) | Fix Core Database Null Handling and User Management UI | High | BUG 021, BUG 031 |
| [TASK-008](TASK-008-pdf-and-diagram-fixes.md) | Fix PDF Export Defaults, Missing PGN Guard, and Diagram Validation | High | BUG 022, BUG 023, BUG 024 |
| [TASK-009](TASK-009-endgame-forms-and-pgn.md) | Fix Endgame Forms, Upload Metadata, Themes, and PGN Download | Medium | BUG 025, BUG 026, BUG 027, BUG 028 |
| [TASK-010](TASK-010-frontend-and-articles-validation.md) | Fix Frontend Form Reset, PGN Viewer Guard, and Article Input Validation | Medium | BUG 029, BUG 030 |

## UI / UX Improvement Tasks

See `tasks/ui/recommendations.md` and `tasks/ui/ui_ex.png` for details.

| Task | Title | Priority | Reference |
| :--- | :--- | :--- | :--- |
| [TASK-011](TASK-011-ui-header-navigation-and-auth.md) | Header, Responsive Viewport, Flexbox Navbar, and Click-Based Auth Menu | High | `ui/recommendations.md` §1 |
| [TASK-012](TASK-012-ui-search-form-and-url-state.md) | Search Form UX, Explicit Filter Toggle, Enter Submit, and URL State Sync | High | `ui/recommendations.md` §2 |
| [TASK-013](TASK-013-ui-study-cards-grid-and-pagination.md) | CSS Grid Study Cards, Card Hierarchy, Copy FEN, Direct PGN, and Full Pagination | High | `ui/recommendations.md` §3 |
| [TASK-014](TASK-014-ui-pgn-modal-and-error-states.md) | PGNLive Modal Backdrop, Keyboard Close, Loading & Error States | Medium | `ui/recommendations.md` §3, §4 |
| [TASK-015](TASK-015-ui-sorting-badges-and-cooked-filter.md) | Backend Sorting, Side-to-Move & Stipulation Badges, and 3-State Cooked Filter | Medium | `ui/recommendations.md` §2, §3 |
| [TASK-016](TASK-016-ui-forms-footer-and-accessibility.md) | Endgame Form Preview, Footer Statistics Polish, Typos, and Accessibility | Low | `ui/recommendations.md` §5 |
| [TASK-017](TASK-017-ui-solve-yourself-mode.md) | Interactive "Solve Yourself" Mode in Solution Modal | High | `ui/recommendations.md` §6 |
| [TASK-018](TASK-018-ui-syzygy-tablebase-integration.md) | Syzygy Endgame Tablebase Lookup for <= 7 Pieces | Medium | Solution Viewer |
| [TASK-019](TASK-019-ui-advanced-pgn-player-and-mobile-gestures.md) | Advanced Solution Player Controls, Collapsible Variations, and Mobile Gestures | Medium | Solution Viewer |

## Security, Infrastructure & Product Enhancement Tasks

| Task | Title | Priority | Reference |
| :--- | :--- | :--- | :--- |
| [TASK-020](TASK-020-security-prepared-statements-and-csrf.md) | Prepared Statements, Password Hashing, and CSRF Protection | Critical | Security Hardening |
| [TASK-021](TASK-021-env-config-and-diagram-caching.md) | Environment-Based Configuration and Diagram HTTP Caching | High | Deployment & Performance |
| [TASK-022](TASK-022-automated-tests-and-ci.md) | Automated Tests and GitHub Actions CI | High | Quality & CI/CD |
| [TASK-023](TASK-023-endgame-create-and-admin-user-edit.md) | Complete `endgame/create`, `piece_pattern` UI, and `user/edit` Route | Medium | Functional Stubs |
| [TASK-024](TASK-024-ui-favorites-history-and-dark-mode.md) | Favorites, Viewing History, Dark Mode, and Moderator Cook Toggle | Low | `ui/recommendations.md` §6 |
| [TASK-025](TASK-025-analytics-access-and-permissions.md) | Enable Admin Access and Enforce Permissions for `analytics` Module | High | Module Access & Auth |
