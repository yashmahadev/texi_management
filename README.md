# Digital Duty & Fleet Management System - Phase 1

A complete Laravel 12+ production-grade system for government monthly duty management, featuring driver tracking, WhatsApp OTP authentication, and automated alerts.

## 🚀 Key Features

- **Admin Panel**: Monthly duty management, daily log monitoring, replacement assignment, reports.
- **Driver Web App**: Mobile-friendly interface, WhatsApp OTP login, photo upload for odometer.
- **Automated Alerts**: Delay detection via WhatsApp, missing duty marking.
- **Reports**: Government-style monthly PDF logbooks.
- **Security**: Role-based access (Spatie), Audit logging, Locked entries.

## 🛠 Tech Stack

- **Framework**: Laravel 12+ (PHP 8.3+)
- **Database**: MySQL/PostgreSQL
- **Auth**: Laravel Sanctum (Driver) + Session (Admin)
- **Permissions**: Spatie Laravel Permission
- **PDF**: dompdf
- **Queue**: Database Queue

## ⚙️ Installation & Setup

1. **Clone & Install Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

2. **Database Setup**
   - Create a database named `taxi_management`.
   - Update `.env` with DB credentials.
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=taxi_management
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **WhatsApp API Setup**
   - Configure Meta Business App.
   - Add credentials to `.env`:
   ```env
   WHATSAPP_API_TOKEN=your_token_here
   WHATSAPP_PHONE_NUMBER_ID=your_id_here
   ```

4. **Migrations & Seeding**
   ```bash
   php artisan migrate --seed
   ```
   *Seeds default roles and Admin user.*
   - **Admin Login**: `admin@example.com` / `password`
   - **Owner Login**: `owner@example.com` / `password`

5. **Running the System**
   - Start Server:
     ```bash
     php artisan serve
     ```
   - Start Queue Worker (Required for alerts):
     ```bash
     php artisan queue:work
     ```
   - Start Scheduler (Required for automation):
     ```bash
     php artisan schedule:work
     ```

## 📱 Driver App Usage

1. Create a Driver in Admin Panel.
2. Driver visits `/driver/login`.
3. Enters mobile number -> OTP sent via WhatsApp (or logged if dev mode).
4. Verifies OTP -> Access Dashboard.

## 🛡 Security & Audit

- All critical actions are logged in `audit_logs`.
- Drivers cannot edit logs after submission.
- Replacements are tracked with reasons.

---
**Note**: Ensure `storage/app/public` is linked:
```bash
php artisan storage:link
```
