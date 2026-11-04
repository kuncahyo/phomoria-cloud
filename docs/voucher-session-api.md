# Voucher Session API

This document describes the first backend-only voucher implementation on
`feature/voucher-service`. It does not modify or replace the existing QRIS,
Midtrans, or DOKU payment flows.

## Voucher statuses

- `active`: may reserve one session at a time. It remains `active` while the
  authorization is pending and changes to `used` only after the completed
  photo-session upload is committed.
- `used`: cannot authorize another session.
- `secret`: may authorize multiple sessions and remains `secret`.

Voucher codes are normalized to uppercase before lookup. Store codes in
uppercase for predictable behavior.

## Create voucher codes

There is not yet an admin screen or admin API for voucher management. For this
backend milestone, voucher rows can be created through a trusted database
administration tool.

Example:

```sql
INSERT INTO voucher_codes (code, status, expires_at, created_at, updated_at)
VALUES ('PROMO-ABC-001', 'active', NULL, NOW(), NOW());

INSERT INTO voucher_codes (code, status, expires_at, created_at, updated_at)
VALUES ('OWNER-SECRET-001', 'secret', NULL, NOW(), NOW());
```

Never expose database credentials or a voucher-management operation to the
desktop client.

## Authorize a session

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

Successful response:

```json
{
  "success": true,
  "message": "Voucher berhasil digunakan. Sesi foto diizinkan.",
  "session_authorization_token": "<opaque one-session token>",
  "expires_at": "<ISO-8601 timestamp>"
}
```

The desktop application calls this endpoint before opening frame selection
and starting the camera/session flow. The server creates a time-limited
reservation. A one-time `active` voucher cannot be reserved by another request
while that reservation is unexpired. The code remains `active` until the
completed photo-session upload commits, then changes to `used`. If the user
abandons the flow, the reservation expires after four hours and the code can
be reserved again. Keep the returned token in memory for that session; do not
log it or persist it as a reusable credential.

Invalid or used voucher returns HTTP 422. An unowned or inactive device returns
HTTP 403. Validation and voucher consumption happen server-side under database
row locks, so concurrent attempts cannot both consume the same one-time code.

The authorization token expires after four hours and is stored in the database
as a SHA-256 hash, not as plaintext.

## Upload the completed session

When uploading the photos through the existing endpoint, include the token:

```
POST /api/session/upload
```

Include `voucher_token` alongside the existing `device_uuid`,
`frame_name`, and photo files. The backend checks that the token belongs to
the authenticated user and device, is still authorized, and has not expired.
On successful upload, it marks the redemption `completed`, links it to the
created `photo_session`, and changes an `active` voucher to `used` in the same
database transaction.

If the upload fails within the database transaction, the redemption remains
authorized so the application can retry the upload using the same token.
Existing non-voucher uploads remain compatible and may omit `voucher_token`.

## Scope and remaining work

This is the backend API foundation only. The Java desktop UI and camera-start
integration, admin voucher management, expiry/quota administration, and
end-to-end tests against the deployed Hostinger database are not included yet.
The current payment dialog and legacy QRIS/Midtrans implementation are
intentionally untouched.
