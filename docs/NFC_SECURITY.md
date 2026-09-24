# NFC Security

## Current status: schema only, no implementation

The `nfc_tags` table and the generic `tags` parent (shared with QR — see `docs/QR_SECURITY.md`) exist and support NFC as a `type`. `QrBatchService::createBatch(quantity, adminId, 'nfc')` will create bare NFC tag rows, but **no chip provisioning, writing, or lock step exists** — the "NFC" batch today is just database rows with no corresponding physical action.

## What section 12 of the spec requires, and the plan for each

| Requirement | Plan | Status |
|---|---|---|
| NFC must not store sensitive personal data | The NFC chip is intended to carry only the same public URL a QR code would (`https://.../t/{public_token}`), never a name/phone/vehicle detail | Design decided, not implemented (nothing writes to a physical chip yet) |
| Secure provisioning process | An admin-initiated provisioning step should mark `nfc_tags.provisioning_status` through `unprovisioned → provisioned → locked`, ideally verifying the chip was actually written before marking it provisioned | Enum exists in the schema; no controller/service implements the transition |
| Prevent unauthorized rewriting where technically possible | For rewritable chips, the recommended approach is to lock the chip (NDEF lock bit or password-protection depending on chip type) immediately after provisioning, and require an explicit admin action + audit log entry to unlock for re-provisioning | Not implemented — `nfc_tags.rewritable` boolean exists but nothing acts on it |
| Independent revocability from QR | The shared `tags` table already gives this for free — an NFC tag and a QR tag are separate `tags` rows even when assigned to the same vehicle, each with its own `status` | Implemented at the schema/service level (`TagLifecycleService` works identically regardless of `type`) |

## Recommended next steps

1. Choose a specific NFC chip/tag hardware family (NTAG213/215/216 are common for this use case) — this determines the actual provisioning protocol and available lock mechanisms.
2. Build an admin-facing provisioning flow: generate the tag row, write the NDEF record (public URL) via a connected NFC reader/writer, verify the write, then flip `provisioning_status`.
3. Extend the audit trail to cover provisioning specifically (`nfc.provisioned`, `nfc.lock_failed`, etc.).
