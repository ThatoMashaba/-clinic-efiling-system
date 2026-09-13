# Clinic E-Filing System

A secure digital patient record management system for the Nelspruit Municipal Clinic. Built with PHP 8, MySQL, HTML5, CSS3, JavaScript and Bootstrap 5.

---

## 📋 Project Overview

The Clinic E-Filing System replaces paper-based patient filing with a secure web platform and Android mobile app. It uses the South African 13-digit National ID number as a unique patient identifier for instant record retrieval.

**Client:** Nelspruit Municipal Clinic, Mpumalanga, South Africa  
**Module:** XISD6329w — Work Integrated Learning  
**Institution:** The Independent Institute of Education (IIE)  
**Year:** 2026  

---

## 👥 Team Members

| Name | Role | Tasks |
|---|---|---|
| Lefa Thato Mashaba | Developer | Website design, PHP backend, API, wireframes |
| Lindokuhle Suli Maseko | Developer | Content, mobile app logic, site map, documentation |

---

## 🛠️ Tech Stack

### Website
- **PHP 8** — backend logic and page routing
- **MySQL** — relational database (7 normalised tables)
- **HTML5** — semantic page structure
- **CSS3** — custom professional styling
- **JavaScript** — SA ID validation and live feedback
- **Bootstrap 5** — responsive grid (CDN)
- **XAMPP** — local development server

### Mobile App
- **Kotlin** — Android development language
- **Android Studio** — IDE
- **Retrofit 2** — HTTP API client
- **Gson** — JSON parsing
- **Material Design 3** — UI components
- **SharedPreferences** — session token storage

---

## 📁 Folder Structure

```
clinic-efiling/
├── index.php                  ← Login page (public entry point)
├── dashboard.php              ← Role-based dashboard with stats
├── register.php               ← Register new patient using SA ID
├── search.php                 ← Search patients by ID or name
├── profile.php                ← Full patient profile with 4 tabs
├── consultation.php           ← Add consultation record
├── referral.php               ← Create outgoing referral
├── incoming_referral.php      ← Record incoming referral from another facility
├── reports.php                ← Generate and print reports
├── logout.php                 ← Destroys session
├── db.php                     ← MySQL database connection
├── auth.php                   ← Session protection middleware
├── includes.php               ← Shared sidebar navigation
├── style.css                  ← Global professional styles
├── clinic_db.sql              ← Full database setup with sample data
└── api/
    ├── config.php             ← API shared config + CORS headers
    ├── auth.php               ← Token validation
    ├── login.php              ← POST: authenticate, return token
    ├── dashboard.php          ← GET: stats + recent patients
    ├── patients.php           ← GET/POST: search, get, register
    ├── consultations.php      ← POST: add consultation
    ├── referrals.php          ← POST: create referral
    └── reports.php            ← GET: generate reports
```

---

## ⚙️ Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL) installed
- PHP 8.0 or higher
- A modern web browser (Chrome, Firefox, Edge)

### Step 1 — Copy files
Extract the project and copy the `clinic-efiling` folder into:
```
C:\xampp\htdocs\clinic-efiling\
```

### Step 2 — Start XAMPP
Open XAMPP Control Panel and start:
- ✅ Apache
- ✅ MySQL

### Step 3 — Import the database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Name it `clinic_db` and click **Create**
4. Click on `clinic_db` → **Import** tab
5. Click **Choose File** → select `clinic_db.sql`
6. Click **Go**

### Step 4 — Open the system
Go to: `http://localhost/clinic-efiling`

---

## 🔑 Demo Login Accounts

All accounts use password: `Password123`

| Username | Role | Access Level |
|---|---|---|
| `sindlovu` | Receptionist | Register & search patients |
| `lkhumalo` | Doctor | Consultations, referrals, medical records |
| `tmaseko` | Nurse | View records, add consultation notes |
| `bnkosi` | Administrator | Full access including activity logs |

---

## 🗄️ Database Tables

| Table | Description |
|---|---|
| `patients` | Patient records with 13-digit SA ID as unique key |
| `staff` | Staff accounts with roles and credentials |
| `consultations` | Visit records with diagnosis and treatment |
| `referrals` | Outgoing and incoming referrals between facilities |
| `medical_documents` | Uploaded patient documents |
| `addresses` | Normalised address data |
| `activity_logs` | Full audit trail of all staff actions |

---

## 🌐 Website Pages

| Page | File | Access |
|---|---|---|
| Login | `index.php` | Public |
| Dashboard | `dashboard.php` | All roles |
| Register Patient | `register.php` | All roles |
| Search Patient | `search.php` | All roles |
| Patient Profile | `profile.php` | All roles |
| Add Consultation | `consultation.php` | Doctor, Nurse |
| Outgoing Referral | `referral.php` | Doctor only |
| Incoming Referral | `incoming_referral.php` | Doctor only |
| Reports | `reports.php` | All roles |

---

## 📱 Mobile App

The Android app connects to the same MySQL database via PHP REST API endpoints in the `api/` folder.

- **Emulator base URL:** `http://10.0.2.2/clinic-efiling/api/`
- **Real device base URL:** `http://YOUR_PC_IP/clinic-efiling/api/`
- **Package name:** `com.clinic.efiling`
- **Min SDK:** API 26 (Android 8.0)

---

## ✨ Key Features

- 🔐 Secure login with role-based access control
- 🪪 SA ID number validation (13-digit format check)
- 👤 Instant patient profile retrieval
- 🏥 Outgoing AND incoming referral tracking
- 📋 6 report types with date-range filtering
- 🖨️ Print support on all report pages
- 📝 Full staff activity audit trail
- 📱 Android mobile app companion

---

## 📚 References

- Laudon, K.C. and Laudon, J.P., 2020. *Management Information Systems*. 16th ed. Pearson.
- Sommerville, I., 2016. *Software Engineering*. 10th ed. Pearson.
- Google, 2024. Material Design 3. https://m3.material.io/
- W3Schools, 2024. HTML5 Tutorial. https://www.w3schools.com/html/

---

*© 2026 Lefa Thato Mashaba & Lindokuhle Suli Maseko — XISD6329w — The IIE*
