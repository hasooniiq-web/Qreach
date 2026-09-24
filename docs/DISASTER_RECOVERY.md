# Disaster Recovery

## Current status: not implemented

No backup automation, restore testing, or documented recovery procedure exists yet for this project. This document records the plan so it isn't lost.

## What production needs (none of this exists yet)

1. **Automated encrypted Postgres backups** — daily full backup at minimum (e.g., `pg_dump` piped to encrypted storage, or the hosting provider's managed backup feature), with a retention window long enough to cover the audit-log retention policy (see `docs/DATABASE.md`/section 52 discussion).
2. **Tested restore procedure** — per the spec's own principle: "a backup that has never been restored/tested must not be considered reliable." A quarterly (at minimum) restore-to-a-scratch-environment drill should be scheduled once backups exist.
3. **Object storage backup** for QR image files (currently stored on local disk under `storage/app/private/qr/` — see `docs/DATABASE.md`; these are regenerable from the `tags` table data since the QR content is just the public URL, so losing them is recoverable by re-rendering rather than requiring a traditional backup, but this hasn't been built as a re-render tool yet).
4. **Runbook** for what to do when: the primary database is unreachable, a bad migration needs rolling back, or the queue backs up unprocessed.

## What's recoverable today without any DR tooling

Because QR/NFC images are deterministically derivable from `tags.public_token` (the image just encodes `{app_url}/t/{token}`), losing the rendered PNG/SVG files is not data loss — a script re-running `QrBatchService`'s render step for existing tag rows would regenerate them identically. This wasn't built as a standalone recovery tool, but it's worth knowing the underlying data isn't actually at risk from a lost `storage/` directory the way the database would be.

## Recommendation before production launch

Do not launch with real customer data until at least items 1 and 2 above exist and have been exercised once. This is explicitly called out because it's the kind of gap that's easy to defer indefinitely until it's needed under pressure.
