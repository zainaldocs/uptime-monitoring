# Uptime Monitoring System (UMS)

A lightweight, responsive, and secure self-hosted uptime monitoring web application built using native **PHP** and **Vanilla JS / Tailwind CSS**. UMS allows you to monitor servers, services, websites, and ports with real-time dashboards, historical uptime reports, and automated email alerts.

---

## 🚀 Key Features

*   **Live Uptime Dashboard:** Dynamic real-time status updates via JSON API and Web interface.
*   **Device Management (CRUD):** Easily register, update, and remove devices or host endpoints.
*   **Multiple Check Types:**
    *   **Ping (ICMP):** Checks host reachability.
    *   **TCP Port:** Checks if specific services (like MySQL, SSH, or Custom Port) are listening.
    *   **HTTP/HTTPS:** Validates web service response status code.
*   **Device Grouping:** Organize your monitored hosts into custom groups.
*   **Uptime Reports & Graphs:** Historical graphs (powered by Chart.js) and custom date-range reporting.
*   **SMTP Email Alerts:** Automated email notifications sent to admins when a device changes status (UP ➡️ DOWN / DOWN ➡️ UP).
*   **Role-Based Access Control (RBAC):** Pemisahan akses antara **Admin** (kontrol penuh, konfigurasi SMTP, manajemen pengguna) dan **Staff** (akses view, kelola perangkat/grup).
*   **Theme Toggle:** Real-time theme toggle (Light / Dark Mode).
*   **CLI Daemon/Workers:** PHP scripts optimized to run via cron job to handle continuous monitoring and daily reports.

---

## 🔒 Security Hardening (Best Practices Implemented)

*   **Environment Configuration:** Secure database credentials stored in a `.env` file (protected from public access and version control).
*   **Dual-layer CSRF Protection:** Automatic JS interceptor + fallback form tokens to protect all POST/PUT mutation endpoints.
*   **Secure CLI Workers:** Daemon/Cron scripts can only be triggered via shell (CLI), preventing arbitrary HTTP abuse.
*   **Database Safety:** Prepared Statements implemented with PDO parameter binding globally (100% SQL Injection Protection).
*   **Generic Error Messages:** Standardized JSON error response templates avoiding raw SQL error leakage.
*   **Rate Limiting:** Lockout mechanism on authentication forms to prevent brute-force dictionary attacks.
*   **Output Escaping:** Strict HTML character sanitization (`escapeHtml` / `htmlspecialchars`) on all dynamically rendered attributes (Stored & Reflected XSS Protection).
*   **Apache Directory Protection:** `.htaccess` blocks listing folders on sensitive directories (`api/`, `workers/`, `includes/`, `config/`).

---

## 🛠️ Technology Stack

*   **Backend:** Native PHP 8.x
*   **Database:** MySQL >= 5.7 / MariaDB >= 10.3
*   **CSS Engine:** Tailwind CSS (Visual UI system with dark mode)
*   **JS Interface:** Vanilla Javascript (Fetch API, DOM manipulation)
*   **Dependencies:** PHPMailer v7.x (SMTP wrapper)

---

## ⚙️ Requirements

*   PHP >= 8.0 (with `curl` and `pdo_mysql` extensions enabled)
*   MySQL >= 5.7 or MariaDB >= 10.3
*   Composer (for installing PHPMailer dependency)
*   Web Server (Apache / Nginx / Laragon)

---

## 📥 Installation & Setup

### 1. Clone & Extract
Clone or download this repository to your web server document root directory (e.g., `C:/laragon/www/uptime-monitoring` or `/var/www/html/uptime-monitoring`).

### 2. Install Dependencies
Navigate to the root directory and install dependencies via Composer:
```bash
composer install
```

### 3. Database Schema Setup
Create a new MySQL database named `db_uptime_monitoring`. Execute the following SQL schema to construct the tables:

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    email VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NULL,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(150) NOT NULL,
    port INT NULL DEFAULT NULL,
    check_type ENUM('ping', 'tcp', 'http') DEFAULT 'ping',
    status ENUM('UP', 'DOWN', 'UNKNOWN') DEFAULT 'UNKNOWN',
    last_status_change DATETIME NULL DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS status_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    status ENUM('UP', 'DOWN') NOT NULL,
    latency FLOAT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_time (device_id, timestamp),
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    ip_address VARCHAR(45) NOT NULL,
    attempt_time INT NOT NULL,
    INDEX idx_ip_time (ip_address, attempt_time)
);
```

### 4. Configuration (`.env`)
Copy the template configuration file:
```bash
cp .env.example .env
```
Open `.env` and fill in your environment specifics:
```env
DB_HOST=localhost
DB_NAME=db_uptime_monitoring
DB_USER=root
DB_PASS=your_password
APP_URL=http://localhost/uptime-monitoring/
```

### 5. Default Login Accounts
By default, if using standard initialization, you can set up initial users with the following credentials (ensure password hashes are created via `password_hash()`):
*   **Admin User:** `admin` / Password: `password`
*   **Staff User:** `staff` / Password: `password`

---

## 🤖 Automated Cron Job Setup (Workers)

To monitor devices continuously and send daily status reports, you must schedule the worker scripts on your server.

### Linux (Crontab Setup)
Run `crontab -e` and add the following lines:
```bash
# Run monitoring loop every minute
* * * * * php /var/www/html/uptime-monitoring/workers/monitor.php > /dev/null 2>&1

# Run daily summarized reports at 08:00 AM every day
0 8 * * * php /var/www/html/uptime-monitoring/workers/daily_report.php > /dev/null 2>&1
```

### Windows (Task Scheduler Setup)
1.  Open **Task Scheduler** and select **Create Basic Task**.
2.  Set the Trigger to **Daily** or **One time**, repeat every 1 minute.
3.  Set the Action to **Start a Program**:
    *   **Program/script:** `C:\laragon\bin\php\php-X.X.X-Win32\php.exe` (pointing to your PHP executable)
    *   **Add arguments:** `C:\laragon\www\uptime-monitoring\workers\monitor.php`

---

## 📂 Project Structure

```text
├── api/                   # API Endpoints (auth, devices, groups, settings, live status)
├── assets/                # CSS, Custom Javascript utilities, and SVG Icons
├── config/                # Database configuration & environment parsing helper
├── documents/             # Code Review reports and migration design plans
├── includes/              # Shared helper libraries (auth guard, mailer class, checker logic)
├── vendor/                # Composer 3rd-party dependencies (PHPMailer)
├── views/                 # HTML UI layouts, dashboards, settings, reporting pages
└── workers/               # Background Cron Daemon Scripts (monitor, reports)
```
