<?php
/**
 * Sidebar Partial
 * Include this file in every authenticated page.
 * Reads session for user data.
 *
 * Usage: <?php include __DIR__ . '/../partials/sidebar.php'; ?>
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Guard — redirect to login if not authenticated
if (empty($_SESSION['logged_in'])) {
    header('Location: ../pages/login.php');
    exit;
}

$currentUser  = [
    'name'   => htmlspecialchars($_SESSION['full_name'] ?? 'User'),
    'role'   => htmlspecialchars($_SESSION['role']      ?? 'viewer'),
    'initials' => strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)
                           . substr(strrchr($_SESSION['full_name'] ?? '', ' '), 1, 1)),
];

$currentPage = basename($_SERVER['PHP_SELF']);

function navLink(string $href, string $icon, string $label, string $current, string $badge = ''): string {
    $active = ($current === $href) ? ' active' : '';
    $badgeHtml = $badge ? "<span class=\"nav-badge\">{$badge}</span>" : '';
    return <<<HTML
    <li>
      <a href="{$href}" class="{$active}">
        <span class="nav-icon"><i class="fa-solid {$icon}"></i></span>
        {$label}
        {$badgeHtml}
      </a>
    </li>
    HTML;
}
?>

<!-- Mobile overlay -->
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<aside class="qa-sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-shield-halved"></i></div>
    <div>
      <div class="brand-text">QA System</div>
      <div class="brand-sub">Management Platform</div>
    </div>
  </div>

  <!-- Main navigation -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Main</div>
    <ul class="sidebar-nav">
      <?= navLink('dashboard.php', 'fa-gauge-high',   'Dashboard',    $currentPage) ?>
      <?= navLink('standards.php', 'fa-book-bookmark','Standards',    $currentPage) ?>
      <?= navLink('audits.php',    'fa-clipboard-check','Audits',     $currentPage) ?>
      <?= navLink('surveys.php',   'fa-chart-bar',    'Surveys',      $currentPage) ?>
      <?= navLink('kpis.php',      'fa-bullseye',     'KPIs',         $currentPage) ?>
    </ul>
  </div>

  <!-- Management -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Management</div>
    <ul class="sidebar-nav">
      <?= navLink('action_plans.php', 'fa-list-check',  'Action Plans', $currentPage) ?>
      <?= navLink('reports.php',      'fa-file-lines',  'Reports',      $currentPage) ?>
      <?php if ($currentUser['role'] === 'admin'): ?>
        <?= navLink('users.php',      'fa-users',       'Users',        $currentPage) ?>
      <?php endif; ?>
    </ul>
  </div>

  <!-- Resources -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Resources</div>
    <ul class="sidebar-nav">
      <?= navLink('integration.php', 'fa-plug',         'Integrations', $currentPage) ?>
      <?= navLink('settings.php',    'fa-gear',         'Settings',     $currentPage) ?>
    </ul>
  </div>

  

</aside>
