<?php
require_once 'auth.php';
require_once 'db.php';

$query = clean($conn, $_GET['q'] ?? '');
$results = null;

if ($query) {
    $like = "%$query%";
    $stmt = $conn->prepare(
        "SELECT p.patient_id, p.id_number, p.first_name, p.last_name,
                p.gender, p.contact_number, p.date_of_birth, p.created_at,
                (SELECT COUNT(*) FROM consultations c WHERE c.patient_id = p.patient_id) AS visit_count
         FROM patients p
         WHERE p.id_number LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?
              OR CONCAT(p.first_name,' ',p.last_name) LIKE ?
         ORDER BY p.last_name, p.first_name LIMIT 50"
    );
    $stmt->bind_param('ssss', $like,$like,$like,$like);
    $stmt->execute();
    $results = $stmt->get_result();
    log_activity($conn, $session_id, 'SEARCH_PATIENT', 'patients', null, "Searched: $query");
}

$all = $conn->query(
    "SELECT p.patient_id, p.id_number, p.first_name, p.last_name,
            p.gender, p.contact_number,
            (SELECT COUNT(*) FROM consultations c WHERE c.patient_id=p.patient_id) AS visit_count
     FROM patients p ORDER BY p.last_name, p.first_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patient — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Search Patient</h1>
            <p class="page-sub">Search by SA ID number, first name or last name</p>
        </div>
        <a href="register.php" class="btn btn-primary">&#43; Register New</a>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="q" class="search-input"
               value="<?= htmlspecialchars($query) ?>"
               placeholder="Enter SA ID number, first name or last name..."
               autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($query): ?>
        <a href="search.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($query && $results): ?>
    <div class="card">
        <div class="card-header">
            <h2>&#9679; Results for &ldquo;<?= htmlspecialchars($query) ?>&rdquo;</h2>
            <span style="font-size:13px;color:var(--slate);"><?= $results->num_rows ?> found</span>
        </div>
        <?php if ($results->num_rows > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>SA ID Number</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Date of Birth</th>
                        <th>Visits</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($p = $results->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($p['id_number']) ?></code></td>
                    <td>
                        <span class="badge <?= $p['gender']==='Male'?'badge-navy':'badge-green' ?>">
                            <?= $p['gender'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['contact_number'] ?? '—') ?></td>
                    <td><?= $p['date_of_birth'] ? date('d M Y', strtotime($p['date_of_birth'])) : '—' ?></td>
                    <td><span class="badge badge-gray"><?= $p['visit_count'] ?> visits</span></td>
                    <td><a href="profile.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline btn-sm">View Profile</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card-body">
            <div class="alert alert-info">
                &#9432; No patients found for &ldquo;<?= htmlspecialchars($query) ?>&rdquo;.
                <a href="register.php">Register this patient</a>?
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- All patients table -->
    <div class="card">
        <div class="card-header">
            <h2>&#9679; All Patients</h2>
            <span style="font-size:13px;color:var(--slate);"><?= $all->num_rows ?> registered</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th><th>SA ID Number</th><th>Gender</th><th>Contact</th><th>Visits</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($p = $all->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($p['id_number']) ?></code></td>
                    <td><span class="badge <?= $p['gender']==='Male'?'badge-navy':'badge-green' ?>"><?= $p['gender'] ?></span></td>
                    <td><?= htmlspecialchars($p['contact_number'] ?? '—') ?></td>
                    <td><span class="badge badge-gray"><?= $p['visit_count'] ?> visits</span></td>
                    <td><a href="profile.php?id=<?= $p['patient_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
