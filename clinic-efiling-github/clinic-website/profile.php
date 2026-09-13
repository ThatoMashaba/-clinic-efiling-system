<?php
require_once 'auth.php';
require_once 'db.php';

$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { header('Location: search.php'); exit; }

$stmt = $conn->prepare(
    "SELECT p.*, a.street, a.suburb, a.city, a.province, a.postal_code, s.full_name AS registered_by
     FROM patients p
     LEFT JOIN addresses a ON p.address_id = a.address_id
     LEFT JOIN staff s     ON p.created_by  = s.staff_id
     WHERE p.patient_id = ?"
);
$stmt->bind_param('i', $pid); $stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { header('Location: search.php'); exit; }

$consults = $conn->prepare(
    "SELECT c.*, s.full_name AS doctor_name FROM consultations c
     JOIN staff s ON c.staff_id=s.staff_id WHERE c.patient_id=? ORDER BY c.visit_date DESC"
);
$consults->bind_param('i', $pid); $consults->execute();
$consults = $consults->get_result();

$refs = $conn->prepare(
    "SELECT r.*, s.full_name AS doctor_name FROM referrals r
     JOIN staff s ON r.staff_id=s.staff_id WHERE r.patient_id=? ORDER BY r.referral_date DESC"
);
$refs->bind_param('i', $pid); $refs->execute();
$refs = $refs->get_result();

$docs = $conn->prepare(
    "SELECT d.*, s.full_name AS uploaded_by FROM medical_documents d
     JOIN staff s ON d.staff_id=s.staff_id WHERE d.patient_id=? ORDER BY d.upload_date DESC"
);
$docs->bind_param('i', $pid); $docs->execute();
$docs = $docs->get_result();

log_activity($conn, $session_id, 'VIEW_PATIENT', 'patients', $pid, "Viewed: {$p['first_name']} {$p['last_name']}");

