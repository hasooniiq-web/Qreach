# SmartTag/Qreach Design System v2 (Redesign Phase 3)

Builds on `docs/UI_UX_REDESIGN_AUDIT.md`'s findings. This is the token/
component spec implementation follows — kept deliberately compact since the
real deliverable is the code, not the document.

## Spacing scale (both platforms)

`4 · 8 · 12 · 16 · 24 · 32 · 48` — no other values. Flutter: `lib/core/theme/app_spacing.dart`. Tailwind: already on the 4px scale by default (`p-1`=4px … `p-12`=48px); the fix needed is *discipline*, not new tokens — stop introducing one-off values.

## Typography scale

| Role | Flutter (`TextTheme`) | Tailwind | Weight |
|---|---|---|---|
| Display (rare — hero numbers only) | `displaySmall` | `text-3xl` | 700 |
| H1 (screen title) | `headlineSmall` | `text-xl` | 700 |
| H2 (section header) | `titleMedium` | `text-base` | 600 |
| Body | `bodyMedium` | `text-sm` | 400 |
| Caption/meta | `bodySmall` | `text-xs` | 400/500 |

Never introduce a font size outside this ramp. IBM Plex Sans Arabic stays the only typeface on both platforms (already correct — preserve).

## Color — unchanged, already disciplined

`#FFC107` / `#000000` / `#FFFFFF` + the existing `ink50…ink900` neutral ramp + three semantic colors (`success`/`danger`/`warning`). The audit found no violations of this; the redesign must not introduce any new hues.

## Component inventory (new shared components to build)

| Component | Platform | Replaces |
|---|---|---|
| `AppCard` | Flutter (`shared/widgets/app_card.dart`) | Every hand-written `Card(...)` with the same 12px-radius/`ink200`-border recipe |
| `StatusBadge` | Flutter (`shared/widgets/status_badge.dart`) | `vehicle_detail_screen.dart`'s private `_StatusChip` — generalized so tag status, vehicle protection status, and future statuses share one visual language |
| `ProfileMenuButton` | Flutter (`features/auth/presentation/profile_menu_button.dart`) | The avatar `PopupMenuButton` duplicated inline in `vehicle_list_screen.dart` |
| `<x-card>` | Blade | The repeated `rounded-lg border border-ink-200 bg-white p-5` recipe in every admin view |
| `<x-kpi>` | Blade | The 8 identical cards in `dashboard/index.blade.php` |

Dashboard/table component work (`<x-data-table>`, sidebar IA regroup) is scoped to Phases 8–10 — mobile ships first per the audit's priority call.

## Mobile navigation & home IA (Phase 4 target)

No bottom tab bar is introduced — the audit found no navigation-depth problem that justifies one yet (3 real top-level destinations: Home, Vehicles, Profile; a 4th, Notifications, has no backend data source — see below). Structure instead:

- **New `/home` route** becomes the post-login landing screen (previously `/vehicles`).
- **Header**: greeting (name if set, else phone) + `ProfileMenuButton` (reused as-is from the prior turn's work).
- **Primary vehicle section**: the first vehicle from `vehicleListControllerProvider`, showing its SmartTag status (derived from real `tags` data already fetched — "protected" if any tag has `status == 'assigned'`, not fabricated) via `StatusBadge`.
- **Quick actions**: المركبات (→ `/vehicles`), إضافة مركبة (→ `/vehicles/new`), التنبيهات (→ new `NotificationsScreen`), الملف الشخصي (→ `/profile`).
- **Notifications**: no customer-facing notifications API exists yet (confirmed absent from `docs/API.md`) — building a real feed would either fabricate data or call an endpoint that doesn't exist. `NotificationsScreen` ships as an honest empty-state screen ("قريبًا"), the same pattern the admin dashboard already uses for its own not-yet-built nav entries, not silently hidden.
- **Vehicles list stays exactly where it is** (`/vehicles`) as the dedicated full-list destination reachable from Home's quick action — not merged into Home, since Home is a *summary*, not a duplicate list.
