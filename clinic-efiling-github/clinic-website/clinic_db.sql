-- ============================================================
-- Clinic E-Filing System — Full Database Setup
-- Run this in phpMyAdmin: Import → Select this file → Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS clinic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_db;

-- ── 1. Addresses ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS addresses (
    address_id   INT AUTO_INCREMENT PRIMARY KEY,
    street       VARCHAR(100) NOT NULL,
    suburb       VARCHAR(50),
    city         VARCHAR(50)  NOT NULL,
    province     VARCHAR(50),
    postal_code  VARCHAR(10)
);

-- ── 2. Staff ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff (
    staff_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    role         ENUM('Doctor','Nurse','Receptionist','Administrator') NOT NULL,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    email        VARCHAR(100),
    is_active    TINYINT(1)   DEFAULT 1,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ── 3. Patients ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patients (
    patient_id      INT AUTO_INCREMENT PRIMARY KEY,
    id_number       VARCHAR(13) NOT NULL UNIQUE,
    first_name      VARCHAR(50) NOT NULL,
    last_name       VARCHAR(50) NOT NULL,
    date_of_birth   DATE,
    gender          ENUM('Male','Female','Other') NOT NULL,
    contact_number  VARCHAR(15),
    email           VARCHAR(100),
    address_id      INT,
    created_by      INT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (address_id) REFERENCES addresses(address_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staff(staff_id)       ON DELETE SET NULL
);

-- ── 4. Consultations ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS consultations (
    consultation_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT  NOT NULL,
    staff_id         INT  NOT NULL,
    visit_date       DATE NOT NULL,
    notes            TEXT,
    diagnosis        VARCHAR(150),
    treatment        VARCHAR(150),
    next_visit_date  DATE,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)  ON DELETE CASCADE,
    FOREIGN KEY (staff_id)   REFERENCES staff(staff_id)       ON DELETE CASCADE
);

-- ── 5. Referrals (updated — supports incoming & outgoing) ─
CREATE TABLE IF NOT EXISTS referrals (
    referral_id          INT AUTO_INCREMENT PRIMARY KEY,
    patient_id           INT  NOT NULL,
    staff_id             INT  NOT NULL,
    referral_date        DATE NOT NULL,
    referred_to          VARCHAR(100) NOT NULL,
    reason               TEXT,
    referral_type        ENUM('Outgoing','Incoming') DEFAULT 'Outgoing',
    referring_facility   VARCHAR(100) NULL,
    referring_doctor     VARCHAR(100) NULL,
    referral_letter_path VARCHAR(255) NULL,
    document_path        VARCHAR(255),
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)  ON DELETE CASCADE,
    FOREIGN KEY (staff_id)   REFERENCES staff(staff_id)       ON DELETE CASCADE
);

-- ── 6. Medical Documents ─────────────────────────────────
CREATE TABLE IF NOT EXISTS medical_documents (
    document_id    INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT  NOT NULL,
    staff_id       INT  NOT NULL,
    document_type  ENUM('Lab Result','X-Ray','Prescription','Referral Letter','Other') NOT NULL,
    file_name      VARCHAR(150) NOT NULL,
    file_path      VARCHAR(255) NOT NULL,
    upload_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)  ON DELETE CASCADE,
    FOREIGN KEY (staff_id)   REFERENCES staff(staff_id)       ON DELETE CASCADE
);

