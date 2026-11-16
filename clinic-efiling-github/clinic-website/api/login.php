<?php
// ============================================================
// api/login.php — POST: username, password → token + role
// ============================================================
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, "POST required");

$input    = json_decode(file_get_contents("php://input"), true);
$username = clean($conn, $input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) respond(false, "Username and password required");

$stmt = $conn->prepare(
    "SELECT staff_id, full_name, role, password FROM staff
     WHERE username = ? AND is_active = 1"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($password, $row['password'])) {
    respond(false, "Invalid username or password");
}

// Generate session token
$token = bin2hex(random_bytes(32));

// Store token in a simple tokens table (create if not exists)
$conn->query("CREATE TABLE IF NOT EXISTS api_tokens (
    token VARCHAR(64) PRIMARY KEY,
    staff_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
)");
$conn->query("DELETE FROM api_tokens WHERE staff_id = {$row['staff_id']}");
$conn->query("INSERT INTO api_tokens (token, staff_id) VALUES ('$token', {$row['staff_id']})");

respond(true, "Login successful", [
    "token"     => $token,
    "staff_id"  => $row['staff_id'],
    "full_name" => $row['full_name'],
    "role"      => $row['role'],
]);
