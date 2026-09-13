<?php
// ============================================================
// db.php — Database connection
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:Inter,sans-serif;padding:2rem;color:#DC2626;">
        <h2>Database Connection Failed</h2>
        <p>' . $conn->connect_error . '</p>
        <p>Make sure XAMPP is running and you have imported <code>clinic_db.sql</code>.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

function log_activity($conn, $staff_id, $action, $table = null, $record_id = null, $desc = null) {
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (staff_id, action_type, target_table, target_record_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss', $staff_id, $action, $table, $record_id, $desc);
    $stmt->execute();
    $stmt->close();
}

function clean($conn, $val) {
    return $conn->real_escape_string(htmlspecialchars(trim($val)));
}
