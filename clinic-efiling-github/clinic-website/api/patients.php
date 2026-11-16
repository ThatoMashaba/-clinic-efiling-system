<?php
// ============================================================
// api/patients.php
// GET  ?action=search&q=...  → search patients
// GET  ?action=get&id=...    → single patient profile
// POST ?action=register      → register new patient
// ============================================================
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/../sa_id.php';

$staff = getAuthStaff($conn);
if (!$staff) respond(false, "Unauthorised");

$action = $_GET['action'] ?? $_POST['action'] ?? (json_decode(file_get_contents("php://input"),true)['action'] ?? '');

// ── Search ────────────────────────────────────────────────
if ($action === 'search') {
    $q    = clean($conn, $_GET['q'] ?? '');
    $like = "%$q%";
    $stmt = $conn->prepare(
        "SELECT p.patient_id, p.id_number, p.first_name, p.last_name,
                p.gender, p.contact_number, p.date_of_birth,
                (SELECT COUNT(*) FROM consultations c WHERE c.patient_id=p.patient_id) AS visit_count
         FROM patients p
         WHERE p.id_number LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?
              OR CONCAT(p.first_name,' ',p.last_name) LIKE ?
         ORDER BY p.last_name, p.first_name LIMIT 30"
    );
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $patients = [];
    while ($r = $res->fetch_assoc()) $patients[] = $r;
    respond(true, "OK", $patients);
}

// ── Get single patient ─────────────────────────────────────
if ($action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare(
        "SELECT p.*, a.street, a.suburb, a.city, a.province, a.postal_code,
                s.full_name AS registered_by
         FROM patients p
         LEFT JOIN addresses a ON p.address_id = a.address_id
         LEFT JOIN staff s     ON p.created_by  = s.staff_id
         WHERE p.patient_id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    if (!$patient) respond(false, "Patient not found");

    // Consultations
    $cs = $conn->prepare(
        "SELECT c.*, s.full_name AS doctor_name
         FROM consultations c JOIN staff s ON c.staff_id=s.staff_id
         WHERE c.patient_id=? ORDER BY c.visit_date DESC"
    );
    $cs->bind_param('i', $id);
    $cs->execute();
    $consultations = [];
    while ($r = $cs->get_result()->fetch_assoc()) $consultations[] = $r;

    // Referrals
    $rs = $conn->prepare(
        "SELECT r.*, s.full_name AS doctor_name
         FROM referrals r JOIN staff s ON r.staff_id=s.staff_id
         WHERE r.patient_id=? ORDER BY r.referral_date DESC"
    );
    $rs->bind_param('i', $id);
    $rs->execute();
    $referrals = [];
    while ($r = $rs->get_result()->fetch_assoc()) $referrals[] = $r;

    respond(true, "OK", [
        "patient"       => $patient,
        "consultations" => $consultations,
        "referrals"     => $referrals,
    ]);
}

// ── Register ───────────────────────────────────────────────
if ($action === 'register') {
    $input = json_decode(file_get_contents("php://input"), true);
    $id_number  = clean($conn, $input['id_number']  ?? '');
    $first_name = clean($conn, $input['first_name'] ?? '');
    $last_name  = clean($conn, $input['last_name']  ?? '');
    $dob        = clean($conn, $input['dob']         ?? '');
    $gender     = clean($conn, $input['gender']      ?? '');
    $contact    = clean($conn, $input['contact']     ?? '');
    $email      = clean($conn, $input['email']       ?? '');
    $street     = clean($conn, $input['street']      ?? '');
    $suburb     = clean($conn, $input['suburb']      ?? '');
    $city       = clean($conn, $input['city']        ?? '');
    $province   = clean($conn, $input['province']    ?? '');
    $postal     = clean($conn, $input['postal']      ?? '');

    [$id_ok, $id_msg, $id_dob, $id_gender] = validate_sa_id($id_number);
if (!$id_ok) respond(false, $id_msg);
$dob = $id_dob;
    if (!$first_name || !$last_name || !$gender)   respond(false, "Name and gender required");

    $chk = $conn->prepare("SELECT patient_id FROM patients WHERE id_number=?");
    $chk->bind_param('s', $id_number);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) respond(false, "Patient with this ID already exists");

    $a = $conn->prepare("INSERT INTO addresses (street,suburb,city,province,postal_code) VALUES(?,?,?,?,?)");
    $a->bind_param('sssss', $street, $suburb, $city, $province, $postal);
    $a->execute();
    $addr_id = $conn->insert_id;

    $p = $conn->prepare(
        "INSERT INTO patients (id_number,first_name,last_name,date_of_birth,gender,contact_number,email,address_id,created_by)
         VALUES(?,?,?,?,?,?,?,?,?)"
    );
    $p->bind_param('sssssssii', $id_number,$first_name,$last_name,$dob,$gender,$contact,$email,$addr_id,$staff['staff_id']);
    $p->execute();
    $new_id = $conn->insert_id;

    $conn->query("INSERT INTO activity_logs (staff_id,action_type,target_table,target_record_id,description)
        VALUES({$staff['staff_id']},'REGISTER_PATIENT','patients',$new_id,'Registered: $first_name $last_name')");

    respond(true, "Patient registered", ["patient_id" => $new_id]);
}

respond(false, "Unknown action");
