# Implementation Plan — Feedback Fixes

---

## Group 1 — Auth & Login Page
**Feedback:** Admin password show/hide toggle, Remember me checkbox

- [x] `resources/views/admin/auth/login.blade.php` — add password show/hide toggle button, add "Remember me" checkbox
- [x] `app/Http/Controllers/Admin/AuthController.php` — pass `remember` boolean to `Auth::attempt()`

---

## Group 2 — Route (Remarks) Field
**Feedback:** Add Route/Remarks description box to both Monthly Duty and Direct Booking

- [x] New migration: add `route_remarks` (text, nullable) to `monthly_duties` and `direct_bookings`
- [x] `CreateMonthlyDutyRequest` + `StoreDirectBookingRequest` / `UpdateDirectBookingRequest` — add validation rule
- [x] `DutyService::createMonthlyDuty()` — include field
- [x] `DirectBookingService::createBooking()` / `updateBooking()` — include field
- [x] Views: `monthly-duties/create.blade.php`, `direct_bookings/create.blade.php`, `direct_bookings/edit.blade.php`, show views

---

## Group 3 — Expected Start/End Time on Single Line
**Feedback:** Show both time fields inline (side by side)

- [x] `monthly-duties/create.blade.php` — wrap both time inputs in a `row` with `col-6` each (pure view change)

---

## Group 4 — State / City / Zipcode Bind the Data
**Feedback:** State/city/pincode fields should be properly bound and saved

- [x] Verify `DutyService::createMonthlyDuty()` passes state/city/pincode through correctly
- [x] Add JS-based state → city cascade or ensure fields are pre-populated on edit
- [x] Verify monthly duty create/edit views save and re-populate these fields correctly

---

## Group 5 — Monthly Duty Recurrence Option
**Feedback:** Recurrence option for duties (weekly/monthly repeat)

- [x] New migration: add `recurrence_type` (none/weekly/monthly, default none), `recurrence_end_date`, `parent_duty_id` to `monthly_duties`
- [x] `MonthlyDuty` model — add new fillable fields
- [x] `CreateMonthlyDutyRequest` — add recurrence validation rules
- [x] `DutyService::createMonthlyDuty()` — if recurrence set, auto-create child duties up to `recurrence_end_date`
- [x] View: add recurrence UI section in `monthly-duties/create.blade.php`

---

## Group 6 — Department and Officer as Separate Fields
**Feedback:** Keep Department and Officer as separate, independent fields

- [x] Verify `monthly-duties/create.blade.php` has separate `department_id` dropdown and `officer_name` text input
- [x] Fix form if they are merged or linked incorrectly

---

## Group 7 — Expiry Date Notifications
**Feedback:** Auto-notify when DL expiry, PUC expiry, insurance expiry are approaching

- [x] New job: `CheckExpiryNotificationsJob` — checks `drivers.dl_expiry`, `vehicles.puc_expiry_date`, `vehicles.insurance_expiry_date` for records expiring in 30 / 7 / 1 days
- [x] Send FCM + WhatsApp notification to driver and admin
- [x] Register job in `routes/console.php` scheduler

---

## Group 8 — Admin Settings: Address and GST Number
**Feedback:** Add company address and GST number to settings

- [x] `SettingController::index()` and `update()` — add `company_gst` key
- [x] `admin/settings/index.blade.php` — add GST number field (address already exists, verify it's shown)

---

## Group 9 — PDF Footer Note
**Feedback:** Add note at end of all PDFs: "This is a system-generated document and does not require a physical signature."

- [x] `resources/views/admin/reports/bill-processing-pdf.blade.php` — add footer note
- [x] `resources/views/admin/reports/monthly-pdf.blade.php` — add footer note

---

## Group 10 — Driver Log Entry Locked After First Submission
**Feedback:** Once driver submits a log entry, they cannot change it — only admin can edit

- [ ] New migration: add `driver_locked` (boolean, default false) to `daily_duty_logs`
- [ ] `DutyController::start()` and `end()` — set `driver_locked = true` after successful submission
- [ ] `DutyController` — block re-submission if `driver_locked = true`

---

## Group 11 — Original Log Immutable; Admin Edits Create Separate Version
**Feedback:** Admin edits must NOT overwrite original driver log — create a separate edited copy

- [ ] New migration: add `edited_start_time`, `edited_end_time`, `edited_start_km`, `edited_end_km`, `edited_total_km`, `edited_by`, `edited_at`, `edit_reason` to `daily_duty_logs`
- [ ] `DailyDutyLogController::update()` — write to edit fields only, never overwrite original driver-submitted values
- [ ] Admin show/edit views — display both original and edited values clearly

---

## Group 12 — Sorting, Pagination, Export to Excel
**Feedback:** All listing pages need sortable columns, pagination, and Excel export

- [x] Add `maatwebsite/excel` package (or CSV fallback)
- [x] `MonthlyDutyController::index()` — add sort params + export action
- [x] `DailyDutyLogController::index()` — add sort params + export action
- [x] `VehicleController::index()` — add sort params + export action
- [x] `DirectBookingController::index()` — add sort params + export action
- [x] `CustomerController::index()` — add sort params + export action
- [x] All index views — add sortable column headers and "Export to Excel" button

---

## Group 13 — Left Sidebar Scrolling
**Feedback:** Sidebar not scrollable when nav items overflow the screen height

- [x] `resources/views/admin/layouts/app.blade.php` — add `overflow-y: auto` to `.sidebar` CSS

---

## Group 14 — Logout & My Account Options
**Feedback:** Logout and My Account should be clearly accessible in the sidebar/header

- [x] `admin/layouts/app.blade.php` — add user dropdown in sidebar header with "My Account" and "Logout" links
- [x] New route + controller method for profile page (change name / password)
- [x] New view: `admin/profile/index.blade.php`

---

## Group 15 — Roles and Permissions Review
**Feedback:** Check all roles and permissions are correctly applied throughout the project

- [x] Audit all route `can:` middleware against permissions defined in `RolePermissionSeeder`
- [x] Add any missing permissions to the seeder
- [x] Verify all admin views have correct `@can` / `@cannot` guards
- [x] Ensure driver-side routes are protected with `auth:driver` middleware

---

## Group 16 — Mobile Responsive
**Feedback:** Full mobile responsiveness across admin and driver panels

- [x] `admin/layouts/app.blade.php` — refine sidebar mobile toggle behaviour
- [x] All admin table views — wrap tables in `table-responsive`, add card-style layout for small screens
- [x] All forms — verify `col-md-*` breakpoints are correct and inputs stack properly on mobile
- [x] `driver/layouts/app.blade.php` — verify mobile-first layout is consistent

---

## Implementation Order

1. DB migrations (Groups 2, 5, 7, 10, 11)
2. Backend — services, controllers, jobs (Groups 2, 5, 7, 10, 11, 12, 14)
3. View / UI fixes (Groups 1, 3, 4, 6, 8, 9, 13, 14, 15, 16)
