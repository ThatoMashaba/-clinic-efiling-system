<?php
// ============================================================
// api/auth.php — Token validation helper
// Include in every protected endpoint
// ============================================================
function getAuthStaff($conn) {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token   = str_replace('Bearer ', '', $auth);
    if (!$token) return null;
    $token = $conn->real_escape_string($token);
    $res = $conn->query(
        "SELECT s.staff_id, s.full_name, s.role
         FROM api_tokens t JOIN staff s ON t.staff_id = s.staff_id
         WHERE t.token = '$token'"
    );
    return $res ? $res->fetch_assoc() : null;
}
