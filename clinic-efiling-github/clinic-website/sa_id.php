<?php
// South African ID number: YYMMDD SSSS C A Z
// Returns [ok, message, dob (YYYY-MM-DD), gender]
function validate_sa_id($id) {
    $id = trim((string)$id);
    if (!preg_match('/^\d{13}$/', $id)) return [false, 'SA ID number must be exactly 13 digits.', null, null];
    $yy = (int)substr($id, 0, 2); $mm = (int)substr($id, 2, 2); $dd = (int)substr($id, 4, 2);
    $today = date('Y-m-d'); $dob = null;
    foreach ([2000 + $yy, 1900 + $yy] as $y) {
        if (checkdate($mm, $dd, $y)) {
            $d = sprintf('%04d-%02d-%02d', $y, $mm, $dd);
            if ($d <= $today) { $dob = $d; break; }
        }
    }
    if (!$dob) return [false, 'The first 6 digits of an SA ID must be a valid date of birth (YYMMDD) that is not in the future.', null, null];
    if ($id[10] !== '0' && $id[10] !== '1') return [false, 'Digit 11 of an SA ID must be 0 (citizen) or 1 (permanent resident).', null, null];
    $sum = 0;
    for ($i = 0; $i < 13; $i++) { $n = (int)$id[$i]; if ($i % 2 === 1) { $n *= 2; if ($n > 9) $n -= 9; } $sum += $n; }
    if ($sum % 10 !== 0) return [false, 'This SA ID number has an invalid check digit. Please re-check it.', null, null];
    return [true, 'Valid', $dob, ((int)substr($id, 6, 4) >= 5000) ? 'Male' : 'Female'];
}
