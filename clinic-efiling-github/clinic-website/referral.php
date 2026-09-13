<?php
require_once 'auth.php';
require_once 'db.php';
if ($session_role !== 'Doctor') { header('Location: dashboard.php'); exit; }

$pid = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$patient = null; $error = '';

if ($pid) {
    $s = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $s->bind_param('i', $pid); $s->execute();
    $patient = $s->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient) {
    $referral_date = clean($conn, $_POST['referral_date'] ?? date('Y-m-d'));
    $referred_to   = clean($conn, $_POST['referred_to']   ?? '');
    $reason        = clean($conn, $_POST['reason']        ?? '');

    if (!$referred_to || !$reason) { $error = 'All fields are required.'; }
    else {
        $stmt = $conn->prepare(
            "INSERT INTO referrals (patient_id,staff_id,referral_date,referred_to,reason,referral_type)
             VALUES (?,?,?,?,?,'Outgoing')"
        );
        $stmt->bind_param('iisss', $pid,$session_id,$referral_date,$referred_to,$reason);
        $stmt->execute();
        $new_id = $conn->insert_id;
        log_activity($conn, $session_id, 'CREATE_REFERRAL', 'referrals', $new_id,
            "Outgoing referral for {$patient['first_name']} {$patient['last_name']} to $referred_to");
        header("Location: profile.php?id=$pid&tab=referrals"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outgoing Referral — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Create Outgoing Referral</h1>
            <?php if ($patient): ?>
            <p class="page-sub">
                Referring <strong><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']) ?></strong>
                &mdash; <code><?= htmlspecialchars($patient['id_number']) ?></code>
                to an external facility
            </p>
            <?php endif; ?>
        </div>
        <?php if ($patient): ?>
        <a href="profile.php?id=<?= $pid ?>" class="btn btn-outline">&larr; Back</a>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        &#8599; Use this form to refer a patient <strong>from</strong> Nelspruit Municipal Clinic
        <strong>to</strong> another hospital or specialist department.
        For a patient arriving with a referral, use
        <a href="incoming_referral.php<?= $pid ? "?patient_id=$pid" : '' ?>">Incoming Referral</a> instead.
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">&#9888; <?= $error ?></div>
    <?php endif; ?>

    <?php if ($patient): ?>
    <form method="POST">
        <input type="hidden" name="patient_id" value="<?= $pid ?>">
        <div class="card">
            <div class="card-header"><h2>&#9679; Referral Details</h2></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Referral Date <span class="req">*</span></label>
                        <input type="date" name="referral_date" value="<?= htmlspecialchars($_POST['referral_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Refer To (Department / Hospital) <span class="req">*</span></label>
                        <input type="text" name="referred_to"
                               value="<?= htmlspecialchars($_POST['referred_to'] ?? '') ?>"
                               placeholder="e.g. Cardiology Dept, Nelspruit Hospital" required>
                    </div>
                </div>
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>Clinical Reason for Referral <span class="req">*</span></label>
                        <textarea name="reason" rows="6"
                                  placeholder="Provide detailed clinical reason for the referral..." required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary btn-lg">&#8599; Save Referral</button>
            <a href="profile.php?id=<?= $pid ?>" class="btn btn-outline btn-lg">Cancel</a>
        </div>
    </form>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">&#9432; No patient selected. <a href="search.php">Search for a patient</a> first.</div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
