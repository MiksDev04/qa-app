<?php
/**
 * Reports Dashboard Page
 * Quality Assurance Management System
 * frontend/pages/reports.php
 */

session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — QA System</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<div class="qa-wrapper">

  <!-- ── Sidebar ────────────────────────────────────────────── -->
  <?php include '../partials/sidebar.php'; ?>

  <!-- ── Main content ──────────────────────────────────────── -->
  <div class="qa-content">

    <?php include '../partials/header.php'; ?>

    <main class="qa-page">

      <!-- ── Page header ─────────────────────────────────────── -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
            Reports Dashboard
          </h2>
          <p class="text-muted-qa mb-0" style="font-size:.83rem;margin-top:2px;">
            Aggregated view of all quality assurance activities
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-outline-qa" id="refresh-btn">
            <i class="fa-solid fa-rotate-right"></i> Refresh
          </button>
          <button class="btn-outline-qa" id="export-pdf-btn">
            <i class="fa-solid fa-file-pdf" style="color:#c0392b;"></i> Export PDF
          </button>
          <button class="btn-primary-qa" id="export-excel-btn">
            <i class="fa-solid fa-file-excel"></i> Export Excel
          </button>
        </div>
      </div>

      <!-- ── Section tabs ─────────────────────────────────────── -->
      <div class="mb-4" style="border-bottom:1px solid var(--border);">
        <nav class="d-flex gap-3" style="padding-bottom:0;" id="report-tabs">
          <?php
          $tabs = [
            ['key' => 'summary',   'label' => 'Overview'],
            ['key' => 'audits',    'label' => 'Audits'],
            ['key' => 'tasks',     'label' => 'Tasks'],
            ['key' => 'kpis',      'label' => 'KPIs'],
            ['key' => 'surveys',   'label' => 'Surveys'],
            ['key' => 'plans',     'label' => 'Action Plans'],
            ['key' => 'standards', 'label' => 'Standards'],
          ];
          foreach ($tabs as $i => $tab):
          ?>
          <button class="report-tab-btn<?= $i === 0 ? ' active' : '' ?>"
                  data-tab="<?= $tab['key'] ?>"
                  style="background:none;border:none;padding:8px 0;margin-bottom:-1px;cursor:pointer;
                         font-family:var(--font);font-size:.88rem;
                         <?= $i === 0
                             ? 'font-weight:600;color:var(--primary);border-bottom:2px solid var(--primary);'
                             : 'font-weight:500;color:var(--text-secondary);border-bottom:2px solid transparent;' ?>">
            <?= $tab['label'] ?>
          </button>
          <?php endforeach; ?>
        </nav>
      </div>

      <!-- ══════════════════════════════════════════════════════
           OVERVIEW SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-summary" class="report-section">

        <!-- Stat cards — same pattern as dashboard.php -->
        <div class="row g-3 mb-4" id="summary-stats">
          <?php
          $statCards = [
            ['id'=>'stat-audits',    'icon'=>'fa-magnifying-glass',       'color'=>'blue',   'label'=>'Total Audits',     'sub'=>'Across all types'],
            ['id'=>'stat-tasks',     'icon'=>'fa-list-check',             'color'=>'purple', 'label'=>'Audit Tasks',      'sub'=>'Accreditation tasks'],
            ['id'=>'stat-kpi',       'icon'=>'fa-chart-line',             'color'=>'green',  'label'=>'KPI Avg Value',    'sub'=>'Latest records'],
            ['id'=>'stat-plans',     'icon'=>'fa-triangle-exclamation',   'color'=>'orange', 'label'=>'Open Action Plans','sub'=>'Pending resolution'],
            ['id'=>'stat-surveys',   'icon'=>'fa-paper-plane',            'color'=>'blue',   'label'=>'Surveys',          'sub'=>'Total created'],
            ['id'=>'stat-responses', 'icon'=>'fa-comments',               'color'=>'green',  'label'=>'Responses',        'sub'=>'Survey submissions'],
            ['id'=>'stat-standards', 'icon'=>'fa-clipboard-check',        'color'=>'purple', 'label'=>'Active Standards', 'sub'=>'CHED, ISO & more'],
            ['id'=>'stat-policies',  'icon'=>'fa-file-shield',            'color'=>'orange', 'label'=>'Active Policies',  'sub'=>'Linked to standards'],
          ];
          foreach ($statCards as $c):
          ?>
          <div class="col-6 col-md-3">
            <div class="stat-card">
              <div class="stat-icon <?= $c['color'] ?>"><i class="fa-solid <?= $c['icon'] ?>"></i></div>
              <div class="stat-label"><?= $c['label'] ?></div>
              <div class="stat-value" id="<?= $c['id'] ?>">
                <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
              </div>
              <div class="stat-sub"><?= $c['sub'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Charts row -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                  Audit Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-audit-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
                  Task Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-task-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                  Survey Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-survey-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Audits & Recent Surveys side by side -->
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                  Recent Audits
                </h3>
                <a href="audits.php" class="btn-outline-qa" style="font-size:.76rem;padding:5px 10px;">
                  View all <i class="fa-solid fa-chevron-right ms-1" style="font-size:.65rem;"></i>
                </a>
              </div>
              <div class="card-body-custom p-0" id="summary-recent-audits">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="placeholder-wave" style="width:60%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                    <span class="placeholder-wave" style="width:18%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  </div>
                  <div class="progress-bar-wrap mt-2"><div class="progress-bar-fill blue" style="width:45%;"></div></div>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                  Recent Surveys
                </h3>
                <a href="surveys.php" class="btn-outline-qa" style="font-size:.76rem;padding:5px 10px;">
                  View all <i class="fa-solid fa-chevron-right ms-1" style="font-size:.65rem;"></i>
                </a>
              </div>
              <div class="card-body-custom p-0" id="summary-recent-surveys">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="placeholder-wave" style="width:55%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                    <span class="placeholder-wave" style="width:20%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  </div>
                  <span class="placeholder-wave" style="width:30%;display:block;margin-top:6px;"><span class="placeholder col-12 bg-secondary rounded" style="height:8px;display:block;"></span></span>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /#section-summary -->

      <!-- ══════════════════════════════════════════════════════
           AUDITS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-audits" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
              Audits Report
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="audits-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-audits">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Type</th><th>Scheduled</th>
                  <th>Completion</th><th>Status</th><th>Tasks</th><th>Progress</th><th>Notes</th>
                </tr>
              </thead>
              <tbody id="audits-tbody">
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr><?php for ($j = 0; $j < 9; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           TASKS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-tasks" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
              Accreditation Tasks
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="tasks-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-tasks">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Audit</th><th>Standard</th>
                  <th>Due Date</th><th>Status</th><th>Remarks</th>
                </tr>
              </thead>
              <tbody id="tasks-tbody">
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr><?php for ($j = 0; $j < 7; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           KPIs SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-kpis" class="report-section d-none">
        <div class="row g-3 mb-3">
          <div class="col-12">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-green);border-radius:50%;display:inline-block;"></span>
                  KPI — Actual vs. Target
                </h3>
              </div>
              <div class="card-body-custom" style="min-height:220px;">
                <canvas id="chart-kpi" style="max-height:220px;"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">KPI Indicators</h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="kpis-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-kpis">
              <thead>
                <tr>
                  <th>#</th><th>Indicator</th><th>Category</th><th>Unit</th>
                  <th>Target</th><th>Latest Value</th><th>Period</th><th>Meets Target</th>
                </tr>
              </thead>
              <tbody id="kpis-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 8; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           SURVEYS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-surveys" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
              Surveys Report
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="surveys-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-surveys">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Target Group</th><th>Start</th><th>End</th>
                  <th>Status</th><th>Questions</th><th>Responses</th><th>Created By</th>
                </tr>
              </thead>
              <tbody id="surveys-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 9; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           ACTION PLANS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-plans" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
              Action Plans
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="plans-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-plans">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Related Audit</th><th>Root Cause</th>
                  <th>Target Date</th><th>Status</th><th>Resolution</th>
                </tr>
              </thead>
              <tbody id="plans-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 7; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           STANDARDS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-standards" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-green);border-radius:50%;display:inline-block;"></span>
              Standards &amp; Policies
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="standards-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-standards">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Body</th><th>Version</th><th>Effective</th>
                  <th>Status</th><th>Active Policies</th><th>Linked Tasks</th>
                </tr>
              </thead>
              <tbody id="standards-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 8; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="../assets/js/app.js"></script>

<script>
$(function () {

  /* ──────────────────────────────────────────────────────────
     CONFIG
  ────────────────────────────────────────────────────────── */
  const API = '../../backend/api/reports_api.php';
  const cache = {};           // store fetched data per action
  const chartInst = {};       // Chart.js instances
  let activeSection = 'summary';

  /* ──────────────────────────────────────────────────────────
     HELPERS  (mirror dashboard.php helpers)
  ────────────────────────────────────────────────────────── */
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function badgeClass(status) {
    const map = {
      'scheduled'  : 'pending',
      'in progress': 'in-progress',
      'completed'  : 'completed',
      'cancelled'  : 'cancelled',
      'pending'    : 'pending',
      'active'     : 'active',
      'open'       : 'pending',
      'resolved'   : 'completed',
      'closed'     : 'completed',
      'draft'      : 'pending',
      'archived'   : 'cancelled',
    };
    return map[(status ?? '').toLowerCase()] || 'pending';
  }

  function badge(status) {
    return `<span class="badge-qa ${badgeClass(status)}">${esc(status)}</span>`;
  }

  function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}" class="text-center py-4 text-muted-qa"
                style="font-size:.82rem;">
              <i class="fa-regular fa-folder-open mb-2" style="font-size:1.5rem;display:block;opacity:.3;"></i>
              ${esc(msg)}
            </td></tr>`;
  }

  function progressCell(pct) {
    return `<div class="d-flex align-items-center gap-2">
              <div class="progress-bar-wrap flex-fill">
                <div class="progress-bar-fill blue" style="width:${pct}%;"></div>
              </div>
              <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
            </div>`;
  }

  function meetsTarget(val) {
    if (val === null) return '<span style="color:var(--text-muted);font-size:.78rem;">N/A</span>';
    return val
      ? '<span class="badge-qa active"><i class="fa-solid fa-check me-1"></i>Yes</span>'
      : '<span class="badge-qa cancelled"><i class="fa-solid fa-xmark me-1"></i>No</span>';
  }

  function animateCount(selector, target, suffix = '') {
    const el = $(selector);
    let cur = 0;
    const step = Math.ceil(Math.max(target, 1) / 30);
    const t = setInterval(() => {
      cur = Math.min(cur + step, target);
      el.text(cur + suffix);
      if (cur >= target) clearInterval(t);
    }, 30);
  }

  /* ──────────────────────────────────────────────────────────
     FETCH WRAPPER  (caches per action key)
  ────────────────────────────────────────────────────────── */
  function fetchReport(action) {
    if (cache[action]) return $.Deferred().resolve(cache[action]).promise();
    return $.ajax({ url: API, type: 'GET', data: { action }, dataType: 'json' })
      .then(function (res) {
        if (!res.success) throw res.message;
        // API responses include a top-level `data` key containing the payload.
        // Some endpoints may wrap lists inside another `data` key; support both.
        cache[action] = (res.data && res.data.data) ? res.data.data : res.data;
        return cache[action];
      });
  }

  /* ──────────────────────────────────────────────────────────
     TAB SWITCHING
  ────────────────────────────────────────────────────────── */
  $('#report-tabs').on('click', '.report-tab-btn', function () {
    const tab = $(this).data('tab');
    if (tab === activeSection) return;
    activeSection = tab;

    // Update tab styles (mirror dashboard pattern)
    $('.report-tab-btn').css({
      'color'        : 'var(--text-secondary)',
      'border-bottom': '2px solid transparent',
      'font-weight'  : '500',
    }).removeClass('active');
    $(this).css({
      'color'        : 'var(--primary)',
      'border-bottom': '2px solid var(--primary)',
      'font-weight'  : '600',
    }).addClass('active');

    // Show / hide sections
    $('.report-section').addClass('d-none');
    $(`#section-${tab}`).removeClass('d-none');

    loadSection(tab);
  });

  function loadSection(tab) {
    switch (tab) {
      case 'summary':   loadSummary();   break;
      case 'audits':    loadAudits();    break;
      case 'tasks':     loadTasks();     break;
      case 'kpis':      loadKpis();      break;
      case 'surveys':   loadSurveys();   break;
      case 'plans':     loadPlans();     break;
      case 'standards': loadStandards(); break;
    }
  }

  /* ──────────────────────────────────────────────────────────
     DONUT CHART HELPER
  ────────────────────────────────────────────────────────── */
  function donutChart(id, labels, values, colors) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    if (chartInst[id]) chartInst[id].destroy();
    chartInst[id] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
      },
      options: {
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { family: 'var(--font)', size: 11 }, boxWidth: 12, padding: 10 }
          }
        },
        cutout: '62%',
        animation: { animateScale: true }
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     OVERVIEW / SUMMARY
  ────────────────────────────────────────────────────────── */
  function loadSummary() {
    fetchReport('summary').then(function (d) {

      // Stat cards
      animateCount('#stat-audits',    d.audits.total);
      animateCount('#stat-tasks',     d.tasks.total);
      $('#stat-kpi').text(d.kpis.avg);
      animateCount('#stat-plans',     d.plans.by_status['Open'] ?? 0);
      animateCount('#stat-surveys',   d.surveys.total);
      animateCount('#stat-responses', d.surveys.total_responses);
      animateCount('#stat-standards', d.standards.active);
      animateCount('#stat-policies',  d.standards.policies);

      // Audit status donut
      const auditColors = { 'Scheduled':'#2980b9','In Progress':'#e67e22','Completed':'#27ae60','Cancelled':'#c0392b' };
      const aLabels = Object.keys(d.audits.by_status);
      donutChart('chart-audit-status', aLabels, Object.values(d.audits.by_status), aLabels.map(l => auditColors[l] || '#999'));

      // Task status donut
      const taskColors = { 'Pending':'#6b6860','In Progress':'#e67e22','Completed':'#27ae60' };
      const tLabels = Object.keys(d.tasks.by_status);
      donutChart('chart-task-status', tLabels, Object.values(d.tasks.by_status), tLabels.map(l => taskColors[l] || '#999'));

      // Survey status donut
      const survColors = { 'Draft':'#aaa','Active':'#27ae60','Closed':'#c0392b' };
      const sLabels = Object.keys(d.surveys.by_status);
      donutChart('chart-survey-status', sLabels, Object.values(d.surveys.by_status), sLabels.map(l => survColors[l] || '#999'));

      // Recent audits list
      const $auditList = $('#summary-recent-audits').empty();
      if (!d.recent_audits?.length) {
        $auditList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No audits found.</div>');
      } else {
        d.recent_audits.forEach(a => {
          const pct = a.progress_pct ?? 0;
          $auditList.append(`
            <div class="p-3" style="border-bottom:1px solid var(--border-light);">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(a.title)}</span>
                ${badge(a.status)}
              </div>
              <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px;">
                <i class="fa-regular fa-calendar me-1"></i>${esc(a.scheduled_date || '—')}
                &nbsp;·&nbsp; ${esc(a.audit_type)}
              </div>
              <div class="d-flex align-items-center gap-2">
                <div class="progress-bar-wrap flex-fill">
                  <div class="progress-bar-fill blue" style="width:${pct}%;"></div>
                </div>
                <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
              </div>
            </div>`);
        });
      }

      // Recent surveys list
      const $survList = $('#summary-recent-surveys').empty();
      if (!d.recent_surveys?.length) {
        $survList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No surveys found.</div>');
      } else {
        d.recent_surveys.forEach(s => {
          $survList.append(`
            <div class="p-3" style="border-bottom:1px solid var(--border-light);">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(s.title)}</span>
                ${badge(s.status)}
              </div>
              <div style="font-size:.74rem;color:var(--text-muted);">
                <i class="fa-solid fa-users me-1"></i>${esc(s.target_group)}
                &nbsp;·&nbsp;
                <i class="fa-solid fa-comments me-1"></i>${s.responses_count} response${s.responses_count !== 1 ? 's' : ''}
              </div>
            </div>`);
        });
      }

    }).fail(function (err) {
      toast.error('Could not load overview: ' + err);
    });
  }

  /* ──────────────────────────────────────────────────────────
     AUDITS TABLE
  ────────────────────────────────────────────────────────── */
  function loadAudits() {
    if (cache['audits']) { renderAudits(cache['audits']); return; }
    fetchReport('audits').then(renderAudits).fail(() => toast.error('Failed to load audits.'));
  }

  function renderAudits(rows) {
    $('#audits-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#audits-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(9, 'No audits found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.audit_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_type)}</td>
        <td>${esc(r.scheduled_date  || '—')}</td>
        <td>${esc(r.completion_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td><span style="font-size:.8rem;">${esc(r.completed_tasks)}/${esc(r.total_tasks)}</span></td>
        <td style="min-width:130px;">${progressCell(r.progress_pct)}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.notes || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     TASKS TABLE
  ────────────────────────────────────────────────────────── */
  function loadTasks() {
    if (cache['tasks']) { renderTasks(cache['tasks']); return; }
    fetchReport('tasks').then(renderTasks).fail(() => toast.error('Failed to load tasks.'));
  }

  function renderTasks(rows) {
    $('#tasks-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#tasks-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(7, 'No tasks found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.task_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_title  || '—')}</td>
        <td>${r.standard_title
              ? `<span style="font-size:.72rem;color:var(--text-muted);">[${esc(r.standard_body)}]</span> ${esc(r.standard_title)}`
              : '—'}</td>
        <td>${esc(r.due_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td style="max-width:180px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.remarks || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     KPIs
  ────────────────────────────────────────────────────────── */
  function loadKpis() {
    if (cache['kpis']) { renderKpis(cache['kpis']); return; }
    fetchReport('kpis').then(renderKpis).fail(() => toast.error('Failed to load KPIs.'));
  }

  function renderKpis(rows) {
    $('#kpis-count').text(rows.length + ' indicator' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#kpis-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(8, 'No KPI indicators found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.indicator_id)}</td>
        <td style="font-weight:600;">${esc(r.name)}</td>
        <td>${esc(r.category || '—')}</td>
        <td>${esc(r.unit    || '—')}</td>
        <td>${r.target_value != null ? Number(r.target_value).toLocaleString('en-PH') : '—'}</td>
        <td style="font-weight:700;">${r.latest_value != null ? Number(r.latest_value).toLocaleString('en-PH') : '—'}</td>
        <td style="font-size:.78rem;color:var(--text-muted);">${esc(r.latest_period || '—')}</td>
        <td>${meetsTarget(r.meets_target)}</td>
      </tr>`);
    });

    // Bar chart: actual vs target
    const ctx = document.getElementById('chart-kpi');
    if (ctx && rows.length) {
      if (chartInst['chart-kpi']) chartInst['chart-kpi'].destroy();
      chartInst['chart-kpi'] = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: rows.map(r => r.name),
          datasets: [
            {
              label: 'Target',
              data: rows.map(r => r.target_value),
              backgroundColor: 'rgba(45,90,61,.18)',
              borderColor: '#2d5a3d',
              borderWidth: 2,
            },
            {
              label: 'Actual (Latest)',
              data: rows.map(r => r.latest_value),
              backgroundColor: 'rgba(41,128,185,.65)',
              borderColor: '#2980b9',
              borderWidth: 2,
            },
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { labels: { font: { family: 'var(--font)', size: 11 }, boxWidth: 12 } }
          },
          scales: { y: { beginAtZero: true } }
        }
      });
    }
  }

  /* ──────────────────────────────────────────────────────────
     SURVEYS TABLE
  ────────────────────────────────────────────────────────── */
  function loadSurveys() {
    if (cache['surveys']) { renderSurveys(cache['surveys']); return; }
    fetchReport('surveys').then(renderSurveys).fail(() => toast.error('Failed to load surveys.'));
  }

  function renderSurveys(rows) {
    $('#surveys-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#surveys-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(9, 'No surveys found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.survey_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.target_group)}</td>
        <td>${esc(r.start_date || '—')}</td>
        <td>${esc(r.end_date   || '—')}</td>
        <td>${badge(r.status)}</td>
        <td>${esc(r.questions_count)}</td>
        <td>${esc(r.responses_count)}</td>
        <td style="font-size:.8rem;color:var(--text-muted);">${esc(r.creator_name || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     ACTION PLANS TABLE
  ────────────────────────────────────────────────────────── */
  function loadPlans() {
    if (cache['action_plans']) { renderPlans(cache['action_plans']); return; }
    fetchReport('action_plans').then(renderPlans).fail(() => toast.error('Failed to load action plans.'));
  }

  function renderPlans(rows) {
    $('#plans-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#plans-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(7, 'No action plans found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.plan_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_title || '—')}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.root_cause || '—')}</td>
        <td>${esc(r.target_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.resolution || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     STANDARDS TABLE
  ────────────────────────────────────────────────────────── */
  function loadStandards() {
    if (cache['standards']) { renderStandards(cache['standards']); return; }
    fetchReport('standards').then(renderStandards).fail(() => toast.error('Failed to load standards.'));
  }

  function renderStandards(rows) {
    $('#standards-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#standards-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(8, 'No standards found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.standard_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td><span class="badge-qa pending">${esc(r.body)}</span></td>
        <td>${esc(r.version || '—')}</td>
        <td>${esc(r.effective_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td>${esc(r.active_policies)} / ${esc(r.total_policies)}</td>
        <td>${esc(r.linked_tasks)}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     REFRESH BUTTON
  ────────────────────────────────────────────────────────── */
  $('#refresh-btn').on('click', function () {
    const btn = this;
    $(btn).find('i').addClass('fa-spin');
    btn.disabled = true;

    // Bust cache for current section only
    // map UI tab keys to API action keys (e.g. 'plans' -> 'action_plans')
    const actionKey = (tab) => tab === 'plans' ? 'action_plans' : tab === 'summary' ? 'summary' : tab;
    delete cache[actionKey(activeSection)];
    // Also bust summary sub-data
    if (activeSection === 'summary') {
      Object.keys(cache).forEach(k => delete cache[k]);
      // Reset chart instances
      Object.values(chartInst).forEach(c => c?.destroy());
      Object.keys(chartInst).forEach(k => delete chartInst[k]);
    }
    loadSection(activeSection);

    setTimeout(() => {
      $(btn).find('i').removeClass('fa-spin');
      btn.disabled = false;
      if (typeof toast !== 'undefined') toast.success('Report refreshed.', 'Updated');
    }, 900);
  });

  /* ──────────────────────────────────────────────────────────
     EXPORT — PDF
  ────────────────────────────────────────────────────────── */
  $('#export-pdf-btn').on('click', async function () {
    const btn = this;
    btnLoading(btn, 'Generating…');

    try {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
      const now = new Date().toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
      const accentFill = [45, 90, 61];

      // Ensure summary data is loaded using fetchReport (normalizes response)
      const sum = cache['summary'] || await fetchReport('summary');

      let pageNum = 1;

      function pageHeader(title) {
        doc.setFillColor(...accentFill);
        doc.rect(0, 0, 297, 18, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(10); doc.setFont('helvetica', 'bold');
        doc.text('QA Management System — Reports Dashboard', 10, 8);
        doc.setFontSize(8); doc.setFont('helvetica', 'normal');
        doc.text('Generated: ' + now, 10, 13.5);
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(13); doc.setFont('helvetica', 'bold');
        doc.text(title, 10, 26);
        doc.setFont('helvetica', 'normal');
      }

      function pageFooter() {
        doc.setFontSize(7); doc.setTextColor(160);
        doc.text('Page ' + pageNum, 287, 205, { align: 'right' });
        doc.setTextColor(0);
      }

      // ── Summary ──
      pageHeader('Executive Summary');
      doc.autoTable({
        head: [['Module', 'Total', 'Key Metric', 'Value']],
        body: [
          ['Audits',          sum.audits.total,              'Completed',         sum.audits.by_status['Completed']    ?? 0],
          ['Audit Tasks',     sum.tasks.total,               'Pending',           sum.tasks.by_status['Pending']        ?? 0],
          ['Action Plans',    sum.plans.total,               'Open',              sum.plans.by_status['Open']           ?? 0],
          ['KPI Indicators',  sum.kpis.total,                'Meeting Target',    sum.kpis.meeting_target],
          ['KPI Avg Value',   sum.kpis.avg,                  '—',                 '—'],
          ['Surveys',         sum.surveys.total,             'Responses',         sum.surveys.total_responses],
          ['Active Standards',sum.standards.active,          'Active Policies',   sum.standards.policies],
        ],
        startY: 30, styles: { fontSize: 9 },
        headStyles: { fillColor: accentFill },
      });
      pageFooter();

      const sections = [
        { action:'audits',    title:'Audits Report',
          head:['ID','Title','Type','Scheduled','Completion','Status','Done/Total','%'],
          rows: d => d.map(r => [r.audit_id, r.title, r.audit_type, r.scheduled_date||'—', r.completion_date||'—', r.status, `${r.completed_tasks}/${r.total_tasks}`, r.progress_pct+'%']) },
        { action:'tasks',     title:'Accreditation Tasks',
          head:['ID','Title','Audit','Standard','Due Date','Status','Remarks'],
          rows: d => d.map(r => [r.task_id, r.title, r.audit_title||'—', r.standard_title||'—', r.due_date||'—', r.status, r.remarks||'—']) },
        { action:'kpis',      title:'KPI Indicators',
          head:['ID','Indicator','Category','Unit','Target','Actual','Period','Meets Target'],
          rows: d => d.map(r => [r.indicator_id, r.name, r.category||'—', r.unit||'—', r.target_value??'—', r.latest_value??'—', r.latest_period||'—', r.meets_target===null?'N/A':r.meets_target?'Yes':'No']) },
        { action:'surveys',   title:'Surveys Report',
          head:['ID','Title','Target Group','Start','End','Status','Questions','Responses'],
          rows: d => d.map(r => [r.survey_id, r.title, r.target_group, r.start_date||'—', r.end_date||'—', r.status, r.questions_count, r.responses_count]) },
        { action:'action_plans', title:'Action Plans',
          head:['ID','Title','Audit','Root Cause','Target Date','Status','Resolution'],
          rows: d => d.map(r => [r.plan_id, r.title, r.audit_title||'—', r.root_cause||'—', r.target_date||'—', r.status, r.resolution||'—']) },
        { action:'standards', title:'Standards & Policies',
          head:['ID','Title','Body','Version','Effective','Status','Active Policies','Linked Tasks'],
          rows: d => d.map(r => [r.standard_id, r.title, r.body, r.version||'—', r.effective_date||'—', r.status, `${r.active_policies}/${r.total_policies}`, r.linked_tasks]) },
      ];

      for (const s of sections) {
        const data = cache[s.action] || await fetchReport(s.action);
        doc.addPage(); pageNum++;
        pageHeader(s.title);
        doc.autoTable({ head: [s.head], body: s.rows(data), startY: 30, styles: { fontSize: 8 }, headStyles: { fillColor: accentFill } });
        pageFooter();
      }

      doc.save('QA_Reports_' + new Date().toISOString().slice(0,10) + '.pdf');
      if (typeof toast !== 'undefined') toast.success('PDF exported successfully.', 'Export');
    } catch (e) {
      if (typeof toast !== 'undefined') toast.error('PDF export failed: ' + e);
    }
    btnReset(btn);
  });

  /* ──────────────────────────────────────────────────────────
     EXPORT — EXCEL
  ────────────────────────────────────────────────────────── */
  $('#export-excel-btn').on('click', async function () {
    const btn = this;
    btnLoading(btn, 'Generating…');

    try {
      const wb = XLSX.utils.book_new();
      const now = new Date().toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });

      function addSheet(name, headers, rows) {
        const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
        XLSX.utils.book_append_sheet(wb, ws, name);
      }

      // Fetch all in parallel using fetchReport (returns normalized data)
      const fetcher = action => fetchReport(action);

      const [sum, audits, tasks, kpis, surveys, plans, standards] = await Promise.all([
        fetcher('summary'), fetcher('audits'), fetcher('tasks'),
        fetcher('kpis'), fetcher('surveys'), fetcher('action_plans'), fetcher('standards'),
      ]);

      addSheet('Summary', ['Module','Total','Key Metric','Value'], [
        ['Report Generated', now, '', ''],
        ['', '', '', ''],
        ['Audits',         sum.audits.total,    'Completed',       sum.audits.by_status['Completed']??0],
        ['Audit Tasks',    sum.tasks.total,     'Pending',         sum.tasks.by_status['Pending']??0],
        ['Action Plans',   sum.plans.total,     'Open',            sum.plans.by_status['Open']??0],
        ['KPI Indicators', sum.kpis.total,      'Meeting Target',  sum.kpis.meeting_target],
        ['KPI Avg Value',  sum.kpis.avg,        '',                ''],
        ['Surveys',        sum.surveys.total,   'Responses',       sum.surveys.total_responses],
        ['Active Standards',sum.standards.active,'Active Policies',sum.standards.policies],
      ]);

      addSheet('Audits',
        ['ID','Title','Type','Scheduled','Completion','Status','Completed Tasks','Total Tasks','Progress %','Notes'],
        audits.map(r => [r.audit_id, r.title, r.audit_type, r.scheduled_date||'', r.completion_date||'', r.status, r.completed_tasks, r.total_tasks, r.progress_pct, r.notes||''])
      );

      addSheet('Tasks',
        ['ID','Title','Audit','Standard Body','Standard','Due Date','Status','Remarks'],
        tasks.map(r => [r.task_id, r.title, r.audit_title||'', r.standard_body||'', r.standard_title||'', r.due_date||'', r.status, r.remarks||''])
      );

      addSheet('KPIs',
        ['ID','Indicator','Category','Unit','Target','Latest Value','Period','Meets Target'],
        kpis.map(r => [r.indicator_id, r.name, r.category||'', r.unit||'', r.target_value??'', r.latest_value??'', r.latest_period||'', r.meets_target===null?'N/A':r.meets_target?'Yes':'No'])
      );

      addSheet('Surveys',
        ['ID','Title','Target Group','Start Date','End Date','Status','Questions','Responses','Created By'],
        surveys.map(r => [r.survey_id, r.title, r.target_group, r.start_date||'', r.end_date||'', r.status, r.questions_count, r.responses_count, r.creator_name||''])
      );

      addSheet('Action Plans',
        ['ID','Title','Related Audit','Audit Type','Root Cause','Target Date','Status','Resolution','Created Date'],
        plans.map(r => [r.plan_id, r.title, r.audit_title||'', r.audit_type||'', r.root_cause||'', r.target_date||'', r.status, r.resolution||'', r.created_date||''])
      );

      addSheet('Standards',
        ['ID','Title','Body','Version','Effective Date','Status','Active Policies','Total Policies','Linked Tasks'],
        standards.map(r => [r.standard_id, r.title, r.body, r.version||'', r.effective_date||'', r.status, r.active_policies, r.total_policies, r.linked_tasks])
      );

      XLSX.writeFile(wb, 'QA_Reports_' + new Date().toISOString().slice(0,10) + '.xlsx');
      if (typeof toast !== 'undefined') toast.success('Excel exported successfully.', 'Export');
    } catch (e) {
      if (typeof toast !== 'undefined') toast.error('Excel export failed: ' + e);
    }
    btnReset(btn);
  });

  /* ──────────────────────────────────────────────────────────
     INIT — load default tab
  ────────────────────────────────────────────────────────── */
  loadSummary();

});
</script>
</body>
</html>