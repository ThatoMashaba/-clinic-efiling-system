<?php
// ============================================================
// api/referrals.php — POST: create referral
// ============================================================
require_once 'config.php';
require_once 'auth.php';

$staff = getAuthStaff($conn);
if (!$staff) respond(false, "Unauthorised");
if ($staff['role'] !== 'Doctor') respond(false, "Only doctors can create referrals");

$input        = json_decode(file_get_contents("php://input"), true);
$patient_id   = (int)($input['patient_id']   ?? 0);
$referral_date= clean($conn, $input['referral_date'] ?? date('Y-m-d'));
$referred_to  = clean($conn, $input['referred_to']   ?? '');
$reason       = clean($conn, $input['reason']        ?? '');

if (!$patient_id || !$referred_to || !$reason) respond(false, "All fields required");

$stmt = $conn->prepare(
    "INSERT INTO referrals (patient_id,staff_id,referral_date,referred_to,reason)
     VALUES(?,?,?,?,?)"
);
$stmt->bind_param('iisss', $patient_id,$staff['staff_id'],$referral_date,$referred_to,$reason);
$stmt->execute();
$new_id = $conn->insert_id;

$conn->query("INSERT INTO activity_logs (staff_id,action_type,target_table,target_record_id,description)
    VALUES({$staff['staff_id']},'CREATE_REFERRAL','referrals',$new_id,'Referral created for patient $patient_id')");

respond(true, "Referral created", ["referral_id" => $new_id]);
