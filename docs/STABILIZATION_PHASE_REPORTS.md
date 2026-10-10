# Raflora Stabilization — Phase Exit Reports

Reports for `README_Raflora_System_Stabilization_Plan.md`. Test counts are from `vendor/bin/phpunit` runs in this repository (SQLite in-memory, `phpunit.xml`).

Known environment-only failure (all phases): `Auth\OtpDeliveryFeedbackAndRecoveryTest::test_smtp_configuration_resolves_via_config_repository` expects `smtp.gmail.com` from a local `.env`; the test environment has no `.env`, so mail resolves to `127.0.0.1`. Not caused by these changes (it failed before any change was made).

---

## Phase 2 — Stabilize Auth and Ownership

**Phase status:** Complete

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | Login regenerates the session; logout invalidates the session and regenerates the CSRF token; Admin/Staff passwords are verified before the device/OTP chain and never use remember-me. | WORKING | `AuthController::login`, `handleAdminStaffLogin`, `logout`; existing auth tests pass |
| 2 | Client portal routes only required `auth` + `verified`, not the client role. Admin/Staff could open `/client/*`, which silently created a `clients` row for their email and let an Admin change their email through `/client/account-settings`, bypassing the Admin email-change OTP flow. | UNSAFE | `routes/web.php` client group |
| 3 | Client accounts are linked to `clients` rows by email only. Changing the account email did not move the client record (bookings became orphaned), did not reset email verification, and could attach the account to another, unlinked client record with that email. | UNSAFE / BROKEN | `account-settings.update` closure; `BookingController::resolveClient` |
| 4 | Secure file endpoints compared `booking.guest_access_token === query('guest_token')`. Client-created bookings have a `null` token and Laravel converts `?guest_token=` to `null`, so anonymous requests could read any client booking's inspiration images and message attachments (including payment proofs). Verified with a probe request returning HTTP 200. | UNSAFE | `SecureFileController::showInspirationImage`, `showMessageAttachment` |
| 5 | The operations panel could create additional `admin` users, contradicting the single-admin design (S-01D) and breaking emergency recovery, which resolves "the" admin with `User::where('role','admin')->first()`. | UNSAFE | `admin.account.accounts.store`; `AdminRecoveryController` |
| 6 | Temporary UI mock routes (`/mock/*`, marked "TEMPORARY ONLY") were public in every environment and rendered Admin/Staff screens. | UNSAFE | `routes/web.php`, `MockUIController` |
| 7 | `POST /bookings/validate-image` was public and unthrottled; each request calls the Gemini API (cost/DoS exposure). The endpoint is not used by any view. | UNSAFE | `routes/web.php`, `BookingController::validateImageAjax` |
| 8 | Client booking actions (analysis, status, accept, request changes, reply, cancellation, payment reference, proposal feedback, meetings) enforce booking ownership; guest accept/payment endpoints validate the token with `hash_equals` and stay blocked for unclaimed guests. | WORKING | controller checks; existing + new tests |

