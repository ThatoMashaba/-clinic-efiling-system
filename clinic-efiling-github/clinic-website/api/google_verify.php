<?php
// Paste the WEB application client ID from Google Cloud Console
define('GOOGLE_WEB_CLIENT_ID', 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com');

// Verifies a Google ID token and returns [staff_row, null] or [null, error_message].
// Only staff whose email already exists in the staff table can sign in.
function verify_google_staff($conn, $idToken) {
    if (!$idToken) return [null, 'No Google token received'];
    $ctx  = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $raw  = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken), false, $ctx);
    $info = $raw ? json_decode($raw, true) : null;
    if (!$info || isset($info['error']) || empty($info['email'])) return [null, 'Google could not verify this sign-in'];
    if (($info['aud'] ?? '') !== GOOGLE_WEB_CLIENT_ID) return [null, 'This Google token was not issued for the clinic app'];
    if (($info['email_verified'] ?? '') !== 'true') return [null, 'Your Google email is not verified'];
    $email = strtolower($info['email']);
    $stmt = $conn->prepare("SELECT staff_id, full_name, role FROM staff WHERE LOWER(email) = ? AND is_active = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? [$row, null] : [null, 'No staff account is linked to this Google email. Ask the administrator to add it.'];
}
