# CSRF Endpoints Prioritized Inventory

This file lists API endpoints discovered under `backend/api/` that accept POST data or JSON bodies. It is prioritized by risk/public exposure. "Patched" indicates whether the file has already been updated to call `require_valid_csrf()` (or had an inline fallback added) during the staged rollout.

Notes:
- Webhooks and third-party callbacks are intentionally excluded from CSRF protection and should be validated via signatures instead.
- For JSON endpoints, CSRF checks should run after JSON->$_POST merging so tokens sent in JSON are validated.
- After full coverage is confirmed, inline fallbacks should be removed and endpoints should rely exclusively on `require_valid_csrf()` from `backend/includes/csrf_protect.php`.

Columns: Path | Category | Risk | Notes | Patched

---
| Path | Category | Risk | Notes | Patched |
|---|---|---:|---|:---:|
| backend/api/create_booking.php | Booking | High | public booking creation (POST) | Y |
| backend/api/bookings/create.php | Booking | High | booking (legacy) | Y |
| backend/api/bookings/update.php | Booking | High | booking update | Y |
| backend/api/booking_process.php | Booking | High | JSON body -> POST; complex flow | Y |
| backend/api/booking/create.php | Booking | High | alternate booking endpoint | N |
| backend/api/booking/get_timeslots.php | Booking | Medium | may be POST or GET; check | N |
| backend/api/initiate_payment.php | Payment | High | payment initiation (JSON) | Y |
| backend/api/process_payment.php | Payment | High | payment processor | Y |
| backend/api/payment/process.php | Payment | High | payment processing (JSON) | Y |
| backend/api/payment/process_payment.php | Payment | High | alternate payment route | Y |
| backend/api/save_cart.php | Cart | High | saves cart via JSON | Y |
| backend/api/reviews/submit_review.php | Reviews | High | public reviews | Y |
| backend/api/report_review.php | Reviews | High | reporting/reviews | N |
| backend/api/profile_update.php | Profile | High | user profile update (auth) | Y |
| backend/api/update_password.php | Auth | High | password change | Y |
| backend/api/auth/login.php | Auth | High | login (JSON) | N |
| backend/api/search_carwash.php | Search | Medium | search endpoint | N |
| backend/api/search/** | Search | Medium | various search endpoints (JSON) | N |
| backend/api/get_available_times.php | Availability | High | POST-only time lookup | Y |
| backend/api/get_reservations.php | Reservations | High | auth required | N |
| backend/api/get_orders.php | Orders | High | auth required | N |
| backend/api/get_payments.php | Payments | High | auth required | N |
| backend/api/get_payment_detail.php | Payments | High | auth required | N |
| backend/api/payment/webhook.php | Webhook | Low | webhook — signature validation instead | N (excluded)
| backend/api/payment/webhook_handler.php | Webhook | Low | webhook handler — signature validation | N (excluded)
| backend/api/webhooks/* | Webhook | Low | various webhooks — exclude from CSRF | N (excluded)
| backend/api/carwash/manage_categories.php | Admin/Carwash | High | patched | Y |
| backend/api/carwash/add_service.php | Admin/Carwash | High | patched (file upload) | Y |
| backend/api/carwash/manage_availability.php | Admin/Carwash | High | patched | Y |
| backend/api/carwash/bulk_services.php | Admin/Carwash | High | patched (import/export) | Y |
| backend/api/carwash/create.php | Admin/Carwash | High | create carwash (admin) | N |
| backend/api/carwash/reply_to_review.php | Admin/Carwash | High | reply to review | N |
| backend/api/carwash/batch_update_availability.php | Admin/Carwash | High | batch availability update | N |
| backend/api/services/save_service.php | Services/Admin | High | patched | Y |
| backend/api/services/* | Services | Medium | get/list endpoints (read-only) | N |
| backend/api/services/list.php | Services | Low | read-only | N |
| backend/api/services/get_services.php | Services | Low | read-only | N |
| backend/api/availability/check_availability.php | Availability | Medium | JSON endpoint | N |
| backend/api/search/find_carwash.php | Search | Medium | JSON search | N |
| backend/api/search/carwash.php | Search | Medium | JSON search | N |
| backend/api/save_cart.php | Cart | High | patched | Y |
| backend/api/get_profile.php | Profile | High | auth required | N |
| backend/api/get_reservations.php | Reservations | High | auth required | N |
| backend/api/bookings/* | Booking | High | various booking endpoints | Partial |
| backend/api/booking/* | Booking | High | various booking endpoints | Partial |
| backend/api/notifications/* | Notifications | Medium | some accept POST | N |
| backend/api/admin/* | Admin | High | many admin endpoints; prioritize | Partial |
| backend/api/admin/notifications.php | Admin | Medium | JSON parsing present | N |
| backend/api/admin/update_report_status.php | Admin | Medium | JSON parsing present | N |
| backend/api/admin/export_reviews.php | Admin | Medium | export (POST filters) | N |
| backend/api/admin/content.php | Admin | Medium | JSON updates | N |
| backend/api/admin/reports.php | Admin | Medium | report endpoints | N |
| backend/api/admin/get_reports.php | Admin | Medium | listing | N |
| backend/api/admin/get_pages.php | Admin | Medium | page management | N |
| backend/api/admin/get_report_detail.php | Admin | Medium | report detail | N |
| backend/api/admin/analytics_collector.php | Admin | Low | analytics collector | N |
| backend/api/disputes/upload_evidence.php | Disputes | Medium | file upload | N |
| backend/dashboard/Customer_Dashboard_process.php | Dashboard | High | profile & password update handler | Y |
| backend/api/disputes/reporting.php | Disputes | Medium | reporting | N |
| backend/api/reports/reports.php | Reports | Medium | report generation | N |
| backend/api/report_review.php | Reporting | Medium | review reports | N |
| backend/api/save_cart.php | Cart | High | patched | Y |
| backend/api/booking_process.php | Booking | High | patched | Y |
| backend/api/create_booking.php | Booking | High | patched | Y |
| backend/api/get_available_times.php | Availability | High | patched | Y |
| backend/api/process_payment.php | Payment | High | patched earlier | Y |
| backend/api/initiatiate_payment.php | Payment | High | (duplicate/typo check) | N |
| backend/api/initiate_payment.php | Payment | High | patched | Y |
| backend/api/payment/process.php | Payment | High | patched | Y |
| backend/api/payment/process_payment.php | Payment | High | patched | Y |
| backend/api/payment/process.php | Payment | High | patched | Y |
| backend/api/payment/* | Payment | High | other payment helpers | N |
| backend/api/search/* | Search | Medium | many search endpoints accept JSON | N |
| backend/api/availability/* | Availability | Medium | various endpoints | N |
| backend/api/locations/* | Locations | Medium | update/search endpoints | N |
| backend/api/notifications/get_notifications.php | Notifications | Medium | session_start present | N |
| backend/api/get_orders.php | Orders | High | auth required | N |
| backend/api/get_payments.php | Payments | High | auth required | N |
| backend/api/download_receipt.php | Download | Medium | session_start present | N |
| backend/api/download_invoice.php | Download | Medium | session_start present | N |
| backend/api/disputes/upload_evidence.php | Disputes | Medium | file upload (session) | N |
| backend/api/webhooks/dispute_tracker.php | Webhook | Low | webhook — signature validation | N (excluded)
| backend/api/payment/webhook.php | Webhook | Low | excluded | N (excluded)
| backend/api/payment/webhook_handler.php | Webhook | Low | excluded | N (excluded)

---

If you want, I can now:

1. Patch the next batch (I recommended: `backend/api/booking/create.php`, `backend/api/booking/get_timeslots.php`, `backend/api/get_reservations.php`) and run `php -l` on each, or
2. Continue in configurable batches (3–5 files each) and write a short report after each batch (files changed, php -l results, next proposed batch), or
3. Expand this file to list all 134 paths explicitly in separate rows (I included the majority above) and generate a CSV.

Tell me which you'd like next and I will proceed.


