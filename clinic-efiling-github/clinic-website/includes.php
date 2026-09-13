<?php
// ============================================================
// includes.php — Shared sidebar navigation
// ============================================================
$current = basename($_SERVER['PHP_SELF']);
$role    = $_SESSION['role'] ?? '';

function nav_link($href, $icon, $label, $current) {
    $active = basename($current) === $href ? ' active' : '';
    echo "<a href=\"{$href}\" class=\"{$active}\">
            <span class=\"nav-icon\">{$icon}</span> {$label}
          </a>";
}
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="cross">+</div>
    <h2>Clinic E-Filing</h2>
    <p>Nelspruit Municipal Clinic</p>
  </div>

  <div class="sidebar-section">Main</div>
  <nav>
    <?php nav_link('dashboard.php',   '⊞', 'Dashboard',        $current); ?>
    <?php nav_link('search.php',      '⊙', 'Search Patient',    $current); ?>
    <?php nav_link('register.php',    '⊕', 'Register Patient',  $current); ?>
  </nav>

  <?php if (in_array($role, ['Doctor','Nurse'])): ?>
  <div class="sidebar-section">Medical</div>
  <nav>
    <?php nav_link('consultation.php',      '♥', 'Consultations',      $current); ?>
    <?php nav_link('referral.php',          '↗', 'Outgoing Referrals',  $current); ?>
    <?php nav_link('incoming_referral.php', '↙', 'Incoming Referrals',  $current); ?>
  </nav>
  <?php endif; ?>

  <div class="sidebar-section">Data</div>
  <nav>
    <?php nav_link('reports.php', '▤', 'Reports', $current); ?>
  </nav>

  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($session_name ?? 'Staff') ?></div>
    <div class="user-role"><?= htmlspecialchars($role) ?></div>
    <a href="logout.php" class="logout-btn">&#x2192; Sign out</a>
  </div>
</div>
