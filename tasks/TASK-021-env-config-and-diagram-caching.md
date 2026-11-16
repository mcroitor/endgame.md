# TASK-021: Environment-Based Configuration and Diagram HTTP Caching

**Priority:** High  
**Reference:** Deployment & Performance Optimization

## Scope
Externalize environment-specific settings from `config.php` so production/Docker deployments do not rely on hardcoded `localhost` or `debug = true`, and add HTTP/ETag caching to the diagram generator (`modules/diagram/index.php`) to speed up search result grids.

## Affected Files
- `config.php`
- `config.sample.php`
- `docker-compose.yaml`
- `modules/diagram/index.php`

## Implementation Checklist
1. **Environment Configuration (`config.php`, `config.sample.php`, `docker-compose.yaml`)**
   - Read `APP_WWW`, `APP_DB_PATH` / `APP_DSN`, and `APP_DEBUG` from `getenv()` with safe local defaults.
   - Untrack environment-specific overrides if needed or document `.env` / Docker environment variables in `README.md` and `config.sample.php`.
2. **Diagram Caching (`modules/diagram/index.php`)**
   - Compute a deterministic `ETag` from the sanitized `fen`, `size`, `color`, and `coordinates` query parameters.
   - Handle `If-None-Match` requests by returning `304 Not Modified` without invoking GD rendering.
   - Send `Cache-Control: public, max-age=31536000, immutable` headers on valid PNG responses.

## Acceptance Criteria
- [x] Application base URL, database DSN, and debug mode can be configured via environment variables in Docker and local setups.
- [x] Repeated requests for the same FEN diagram return `304 Not Modified` or are served from browser cache with `Cache-Control` headers.
