<?php
// ============================================================
// api/consultations.php — POST: add consultation
// ============================================================
require_once 'config.php';
require_once 'auth.php';

$staff = getAuthStaff($conn);
if (!$staff) respond(false, "Unauthorised");
if (!in_array($staff['role'], ['Doctor','Nurse'])) respond(false, "Access denied");

$input          = json_decode(file_get_contents("php://input"), true);
$patient_id     = (int)($input['patient_id']     ?? 0);
$visit_date     = clean($conn, $input['visit_date']     ?? '');
$notes          = clean($conn, $input['notes']          ?? '');
$diagnosis      = clean($conn, $input['diagnosis']      ?? '');
$treatment      = clean($conn, $input['treatment']      ?? '');
$next_visit     = clean($conn, $input['next_visit_date'] ?? '');

if (!$patient_id || !$visit_date || !$diagnosis) respond(false, "Patient ID, date and diagnosis required");

$next = $next_visit ?: null;
$stmt = $conn->prepare(
    "INSERT INTO consultations (patient_id,staff_id,visit_date,notes,diagnosis,treatment,next_visit_date)
     VALUES(?,?,?,?,?,?,?)"
);
$stmt->bind_param('iisssss', $patient_id,$staff['staff_id'],$visit_date,$notes,$diagnosis,$treatment,$next);
$stmt->execute();
$new_id = $conn->insert_id;

$conn->query("INSERT INTO activity_logs (staff_id,action_type,target_table,target_record_id,description)
    VALUES({$staff['staff_id']},'ADD_CONSULTATION','consultations',$new_id,'Consultation added for patient $patient_id')");

respond(true, "Consultation saved", ["consultation_id" => $new_id]);
