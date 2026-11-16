<?php
// ============================================================
// api/reports.php — GET: generate reports
// ?type=patients|consultations|referrals|activity
// &date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
// ============================================================
require_once 'config.php';
require_once 'auth.php';

$staff = getAuthStaff($conn);
if (!$staff) respond(false, "Unauthorised");

$type      = clean($conn, $_GET['type']      ?? 'patients');
$date_from = clean($conn, $_GET['date_from'] ?? date('Y-m-01'));
$date_to   = clean($conn, $_GET['date_to']   ?? date('Y-m-d'));

if ($type === 'patients') {
    $res = $conn->query(
        "SELECT p.patient_id,p.id_number,p.first_name,p.last_name,p.gender,
                p.contact_number,p.date_of_birth,a.city,p.created_at
         FROM patients p LEFT JOIN addresses a ON p.address_id=a.address_id
         WHERE DATE(p.created_at) BETWEEN '$date_from' AND '$date_to'
         ORDER BY p.last_name"
    );
} elseif ($type === 'consultations') {
    $res = $conn->query(
        "SELECT c.consultation_id,c.visit_date,c.diagnosis,c.treatment,c.notes,
                c.next_visit_date,p.first_name,p.last_name,p.id_number,s.full_name AS doctor
         FROM consultations c
         JOIN patients p ON c.patient_id=p.patient_id
         JOIN staff s    ON c.staff_id=s.staff_id
         WHERE c.visit_date BETWEEN '$date_from' AND '$date_to'
         ORDER BY c.visit_date DESC"
    );
} elseif ($type === 'referrals') {
    $res = $conn->query(
        "SELECT r.referral_id,r.referral_date,r.referred_to,r.reason,
                p.first_name,p.last_name,p.id_number,s.full_name AS doctor
         FROM referrals r
         JOIN patients p ON r.patient_id=p.patient_id
         JOIN staff s    ON r.staff_id=s.staff_id
         WHERE r.referral_date BETWEEN '$date_from' AND '$date_to'
         ORDER BY r.referral_date DESC"
    );
} elseif ($type === 'activity') {
    if ($staff['role'] !== 'Administrator') respond(false, "Administrators only");
    $res = $conn->query(
        "SELECT l.log_id,l.action_type,l.description,l.action_timestamp,
                s.full_name,s.role
         FROM activity_logs l JOIN staff s ON l.staff_id=s.staff_id
         WHERE DATE(l.action_timestamp) BETWEEN '$date_from' AND '$date_to'
         ORDER BY l.action_timestamp DESC"
    );
} else {
    respond(false, "Unknown report type");
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
respond(true, "OK", ["report" => $type, "date_from" => $date_from, "date_to" => $date_to, "rows" => $rows]);
