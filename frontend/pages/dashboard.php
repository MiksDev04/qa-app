<?php
/**
 * Dashboard Page
 * frontend/pages/dashboard.php
 *
 * Main QA overview — stat cards, charts,
 * quick actions. Content is loaded via AJAX from backend/api/qa/*.php
 */

session_start();

// Auth guard
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Dashboard';
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="qa-wrapper">

  <!-- ── Sidebar ─────────────────────────────────────────────── -->
  <?php include '../partials/sidebar.php'; ?>

  <!-- ── Main content ─────────────────────────────────────────── -->
  <div class="qa-content">

    <!-- ── Header ───────────────────────────────────────────── -->
    <?php include '../partials/header.php'; ?>

    <!-- ── Page body ─────────────────────────────────────────── -->
    <main class="qa-page">

      <!-- Page intro -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
            Launch QA Dashboard
          </h2>
          <p class="text-muted-qa mb-0" style="font-size:.83rem; margin-top:2px;">
            Overview of all quality assurance activities
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-outline-qa" id="refresh-btn">
            <i class="fa-solid fa-rotate-right"></i>
            Refresh
          </button>
        </div>
      </div>

      <!-- ── Stat Cards Row ───────────────────────────────────── -->
      <div class="row g-3 mb-4" id="stats-row">

        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-clipboard-check"></i></div>
            <div class="stat-label">Active Standards</div>
            <div class="stat-value" id="stat-standards">
              <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
            </div>
            <div class="stat-sub">CHED &amp; ISO compliant</div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="stat-label">Ongoing Audits</div>
            <div class="stat-value" id="stat-audits">
              <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
            </div>
            <div class="stat-sub">This academic year</div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-label">KPI Score</div>
            <div class="stat-value" id="stat-kpi">
              <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
            </div>
            <div class="stat-sub">Average across programs</div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-label">Open Action Plans</div>
            <div class="stat-value" id="stat-actions">
              <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
            </div>
            <div class="stat-sub">Pending resolution</div>
          </div>
        </div>

      </div><!-- /stats row -->

      <!-- ── Main Grid (like Clever's kanban columns) ──────────── -->
      <div class="row g-3">

      
        <!-- Surveys summary -->
        <div class="col-12 col-lg-6">
          <div class="card" style="overflow:hidden;border:1px solid rgba(63,81,181,.10);box-shadow:0 18px 40px rgba(15,23,42,.06);background:linear-gradient(180deg,#ffffff 0%,#f8faff 100%);">
            <div class="card-header-custom" style="padding:18px 20px 14px;border-bottom:1px solid rgba(63,81,181,.08);background:linear-gradient(90deg,rgba(63,81,181,.06),rgba(63,81,181,.015));">
              <div>
                <h3 class="card-title mb-1" style="display:flex;align-items:center;gap:10px;">
                  <span style="width:11px;height:11px;background:linear-gradient(135deg,var(--primary),#7c8cff);border-radius:50%;display:inline-block;box-shadow:0 0 0 4px rgba(63,81,181,.10);"></span>
                  Survey Responses
                </h3>
                <div style="font-size:.78rem;color:var(--text-muted);">Recent survey volume by form</div>
              </div>
            </div>
            <div class="card-body-custom" style="min-height:260px;padding:18px 20px 20px;background:linear-gradient(180deg,rgba(255,255,255,.9),rgba(248,250,255,.95));" id="survey-chart-wrap">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Survey trend</div>
                <div style="font-size:.74rem;color:var(--primary);font-weight:700;background:rgba(63,81,181,.08);padding:6px 10px;border-radius:999px;">Responses</div>
              </div>
              <canvas id="survey-chart" style="max-height:210px;"></canvas>
            </div>
          </div>
        </div>

        <!-- KPI mini panel -->
        <div class="col-12 col-lg-6">
          <div class="card h-100" style="overflow:hidden;border:1px solid rgba(34,197,94,.10);box-shadow:0 18px 40px rgba(15,23,42,.06);background:linear-gradient(180deg,#ffffff 0%,#f8fff9 100%);">
            <div class="card-header-custom" style="padding:18px 20px 14px;border-bottom:1px solid rgba(34,197,94,.08);background:linear-gradient(90deg,rgba(34,197,94,.06),rgba(34,197,94,.015));">
              <div>
                <h3 class="card-title mb-1" style="display:flex;align-items:center;gap:10px;">
                  <span style="width:11px;height:11px;background:linear-gradient(135deg,var(--accent-green),#7ddc9a);border-radius:50%;display:inline-block;box-shadow:0 0 0 4px rgba(34,197,94,.10);"></span>
                  KPI Targets
                </h3>
                <div style="font-size:.78rem;color:var(--text-muted);">Target versus actual performance</div>
              </div>
            </div>
            <div class="card-body-custom" style="min-height:260px;padding:18px 20px 20px;background:linear-gradient(180deg,rgba(255,255,255,.9),rgba(248,255,249,.95));" id="kpi-chart-wrap">
              <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
                <span style="font-size:.74rem;color:#166534;font-weight:700;background:rgba(34,197,94,.10);padding:6px 10px;border-radius:999px;">Target</span>
                <span style="font-size:.74rem;color:#1d4ed8;font-weight:700;background:rgba(59,130,246,.10);padding:6px 10px;border-radius:999px;">Actual</span>
              </div>
              <canvas id="kpi-chart" style="max-height:210px;"></canvas>
            </div>
          </div>
        </div>
        <!-- Recent Audits column -->
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
            <div class="card-body-custom p-0" id="audits-list">
              <!-- Skeleton loaders -->
              <?php for ($i = 0; $i < 3; $i++): ?>
              <div class="audit-skeleton p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between mb-1">
                  <span class="placeholder-wave" style="width:60%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  <span class="placeholder-wave" style="width:18%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                </div>
                <span class="placeholder-wave" style="width:40%;margin-top:6px;display:block;"><span class="placeholder col-12 bg-secondary rounded" style="height:8px;display:block;"></span></span>
                <div class="progress-bar-wrap mt-2">
                  <div class="progress-bar-fill blue placeholder-wave" style="width:55%;"></div>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Action Plans column -->
        <div class="col-12 col-lg-6">
          <div class="card">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
                Action Plans
              </h3>
              <a href="action_plans.php" class="btn-outline-qa" style="font-size:.76rem;padding:5px 10px;">
                View all <i class="fa-solid fa-chevron-right ms-1" style="font-size:.65rem;"></i>
              </a>
            </div>
            <div class="card-body-custom p-0" id="actions-list">
              <?php for ($i = 0; $i < 3; $i++): ?>
              <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between mb-1">
                  <span class="placeholder-wave" style="width:55%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  <span class="placeholder-wave" style="width:20%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                </div>
                <span class="placeholder-wave" style="width:35%;margin-top:6px;display:block;"><span class="placeholder col-12 bg-secondary rounded" style="height:8px;display:block;"></span></span>
                <div class="progress-bar-wrap mt-2">
                  <div class="progress-bar-fill orange placeholder-wave" style="width:35%;"></div>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>


      </div><!-- /main grid -->

    </main><!-- /qa-page -->
  </div><!-- /qa-content -->
</div><!-- /qa-wrapper -->

<!-- Toast container -->
<div id="toast-container"></div>

<!-- ── Create Modal (sample) ───────────────────────────────────── -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content" style="border-radius:var(--radius-lg);border:1px solid var(--border);box-shadow:var(--shadow-lg);">

      <div class="modal-header" style="border-bottom:1px solid var(--border-light);padding:18px 22px;">
        <h5 class="modal-title" id="createModalLabel"
            style="font-size:.95rem;font-weight:700;color:var(--text-primary);">
          Create New Item
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" style="padding:22px;">
        <form id="create-form" novalidate>

          <div class="mb-3">
            <label class="form-label-qa" for="create-type">Type</label>
            <select name="type" id="create-type" class="form-control-qa" required>
              <option value="">Select type…</option>
              <option value="audit">Audit</option>
              <option value="action_plan">Action Plan</option>
              <option value="survey">Survey</option>
              <option value="standard">Standard</option>
            </select>
            <div class="form-error-msg" id="err-type"></div>
          </div>

          <div class="mb-3">
            <label class="form-label-qa" for="create-title">Title</label>
            <input type="text" name="title" id="create-title"
                   class="form-control-qa"
                   placeholder="Enter a descriptive title…"
                   maxlength="150"
                   required>
            <div class="form-error-msg" id="err-title"></div>
          </div>

          <div class="mb-3">
            <label class="form-label-qa" for="create-desc">Description <span class="text-muted-qa">(optional)</span></label>
            <textarea name="description" id="create-desc"
                      class="form-control-qa"
                      rows="3"
                      placeholder="Brief description…"
                      maxlength="500"
                      style="resize:vertical;"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label-qa" for="create-due">Due / Effective Date <span class="text-muted-qa">(optional)</span></label>
            <input type="date" name="due_date" id="create-due"
                   class="form-control-qa"
                   min="<?= date('Y-m-d') ?>">
          </div>

        </form>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--border-light);padding:14px 22px;">
        <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-primary-qa" id="create-submit-btn">
          <i class="fa-solid fa-plus"></i> Create
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>

