# Voucher Session API

Voucher support is added alongside the persistent session reservation and
asset-upload API. It does not change QRIS, Midtrans, or DOKU payment behavior.

## Voucher statuses

- `active`: may authorize one session at a time. The voucher stays active
  while reserved and changes to `used` only after every manifest asset for
  its linked photo session is uploaded.
- `used`: cannot authorize another session.
- `secret`: may authorize multiple sessions and remains secret.

Voucher codes are normalized to uppercase before lookup. Create and manage
voucher rows only through a trusted administrative process; no desktop-facing
voucher administration endpoint is provided in this milestone.

## Authorize before starting the camera

Authenticated endpoint:

```
POST /api/voucher/authorize-session
Authorization: Bearer <Sanctum token>
Content-Type: application/json
```

Request:

```json
{
  "code": "PROMO-ABC-001",
  "device_uuid": "123e4567-e89b-12d3-a456-426614174000"
}
```

The server verifies the authenticated account, active device ownership,
voucher status, and expiry. It creates a time-limited authorization and
returns an opaque one-session token:

```json
{
  "success": true,
  "message": "Voucher berhasil digunakan. Sesi foto diizinkan.",
  "session_authorization_token": "<opaque one-session token>",
  "expires_at": "<ISO-8601 timestamp>"
}
```

For an `active` voucher, database row locking and an outstanding-reservation
check prevent another device from reserving the same voucher concurrently.
The voucher status itself remains `active` until the session is fully
uploaded. If the user abandons the flow before the server session is reserved,
the authorization expires after four hours and the voucher can be reserved
again. Once linked to an incomplete server session, that reservation remains
protected from reuse until the session completes.

The desktop client must keep the token private. It may be held in memory and
temporarily persisted with the local upload queue until the server accepts the
session reservation. It must never be logged or sent to a different device.

## Link voucher to persistent session reservation

The desktop sends the token with its existing manifest request:

```
POST /api/session/reserve
```

Add `voucher_token` to the existing JSON payload, alongside `device_uuid`,
`client_session_id`, `frame_name`, and `assets`.

The backend verifies that the token belongs to the authenticated user and
device, then binds the voucher redemption to exactly that `photo_session`.
Retries with the same client session ID and same token are idempotent. A token
already linked to another photo session is rejected.

After the server accepts the reservation, the desktop can discard the token
from its local queue entry. The server-side redemption is already linked to
the session.

## Consume after all assets arrive

The existing asset endpoint remains:

```
POST /api/session/asset
```

Each asset upload is processed normally. When the final pending asset is
committed, the same database transaction marks the voucher redemption
`completed`, records `completed_at`, and changes an `active` voucher to
`used`. A `secret` voucher remains `secret`.

If upload is interrupted, the server session remains incomplete and the
voucher is not marked used yet. The persistent upload queue can retry the
remaining assets. Existing non-voucher sessions remain compatible and do not
need to send `voucher_token`.

## Scope

This milestone includes the backend voucher tables, authorization endpoint,
linkage to persistent session reservation, final-asset consumption, and
feature tests. It does not include voucher administration UI, quotas beyond
the one-time status rule, partner/device eligibility rules beyond authenticated
device ownership and active status, or any QRIS/payment changes.
