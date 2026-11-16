# TASK-011: Header, Responsive Viewport, Flexbox Navbar, and Click-Based Auth Menu

**Priority:** High  
**Reference:** `tasks/ui/recommendations.md` (Section 1), `tasks/ui/ui_ex.png`

## Scope
Fix mobile viewport scaling, modernize the top navigation bar with Flexbox and active link highlighting, and replace the `:hover`-only hamburger dropdown with an explicit click-based Login/User menu.

## Affected Files
- `theme/templates/index.template.php`
- `theme/templates/loginform.template.php`
- `theme/templates/usermenu.template.php`
- `theme/css/main.css`
- `index.php` (optional active route class or JS active check)

## Implementation Checklist
1. **Viewport and Header (`theme/templates/index.template.php`, `theme/css/main.css`)**
   - Add `<meta name="viewport" content="width=device-width, initial-scale=1">` inside `<head>`.
   - Reduce `.header` font size so it does not compete with the search form, and wrap the site title in a link to `<!-- www -->/`.
2. **Flexbox Navbar (`theme/templates/index.template.php`, `theme/css/main.css`)**
   - Replace Skeleton `one column` classes on `.navbar-link` items with a Flexbox `.navbar` layout (`display: flex; align-items: center; flex-wrap: wrap; gap: 1.5rem;`).
   - Rename `FENBuilder` label to `Position Builder (FEN)` (or localized equivalent) and fix the relative `/../builder/` path if needed.
   - Apply `.navbar-link.active` to the current route.
3. **Auth & User Menu (`theme/templates/loginform.template.php`, `theme/templates/usermenu.template.php`, `theme/css/main.css`)**
   - Replace the `:hover` `.dropdown` with an explicit **Log In** button/trigger in the navbar (and a responsive menu button with `aria-label` on narrow screens).
   - Open the login form / user menu on `click` with `position: absolute; right: 0; top: 100%; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.12);` and close on outside click or `Escape`.
   - Remove the duplicate `id="login-form"` from the `Log Out` link in `usermenu.template.php`.

## Acceptance Criteria
- [ ] Site renders at native 1:1 scale on mobile viewports without horizontal overflow.
- [ ] Navbar links do not wrap awkwardly or collide (`Position Builder (FEN)` fits cleanly), and the active page link is highlighted.
- [ ] Login / User menu opens on click/tap, does not shift page layout, and closes on outside click or `Escape`.
