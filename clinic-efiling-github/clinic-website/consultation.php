<?php
require_once 'auth.php';
require_once 'db.php';
if (!in_array($session_role, ['Doctor','Nurse'])) { header('Location: dashboard.php'); exit; }

$pid = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$patient = null;
$error = '';

if ($pid) {
    $s = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $s->bind_param('i', $pid); $s->execute();
    $patient = $s->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient) {
    $visit_date  = clean($conn, $_POST['visit_date']      ?? '');
    $notes       = clean($conn, $_POST['notes']           ?? '');
    $diagnosis   = clean($conn, $_POST['diagnosis']       ?? '');
    $treatment   = clean($conn, $_POST['treatment']       ?? '');
    $next_visit  = clean($conn, $_POST['next_visit_date'] ?? '');

    if (!$visit_date || !$diagnosis) {
        $error = 'Visit date and diagnosis are required.';
    } else {
        $next = $next_visit ?: null;
        $stmt = $conn->prepare(
            "INSERT INTO consultations (patient_id,staff_id,visit_date,notes,diagnosis,treatment,next_visit_date)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('iisssss', $pid,$session_id,$visit_date,$notes,$diagnosis,$treatment,$next);
        $stmt->execute();
        $new_id = $conn->insert_id;
        log_activity($conn, $session_id, 'ADD_CONSULTATION', 'consultations', $new_id,
            "Consultation added for {$patient['first_name']} {$patient['last_name']}");
        header("Location: profile.php?id=$pid&tab=consultations"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Consultation — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Add Consultation</h1>
            <?php if ($patient): ?>
            <p class="page-sub">
                Patient: <strong><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']) ?></strong>
                &mdash; <code><?= htmlspecialchars($patient['id_number']) ?></code>
            </p>
            <?php endif; ?>
        </div>
        <?php if ($patient): ?>
        <a href="profile.php?id=<?= $pid ?>" class="btn btn-outline">&larr; Back to Profile</a>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">&#9888; <?= $error ?></div>
    <?php endif; ?>

    <?php if (!$patient): ?>
    <div class="card">
        <div class="card-header"><h2>&#9679; Find Patient</h2></div>
        <div class="card-body">
            <form method="GET" class="search-bar">
                <input type="text" name="q" class="search-input" placeholder="Enter SA ID or patient name..." autofocus>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <?php
            if (isset($_GET['q'])) {
                $q = clean($conn, $_GET['q']); $like = "%$q%";
                $res = $conn->prepare("SELECT patient_id,id_number,first_name,last_name FROM patients WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?");
                $res->bind_param('sss',$like,$like,$like); $res->execute();
                $res = $res->get_result();
                if ($res->num_rows > 0) {
                    echo '<div class="table-wrap"><table><thead><tr><th>Name</th><th>ID</th><th></th></tr></thead><tbody>';
                    while ($r = $res->fetch_assoc()) {
                        echo "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td>
                              <td><code>".htmlspecialchars($r['id_number'])."</code></td>
                              <td><a href='consultation.php?patient_id={$r['patient_id']}' class='btn btn-outline btn-sm'>Select</a></td></tr>";
                    }
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info">No patients found.</div>';
                }
            }
            ?>
        </div>
    </div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="patient_id" value="<?= $pid ?>">
        <div class="card">
            <div class="card-header"><h2>&#9679; Consultation Details</h2></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Visit Date <span class="req">*</span></label>
                        <input type="date" name="visit_date" value="<?= htmlspecialchars($_POST['visit_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Next Visit Date</label>
                        <input type="date" name="next_visit_date" value="<?= htmlspecialchars($_POST['next_visit_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>Consultation Notes</label>
                        <textarea name="notes" placeholder="Describe presenting symptoms and clinical observations..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Diagnosis <span class="req">*</span></label>
                        <input type="text" name="diagnosis" value="<?= htmlspecialchars($_POST['diagnosis'] ?? '') ?>" placeholder="e.g. Hypertension, Influenza" required>
                    </div>
                    <div class="form-group">
                        <label>Treatment / Prescription</label>
                        <input type="text" name="treatment" value="<?= htmlspecialchars($_POST['treatment'] ?? '') ?>" placeholder="e.g. Paracetamol 500mg, bed rest">
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary btn-lg">Save Consultation</button>
            <a href="profile.php?id=<?= $pid ?>" class="btn btn-outline btn-lg">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