$tab      = $_GET['tab'] ?? 'overview';
$initials = strtoupper(substr($p['first_name'],0,1).substr($p['last_name'],0,1));
$registered = $_GET['registered'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?> — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">

    <?php if ($registered): ?>
    <div class="alert alert-success">&#10003; Patient registered successfully!</div>
    <?php endif; ?>

    <div class="card">
        <!-- Patient hero header -->
        <div class="patient-hero">
            <div class="patient-avatar"><?= $initials ?></div>
            <div style="flex:1;">
                <div class="patient-hero-name"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></div>
                <div class="patient-hero-id"><?= htmlspecialchars($p['id_number']) ?></div>
                <span class="badge <?= $p['gender']==='Male'?'badge-navy':'badge-green' ?>" style="margin-top:6px;display:inline-flex;">
                    <?= $p['gender'] ?>
                </span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if (in_array($session_role, ['Doctor','Nurse'])): ?>
                <a href="consultation.php?patient_id=<?= $pid ?>" class="btn btn-primary btn-sm">&#43; Consultation</a>
                <?php endif; ?>
                <?php if ($session_role === 'Doctor'): ?>
                <a href="referral.php?patient_id=<?= $pid ?>" class="btn btn-outline btn-sm">&#8599; Referral</a>
                <a href="incoming_referral.php?patient_id=<?= $pid ?>" class="btn btn-outline btn-sm">&#8601; Incoming</a>
                <?php endif; ?>
                <a href="search.php" class="btn btn-ghost btn-sm">&larr; Back</a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?id=<?= $pid ?>&tab=overview"      class="<?= $tab==='overview'      ?'active':'' ?>">Overview</a>
            <a href="?id=<?= $pid ?>&tab=consultations"  class="<?= $tab==='consultations'  ?'active':'' ?>">Consultations (<?= $consults->num_rows ?>)</a>
            <a href="?id=<?= $pid ?>&tab=referrals"      class="<?= $tab==='referrals'      ?'active':'' ?>">Referrals (<?= $refs->num_rows ?>)</a>
            <a href="?id=<?= $pid ?>&tab=documents"      class="<?= $tab==='documents'      ?'active':'' ?>">Documents (<?= $docs->num_rows ?>)</a>
        </div>

        <!-- Overview -->
        <?php if ($tab === 'overview'): ?>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;">
                <div>
                    <p style="font-size:12px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">Personal Details</p>
                    <?php
                    $fields = [
                        'Full Name'     => $p['first_name'].' '.$p['last_name'],
                        'SA ID Number'  => $p['id_number'],
                        'Date of Birth' => $p['date_of_birth'] ? date('d F Y', strtotime($p['date_of_birth'])) : '—',
                        'Gender'        => $p['gender'],
                        'Contact'       => $p['contact_number'] ?? '—',
                        'Email'         => $p['email'] ?? '—',
                    ];
                    foreach ($fields as $label => $value):
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);">
                        <span style="color:var(--slate);font-size:13px;"><?= $label ?></span>
                        <span style="font-weight:500;font-size:13px;"><?= htmlspecialchars($value) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div>
                    <p style="font-size:12px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">Address</p>
                    <?php
                    $addr = [
                        'Street'      => $p['street']      ?? '—',
                        'Suburb'      => $p['suburb']      ?? '—',
                        'City'        => $p['city']        ?? '—',
                        'Province'    => $p['province']    ?? '—',
                        'Postal Code' => $p['postal_code'] ?? '—',
                    ];
                    foreach ($addr as $label => $value):
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);">
                        <span style="color:var(--slate);font-size:13px;"><?= $label ?></span>
                        <span style="font-weight:500;font-size:13px;"><?= htmlspecialchars($value) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:14px;font-size:12px;color:var(--slate-light);">
                        Registered by <?= htmlspecialchars($p['registered_by'] ?? 'Unknown') ?>
                        on <?= date('d F Y', strtotime($p['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consultations -->
        <?php elseif ($tab === 'consultations'): ?>
        <div class="card-body">
            <?php if (in_array($session_role, ['Doctor','Nurse'])): ?>
            <a href="consultation.php?patient_id=<?= $pid ?>" class="btn btn-primary btn-sm" style="margin-bottom:16px;">&#43; Add Consultation</a>
            <?php endif; ?>
            <?php if ($consults->num_rows === 0): ?>
            <div class="alert alert-info">&#9432; No consultations recorded yet.</div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Doctor / Nurse</th><th>Diagnosis</th><th>Treatment</th><th>Next Visit</th></tr></thead>
                    <tbody>
                    <?php while ($c = $consults->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($c['visit_date'])) ?></td>
                        <td><?= htmlspecialchars($c['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($c['diagnosis'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($c['treatment'] ?? '—') ?></td>
                        <td><?= $c['next_visit_date'] ? date('d M Y', strtotime($c['next_visit_date'])) : '—' ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Referrals -->
        <?php elseif ($tab === 'referrals'): ?>
        <div class="card-body">
            <?php if ($session_role === 'Doctor'): ?>
            <div style="display:flex;gap:8px;margin-bottom:16px;">
                <a href="referral.php?patient_id=<?= $pid ?>" class="btn btn-primary btn-sm">&#8599; Outgoing Referral</a>
                <a href="incoming_referral.php?patient_id=<?= $pid ?>" class="btn btn-outline btn-sm">&#8601; Record Incoming</a>
            </div>
            <?php endif; ?>
            <?php if ($refs->num_rows === 0): ?>
            <div class="alert alert-info">&#9432; No referrals recorded yet.</div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Type</th><th>Facility</th><th>Reason</th><th>Doctor</th></tr></thead>
                    <tbody>
                    <?php while ($r = $refs->fetch_assoc()):
                        $is_incoming = ($r['referral_type'] ?? 'Outgoing') === 'Incoming';
                    ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($r['referral_date'])) ?></td>
                        <td>
                            <?php if ($is_incoming): ?>
                            <span class="referral-incoming">&#8601; Incoming</span>
                            <?php else: ?>
                            <span class="referral-outgoing">&#8599; Outgoing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_incoming): ?>
                            <strong><?= htmlspecialchars($r['referring_facility'] ?? '—') ?></strong>
                            <?php if ($r['referring_doctor']): ?>
                            <br><span style="font-size:12px;color:var(--slate);"><?= htmlspecialchars($r['referring_doctor']) ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <?= htmlspecialchars($r['referred_to']) ?>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:200px;"><?= htmlspecialchars(substr($r['reason'] ?? '', 0, 80)) ?>...</td>
                        <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Documents -->
        <?php elseif ($tab === 'documents'): ?>
        <div class="card-body">
            <?php if ($docs->num_rows === 0): ?>
            <div class="alert alert-info">&#9432; No documents uploaded yet.</div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>File Name</th><th>Type</th><th>Uploaded By</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php while ($d = $docs->fetch_assoc()): ?>
                    <tr>
                        <td>&#128196; <?= htmlspecialchars($d['file_name']) ?></td>
                        <td><span class="badge badge-navy"><?= htmlspecialchars($d['document_type']) ?></span></td>
                        <td><?= htmlspecialchars($d['uploaded_by']) ?></td>
                        <td><?= date('d M Y', strtotime($d['upload_date'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