-- ── 7. Activity Logs ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id            INT AUTO_INCREMENT PRIMARY KEY,
    staff_id          INT NOT NULL,
    action_type       VARCHAR(50) NOT NULL,
    target_table      VARCHAR(50),
    target_record_id  INT,
    description       VARCHAR(255),
    action_timestamp  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- ── 8. API Tokens ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS api_tokens (
    token      VARCHAR(64) PRIMARY KEY,
    staff_id   INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Addresses
INSERT INTO addresses (street, suburb, city, province, postal_code) VALUES
('123 Mbeki Street',      'Soweto',   'Johannesburg', 'Gauteng',       '1804'),
('45 King Dinuzulu Road', 'Pinetown', 'Durban',        'KwaZulu-Natal', '3610'),
('78 Church Street',      'Central',  'Pretoria',      'Gauteng',       '0002'),
('12 Mandela Drive',      'Mkondeni', 'Nelspruit',     'Mpumalanga',    '1200');

-- Staff (all passwords = "Password123")
INSERT INTO staff (full_name, role, username, password, email) VALUES
('Dr. Lerato Khumalo',  'Doctor',        'lkhumalo', '$2y$10$hH01gAi/1UxxVuL7cTDsaOum2zvQjngQEY3gbI0kgVMhHEhUqpcGe', 'lerato.khumalo@clinic.co.za'),
('Nurse Thandi Maseko', 'Nurse',         'tmaseko',  '$2y$10$hH01gAi/1UxxVuL7cTDsaOum2zvQjngQEY3gbI0kgVMhHEhUqpcGe', 'thandi.maseko@clinic.co.za'),
('Sipho Ndlovu',        'Receptionist',  'sindlovu', '$2y$10$hH01gAi/1UxxVuL7cTDsaOum2zvQjngQEY3gbI0kgVMhHEhUqpcGe', 'sipho.ndlovu@clinic.co.za'),
('Admin Bongani Nkosi', 'Administrator', 'bnkosi',   '$2y$10$hH01gAi/1UxxVuL7cTDsaOum2zvQjngQEY3gbI0kgVMhHEhUqpcGe', 'b.nkosi@clinic.co.za');

-- Patients
INSERT INTO patients (id_number, first_name, last_name, date_of_birth, gender, contact_number, email, address_id, created_by) VALUES
('9001015801086', 'Thabo',  'Mokoena', '1990-01-01', 'Male',   '0721234567', 'thabo.m@email.com',  1, 3),
('8805050342082', 'Nomsa',  'Dlamini', '1988-05-05', 'Female', '0719876543', 'nomsa.d@email.com',  2, 3),
('9503035127087', 'Sipho',  'Zwane',   '1995-03-03', 'Male',   '0735556677', 'sipho.z@email.com',  3, 3),
('0204240615086', 'Zanele', 'Nkosi',   '2002-04-24', 'Female', '0711223344', 'zanele.n@email.com', 4, 3);

-- Consultations
INSERT INTO consultations (patient_id, staff_id, visit_date, notes, diagnosis, treatment, next_visit_date) VALUES
(1, 1, '2024-05-10', 'Patient presents with high fever and chills.',     'Influenza',   'Rest, fluids, paracetamol 500mg',    '2024-05-17'),
(2, 1, '2024-05-11', 'Severe recurring headache, sensitivity to light.', 'Migraine',    'Sumatriptan 50mg, rest in dark room', '2024-05-18'),
(3, 2, '2024-05-12', 'Persistent cough and sore throat for 5 days.',     'Common Cold', 'Steam inhalation, throat lozenges',   '2024-05-19'),
(1, 1, '2024-06-01', 'Follow-up visit. Fever resolved. General check.',  'Recovered',   'No medication required',              '2024-07-01');

-- Referrals (outgoing + incoming)
INSERT INTO referrals (patient_id, staff_id, referral_date, referred_to, reason, referral_type, referring_facility, referring_doctor) VALUES
(1, 1, '2024-05-10', 'Cardiology Dept, Nelspruit Hospital', 'Persistent high blood pressure requires specialist.',   'Outgoing', NULL, NULL),
(2, 1, '2024-05-11', 'Neurology Dept, Nelspruit Hospital',  'Recurring severe migraines unresponsive to treatment.', 'Outgoing', NULL, NULL),
(3, 2, '2024-05-12', 'ENT Department, Nelspruit Hospital',  'Chronic cough and throat inflammation 3+ weeks.',       'Outgoing', NULL, NULL),
-- ★ Incoming referral — patient sent TO our clinic FROM another hospital
(2, 1, '2024-05-15', 'Nelspruit Municipal Clinic', 'Patient referred for follow-up treatment after cardiac procedure.', 'Incoming', 'Shongwe Hospital', 'Dr. Nkosi Dlamini');

-- Activity Logs
INSERT INTO activity_logs (staff_id, action_type, target_table, target_record_id, description) VALUES
(3, 'REGISTER_PATIENT',        'patients',    1, 'Registered new patient: Thabo Mokoena'),
(3, 'REGISTER_PATIENT',        'patients',    2, 'Registered new patient: Nomsa Dlamini'),
(1, 'ADD_CONSULTATION',        'consultations',1,'Added consultation for patient ID 1'),
(1, 'CREATE_REFERRAL',         'referrals',   1, 'Created outgoing referral for patient ID 1'),
(1, 'RECORD_INCOMING_REFERRAL','referrals',   4, 'Recorded incoming referral from Shongwe Hospital for Nomsa Dlamini');

-- Demo follow-ups due soon (so the dashboard card has data)
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) WHERE consultation_id = 2;
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY) WHERE consultation_id = 3;
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 6 DAY) WHERE consultation_id = 4;
