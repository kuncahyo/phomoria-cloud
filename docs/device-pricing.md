# Device Session Pricing — Phase 1

## Decision

Each registered device has its own optional `session_price`, stored as an integer number of Indonesian rupiah. The owning account may set a different price for each device. A null value means the price has not been configured and payment creation must be blocked.

## Ownership and editing

- Website editing is available to authenticated users and is scoped through the authenticated user's `devices()` relationship.
- Super administrators may select an account to manage, matching the existing frame administration pattern.
- Desktop/API editing uses Sanctum and resolves the device only through the authenticated user's `devices()` relationship.
- An additional security-code challenge is intentionally deferred to a later phase.

## API

`PUT /api/device/price` (Sanctum)

Request JSON:

```json
{
  "device_uuid": "registered-device-uuid",
  "session_price": 15000
}
```

Set `session_price` to `null` (or submit an empty value) to clear the configured price. Positive integer values are accepted. The API never accepts a user ID or device database ID from the client.

`GET /api/device/config` and `GET /api/device/frames` include `data.device.session_price` so a client can display the configured amount. This value is informational only: future payment creation must read the amount from the database on the server and must not trust an amount submitted by desktop.

## Migration

The additive migration creates a nullable unsigned big integer column. Existing device records remain valid and simply have no configured price until the owner sets one.
