<?php
// ============================================================
// incoming_referral.php — Record an incoming referral
// Patient arrives from another clinic/hospital with a referral letter
// ============================================================
require_once 'auth.php';
require_once 'db.php';

$success = $error = '';
$patient_id = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$patient = null;

if ($patient_id) {
    $s = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $s->bind_param('i', $patient_id);
    $s->execute();
    $patient = $s->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient) {
    $referring_facility = clean($conn, $_POST['referring_facility'] ?? '');
    $referring_doctor   = clean($conn, $_POST['referring_doctor']   ?? '');
    $referral_date      = clean($conn, $_POST['referral_date']      ?? date('Y-m-d'));
    $reason             = clean($conn, $_POST['reason']             ?? '');
    $referred_to        = 'Nelspruit Municipal Clinic';
    $referral_type      = 'Incoming';
    $letter_path        = null;

    // Handle file upload
    if (!empty($_FILES['referral_letter']['name'])) {
        $upload_dir = 'uploads/referrals/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = pathinfo($_FILES['referral_letter']['name'], PATHINFO_EXTENSION);
        $filename = 'incoming_' . $patient_id . '_' . time() . '.' . $ext;
        $target   = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['referral_letter']['tmp_name'], $target)) {
            $letter_path = $target;
        }
    }

    if (!$referring_facility || !$reason) {
        $error = 'Referring facility and reason are required.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO referrals
             (patient_id, staff_id, referral_date, referred_to, reason,
              referral_type, referring_facility, referring_doctor, referral_letter_path)
             VALUES (?, ?, ?, ?, ?, 'Incoming', ?, ?, ?)"
        );
        $stmt->bind_param('iisssss',
            $patient_id, $session_id, $referral_date,
            $referred_to, $reason,
            $referring_facility, $referring_doctor, $letter_path
        );
        $stmt->execute();
        $new_id = $conn->insert_id;

        log_activity($conn, $session_id, 'RECORD_INCOMING_REFERRAL', 'referrals', $new_id,
            "Incoming referral recorded for {$patient['first_name']} {$patient['last_name']} from $referring_facility");

        header("Location: profile.php?id=$patient_id&tab=referrals&incoming=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Incoming Referral — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
<?php require_once 'includes.php'; ?>

<div class="main">
    <div class="page-header">
        <div>
            <h1><i class="ti ti-file-import"></i> Record Incoming Referral</h1>
            <div class="sub">
                Patient referred TO this clinic FROM another facility
                <?php if ($patient): ?>
                — <strong><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></strong>
                &nbsp;<code><?= htmlspecialchars($patient['id_number']) ?></code>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($patient): ?>
            <a href="profile.php?id=<?= $patient_id ?>" class="btn btn-secondary">
                <i class="ti ti-arrow-left"></i> Back to Profile
            </a>
        <?php else: ?>
            <a href="search.php" class="btn btn-secondary">
                <i class="ti ti-search"></i> Find Patient First
            </a>
        <?php endif; ?>
    </div>

    <!-- Info banner -->
    <div class="alert alert-info" style="margin-bottom:20px;">
        <i class="ti ti-info-circle"></i>
        Use this form when a patient arrives at our clinic with a referral letter from
        <strong>another hospital or clinic</strong>. This records who sent them and why.
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="ti ti-alert-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <?php if (!$patient): ?>
        <!-- Search for patient first -->
        <div class="card">
            <div class="card-title"><i class="ti ti-search"></i> Find Patient</div>
            <form method="GET" class="search-bar">
                <input type="hidden" name="action" value="find">
                <input type="text" name="q" placeholder="Enter SA ID number or patient name..." autofocus>
                <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i> Search</button>
            </form>
            <?php
            if (isset($_GET['q'])) {
                $q    = clean($conn, $_GET['q']);
                $like = "%$q%";
                $res  = $conn->prepare(
                    "SELECT patient_id, id_number, first_name, last_name
                     FROM patients WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?"
                );
                $res->bind_param('sss', $like, $like, $like);
                $res->execute();
                $res = $res->get_result();
                if ($res->num_rows > 0) {
                    echo '<div class="table-wrap"><table><thead><tr><th>Name</th><th>ID</th><th></th></tr></thead><tbody>';
                    while ($r = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . "</td>
                            <td><code>" . htmlspecialchars($r['id_number']) . "</code></td>
                            <td><a href='incoming_referral.php?patient_id={$r['patient_id']}' class='btn btn-primary btn-sm'>Select</a></td>
                        </tr>";
                    }
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info">No patients found. <a href="register.php">Register this patient first</a>?</div>';
                }
            }
            ?>
        </div>

    <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

            <!-- Referring facility details -->
            <div class="card">
                <div class="card-title"><i class="ti ti-building-hospital"></i> Referring Facility Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Referring Hospital / Clinic <span style="color:red">*</span></label>
                        <input type="text" name="referring_facility"
                               value="<?= htmlspecialchars($_POST['referring_facility'] ?? '') ?>"
                               placeholder="e.g. Shongwe Hospital, Rob Ferreira Hospital"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Referring Doctor</label>
                        <input type="text" name="referring_doctor"
                               value="<?= htmlspecialchars($_POST['referring_doctor'] ?? '') ?>"
                               placeholder="e.g. Dr. Nkosi Dlamini">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Referral <span style="color:red">*</span></label>
                        <input type="date" name="referral_date"
                               value="<?= htmlspecialchars($_POST['referral_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Upload Referral Letter (PDF or image)</label>
                        <input type="file" name="referral_letter" accept=".pdf,.jpg,.jpeg,.png">
                        <span style="font-size:11px;color:var(--gray);margin-top:4px;">Optional — scan or photo of the physical letter</span>
                    </div>
                </div>
            </div>

            <!-- Reason -->
            <div class="card">
                <div class="card-title"><i class="ti ti-notes"></i> Reason for Referral</div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Clinical Reason <span style="color:red">*</span></label>
                        <textarea name="reason" rows="5"
                                  placeholder="Describe the clinical reason the patient was referred to this clinic..."
                                  required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-success">
                    <i class="ti ti-circle-check"></i> Save Incoming Referral
                </button>
                <a href="profile.php?id=<?= $patient_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
