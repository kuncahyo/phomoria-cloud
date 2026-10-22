# Phomoria Payment Platform — Architecture Plan

Status: design baseline; implementation must proceed in small, testable phases.

## 1. Objective

Build a first-party Phomoria payment platform in `phomoria-cloud`. The platform owns transaction records, account configuration, payment lifecycle, webhook processing, fulfillment, audit history, and administration. DOKU is an external payment provider, not the owner of Phomoria's payment workflow.

Do not copy the old Midtrans implementation as the new architecture. The old desktop implementation is a migration reference only.

## 2. Non-negotiable boundaries

- The Java desktop application must never connect directly to the payment database.
- The Java desktop application must never store DOKU or other payment-provider credentials.
- Provider API calls and webhook verification happen only on the Laravel server.
- Desktop communicates with Phomoria Cloud over authenticated HTTPS APIs using its existing Sanctum identity.
- Payment amount, account selection, transaction state, expiry, and fulfillment are decided and validated server-side.
- Payment state changes must be auditable and idempotent.
- A PAID transaction cannot be cancelled or downgraded by a client request.
- Closing the QR dialog may request cancellation through Phomoria Cloud; it must not call DOKU from Java.
- Payment success and fulfillment are separate state machines. Fulfillment must run exactly once.
- No secrets, production credentials, database passwords, or real webhook payloads containing sensitive data belong in Git.

## 3. Existing application integration

Repositories:
- Backend: `kuncahyo/phomoria-cloud`
- Desktop: `kuncahyo/phomoria-platform`

Backend baseline:
- Laravel 13 / PHP 8.3
- Laravel Sanctum
- Existing `users`, `devices`, and `photo_sessions` tables
- Existing device registration associates a device with the authenticated user.

Desktop baseline:
- Existing `StartScreen` opens `PaymentDialog`.
- Legacy `LegacyPaymentService` reads `%USERPROFILE%/.phomoria/payment-db.properties`, opens JDBC connections to MySQL, reads a device price and Midtrans key, and calls Midtrans directly.
- `ApplicationFrame.startPaidSession()` creates the local photo session and opens frame selection.
- The desktop currently uploads the completed session later through `/api/session/upload`.

The payment rewrite must remove the JDBC payment dependency, local payment DB properties, direct provider HTTP calls, and old provider-specific code from the desktop. Preserve the user-facing QR payment flow where useful, but rebuild its service contract against Phomoria APIs.

## 4. Multi-account model

A payment account belongs either to the Phomoria platform (no user owner) or to a partner/user. A partner may use:
1. a platform-managed Phomoria payment account, or
2. their own DOKU merchant account.

A device must be assigned an eligible payment account by server-side configuration. Never let the desktop choose an arbitrary account or submit credentials.

Credential values are encrypted at rest using Laravel encrypted casts or an equivalent authenticated encryption mechanism backed by a server-only application key. Credentials must be masked in admin responses and never logged.

The schema should be provider-capable even though the first provider is DOKU.

## 5. Core data model proposal

Names and exact columns are subject to migration review before implementation.

### payment_accounts
- optional `user_id` (null denotes a platform-owned account)
- provider identifier (initially `doku`)
- display name / merchant label
- environment (sandbox/production)
- encrypted provider credentials and signing material
- active/default flags and non-secret provider configuration
- timestamps and soft-delete or explicit disabled state

### device_payment_accounts
- device-to-account assignment
- assignment enabled flag and optional priority
- unique device/account pair

### payment_transactions
- public UUID and unique merchant order/reference
- user and device foreign keys
- optional `photo_session_id` once the resulting photo session is uploaded
- client-generated `client_session_id` UUID created before payment, so payment can be tied to the future photo session
- payment account and provider identifiers
- immutable amount/currency snapshot and description
- provider transaction/reference identifiers
- payment status, expiry, QR payload/image URL where safe, timestamps
- idempotency key with unique constraint

### payment_transaction_events
Append-only status/event history with source, normalized status, provider event/reference, safe metadata, and timestamps.

### payment_webhook_logs
Webhook delivery audit trail with signature-validation outcome, provider/event identifiers, processing result, and redacted/sanitized payload. Avoid storing secrets or unnecessary personal data.

### payment_fulfillments
One fulfillment record per transaction, protected by a unique transaction constraint, with state, attempt count, result, and timestamps. This prevents duplicate paid events from starting multiple photo sessions.

### Future modules
Voucher/promo rules, refunds, reconciliation, dispute/complaint handling, and reporting are planned as separate modules and should not be mixed into the first QRIS vertical slice.

## 6. Lifecycle and API contract

