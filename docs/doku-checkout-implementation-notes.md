# DOKU Checkout implementation notes

This overlay switches the payment provider adapter to DOKU's non-SNAP Hosted Checkout API.

- Create payment: `POST /checkout/v1/payment`
- Sandbox host: `https://api-sandbox.doku.com`
- Production host: `https://api.doku.com`
- Authentication: `Client-Id`, `Request-Id`, `Request-Timestamp`, `Signature`
- Request signature components: `Client-Id`, `Request-Id`, `Request-Timestamp`, `Request-Target`, `Digest`
- Digest: Base64(SHA-256(raw JSON request body))
- Signature: `HMACSHA256=` + Base64(HMAC-SHA256(component string, Secret Key))
- Checkout URL: `response.payment.url`
- Check status: `GET /orders/v1/status/{invoice_number}`; GET signature omits Digest.

DOKU documents that Checkout notification `transaction.status=FAILED` must be ignored because a customer may select another payment method. Repeated notification Request-Ids must be handled idempotently.

The overlay intentionally leaves fulfillment for the subsequent desktop integration phase. A verified payment status and the entitlement/session-creation operation are separate concerns.