### Changes made
- `app/Http/Middleware/ClientMiddleware.php` (new), `bootstrap/app.php` (alias `client`), `routes/web.php`: client portal limited to client accounts; Admin/Staff are redirected to their own dashboards (JSON → 403). Admin keeps read access to `client/bookings/{booking}/updates` for supervision (controller enforces admin-or-owner).
- `routes/web.php` account settings: an email change moves the user's client record, refuses an email already linked to another client record, resets `email_verified_at`, sends a verification OTP, and is audit-logged.
- `app/Http/Controllers/SecureFileController.php`: guest-token access requires a non-empty token, a stored token, a constant-time match, and an unclaimed booking.
- `routes/web.php` + `resources/views/admin/settings.blade.php`: account creation limited to Staff.
- `routes/web.php`: mock routes registered only in the `local` environment; `validate-image` throttled (5/min, same as its sibling endpoint).
- `tests/Feature/PackageFallbackConfirmationTest.php`: posts the client booking form as a client account instead of an admin (the test's subject — package fallback confirmation — is unchanged).

### Tests run
- `tests/Feature/Phase2AuthAndOwnershipStabilizationTest.php` (new): 11 tests, 58 assertions — pass.
- Full suite: 1132 tests, 1 failure (the known environment-only SMTP test).

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| Authentication and session behavior work as intended | Pass |
| Roles and permissions are enforced server-side | Pass (client portal now role-checked) |
| Users can access only authorized records and actions | Pass (secure-file IDOR closed) |
| Guest links are protected and validated consistently | Pass |
| Account, client, and booking relationships are correct | Pass (email change keeps linkage) |
| Relevant security and regression tests pass | Pass |

### Remaining blockers
None.

### Other findings (not changed)
- `User::meetings()` and `CalendarEvent` reference a `user_id` column / `calendar_events` table that do not exist; both are unused.
- `ClaimGuestBookingController::claim` calls `lockForUpdate()` outside a transaction for temporary requests, so the row lock has no effect (the claimed-state check still prevents double claims in normal use).
- `tests/Feature/PackageFallbackConfirmationTest.php` writes `scratch/test_response.html` into the project on every run.

### Next step
Phase 3 — Administrator core workflow.

---

## Phase 3 — Stabilize the Administrator's Core Workflow

**Phase status:** Complete

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | `PUT admin/bookings/{booking}` applied any submitted `status` before handling actions. A probe moved a `pending` booking straight to `confirmed` (no verified payment), `quotation_sent` (no quotation issued), `event_in_progress` (no dispatch / fresh-flower checks), and `event_completed`. The UI only ever posts the current status, so these were request-level bypasses of every workflow guard. | UNSAFE / DATA-LOGIC | Probe test; `Admin\BookingController::update` |
| 2 | The `accept` (final approval) action checked the *submitted* status: `status=approved&action=accept` turned a `pending` booking into `admin_approved`. | UNSAFE | Probe test |
| 3 | `mark_event_in_progress`, `mark_event_completed`, and `log_final_payment` did not check the booking's starting stage (e.g. a final payment could be logged on a pending booking). | DATA-LOGIC | `update()` action branches |
| 4 | `verifyPayment` only checked `verified_at`; a payment the Admin had **rejected** could later be verified and confirm the booking. | DATA-LOGIC | `verifyPayment`, `rejectPayment` |
| 5 | `decline` accepted any status, including events in progress and completed/cancelled bookings. | DATA-LOGIC | `Admin\BookingController::decline` |
| 6 | Quotation issuance requires confirmed materials and Admin-reviewed prices; client acceptance moves to `approved` (not confirmed); payment submission moves to `payment_submitted` (not verified); verification requires the exact submitted amount and re-checks stock. Client quotation breakdowns show selling prices only (the R1-B raw-cost exposure is no longer present). | WORKING | `QuotationIssuanceService`, `BookingController::acceptQuotation` / `submitPaymentReference`, client view |

### Changes made
- `Admin\BookingController::update` + `illegalRawStatusTransition()`: a form-submitted status must be a valid business event (stop: decline/cancel; confirm only with a verified payment; post-event stages only after the event started; client-owned statuses never set by Admin; closed bookings stay closed). Status-governing actions ignore the submitted status and use the booking's real status; event start/complete and final-payment logging check their starting stage. Completion keeps its existing guard.
- `verifyPayment`: only `pending` submissions can be verified.
- `decline`: only before the event starts and never for closed bookings.
- Tests: `tests/TestCase.php` gained `recordVerifiedPayment()`. Tests that previously confirmed bookings without any payment now record a verified payment first (`Step16DBookingConflictAndAvailabilityVerificationTest`, `AdminConfirmedMaterialRequirementTest`, `AdminInventoryStockRulesTest`, `Step16GCoreWorkflowEndToEndVerificationTest` journey E); their subjects (availability, locking, release) are unchanged. `AdminNotificationsTest` label-normalization test now normalizes a label on a legal transition and asserts the illegal jump is rejected.

### Tests run
- `tests/Feature/Phase3AdminWorkflowStabilizationTest.php` (new): 6 tests, 52 assertions — pass.
- Full suite: 1138 tests, 1 failure (known environment-only SMTP test).

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| Admin can process a booking through valid review, approval, quotation, and financial steps | Pass |
| Relationships and calculations are correct | Pass (existing pricing/issuance tests) |
| A quotation does not automatically confirm a booking | Pass |
| A payment submission does not count as verified payment | Pass |
| Status changes reflect valid business events | Pass (guard + tests) |
| Inventory decisions and financial records remain consistent | Pass |

### Remaining blockers
None.

### Other findings (not changed)
- `Admin\BookingController::update` is ~800 lines with several intermediate `save()` calls and no surrounding transaction; a mid-request failure can leave partial item edits. A refactor into a service with one transaction is recommended but outside this phase's minimal scope.
- The update form's status list accepts `payment_pending`/`rejected`, which the workflow no longer produces.

### Next step
Phase 4 — Client journey.

---

## Phase 4 — Connect the Client Journey

**Phase status:** Complete

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | Booking → Quotation → Acceptance → Payment → Confirmation works end to end through the real routes: package booking creation, Raflora Review, official quotation issuance, client acceptance (→ `approved`, not confirmed), Admin final approval, downpayment submission linked to the accepted quotation (amount = quotation × downpayment %), exact-amount verification, and confirmation (`downpayment_received`, `confirmed_at` set, balance correct). | WORKING | `Phase4ClientJourneyEndToEndTest` |
| 2 | Ownership is enforced on read and write: another client receives 403 on the booking page, acceptance, and the status endpoint. | WORKING | same test |
| 3 | The change-request loop works: an outdated quotation cannot be accepted while changes are pending; the revised quotation supersedes the previous version (one issued version at a time). | WORKING | same test |
| 4 | The client could not start a conversation with Raflora until a message already existed, although the backend accepts messages at any stage. | DISCONNECTED | Fixed during the README workflow work (message form always available for active bookings) |
| 5 | The bookings list used its own hard-coded hints (e.g. "Booking confirmed • Preparation underway" as soon as a downpayment was verified, before any preparation). | UX (misleading) | `client/bookings.blade.php` |
| 6 | Client and guest forms offer exactly the event types the server accepts (`wedding`, `birthday`, `corporate`, `other` + custom). | WORKING | views vs `BookingRequest` |

### Changes made
- `resources/views/client/bookings.blade.php`: each booking shows its README workflow stage and detail from `BookingWorkflowService` (same source as the booking page tracker).

### Tests run
- `tests/Feature/Phase4ClientJourneyEndToEndTest.php` (new): 2 tests, 58 assertions — pass.
- Full suite: 1140 tests, 1 failure (known environment-only SMTP test).

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| The client can complete each authorized action in sequence | Pass |
| Every action stores the correct data | Pass |
| The client sees the persisted result and current business status | Pass |
| The client cannot view or modify another client's records | Pass |
| The next expected step is available when prerequisites are met | Pass |

### Remaining blockers
None.

### Other findings (not changed)
- `BookingController::submitPaymentReference` reads `downpayment_percentage` from the accepted quotation (default 50%). The R1-B note about `QuotationIssuanceService` reading it from the HTTP request no longer applies: issuance reads the system setting.

### Next step
Phase 5 — Guest journey.

---

## Phase 5 — Complete the Guest Journey

**Phase status:** Complete

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | Guests can submit a request without an account; the request is stored as a temporary record (24-hour claim window) with a hashed token, and no payment or quotation action is possible before claiming. | WORKING | `GuestBookingController::store`, existing guest tests |
| 2 | Claiming a temporary request locked the row **outside** a transaction and did not re-check the claim inside it, so a double-submit or parallel claim could convert one request into two bookings. Reproduced with a simulated race (test fails without the fix). | DATA-LOGIC | `ClaimGuestBookingController::claim` |
| 3 | Permanent (older) guest bookings could not be claimed — the claim page only looked up temporary requests — and an expired quotation hid the claim button. | DISCONNECTED | Fixed during the README workflow work (claim support + guest page) |
| 4 | Guest numeric-ID endpoints (`accept`, `payment-reference`) require the matching token and stay blocked for unclaimed guests; invalid tokens → 404, expired → 403, claimed → 403 on guest routes. | WORKING | new + existing tests |
| 5 | Secure files accepted an empty guest token for client bookings. | UNSAFE | Fixed in Phase 2 |
| 6 | Expired-request cleanup removes only expired **unclaimed** requests and their images; claimed requests (whose images the booking now uses) are kept. | WORKING | `CleanupExpiredGuestBookings`, new test |

### Changes made
- `app/Http/Controllers/Client/ClaimGuestBookingController.php`: atomic compare-and-set (`unclaimed → claimed`) as the first statement of the claim transaction; a losing claim creates nothing and gets "already claimed".

### Tests run
- `tests/Feature/Phase5GuestJourneyStabilizationTest.php` (new): 5 tests, 35 assertions — pass. The race test was verified to fail with the compare-and-set disabled.
- Full suite at phase close: see Phase 6.

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| Guests use guest functions without creating an account prematurely | Pass |
| Guest records are accessible only through valid authorization | Pass |
| Numeric IDs or guessed URLs cannot bypass token checks | Pass |
| Payment and booking actions are properly authorized | Pass |
| Account conversion preserves ownership and relationships | Pass (single booking, client-linked, details preserved) |

### Remaining blockers
None.

### Other findings (not changed)
- The claimed booking keeps the raw guest token in `bookings.guest_access_token`; guest-only routes and secure files reject it once the booking is claimed, so it no longer grants access.

### Next step
Phase 6 — Staff operations.

---

## Phase 6 — Complete Staff Operations

**Phase status:** Complete

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | Staff see and act only on events assigned to them (404 otherwise); assignment only accepts Staff accounts; Staff are blocked (403) from Admin payment verification, return reconciliation, dispatch, and assignment routes. | WORKING | `Staff\EventController::authorizedBooking`, `StaffMiddleware`, new + existing tests |
| 2 | Staff physical counts create an Admin review alert and never change stock (Decision A). | WORKING | `updatePhysicalStock`, new test |
| 3 | **Returned stock was never restored when Staff recorded the return first.** Staff return submissions store `quantity_good` for Admin review without crediting inventory, but Admin reconciliation treated an existing `quantity_good` as stock already credited. Confirming the Staff count therefore produced a zero difference and the returned items stayed out of inventory (reproduced: 8 instead of 10). | DATA-LOGIC (BLOCKER for reconciliation) | `Admin\ReturnTrackingController::update` |
| 4 | Staff could submit return counts and condition observations after Raflora completed the return audit, reopening a reconciled audit as "Partially Returned". | DATA-LOGIC | `Staff\EventController::submitReturn` / `recordCondition` |
| 5 | Material states are distinct: reservation (`booking_lock`/`booking_release`), physical release (`dispatch`/`dispatch_correction`), credited return (`return`), downward correction (`damage`); damaged and lost units are not credited as usable stock. | WORKING | `InventoryDispatchService`, return reconciliation, new test |

### Changes made
- `Admin\ReturnTrackingController::update`: the good quantity already credited for a booking item is read from the inventory ledger (`return` + `damage` movements); pre-ledger single-field legacy rows keep their previous behavior; counts recorded by Staff are treated as not yet credited.
- `Staff\EventController`: return and condition submissions are refused once the return audit or booking is completed.
- `tests/Feature/DeterministicMultiItemLockOrderingTest.php`: the "previously returned" fixture now includes the ledger entry that a real prior credit always has (the rollback assertion is unchanged; the transaction count reflects that prior entry).

### Tests run
- `tests/Feature/Phase6StaffOperationsStabilizationTest.php` (new): 5 tests, 30 assertions — pass. The stock-credit test was verified to fail under the previous logic.
- Return-related suites (`MixedConditionMaterialReturnTest`, `ReturnAuditIntegrityTest`, `DispatchCorrectionReturnAuditIntegrityTest`, `StaffMaterialReturnTest`, `DeterministicMultiItemLockOrderingTest`): 47 tests — pass.
- Full suite: 1150 tests, 1 failure (known environment-only SMTP test).

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| Staff can perform authorized preparation and event operations | Pass |
| Material movements correspond to real operational events | Pass |
| Inventory records are updated consistently | Pass (return credit fixed) |
| Staff permissions do not grant Admin privileges | Pass |
| Return records are available for reconciliation | Pass |

### Remaining blockers
None.

### Other findings (not changed)
- `StaffMiddleware` admits Admin accounts to Staff routes (supervision by design); Admin actions on Staff pages are not restricted to an assignment.

### Next step
Phase 7 — Reconciliation and end-to-end QA.

---

## Phase 7 — Finish Reconciliation and End-to-End QA

**Phase status:** Complete (MySQL clean install not verified — see Remaining blockers)

### Confirmed findings
| # | Finding | Class | Evidence |
|---|---------|-------|----------|
| 1 | Damaged/lost units are recorded per item, are not credited as usable stock, and charge decisions (charge / no charge / pending) drive `pending_resolution` vs completion. | WORKING | Return suites; Phase 7 journey (9 good + 1 damaged → stock 29 of 30) |
| 2 | Scheduled alert commands are registered (`inventory:check-tiered-shortages`, `alerts:check-expired-quotations`, `alerts:check-price-reconfirmations`, `guest-bookings:cleanup`) and execute successfully against a freshly migrated database — **but the deployment never ran the scheduler**: the container only starts `php artisan serve`, so no alert or cleanup ever ran in production. | BROKEN (deployment) | `php artisan schedule:list`; `nixpacks.toml`; `scripts/container-startup.sh` |
| 3 | Every booking status change was audited twice (once by the Booking model, once by the controller), and some contextual entries reused the same action name. | DATA (duplicate audit) | Probe: one cancellation → two `status_changed` rows |
| 4 | Reports use verified payments only for revenue (`verified_at` + verified statuses); contract and pipeline values come from booking quotes; existing reporting-integrity tests pass. | WORKING | `ReportController`, `AdminReportingCalculationIntegrityTest` |
| 5 | Clean install: all 79 migrations run on a fresh SQLite database. | WORKING (SQLite) | `php artisan migrate` on an empty database |
| 6 | One booking can be traced from the guest request through all 14 README stages to completion and final return records using only real routes for Guest, Client, Admin, and Staff. | WORKING | `Phase7ReadmeEndToEndJourneyTest` |

### Changes made
- `scripts/container-startup.sh`: starts `php artisan schedule:work` in the background before the web server (disable with `RAFLORA_RUN_SCHEDULER=false` when a separate cron/scheduler runs `php artisan schedule:run` every minute); output goes to `storage/logs/scheduler.log`.
- `routes/console.php`: scheduled tasks use `withoutOverlapping()->onOneServer()` so multiple instances do not duplicate alerts (requires a lock-capable cache store such as the default `database` store).
- `app/Models/Booking.php`: the model's `status_changed` entry is the single authoritative status audit (records previous and new status in `old_values` / `new_values`). Controller entries that duplicated it were removed (`Admin\BookingController::update`) or renamed to describe the business event (`quotation_accepted`, `final_payment_status_applied`, `return_audit_status_applied`).

### Tests run
- `tests/Feature/Phase7ReadmeEndToEndJourneyTest.php` (new): 1 test, 63 assertions — pass (includes the ordered, de-duplicated status audit trail).
- Clean SQLite install + manual run of all four scheduled commands — exit code 0.
- Full suite: 1151 tests, 1 failure (known environment-only SMTP test).

### Acceptance criteria
| Criterion | Result |
|-----------|--------|
| Damage and missing-item outcomes are recorded correctly | Pass |
| Inventory reconciliation reflects actual conditions and movements | Pass (Phase 6 fix + journey) |
| Scheduled alerts and audit logging are verified rather than assumed | Pass (scheduler now started; audit de-duplicated) |
| Reports match the underlying data | Pass |
| Critical regression tests pass | Pass |
| A booking can be traced from request to final return records | Pass |

### Remaining blockers
- **MySQL clean install not verified.** A local MySQL server is listening, but no credentials were available and no database was created on it. Run `php artisan migrate` against a disposable MySQL database before release.
- `Auth\OtpDeliveryFeedbackAndRecoveryTest::test_smtp_configuration_resolves_via_config_repository` depends on a local `.env` with Gmail SMTP settings.

### Other findings (not changed)
- `Admin\BookingController::update` remains a large method with intermediate saves (see Phase 3).

### Next step
UI/UX modernization (mobile and desktop).

---

## UI/UX Modernization — Mobile and Desktop

**Status:** Complete

### Method
Real pages were rendered from the application (seeded with representative bookings, inventory, returns, notifications and quotations) and screenshotted at 390 px (phone) and 1440 px (desktop) before and after each change. 49 pages were checked across guest, client, admin, staff, auth and error screens.

### Pass 1 — Core journey screens
- Shared shell: admin and staff layouts (Inter type, sticky header, compact mobile bar), client navbar, notification bell.
- README workflow tracker component (`components/booking-workflow`) redesigned: stage pill, "Now" card, 14-segment progress strip, collapsible phases on phones.
- Admin bookings list, booking review, dashboard; staff dashboard and event page; client bookings and booking pages; guest tracking.
- Shared CSS: softer borders/shadows, compact sidebar items, `rf-table--stack` (tables become cards on phones).

### Pass 2 — Remaining screens
- **Tables that were cut off on phones** now render as labelled cards (`data-label` support added to `rf-table--stack`): inventory, archived inventory, return tracking, return audit, quotations, client records, team accounts, recent activity, client booking history.
- **Summary tiles** show two-up on phones instead of one tall card per metric (inventory, reports).
- **Public packages:** cards no longer overflow at phone width. **Gallery:** hero copy readable over the photo on phones.
- **Package create/edit:** inventory mapping rows no longer overlap the quantity input.
- **Auth and error pages:** the brand panel no longer pushes the form or error message below the fold on phones.

### Defects found and fixed during this phase
| Page | Defect | Fix |
|---|---|---|
| Admin → Change Email | Always returned HTTP 500 (`$admin` undefined in view); linked from Account Management | Controller passes the signed-in admin |
| Admin → Return Tracking | Client name always "N/A" (view read non-existent `first_name`/`last_name` on Client) | Uses `full_name`, falls back to guest name |
| Client → Account | Page was a menu of four `href="#"` links; the working profile form was commented out although `account-settings.update` exists | Rebuilt as a profile/password form posting to the existing route; dead links removed |
| Client → Notifications | Cards showed the event type instead of the notification title; modal inserted message text with `innerHTML` | Title + "Event · Booking #id" meta line; modal uses `textContent` |
| Client → Booking History | Unquoted bookings showed "Quote: ₱0.00 / Balance: ₱0.00" | Shows "Not yet quoted" |
| Admin → Reports | Revenue series never drew: Chart.js treated the `revenue` scale as radial (axis inferred from the id's first letter) | Scales declared `type: 'linear', axis: 'y'`; Chart.js pinned to 4.4.1; charts render after web fonts load |

### Tests run
- `tests/Feature/UiModernizationRegressionTest.php` — 8 tests covering the defects above (all pass).
- Full suite: 1159 tests, 1 failure — `Auth\OtpDeliveryFeedbackAndRecoveryTest::test_smtp_configuration_resolves_via_config_repository` (environment-only, pre-existing; needs local SMTP `.env`).

### Limitations
- Screenshots were taken in headless Chrome only; no physical iOS/Android device or Safari testing.
- Inventory "Reserved" counts in the screenshots come from the seeded fixture, not production data.

### Other findings (not changed)
- `client/booking-create.blade.php` and `packages/index.blade.php` build some markup with `innerHTML` from admin-managed package data (package names, included items). Admin-authored, so lower risk, but worth escaping in a later pass.
- Admin → Account Management shows every team account as "Active"; there is no account deactivation feature, so the label is static.

### Next step
Gemini AI accuracy and evidence validation (`README_Gemini_AI_Accuracy_and_Evidence_Validation.md`).
