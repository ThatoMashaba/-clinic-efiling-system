<?php
require_once 'auth.php';
require_once 'db.php';

$report    = $_GET['report']    ?? 'patients';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$df = clean($conn, $date_from);
$dt = clean($conn, $date_to);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Reports</h1>
            <p class="page-sub">Generate and export clinic reports by date range</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline">&#128438; Print Report</button>
    </div>

    <!-- Filter card -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2>&#9679; Report Filter</h2></div>
        <div class="card-body">
            <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div class="form-group" style="min-width:200px;">
                    <label>Report Type</label>
                    <select name="report">
                        <option value="patients"      <?= $report==='patients'      ?'selected':'' ?>>Patient List</option>
                        <option value="consultations" <?= $report==='consultations' ?'selected':'' ?>>Consultation Report</option>
                        <option value="referrals"     <?= $report==='referrals'     ?'selected':'' ?>>Referral Summary</option>
                        <option value="activity"      <?= $report==='activity'      ?'selected':'' ?>>Staff Activity Log</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>
    </div>

    <!-- Report output -->
    <div class="card" id="report-output">
        <?php
        // ── Patient List ─────────────────────────────────────
        if ($report === 'patients'):
            $rows = $conn->query(
                "SELECT p.patient_id,p.id_number,p.first_name,p.last_name,p.gender,
                        p.contact_number,p.date_of_birth,a.city,a.province,p.created_at
                 FROM patients p LEFT JOIN addresses a ON p.address_id=a.address_id
                 WHERE DATE(p.created_at) BETWEEN '$df' AND '$dt'
                 ORDER BY p.last_name,p.first_name"
            );
        ?>
        <div class="card-header">
            <h2>&#9679; Patient List &mdash; <?= date('d M Y',strtotime($df)) ?> to <?= date('d M Y',strtotime($dt)) ?></h2>
            <span class="badge badge-navy"><?= $rows->num_rows ?> patients</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>SA ID</th><th>Gender</th><th>Contact</th><th>Date of Birth</th><th>City</th><th>Registered</th></tr></thead>
                <tbody>
                <?php $i=1; while ($r=$rows->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--slate-light);"><?= $i++ ?></td>
                    <td><a href="profile.php?id=<?= $r['patient_id'] ?>" style="color:var(--navy);font-weight:500;"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></a></td>
                    <td><code><?= htmlspecialchars($r['id_number']) ?></code></td>
                    <td><span class="badge <?= $r['gender']==='Male'?'badge-navy':'badge-green' ?>"><?= $r['gender'] ?></span></td>
                    <td><?= htmlspecialchars($r['contact_number'] ?? '—') ?></td>
                    <td><?= $r['date_of_birth'] ? date('d M Y',strtotime($r['date_of_birth'])) : '—' ?></td>
                    <td><?= htmlspecialchars($r['city'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--slate);"><?= date('d M Y',strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($report === 'consultations'):
            $rows = $conn->query(
                "SELECT c.consultation_id,c.visit_date,c.diagnosis,c.treatment,c.next_visit_date,
                        p.first_name,p.last_name,p.id_number,s.full_name AS doctor
                 FROM consultations c
                 JOIN patients p ON c.patient_id=p.patient_id
                 JOIN staff s    ON c.staff_id=s.staff_id
                 WHERE c.visit_date BETWEEN '$df' AND '$dt'
                 ORDER BY c.visit_date DESC"
            );
        ?>
        <div class="card-header">
            <h2>&#9679; Consultation Report &mdash; <?= date('d M Y',strtotime($df)) ?> to <?= date('d M Y',strtotime($dt)) ?></h2>
            <span class="badge badge-green"><?= $rows->num_rows ?> consultations</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Patient</th><th>ID Number</th><th>Diagnosis</th><th>Treatment</th><th>Doctor / Nurse</th><th>Next Visit</th></tr></thead>
                <tbody>
                <?php $i=1; while ($r=$rows->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--slate-light);"><?= $i++ ?></td>
                    <td><?= date('d M Y',strtotime($r['visit_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($r['id_number']) ?></code></td>
                    <td><?= htmlspecialchars($r['diagnosis'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['treatment'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['doctor']) ?></td>
                    <td><?= $r['next_visit_date'] ? date('d M Y',strtotime($r['next_visit_date'])) : '—' ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($report === 'referrals'):
            $rows = $conn->query(
                "SELECT r.referral_date,r.referred_to,r.reason,r.referral_type,
                        r.referring_facility,r.referring_doctor,
                        p.first_name,p.last_name,p.id_number,s.full_name AS doctor
                 FROM referrals r
                 JOIN patients p ON r.patient_id=p.patient_id
                 JOIN staff s    ON r.staff_id=s.staff_id
                 WHERE r.referral_date BETWEEN '$df' AND '$dt'
                 ORDER BY r.referral_date DESC"
            );
        ?>
        <div class="card-header">
            <h2>&#9679; Referral Summary &mdash; <?= date('d M Y',strtotime($df)) ?> to <?= date('d M Y',strtotime($dt)) ?></h2>
            <span class="badge badge-warning"><?= $rows->num_rows ?> referrals</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Patient</th><th>Facility</th><th>Reason</th><th>Doctor</th></tr></thead>
                <tbody>
                <?php $i=1; while ($r=$rows->fetch_assoc()):
                    $incoming = ($r['referral_type'] ?? 'Outgoing') === 'Incoming';
                ?>
                <tr>
                    <td style="color:var(--slate-light);"><?= $i++ ?></td>
                    <td><?= date('d M Y',strtotime($r['referral_date'])) ?></td>
                    <td><?php if ($incoming): ?><span class="referral-incoming">&#8601; Incoming</span>
                        <?php else: ?><span class="referral-outgoing">&#8599; Outgoing</span><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong><br>
                        <code style="font-size:11px;"><?= htmlspecialchars($r['id_number']) ?></code></td>
                    <td><?= htmlspecialchars($incoming ? ($r['referring_facility'] ?? '—') : $r['referred_to']) ?></td>
                    <td style="max-width:180px;font-size:12px;"><?= htmlspecialchars(substr($r['reason'] ?? '', 0, 80)) ?>...</td>
                    <td><?= htmlspecialchars($r['doctor']) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($report === 'activity'):
            if ($session_role !== 'Administrator'):
        ?>
        <div class="card-body">
            <div class="alert alert-danger">&#128274; Only Administrators can view the Activity Log.</div>
        </div>
        <?php else:
            $rows = $conn->query(
                "SELECT l.action_type,l.description,l.action_timestamp,s.full_name,s.role
                 FROM activity_logs l JOIN staff s ON l.staff_id=s.staff_id
                 WHERE DATE(l.action_timestamp) BETWEEN '$df' AND '$dt'
                 ORDER BY l.action_timestamp DESC"
            );
        ?>
        <div class="card-header">
            <h2>&#9679; Staff Activity Log &mdash; <?= date('d M Y',strtotime($df)) ?> to <?= date('d M Y',strtotime($dt)) ?></h2>
            <span class="badge badge-gray"><?= $rows->num_rows ?> entries</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Timestamp</th><th>Staff Member</th><th>Role</th><th>Action</th><th>Description</th></tr></thead>
                <tbody>
                <?php $i=1; while ($r=$rows->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--slate-light);"><?= $i++ ?></td>
                    <td style="font-size:12px;"><?= date('d M Y H:i',strtotime($r['action_timestamp'])) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><span class="badge badge-navy"><?= htmlspecialchars($r['role']) ?></span></td>
                    <td><code style="font-size:11px;"><?= htmlspecialchars($r['action_type']) ?></code></td>
                    <td style="font-size:12.5px;"><?= htmlspecialchars($r['description'] ?? '—') ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; endif; ?>
    </div>
</div>
<style>
@media print {
    .sidebar,.page-header .btn,form,.card-header .btn{display:none!important;}
    .main{margin-left:0!important;padding:0!important;}
}
</style>
</body>
</html>
