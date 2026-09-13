<?php
require_once 'auth.php';
require_once 'db.php';

$total_patients      = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$total_consultations = $conn->query("SELECT COUNT(*) c FROM consultations")->fetch_assoc()['c'];
$total_referrals     = $conn->query("SELECT COUNT(*) c FROM referrals")->fetch_assoc()['c'];
$today_visits        = $conn->query("SELECT COUNT(*) c FROM consultations WHERE visit_date = CURDATE()")->fetch_assoc()['c'];
$incoming_refs       = $conn->query("SELECT COUNT(*) c FROM referrals WHERE referral_type='Incoming'")->fetch_assoc()['c'];

$recent = $conn->query(
    "SELECT p.patient_id, p.first_name, p.last_name, p.id_number, p.gender, p.created_at,
            s.full_name AS registered_by
     FROM patients p LEFT JOIN staff s ON p.created_by = s.staff_id
     ORDER BY p.created_at DESC LIMIT 8"
);

$logs = $conn->query(
    "SELECT l.description, l.action_type, l.action_timestamp, s.full_name
     FROM activity_logs l JOIN staff s ON l.staff_id = s.staff_id
     ORDER BY l.action_timestamp DESC LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">

    <div class="page-header">
        <div class="page-header-left">
            <h1>Dashboard</h1>
            <p class="page-sub">Welcome back, <?= htmlspecialchars($session_name) ?> &mdash; <?= date('l, d F Y') ?></p>
        </div>
        <a href="register.php" class="btn btn-primary">&#43; Register Patient</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-label">Total Patients</div>
            <div class="stat-value"><?= $total_patients ?></div>
            <div class="stat-note">Registered in system</div>
        </div>
        <div class="stat-card navy">
            <div class="stat-label">Consultations</div>
            <div class="stat-value"><?= $total_consultations ?></div>
            <div class="stat-note">All time</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">Today's Visits</div>
            <div class="stat-value"><?= $today_visits ?></div>
            <div class="stat-note"><?= date('d M Y') ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Referrals</div>
            <div class="stat-value"><?= $total_referrals ?></div>
            <div class="stat-note"><?= $incoming_refs ?> incoming</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- Recent patients -->
        <div class="card">
            <div class="card-header">
                <h2>&#9679; Recent Patients</h2>
                <a href="search.php" class="btn btn-ghost btn-sm">View all</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>SA ID</th>
                            <th>Gender</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($p = $recent->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                            </td>
                            <td><code style="font-size:12px;"><?= htmlspecialchars($p['id_number']) ?></code></td>
                            <td>
                                <span class="badge <?= $p['gender']==='Male' ? 'badge-navy' : 'badge-green' ?>">
                                    <?= $p['gender'] ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--slate);"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <a href="profile.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Activity log -->
        <div class="card">
            <div class="card-header">
                <h2>&#9679; Recent Activity</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <?php while ($log = $logs->fetch_assoc()): ?>
                <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 22px;border-bottom:1px solid var(--border);">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--navy-light);
                                display:flex;align-items:center;justify-content:center;
                                font-size:14px;flex-shrink:0;">&#9679;</div>
                    <div>
                        <div style="font-size:13px;font-weight:500;color:var(--text);">
                            <?= htmlspecialchars($log['description'] ?? $log['action_type']) ?>
                        </div>
                        <div style="font-size:11.5px;color:var(--slate-light);margin-top:2px;">
                            <?= htmlspecialchars($log['full_name']) ?> &mdash;
                            <?= date('d M Y H:i', strtotime($log['action_timestamp'])) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>
