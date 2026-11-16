<?php
// POST {id_token} -> starts a PHP session (used by the website)
session_start();
require_once 'db.php';
require_once 'api/google_verify.php';
header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true);
[$staff, $err] = verify_google_staff($conn, $in['id_token'] ?? '');
if (!$staff) { echo json_encode(['success' => false, 'message' => $err]); exit; }
$_SESSION['staff_id']  = $staff['staff_id'];
$_SESSION['full_name'] = $staff['full_name'];
$_SESSION['role']      = $staff['role'];
log_activity($conn, $staff['staff_id'], 'LOGIN_GOOGLE', 'staff', $staff['staff_id'], 'Staff signed in with Google (web)');
echo json_encode(['success' => true]);