<script>
$(function () {

  /* ── Load dashboard data on page load ────────────────────── */
  loadDashboard();

  function loadDashboard() {
    loadStats();
    loadAudits();
    loadActions();
    loadKPIs();
    loadSurveyChart();
  }

  /* ── Stats ───────────────────────────────────────────────── */
  function loadStats() {
    $.ajax({
      url     : '../../backend/api/dashboard_api.php?action=get_stats',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        if (data.success && data.stats) {
          const s = data.stats;
          animateCount('#stat-standards', s.standards  ?? 0);
          animateCount('#stat-audits',    s.audits     ?? 0);
          $('#stat-kpi').text(   (s.kpi_avg ?? 0) + '%');
          animateCount('#stat-actions',   s.open_plans ?? 0);
        }
      },
      error() { /* stats fail silently */ }
    });
  }

  /* ── Audits list ─────────────────────────────────────────── */
  function loadAudits() {
    $.ajax({
      url     : '../../backend/api/dashboard_api.php?action=get_audits&limit=5',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        const list = $('#audits-list').empty();
        if (!data.success || !data.audits?.length) {
          list.html(emptyState('No audits found'));
          return;
        }
        data.audits.forEach(a => {
          const pct   = a.progress ?? 0;
          const color = statusColor(a.status);
          list.append(`
            <div class="p-3" style="border-bottom:1px solid var(--border-light);">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${escHtml(a.title)}</span>
                <span class="badge-qa ${badgeClass(a.status)}">${escHtml(a.status)}</span>
              </div>
              <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px;">
                Updated ${escHtml(a.updated ?? '—')}
              </div>
              <div style="font-size:.74rem;font-weight:600;color:var(--text-secondary);letter-spacing:.04em;
                          text-transform:uppercase;margin-bottom:4px;">Progress</div>
              <div class="d-flex align-items-center gap-2">
                <div class="progress-bar-wrap flex-fill">
                  <div class="progress-bar-fill ${color}" style="width:${pct}%;"></div>
                </div>
                <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
              </div>
            </div>
          `);
        });
      },
      error() {
        $('#audits-list').html(errorState('Could not load audits.'));
      }
    });
  }

  /* ── Action plans list ───────────────────────────────────── */
  function loadActions() {
    $.ajax({
      url     : '../../backend/api/dashboard_api.php?action=get_action_plans&limit=5&status=open',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        const list = $('#actions-list').empty();
        if (!data.success || !data.plans?.length) {
          list.html(emptyState('No open action plans'));
          return;
        }
        data.plans.forEach(p => {
          const pct = p.progress ?? 0;
          list.append(`
            <div class="p-3" style="border-bottom:1px solid var(--border-light);">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${escHtml(p.title)}</span>
                <span class="badge-qa ${badgeClass(p.status)}">${escHtml(p.status)}</span>
              </div>
              <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px;">
                Due: ${escHtml(p.target_date ?? '—')}
              </div>
              <div style="font-size:.74rem;font-weight:600;color:var(--text-secondary);letter-spacing:.04em;
                          text-transform:uppercase;margin-bottom:4px;">Progress</div>
              <div class="d-flex align-items-center gap-2">
                <div class="progress-bar-wrap flex-fill">
                  <div class="progress-bar-fill orange" style="width:${pct}%;"></div>
                </div>
                <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
              </div>
            </div>
          `);
        });
      },
      error() {
        $('#actions-list').html(errorState('Could not load action plans.'));
      }
    });
  }

  /* ── KPIs ────────────────────────────────────────────────── */
  let kpiChartInstance = null;

  function loadKPIs() {
    $.ajax({
      url     : '../../backend/api/dashboard_api.php?action=get_kpis',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        if (!data.success || !data.kpis) return;

        const labels = data.kpis.map(k => k.name || 'KPI');
        const targetValues = data.kpis.map(k => k.target ?? 0);
        const actualValues = data.kpis.map(k => k.actual ?? 0);
        const axisMax = Math.max(100, ...targetValues, ...actualValues) + 10;

        const ctx = document.getElementById('kpi-chart');
        if (!ctx) return;

        if (kpiChartInstance) kpiChartInstance.destroy();

        kpiChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Target',
                data: targetValues,
                backgroundColor: 'rgba(34, 197, 94, 0.42)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 1,
                borderRadius: 0,
                barPercentage: 0.92,
                categoryPercentage: 0.72,
                maxBarThickness: 40,
              },
              {
                label: 'Actual',
                data: actualValues,
                backgroundColor: 'rgba(59, 130, 246, 0.42)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 0,
                barPercentage: 0.92,
                categoryPercentage: 0.72,
                maxBarThickness: 40,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  font: { size: 12 },
                  color: 'var(--text-primary)',
                  usePointStyle: true,
                  boxWidth: 10,
                  boxHeight: 10,
                },
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                max: axisMax,
                ticks: {
                  color: 'var(--text-secondary)',
                  font: { size: 11 },
                },
                grid: {
                  color: 'rgba(148,163,184,.16)',
                  drawBorder: false,
                },
              },
              x: {
                ticks: {
                  color: 'var(--text-secondary)',
                  font: { size: 11 },
                },
                grid: {
                  display: false,
                },
              },
            },
          },
        });
      },
    });
  }

  /* ── Survey Chart ────────────────────────────────────────── */
  let surveyChartInstance = null;

  function loadSurveyChart() {
    $.ajax({
      url     : '../../backend/api/survey_responses_api.php?action=get_all_responses',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        if (!data.success || !Array.isArray(data.data) || !data.data.length) {
          $('#survey-chart-wrap').html(emptyState('No survey responses found'));
          return;
        }

        const labels = data.data.map(item => item.title || 'Survey');
        const responseCounts = data.data.map(item => item.responses_count ?? 0);

        const ctx = document.getElementById('survey-chart');
        if (!ctx) return;

        if (surveyChartInstance) surveyChartInstance.destroy();

        surveyChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Survey Responses',
                data: responseCounts,
                borderColor: 'rgba(63, 81, 181, 1)',
                backgroundColor: 'rgba(63, 81, 181, 0.45)',
                borderWidth: 2,
                borderRadius: 0,
                barPercentage: 0.95,
                categoryPercentage: 0.76,
                maxBarThickness: 46,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            layout: {
              padding: {
                top: 4,
                right: 4,
                bottom: 0,
                left: 0,
              },
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  font: { size: 12 },
                  color: 'var(--text-primary)',
                  usePointStyle: true,
                  boxWidth: 10,
                  boxHeight: 10,
                },
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  color: 'var(--text-secondary)',
                  font: { size: 11 },
                },
                grid: {
                  color: 'rgba(148,163,184,.16)',
                  drawBorder: false,
                },
              },
              x: {
                ticks: {
                  color: 'var(--text-secondary)',
                  font: { size: 11 },
                },
                grid: {
                  display: false,
                },
              },
            },
          },
        });
      },
    });
  }

  /* ── Refresh ─────────────────────────────────────────────── */
  $('#refresh-btn').on('click', function () {
    const btn = this;
    $(btn).find('i').addClass('fa-spin');
    btn.disabled = true;
    loadDashboard();
    setTimeout(() => {
      $(btn).find('i').removeClass('fa-spin');
      btn.disabled = false;
      toast.success('Dashboard refreshed.', 'Updated');
    }, 1200);
  });

  /* ── Create form submit ──────────────────────────────────── */
  $('#create-submit-btn').on('click', function () {
    clearFormErrors('#create-form');

    const isValid = validateForm('#create-form', {
      type  : { required: 'Please select a type.' },
      title : { required: 'Title is required.', minLength: 3, maxLength: 150 },
    });

    if (!isValid) return;

    const btn = this;
    btnLoading(btn, 'Creating…');

    $.ajax({
      url     : '../../backend/api/dashboard_api.php?action=create_item',
      type    : 'POST',
      data    : $('#create-form').serialize(),
      dataType: 'json',
      success(data) {
        if (data.success) {
          toast.success(data.message || 'Item created successfully.', 'Created');
          bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
          document.getElementById('create-form').reset();
          clearFormErrors('#create-form');
          loadDashboard(); // refresh
        } else {
          toast.error(data.message || 'Failed to create item.');
          if (data.errors) applyServerErrors('#create-form', data.errors);
        }
        btnReset(btn);
      },
      error(xhr) {
        let msg = 'A server error occurred.';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
        toast.error(msg);
        btnReset(btn);
      }
    });
  });

  // Reset form on modal close
  document.getElementById('createModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('create-form').reset();
    clearFormErrors('#create-form');
  });

  /* ── Helpers ─────────────────────────────────────────────── */
  function escHtml(str) {
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
    };
    return map[(status ?? '').toLowerCase()] || 'pending';
  }

  function statusColor(status) {
    const map = {
      'completed'  : 'green',
      'in progress': 'blue',
      'scheduled'  : 'purple',
      'cancelled'  : 'orange',
    };
    return map[(status ?? '').toLowerCase()] || 'blue';
  }

  function emptyState(msg) {
    return `<div class="text-center py-4 text-muted-qa" style="font-size:.82rem;">
              <i class="fa-regular fa-folder-open mb-2" style="font-size:1.5rem;display:block;opacity:.3;"></i>
              ${escHtml(msg)}
            </div>`;
  }

  function errorState(msg) {
    return `<div class="text-center py-3" style="font-size:.82rem;color:var(--accent-orange);">
              <i class="fa-solid fa-circle-exclamation me-1"></i>${escHtml(msg)}
            </div>`;
  }

  function animateCount(selector, target) {
    const el    = $(selector);
    let   current = 0;
    const step  = Math.ceil(target / 30);
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.text(current);
      if (current >= target) clearInterval(timer);
    }, 30);
  }

});
</script>
</body>
</html>
