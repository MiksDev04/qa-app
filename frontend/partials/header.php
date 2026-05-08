<?php
/**
 * Header Partial
 * Include this file in every authenticated page AFTER sidebar.php.
 *
 * Usage: <?php include __DIR__ . '/partials/header.php'; ?>
 *
 * The including page can set $pageTitle before including:
 *   $pageTitle = 'Dashboard';
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle    = $pageTitle ?? 'QA Management System';
$userName     = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$userInitials = strtoupper(
    substr($_SESSION['full_name'] ?? 'U', 0, 1)
  . substr(strrchr($_SESSION['full_name'] ?? '', ' '), 1, 1)
);
$userRole = htmlspecialchars($_SESSION['role'] ?? 'viewer');
?>

<header class="qa-header">

  <!-- Mobile menu toggle -->
  <button id="sidebar-toggle"
          class="header-icon-btn d-lg-none me-2"
          aria-label="Toggle menu">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- Page title -->
  <h1 class="header-title"><?= htmlspecialchars($pageTitle) ?></h1>

  <!-- Search -->


  <div class="header-actions">

    <!-- Notifications -->
    <button class="header-icon-btn"
            id="notif-btn"
            aria-label="Notifications"
            title="Notifications">
      <i class="fa-regular fa-bell"></i>
      <span class="notif-dot" id="notif-dot"></span>
    </button>


    <!-- User dropdown (Bootstrap) -->
    <div class="dropdown">
      <button class="header-user-btn"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              aria-label="User menu">
        <div class="header-avatar"><?= $userInitials ?></div>
        <span class="header-username d-none d-sm-inline"><?= $userName ?></span>
        <i class="fa-solid fa-chevron-down text-muted-qa" style="font-size:.65rem;"></i>
      </button>

      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width:200px; border-radius:10px; margin-top:6px;">
        <li>
          <div class="px-3 py-2 border-bottom">
            <div class="fw-600" style="font-size:.85rem;"><?= $userName ?></div>
            <div class="text-muted-qa" style="font-size:.75rem; text-transform:capitalize;"><?= $userRole ?></div>
          </div>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2" href="profile.php" style="font-size:.85rem; padding:8px 14px;">
            <i class="fa-regular fa-user" style="width:16px;"></i> My Profile
          </a>
        </li>
       
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <button class="dropdown-item d-flex align-items-center gap-2 text-danger"
                  id="logout-btn"
                  style="font-size:.85rem; padding:8px 14px;">
            <i class="fa-solid fa-right-from-bracket" style="width:16px;"></i> Sign Out
          </button>
        </li>
      </ul>
    </div>

  </div><!-- /.header-actions -->

</header>

<script>
/* Logout handler — placed here so it's available on every page */
document.getElementById('logout-btn')?.addEventListener('click', function () {
  window.location.href = 'logout.php';
});
</script>
