<?php
// ============================================================
// auth.php — Session protection
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: index.php'); exit;
}
$session_id   = $_SESSION['staff_id'];
$session_name = $_SESSION['full_name'];
$session_role = $_SESSION['role'];
