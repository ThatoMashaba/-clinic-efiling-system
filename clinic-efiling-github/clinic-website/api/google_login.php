<?php
// POST {id_token} -> same JSON shape as login.php (used by the Android app)
require_once 'config.php';
require_once 'google_verify.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, "POST required");
$in = json_decode(file_get_contents("php://input"), true);
[$staff, $err] = verify_google_staff($conn, $in['id_token'] ?? '');
if (!$staff) respond(false, $err);
$id    = (int)$staff['staff_id'];
$token = bin2hex(random_bytes(32));
$conn->query("DELETE FROM api_tokens WHERE staff_id = $id");
$stmt = $conn->prepare("INSERT INTO api_tokens (token, staff_id) VALUES (?, ?)");
$stmt->bind_param('si', $token, $id);
$stmt->execute();
$conn->query("INSERT INTO activity_logs (staff_id, action_type, target_table, target_record_id, description)
              VALUES ($id, 'LOGIN_GOOGLE', 'staff', $id, 'Staff signed in with Google (app)')");
respond(true, "Login successful", ["token" => $token, "staff_id" => $id, "full_name" => $staff['full_name'], "role" => $staff['role']]);
