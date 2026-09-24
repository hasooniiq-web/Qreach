# UI/UX Design System

## Brand

Only three colors, per spec section 5 — no exceptions:

| Token | Hex | Usage |
|---|---|---|
| `--color-brand-yellow` | `#FFC107` | Primary actions, active nav state, highlights — used sparingly, never as a background wash |
| `--color-brand-black` | `#000000` | Sidebar, primary buttons, headings |
| `--color-brand-white` | `#FFFFFF` | Page/card backgrounds |

Neutral grays (`--color-ink-50` through `--color-ink-900`) and two semantic status colors (`--color-status-success`, `--color-status-danger`, `--color-status-warning`) are derived for text/borders/states — never a hue outside this family. All tokens are defined once in `backend/resources/css/app.css`'s Tailwind v4 `@theme` block; components reference them as Tailwind utilities (`bg-brand-yellow`, `text-status-danger`), never raw hex values.

Typography: **IBM Plex Sans Arabic** throughout (loaded via Google Fonts), Arabic as the default and only shipped locale for the dashboard and public pages.

## RTL

`dir="rtl"` on `<html>` for every dashboard/public page. This alone is not treated as sufficient (per the spec's explicit warning) — verified specifically:

- Sidebar sits on the physical right via flex order, not a manual `float`/`margin` hack
- Icons using directional strokes (e.g., back/forward chevrons) — none currently in use that would need RTL-mirroring
- Form inputs, tables, and badges all read right-to-left naturally since they're plain block/inline-flex layouts with no hardcoded `left`/`right` physical properties — logical properties (`ms-`/`me-`, `start`/`end`) used wherever direction-sensitive spacing was needed (e.g., the mobile nav's `-ms-2`)
- Numbers (phone numbers, tabular data) are wrapped in `dir="ltr"` spans so they read in the conventional numeral direction inside an RTL sentence

## Component patterns actually built

- **Status badges** (`x-status-badge`): one component, a lookup table of status → color/label, used identically for user status, vehicle status, tag lifecycle status, and contact-request status — not four different ad-hoc badge implementations.
- **Icons** (`x-icon`): a small hand-authored outline SVG set (1.5px stroke, 24×24 grid) rather than emoji, per the "no emoji as structural icons" rule. Not a full icon library — only the ~15 icons the dashboard actually uses.
- **Progressive disclosure without JS**: the public scan page's message/contact/emergency forms, and the admin QR revoke action, all use native `<details>/<summary>` instead of a JS-driven accordion. This was also the fix for a real bug: an earlier `onclick`/inline-`<script>` approach was silently blocked by the app's own `script-src 'self'` Content Security Policy, which was the correct behavior — the fix was to stop relying on inline JS, not to weaken the CSP.
- **Mobile navigation drawer**: the admin sidebar collapses to a slide-over drawer below the `lg` breakpoint, with a backdrop, close button, and Escape-key handling — implemented in an external `admin-nav.js` file (again, for CSP compliance) after discovering Vite's `type="module"` scripts execute *after* `DOMContentLoaded` has already fired, so a `DOMContentLoaded` listener registered inside one never runs.

## Responsiveness

Desktop is the primary admin experience (full sidebar, multi-column stat grids). Tablet and mobile are not simplified afterthoughts — the sidebar becomes a proper drawer with all the same navigation, not a hidden/inaccessible menu, which was itself a bug caught and fixed during manual testing (the first cut hid the sidebar below `lg` width with no way to reopen it).

## Known gaps

No dark mode. No charts yet (nothing to chart until the analytics aggregation job exists — see `ARCHITECTURE_DECISIONS.md`). No formal design tokens file for the (not yet built) Flutter app — the same three-color/typography rules would need to be re-expressed in Dart/Flutter theme data when that work starts.
