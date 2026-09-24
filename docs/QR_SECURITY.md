# QR/NFC Tag Security

## Token design

Each tag's `public_token` is 32 cryptographically random bytes (`random_bytes(32)`, PHP's CSPRNG), base64url-encoded to a ~43-character string, with a uniqueness check against the database before use (`App\Services\TagTokenGenerator::generatePublicToken`).

This is a deliberate correction of a common spec-reading mistake: **QR code visual capacity/error-correction level and token cryptographic entropy are unrelated properties.** A QR code's "version" and error-correction level (we use `H`, the highest, for print durability) determine how much data and damage-tolerance the *visual code* has — they say nothing about how hard the *token itself* is to guess. 256 bits of entropy gives an astronomically large search space regardless of how the token is visually encoded.

The token is:
- Not sequential (random, not auto-increment)
- Not derived from `user_id`, `vehicle_id`, or the internal `tags.id`
- Never exposed anywhere the internal `id`/`uuid` could leak alongside it — `Tag` hides both from serialization (`$hidden = ['id', 'uuid']`)

## Lifecycle

`generated → available → assigned → (revoked | replaced)`. See `backend/database/migrations/..._create_tags_table.php` for the full enum and `docs/DATABASE.md` for why QR and NFC share this lifecycle via a common `tags` parent table.

## One-time activation: the concurrency guarantee

This is the single most security-critical code path in the system (`app/Services/TagLifecycleService::activate`). The guarantee comes from **two independent, deliberately redundant layers**:

1. `Tag::where('public_token', $token)->lockForUpdate()->first()` inside a DB transaction — serializes concurrent requests for the same row at the database level.
2. The actual state change is a **conditional UPDATE**: `Tag::where('id', $tag->id)->whereIn('status', ['available', 'generated'])->update([...])`, and the affected-row count is checked. If it's zero, someone else won the race, and the request fails with `TAG_ALREADY_ACTIVATED` — even in a hypothetical future where the lock is accidentally removed or bypassed, this check alone still prevents double-assignment.

This was verified empirically, not just by code review: 10 concurrent HTTP requests were fired at the same fresh tag from two different vehicles. Exactly 1 succeeded; the other 9 received a clean `TAG_ALREADY_ACTIVATED` error; the database ended up with exactly one `tag_assignments` row and one consistent `vehicle_id` on the tag. See `tests/Feature/Api/TagActivationTest.php` for the automated (sequential) regression version of this test.

## Revocation and replacement

Both are explicit, admin/system-triggered operations — never implicit. Both go through the same lock-then-conditional-update pattern and write a `tag_assignments` history row plus an `audit_logs` entry. Replacement additionally links the old and new tags via `tags.replaced_by_tag_id` so the full chain is traceable.

**Bug found and fixed during development:** `Tag`'s `$fillable` array was initially missing `revocation_reason`, `revoked_at`, and `replaced_by_tag_id`. Since `revoke()`/`replace()` use Eloquent model `->update()` (as opposed to `activate()`, which uses a query-builder `Model::where(...)->update()` that bypasses mass-assignment guarding entirely), the status changed correctly but the reason and timestamp were silently dropped. Caught via manual QA against the running admin dashboard, not by the automated tests that existed at the time — a regression test was added afterward (`test_revoking_a_tag_persists_the_reason_and_timestamp`).

## NFC

Schema and generic-abstraction support exist (`nfc_tags` table, `provisioning_status` enum), but the actual provisioning flow, admin UI, and secure write-lock handling for rewritable tags are **not yet implemented**. NFC tags are not currently produced by `QrBatchService` beyond the bare database row — no chip programming step exists in this codebase.
