<?php
require_once 'auth.php';
require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number  = clean($conn, $_POST['id_number']  ?? '');
    $first_name = clean($conn, $_POST['first_name'] ?? '');
    $last_name  = clean($conn, $_POST['last_name']  ?? '');
    $dob        = clean($conn, $_POST['dob']        ?? '');
    $gender     = clean($conn, $_POST['gender']     ?? '');
    $contact    = clean($conn, $_POST['contact']    ?? '');
    $email      = clean($conn, $_POST['email']      ?? '');
    $street     = clean($conn, $_POST['street']     ?? '');
    $suburb     = clean($conn, $_POST['suburb']     ?? '');
    $city       = clean($conn, $_POST['city']       ?? '');
    $province   = clean($conn, $_POST['province']   ?? '');
    $postal     = clean($conn, $_POST['postal']     ?? '');

    if (!preg_match('/^\d{13}$/', $id_number)) { $error = 'SA ID number must be exactly 13 digits.'; }
    elseif (!$first_name || !$last_name || !$gender) { $error = 'First name, last name and gender are required.'; }
    else {
        $chk = $conn->prepare("SELECT patient_id FROM patients WHERE id_number = ?");
        $chk->bind_param('s', $id_number); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'A patient with this ID number already exists. <a href="search.php?q='.$id_number.'">Search for them</a>.';
        } else {
            $a = $conn->prepare("INSERT INTO addresses (street,suburb,city,province,postal_code) VALUES(?,?,?,?,?)");
            $a->bind_param('sssss', $street,$suburb,$city,$province,$postal); $a->execute();
            $addr_id = $conn->insert_id;
            $p = $conn->prepare("INSERT INTO patients (id_number,first_name,last_name,date_of_birth,gender,contact_number,email,address_id,created_by) VALUES(?,?,?,?,?,?,?,?,?)");
            $p->bind_param('sssssssii', $id_number,$first_name,$last_name,$dob,$gender,$contact,$email,$addr_id,$session_id);
            $p->execute();
            $new_id = $conn->insert_id;
            log_activity($conn, $session_id, 'REGISTER_PATIENT', 'patients', $new_id, "Registered: $first_name $last_name");
            header("Location: profile.php?id=$new_id&registered=1"); exit;
        }
    }
}
$provinces = ['Eastern Cape','Free State','Gauteng','KwaZulu-Natal','Limpopo','Mpumalanga','Northern Cape','North West','Western Cape'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient — Clinic E-Filing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require_once 'includes.php'; ?>
<div class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Register New Patient</h1>
            <p class="page-sub">Complete all required fields to create a patient record</p>
        </div>
        <a href="search.php" class="btn btn-outline">Search Existing</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">&#9888; <?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- SA ID -->
        <div class="card">
            <div class="card-header"><h2>&#9679; Patient Identifier</h2></div>
            <div class="card-body">
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>SA ID Number <span class="req">*</span></label>
                        <input type="text" name="id_number" maxlength="13" pattern="\d{13}"
                               placeholder="e.g. 9001011234085"
                               value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>"
                               oninput="validateID(this)" required>
                        <div class="id-feedback" id="id-msg"></div>
                        <div class="progress-bar"><div class="progress-fill" id="id-prog" style="width:0%"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal details -->
        <div class="card">
            <div class="card-header"><h2>&#9679; Personal Details</h2></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="req">*</span></label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="req">*</span></label>
                        <select name="gender" required>
                            <option value="">Select gender</option>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($_POST['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" placeholder="e.g. 0721234567" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="card">
            <div class="card-header"><h2>&#9679; Address</h2></div>
            <div class="card-body">
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>Street Address</label>
                        <input type="text" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Suburb</label>
                        <input type="text" name="suburb" value="<?= htmlspecialchars($_POST['suburb'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <select name="province">
                            <option value="">Select province</option>
                            <?php foreach ($provinces as $pr): ?>
                            <option value="<?= $pr ?>" <?= ($_POST['province']??'')===$pr?'selected':'' ?>><?= $pr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid-1">
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal" maxlength="10" value="<?= htmlspecialchars($_POST['postal'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary btn-lg">Register Patient</button>
            <a href="dashboard.php" class="btn btn-outline btn-lg">Cancel</a>
        </div>
    </form>
</div>
<script>
function validateID(input) {
    const msg  = document.getElementById('id-msg');
    const prog = document.getElementById('id-prog');
    const val  = input.value;
    const pct  = Math.min(100, Math.round((val.length / 13) * 100));
    prog.style.width = pct + '%';
    if (val.length === 13 && /^\d{13}$/.test(val)) {
        msg.textContent = '✓ Valid SA ID format';
        msg.className   = 'id-feedback valid';
        prog.style.background = 'var(--green)';
    } else if (val.length > 0) {
        msg.textContent = val.length + ' / 13 digits entered';
        msg.className   = val.length === 13 ? 'id-feedback invalid' : 'id-feedback partial';
        prog.style.background = val.length === 13 ? 'var(--danger)' : 'var(--warning)';
    } else {
        msg.textContent = '';
        msg.className   = 'id-feedback';
        prog.style.background = 'var(--green)';
    }
}
</script>
</body>
</html>
