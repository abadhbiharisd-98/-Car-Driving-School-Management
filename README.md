# Online Car Driving School Management System (CDSMS) 🚗
### Production-Ready Render Cloud & Aiven MySQL Deployment (PHP 8.2 / Apache Docker)

A complete, modern PHP-based web application for managing car driving school enrollments, training packages, student bookings, payment records, and administrator operations. This project has been fully containerized and upgraded for seamless deployment on **Render** using **PHP 8.2-Apache** with **Aiven Cloud MySQL (SSL TLS Enforcement)**.

---

## 🚀 Live Cloud Deployment Summary

| Parameter | Details |
| :--- | :--- |
| **Hosting Platform** | [Render Cloud](https://render.com) (Docker Web Service) |
| **Database Platform** | [Aiven Cloud MySQL](https://aiven.io) (SSL / TLS Enforced) |
| **PHP Runtime** | PHP 8.2 (Apache 2.4, mysqli, pdo_mysql, gd, zip, mod_rewrite) |
| **Web Installer** | `/db_setup.php` (1-Click SQL Importer & Connection Doctor) |
| **Admin Panel** | `/admin/login.php` |
| **Website Frontend** | `/index.php` |

---

## 🔑 Default Login Credentials

Once your database is imported using `db_setup.php`, use the following credentials to access the administrative dashboard:

| Role | Username | Password | Dashboard URL |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | `https://<your-app>.onrender.com/admin/login.php` |

---

## ⚙️ Environment Variables (Render & Aiven)

Configure these variables in your **Render Service Dashboard** (`Environment` tab) or automatically via `render.yaml`:

| Variable | Required | Default / Example | Purpose |
| :--- | :---: | :--- | :--- |
| `PORT` | Yes | `10000` | Port on which Apache listens internally inside the Render container |
| `DB_SERVER` | Yes | `mysql-xxxx-project.aivencloud.com` | Aiven Cloud MySQL Service URI / Hostname |
| `DB_PORT` | Yes | `26284` | Aiven 5-digit custom MySQL port |
| `DB_USERNAME` | Yes | `avnadmin` | Aiven MySQL admin username |
| `DB_PASSWORD` | Yes | `AVNS_xxxxxxxxx` | Aiven MySQL password |
| `DB_NAME` | Yes | `defaultdb` (or `cdsms_db`) | Target database name |
| `DB_SSL` | Yes | `true` | Enforces `MYSQLI_CLIENT_SSL` for Aiven secure transport |
| `BASE_URL` | Optional | `https://cdsms-car-driving-school.onrender.com/` | Public URL of your deployed app (auto-detected if omitted) |

---

## 🛠️ Critical Upgrades & Fixes Applied

### 1. Aiven Cloud MySQL & PHP 8.x Compatibility
- **SSL Encryption Enforcement**: Aiven Cloud MySQL enforces `require_secure_transport=ON`. Upgraded `classes/DBConnection.php` using `mysqli_init()`, `MYSQLI_OPT_SSL_VERIFY_SERVER_CERT`, and `MYSQLI_CLIENT_SSL` flags so connections are never rejected.
- **Custom 5-Digit Ports**: Added full integer port routing (`DB_PORT`) for Aiven's dynamic ports.
- **PHP 8.2 Class Properties**: Initialized dynamic connection variables inside `__construct()` to eliminate PHP 8 fatal syntax errors (e.g. constant expressions in properties).
- **Null & Type Safety**: Added `instanceof mysqli_result` guards in `classes/SystemSettings.php`, `classes/Login.php`, `classes/Master.php`, and `classes/Users.php` so empty databases or un-run queries fail gracefully instead of crashing Apache.
- **MySQLi Exception Suppression**: Configured `mysqli_report(MYSQLI_REPORT_OFF)` to prevent unhandled `mysqli_sql_exception` in PHP 8.1+.

### 2. Render Reverse Proxy & Port 10000 Leak Prevention
- **Canonical Port Leak**: Behind Render's reverse proxy, requests to directories without a trailing slash (e.g. `/admin`) previously caused Apache's `mod_dir` to redirect to internal port `https://your-domain.com:10000/admin/` (which fails).
- **Fix**: Configured `UseCanonicalName Off`, `UseCanonicalPhysicalPort Off`, and `ServerName localhost` in Apache startup script (`start.sh`).
- **Direct File Routing**: Updated JS AJAX handlers (`dist/js/script.js`), navbar links (`admin/inc/navigation.php`, `inc/topBarNav.php`), and session auth (`admin/inc/sess_auth.php`) to directly target `index.php` and `admin/login.php`.
- **Smart .htaccess**: Added port-stripping RewriteCond rules to strip `:10000` from the Host header if forwarded.

### 3. Automated 1-Click Database Installer (`db_setup.php`)
- **Auto-Detect & Auto-Import**: On first deployment, visiting the root URL or `db_setup.php` automatically parses and imports `database/cdsms_db.sql`.
- **Foreign Key Safe**: Applies `SET FOREIGN_KEY_CHECKS = 0` during import.
- **Diagnostics Dashboard**: Displays real-time database host, port, SSL cipher status, table row counts, and admin account verification.

---

## 📦 Step-by-Step Deployment Guide

### Step 1: Push Code to GitHub
1. Create a new repository on your GitHub account (e.g., `cdsms` or `online-car-driving-school`).
2. Run the following commands in this directory:
   ```bash
   git remote add origin https://github.com/abadhbiharisd-98/<YOUR-REPO-NAME>.git
   git push -u origin main
   ```

### Step 2: Deploy to Render
1. Go to [Render Dashboard](https://dashboard.render.com).
2. Click **New +** -> **Web Service**.
3. Connect your GitHub repository.
4. Set the **Runtime** to **Docker** (or use the Blueprint `render.yaml`).
5. In the **Environment Variables** section, add your Aiven credentials:
   - `PORT`: `10000`
   - `DB_SERVER`: `<your-aiven-mysql-host>`
   - `DB_PORT`: `<your-aiven-port>`
   - `DB_USERNAME`: `avnadmin`
   - `DB_PASSWORD`: `<your-aiven-password>`
   - `DB_NAME`: `defaultdb`
   - `DB_SSL`: `true`
6. Click **Create Web Service**.

### Step 3: Run Database Setup & Verify
1. Once deployed, open your live URL: `https://<your-app>.onrender.com/db_setup.php`.
2. Check that all 5 tables (`system_info`, `users`, `package_list`, `enrollee_list`, `payment_list`) show **Ready**.
3. Click **Admin Login** (`/admin/login.php`) and sign in with `admin` / `admin123`.