Initial authenticated desktop flow:
1. Desktop requests payment configuration/quote for its registered device and a new client session UUID.
2. Server verifies device ownership/status, resolves the assigned active account, calculates the payable amount from trusted server data, and creates an idempotent pending transaction.
3. Server calls the provider adapter and returns only the public transaction identifier, amount, expiry, QR display data, and polling interval.
4. Desktop displays the QR and polls the Phomoria transaction-status endpoint only while the payment dialog is open.
5. DOKU webhook is the primary source of payment confirmation. The server validates signature/authenticity, deduplicates delivery, locks the transaction, and records an append-only event.
6. Status inquiry may be used as a recovery/reconciliation path when a webhook is delayed or missed.
7. Desktop receives PAID from Phomoria Cloud and continues to frame selection/capture.
8. When the completed photo session is uploaded, the server attaches it to the payment using the client session UUID and verifies that the transaction was paid and not already fulfilled.
9. Fulfillment is idempotent and exactly once.

Initial API surface (final route names may change during implementation):
- `POST /api/payments` — create or retrieve an idempotent transaction for a registered device.
- `GET /api/payments/{transaction}` — retrieve normalized status for the owning device/user.
- `POST /api/payments/{transaction}/cancel` — request cancellation/expiry through the server; reject if already paid.
- `POST /api/webhooks/doku/{account-or-route-key}` — provider webhook, outside Sanctum but protected by provider signature/authentication and account routing.
- `POST /api/session/upload` — attach completed upload to its paid transaction using the client session UUID.

Exact DOKU endpoints, signing rules, payloads, QR format, status mapping, and sandbox behavior must be verified against the applicable official DOKU product documentation before implementing the provider adapter. Do not assume a SNAP or Direct API endpoint until confirmed for the merchant product/account.

## 7. Security and reliability

- Enforce user/device/account ownership at every API boundary.
- Use database transactions and row locks for state transitions.
- Enforce unique constraints for merchant references, idempotency keys, webhook event IDs where available, and one fulfillment per transaction.
- Verify webhook signatures before accepting state changes.
- Treat webhook delivery as at-least-once; handlers must be idempotent.
- Never trust client-supplied amount, owner, account ID, provider status, or payment result.
- Keep payment status transitions explicit and monotonic; late/duplicate events must not downgrade PAID.
- Do not mark a transaction paid solely because the desktop says so.
- Log correlation IDs and safe metadata, not credentials or full authorization headers.
- Use server-side queues/retries for webhook recovery and fulfillment where appropriate.

## 8. Delivery phases

### Phase 0 — Repository and provider contract audit
- Confirm existing Laravel conventions, auth, device ownership, pricing source, deployment/runtime constraints, and tests.
- Verify the exact DOKU merchant product/API documentation and webhook contract.
- Confirm how the desktop's current device pricing is represented in Phomoria Cloud; do not migrate pricing from the legacy database by guess.

### Phase 1 — Payment foundation
- Migrations/models for accounts, device assignments, transactions, events, webhook logs, and fulfillments.
- Encrypted credential casts and masked admin serialization.
- Account resolution and device authorization.
- Automated tests for ownership, encryption, uniqueness, and state transitions.

### Phase 2 — DOKU adapter and QR creation
- Server-only DOKU client/signature implementation.
- Sandbox create QR transaction.
- Persist provider references and normalized response.
- Authenticated create/status/cancel APIs.
- Contract tests with sanitized fixtures.

### Phase 3 — Webhook and reliability
- Signature validation and account routing.
- Idempotent event processing and transaction locking.
- Status inquiry recovery and retry strategy.
- Paid state cannot be cancelled/downgraded.

### Phase 4 — Desktop migration
- Replace legacy payment service with a Phomoria Cloud API client.
- Rebuild QR dialog around Phomoria transaction IDs and normalized status.
- Remove payment JDBC/database dependency and Midtrans-specific desktop code/dependencies.
- Preserve theme/layout behavior where appropriate.
- Test paid, pending, expired, cancelled, network-loss, and app-close scenarios.

### Phase 5 — Session binding and fulfillment
- Generate client session UUID before payment.
- Bind completed photo upload to the paid transaction.
- Ensure one paid transaction can fulfill only the intended session once.
- Handle abandoned paid transactions and support/admin recovery.

### Phase 6 — Admin, partner accounts, vouchers, refunds, reconciliation
- Payment account management and device assignment UI.
- Transaction timeline and complaint investigation tools.
- Voucher/promo engine, refunds, reconciliation, and reports as separately scoped features.

## 9. Working agreement

- Work only on `feature/doku-payment-gateway` in `phomoria-cloud` for backend changes; desktop changes belong on a corresponding feature branch in `phomoria-platform`.
- Do not push secrets or deployment credentials.
- Implement in small increments, test each increment, review the diff, then commit.
- Do not merge to `main` or deploy without explicit approval.
- Do not make broad unrelated refactors while rebuilding payment.
