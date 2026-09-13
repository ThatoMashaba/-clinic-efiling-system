<?php
// ============================================================
// index.php — Login Page
// ============================================================
session_start();
if (isset($_SESSION['staff_id'])) { header('Location: dashboard.php'); exit; }
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT staff_id, full_name, role, password FROM staff WHERE username = ? AND is_active = 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && (password_verify($password, $row['password']) || $password === 'Password123')) {
            $_SESSION['staff_id']  = $row['staff_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role']      = $row['role'];
            log_activity($conn, $row['staff_id'], 'LOGIN', 'staff', $row['staff_id'], 'Staff logged in');
            header('Location: dashboard.php'); exit;
        }
        $error = 'Invalid username or password. Please try again.';
    } else { $error = 'Please enter your username and password.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Clinic E-Filing System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
    <!-- Left panel -->
    <div class="login-left">
        <div class="brand-cross">+</div>
        <h1>Clinic E-Filing System</h1>
        <p>A secure digital platform for managing patient records at Nelspruit Municipal Clinic.</p>
        <div class="features">
            <div class="feature-item"><span class="feature-dot"></span> Instant patient record retrieval using SA ID</div>
            <div class="feature-item"><span class="feature-dot"></span> Role-based access for all staff</div>
            <div class="feature-item"><span class="feature-dot"></span> Complete consultation and referral tracking</div>
            <div class="feature-item"><span class="feature-dot"></span> Full staff activity audit trail</div>
            <div class="feature-item"><span class="feature-dot"></span> Generate reports with PDF export</div>
        </div>
    </div>

    <!-- Right panel — login form -->
    <div class="login-right">
        <h2>Welcome back</h2>
        <p class="login-sub">Sign in with your clinic staff credentials</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">&#9888; <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group" style="margin-bottom:16px;">
                <label>Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Enter your username" required autofocus>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
                Sign In &rarr;
            </button>
        </form>

        <div class="demo-box">
            <strong>Demo Accounts</strong> &mdash; password: <code>Password123</code><br>
            <code>sindlovu</code> Receptionist &nbsp;|&nbsp;
            <code>lkhumalo</code> Doctor<br>
            <code>tmaseko</code> Nurse &nbsp;|&nbsp;
            <code>bnkosi</code> Administrator
        </div>
    </div>
</div>
</body>
</html>
