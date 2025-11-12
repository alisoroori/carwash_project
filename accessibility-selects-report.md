## Accessibility candidate report — <select> elements

This report lists candidate <select> elements that do not have an explicit accessible-name attribute (`title`, `aria-label`, or `aria-labelledby`) at author-time. Each row shows whether the element has an `id`, a `name`, whether a nearby `<label>` was found, an inferred label (heuristic), and a short snippet.

Columns: File | Line | Has ID | Has Name | Has Label | Inferred Label | Snippet

Note: This is a conservative machine-generated candidate list for manual review. Do NOT apply changes automatically without checking language/translation and any server-side rendering variants.

| File | Line | Has ID | Has Name | Has Label | Inferred Label | Snippet |
|---|---:|:---:|:---:|:---:|---|---|
| backend/admin/create_user.php | 344 | Yes | Yes | Yes | User Role | <select id="role" name="role" class="form-control form-select"> |
| backend/admin/create_user_direct.php | 122 | No | Yes | Yes (adjacent) | Role | <select name="role" class="input-field w-full"> |
| backend/admin/create_user_direct.php | 331 | Yes | Yes | Yes | Role | <select id="role" name="role"> |
| backend/admin/create_user_direct.php | 755 | Yes | Yes | Yes | User Role | <select id="role" name="role" class="form-control form-select"> |
| backend/auth/create_user_quick.php | 124 | No | Yes | Yes (adjacent) | Role | <select name="role" class="input-field w-full px-4 py-3 rounded-lg"> |
| backend/auth/debug_registration.php | 71 | No | Yes | No | City | echo '<select name="city"><option value="istanbul">İstanbul</option></select>'; |
| backend/auth/login.php | 273 | No | Yes | Yes (adjacent) | Hesap Türü | <select name="user_type" required class="input-field select-field w-full px-4 py-3 rounded-lg focus:outline-none appearance-none"> |
| backend/booking/new_booking.php | 111 | Yes | Yes | Yes | Şehir | <select id="citySelect" name="city" class="w-full px-4 py-2 border rounded"> |
| backend/booking/new_booking.php | 121 | Yes | Yes | Yes | Mahalle / İlçe | <select id="districtSelect" name="district" class="w-full px-4 py-2 border rounded"> |
| backend/booking/new_booking.php | 131 | Yes | Yes | Yes | Konum (Oto Yıkama) | <select id="carwashSelect" name="carwash_id" class="w-full px-4 py-2 border rounded"> |
| backend/booking/new_booking.php | 143 | Yes | Yes | Yes | Hizmet | <select id="serviceSelect" name="service_id" class="w-full px-4 py-2 border rounded"> |
| backend/booking/new_booking.php | 159 | Yes | Yes | Yes | Saat | <select id="timeSelect" name="time" class="w-full px-4 py-2 border rounded"> |
| backend/dashboard/admin/logs.php | 81 | No | Yes | No | Action | <select name="action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"> |
| backend/dashboard/admin/report_visualizer.php | 23 | Yes | No | No | Chart Type | <select id="chartType" class="rounded-md border-gray-300"> |
| backend/dashboard/admin/report_visualizer.php | 28 | Yes | No | No | Time Range | <select id="timeRange" class="rounded-md border-gray-300"> |
| backend/dashboard/admin/review_moderation.php | 28 | Yes | No | No | Status Filter | <select id="statusFilter" class="rounded-md border-gray-300"> |
| backend/dashboard/admin/review_reports.php | 31 | Yes | No | No | Status Filter | <select id="statusFilter" class="rounded-md border-gray-300 shadow-sm"> |
| backend/dashboard/admin/review_reports.php | 37 | Yes | No | No | Reason Filter | <select id="reasonFilter" class="rounded-md border-gray-300 shadow-sm"> |
| backend/dashboard/admin/sms_templates.php | 103 | Yes | No | Yes | Durum | <select id="templateStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"> |
| backend/dashboard/admin/test_templates.php | 53 | Yes | No | Yes (adjacent) | Şablon Seçin | <select id="templateSelect" class="w-full rounded-md border-gray-300 shadow-sm"> |
| backend/dashboard/admin/upload_form.php | 42 | Yes | Yes | Yes | انتخاب خدمت: | <select name="service_id" id="service_id" required> |
| backend/dashboard/admin/upload_form.php | 52 | Yes | Yes | Yes | دسته‌بندی: | <select name="category" id="category"> |
| backend/dashboard/admin_panel.php | 1775 | Yes | No | No | Status Filter | <select id="statusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 1934 | Yes | No | No | Order Status Filter | <select id="orderStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 1942 | Yes | No | No | Order Service Filter | <select id="orderServiceFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2177 | Yes | No | No | Payment Type Filter | <select id="paymentTypeFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2184 | Yes | No | No | Payment Status Filter | <select id="paymentStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2373 | Yes | No | No | User Type Filter | <select id="userTypeFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2482 | Yes | No | No | Service Category Filter | <select id="serviceCategoryFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2490 | Yes | No | No | Service Status Filter | <select id="serviceStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2711 | Yes | No | No | Ticket Status Filter | <select id="ticketStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2721 | Yes | No | No | Ticket Priority Filter | <select id="ticketPriorityFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2729 | Yes | No | No | Ticket Category Filter | <select id="ticketCategoryFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2903 | Yes | No | No | Review Status Filter | <select id="reviewStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 2911 | Yes | No | No | Review Rating Filter | <select id="reviewRatingFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 3220 | No | No | No | (inferred) Time Range / Filter | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3275 | No | No | No | (inferred) Quarter / Range | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3432 | No | No | No | (inferred) Month Range | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3483 | No | No | No | (inferred) Car Wash Group | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3544 | No | No | No | (inferred) Customer Group | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3654 | No | No | No | (inferred) Year Range | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3701 | No | No | No | (inferred) Quarter Range | <select class="filter-select" style="flex: 1; font-size: 0.85rem;"> |
| backend/dashboard/admin_panel.php | 3869 | Yes | No | No | Notification Type Filter | <select id="notificationTypeFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 3877 | Yes | No | No | Notification Status Filter | <select id="notificationStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 4049 | Yes | No | No | CMS Status Filter | <select id="cmsStatusFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 4056 | Yes | No | No | CMS Type Filter | <select id="cmsTypeFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 4284 | Yes | No | No | Audit Action Filter | <select id="auditActionFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 4293 | Yes | No | No | Audit Entity Filter | <select id="auditEntityFilter" class="filter-select"> |
| backend/dashboard/admin_panel.php | 4621 | No | No | Yes (label above) | Saat Dilimi | <select> |
| backend/dashboard/admin_panel.php | 4629 | No | No | Yes (label above) | Dil | <select> |
| backend/dashboard/admin_panel.php | 4637 | No | No | (likely) Yes | Para Birimi | <select> |
| backend/dashboard/admin_panel.php | 4766 | No | No | Yes (label above) | Encryption | <select> |
| backend/dashboard/admin_panel.php | 5040 | No | No | Yes (label above) | Yedekleme Sıklığı | <select> |
| backend/dashboard/admin_panel.php | 5257 | Yes | Yes | Yes | Kategori | <select id="serviceCategory" name="category" required> |
| backend/dashboard/admin_panel.php | 5303 | Yes | Yes | Yes | Durum | <select id="serviceStatus" name="status" required> |
| backend/dashboard/admin_panel.php | 5340 | Yes | Yes | Yes | Müşteri Seçin | <select id="ticketCustomer" name="customer_id" required> |
| backend/dashboard/admin_panel.php | 5357 | Yes | Yes | Yes | Kategori | <select id="ticketCategory" name="category" required> |
| backend/dashboard/admin_panel.php | 5369 | Yes | Yes | Yes | Öncelik | <select id="ticketPriority" name="priority" required> |
| backend/dashboard/admin_panel.php | 5387 | Yes | Yes | Yes | Assigned To | <select id="ticketAssignedTo" name="assigned_to"> |
| backend/dashboard/admin_panel.php | 5397 | Yes | Yes | Yes | Ticket Status | <select id="ticketStatus" name="status"> |
| backend/dashboard/admin_panel.php | 5592 | Yes | Yes | Yes | Page Category | <select name="page_category" id="pageCategory" required> |
| backend/dashboard/admin_panel.php | 5606 | Yes | Yes | Yes | Page Status | <select name="page_status" id="pageStatus" required> |
| backend/dashboard/admin_panel.php | 5615 | Yes | Yes | Yes | Page Language | <select name="page_language" id="pageLanguage" required> |
| backend/dashboard/admin_panel.php | 5633 | Yes | Yes | Yes | Page Author | <select name="page_author" id="pageAuthor"> |
| backend/dashboard/admin_panel.php | 5658 | Yes | Yes | Yes | Robots Meta Tag | <select name="robots_meta" id="robotsMeta"> |
| backend/dashboard/admin_panel_backup.php | 769 | Yes | No | No | Status Filter | <select id="statusFilter" class="filter-select"> |
| backend/dashboard/admin_panel_backup.php | 876 | Yes | No | No | User Type Filter | <select id="userTypeFilter" class="filter-select"> |
| backend/dashboard/admin_panel_backup.php | 979 | Yes | No | No | Booking Status | <select id="bookingStatus" class="filter-select"> |
| backend/dashboard/Car_Wash_Dashboard.php | 881 | No | No | No | (inferred) Status | <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| backend/dashboard/Car_Wash_Dashboard.php | 1962 | No | No | Yes (adjacent) | Hizmet Seçin | <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| backend/dashboard/Car_Wash_Dashboard.php | 2071 | No | No | Yes (adjacent) | Pozisyon | <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| backend/dashboard/customer/process_booking.php | 106 | Yes | Yes | Yes | Saat | <select id="booking_time_select" name="booking_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"> |
| backend/dashboard/new_booking.php | 35 | Yes | Yes | Yes | Araç Seçin | <select id="vehicle" name="vehicle_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| backend/dashboard/new_booking.php | 47 | Yes | Yes | Yes | Saat | <select id="reservationTime" name="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required"> |
| backend/dashboard/new_booking.php | 55 | Yes | Yes | Yes | Konum | <select id="location" name="carwash_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required"> |
| create_user.php | 302 | No | Yes | Yes (adjacent) | User Role | <select name="role" required class="input-field select-field w-full px-4 py-3 rounded-lg focus:outline-none appearance-none"> |
| frontend/admin/content.html | 22 | Yes | No | No (controls area) | Type Filter | <select id="typeFilter"> |
| frontend/admin/content.html | 28 | Yes | No | No | Status Filter | <select id="statusFilter"> |
| frontend/admin/content.html | 65 | Yes | No | Yes | Type | <select id="type" required> |
| frontend/admin/content.html | 77 | Yes | No | Yes | Status | <select id="status"> |
| frontend/admin/notifications.html | 64 | Yes | No | Yes | Type | <select id="type" required> |
| frontend/admin/notifications.html | 74 | Yes | No | Yes | Target Audience | <select id="targetRole" required> |
| frontend/admin/reports.html | 19 | Yes | No | No | Date Range | <select id="dateRange"> |
| frontend/booking.html | 70 | Yes | No | Yes (adjacent) | Araç Seçin | <select id="vehicle" class="input"> |
| frontend/booking.html | 83 | Yes | No | Yes (adjacent) | Saat | <select id="reservationTime" class="input"> |
| frontend/booking.html | 92 | Yes | No | Yes (adjacent) | Konum | <select id="location" class="input"> |
| frontend/customer_profile.html | 262 | Yes | No | Yes | Şehir | <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/customer_profile.html | 271 | Yes | No | Yes | Mahalle | <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/customer_profile.html | 371 | Yes | No | Yes | Hizmet Seçin | <select id="service" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/customer_profile.html | 383 | Yes | No | Yes | Araç Seçin | <select id="vehicle" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/customer_profile.html | 406 | Yes | No | Yes | Konum | <select id="location" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/customer_profile.html | 647 | No | No | No | (inferred) Misc filter | <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 261 | Yes | No | Yes | Şehir | <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 270 | Yes | No | Yes | Mahalle | <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 370 | Yes | No | Yes | Hizmet Seçin | <select id="service" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 382 | Yes | No | Yes | Araç Seçin | <select id="vehicle" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 405 | Yes | No | Yes | Konum | <select id="location" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| frontend/profile.html | 646 | No | No | No | (inferred) Misc filter | <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"> |
| test_vehicle_update.html | 140 | Yes | No | Yes (wrapping label) | Select Vehicle to Update | <select id="vehicle-select" onchange="populateUpdateForm()"> |

---

How this inference was done (brief):

- If a `<label for="...">` existed within the ±5-line context and it referenced the select's id, that label text was used.
- If an adjacent `<label>` without a `for` attribute immediately precedes the `<select>`, that label text was used (marked "adjacent").
- Otherwise the inference falls back to the select's `id` or `name` (split on `_`, `-`, camelCase → Title Case) and is marked as inferred.

Next steps (recommended):

- Review this report and correct translations / wording for inferred labels.
- Approve a subset to apply safe static edits (add `title` and/or `aria-label` and `for` on labels when safe). I can apply those changes file-by-file, run `php -l` to validate, and commit.
- Alternatively rely on the previously-added runtime helpers (`frontend/js/accessible-select-labels.js`) as a lower-risk mitigation.
