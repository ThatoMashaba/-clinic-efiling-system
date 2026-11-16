<?php
// ============================================================
// api/dashboard.php — GET: stats for the dashboard
// ============================================================
require_once 'config.php';
require_once 'auth.php';

$staff = getAuthStaff($conn);
if (!$staff) respond(false, "Unauthorised");

$total_patients      = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$total_consultations = $conn->query("SELECT COUNT(*) c FROM consultations")->fetch_assoc()['c'];
$total_referrals     = $conn->query("SELECT COUNT(*) c FROM referrals")->fetch_assoc()['c'];
$today_visits        = $conn->query("SELECT COUNT(*) c FROM consultations WHERE visit_date = CURDATE()")->fetch_assoc()['c'];

$recent = $conn->query(
    "SELECT p.patient_id, p.first_name, p.last_name, p.id_number, p.gender, p.created_at
     FROM patients p ORDER BY p.created_at DESC LIMIT 5"
);
$recent_patients = [];
while ($r = $recent->fetch_assoc()) $recent_patients[] = $r;

respond(true, "OK", [
    "stats" => [
        "total_patients"      => (int)$total_patients,
        "total_consultations" => (int)$total_consultations,
        "total_referrals"     => (int)$total_referrals,
        "today_visits"        => (int)$today_visits,
    ],
    "recent_patients" => $recent_patients,
]);
