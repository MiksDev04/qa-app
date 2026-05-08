<?php
/**
 * Dashboard Page
 * frontend/pages/dashboard.php
 *
 * Main QA overview — stat cards, recent activity table,
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
          <button class="btn-primary-qa" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fa-solid fa-plus"></i>
            Create
          </button>
        </div>
      </div>

      <!-- Filter tabs (like Clever's View all / Most recent / Popular) -->
      <div class="mb-4" style="border-bottom:1px solid var(--border);">
        <nav class="d-flex gap-3" style="padding-bottom:0;">
          <button class="tab-btn active" data-tab="all"
                  style="background:none;border:none;font-size:.88rem;font-weight:600;color:var(--primary);
                         padding:8px 0;border-bottom:2px solid var(--primary);margin-bottom:-1px;cursor:pointer;font-family:var(--font);">
            View all
          </button>
          <button class="tab-btn" data-tab="recent"
                  style="background:none;border:none;font-size:.88rem;font-weight:500;color:var(--text-secondary);
                         padding:8px 0;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;font-family:var(--font);">
            Most recent
          </button>
          <button class="tab-btn" data-tab="critical"
                  style="background:none;border:none;font-size:.88rem;font-weight:500;color:var(--text-secondary);
                         padding:8px 0;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;font-family:var(--font);">
            Critical
          </button>
        </nav>
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

        <!-- Surveys summary -->
        <div class="col-12 col-lg-8">
          <div class="card">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                Survey Responses
              </h3>
              <div class="d-flex gap-2 align-items-center">
                <select class="form-control-qa" id="survey-period"
                        style="padding:5px 10px;font-size:.78rem;width:auto;">
                  <option value="this_sem">This Semester</option>
                  <option value="last_sem">Last Semester</option>
                  <option value="this_year">This Year</option>
                </select>
              </div>
            </div>
            <div class="card-body-custom" style="min-height:200px; display:flex; align-items:center; justify-content:center;" id="survey-chart-wrap">
              <div class="text-center text-muted-qa">
                <i class="fa-solid fa-chart-bar" style="font-size:2rem; opacity:.2;"></i>
                <p style="font-size:.82rem; margin-top:8px;">Survey chart will render here</p>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI mini panel -->
        <div class="col-12 col-lg-4">
          <div class="card h-100">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2" style="width:10px;height:10px;background:var(--accent-green);border-radius:50%;display:inline-block;"></span>
                KPI Targets
              </h3>
            </div>
            <div class="card-body-custom" id="kpi-list">
              <!-- KPI items load via AJAX -->
              <?php
              $kpiPlaceholders = [
                ['label' => 'Board Exam Pass Rate',   'color' => 'green'],
                ['label' => 'Graduation Rate',        'color' => 'blue'],
                ['label' => 'Faculty Evaluation Avg', 'color' => 'purple'],
                ['label' => 'Student Satisfaction',   'color' => 'orange'],
              ];
              foreach ($kpiPlaceholders as $kpi):
              ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">
                    <?= $kpi['label'] ?>
                  </span>
                  <span class="kpi-pct" style="font-size:.78rem;font-weight:700;color:var(--text-primary);">—</span>
                </div>
                <div class="progress-bar-wrap">
                  <div class="progress-bar-fill <?= $kpi['color'] ?>" style="width:0%;" data-kpi="<?= $kpi['label'] ?>"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Recent activity table -->
        <div class="col-12">
          <div class="card">
            <div class="card-header-custom">
              <h3 class="card-title">Recent Activity</h3>
              <div class="d-flex gap-2">
                <input type="text" class="form-control-qa" id="activity-search"
                       placeholder="Search activity…"
                       style="padding:6px 10px;font-size:.8rem;width:180px;">
              </div>
            </div>
            <div class="table-responsive">
              <table class="table-qa" id="activity-table">
                <thead>
                  <tr>
                    <th>Activity</th>
                    <th>Module</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="activity-tbody">
                  <!-- Skeleton rows -->
                  <?php for ($i = 0; $i < 5; $i++): ?>
                  <tr>
                    <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                    <td><span class="placeholder-wave"><span class="placeholder col-6 bg-secondary rounded"></span></span></td>
                    <td><span class="placeholder-wave"><span class="placeholder col-5 bg-secondary rounded"></span></span></td>
                    <td><span class="placeholder-wave"><span class="placeholder col-5 bg-secondary rounded"></span></span></td>
                    <td><span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span></td>
                    <td></td>
                  </tr>
                  <?php endfor; ?>
                </tbody>
              </table>
            </div>
            <!-- Pagination placeholder -->
            <div class="d-flex align-items-center justify-content-between p-3"
                 style="border-top:1px solid var(--border-light);">
              <span style="font-size:.78rem; color:var(--text-muted);" id="activity-count">Loading…</span>
              <div class="d-flex gap-1" id="activity-pages"></div>
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
    loadActivity();
  }

  /* ── Stats ───────────────────────────────────────────────── */
  function loadStats() {
    $.ajax({
      url     : '../../backend/api/qa/get_stats.php',
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
      url     : '../../backend/api/qa/get_audits.php?limit=5',
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
      url     : '../../backend/api/qa/get_action_plans.php?limit=5&status=open',
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
  function loadKPIs() {
    $.ajax({
      url     : '../../backend/api/qa/get_kpis.php',
      type    : 'GET',
      dataType: 'json',
      success(data) {
        if (!data.success || !data.kpis) return;
        data.kpis.forEach((k, i) => {
          const fills = $('#kpi-list .progress-bar-fill');
          if (fills.eq(i).length) {
            fills.eq(i).css('width', (k.value ?? 0) + '%');
            fills.eq(i).closest('.mb-3').find('.kpi-pct').text((k.value ?? 0) + '%');
          }
        });
      }
    });
  }

  /* ── Activity table ──────────────────────────────────────── */
  let activityPage = 1;

  function loadActivity(page = 1, search = '') {
    activityPage = page;
    $.ajax({
      url     : `../../backend/api/qa/get_activity.php?page=${page}&search=${encodeURIComponent(search)}&limit=10`,
      type    : 'GET',
      dataType: 'json',
      success(data) {
        const tbody = $('#activity-tbody').empty();
        if (!data.success || !data.items?.length) {
          tbody.html(`<tr><td colspan="6" class="text-center py-4">${emptyState('No activity found')}</td></tr>`);
          $('#activity-count').text('0 records');
          return;
        }
        data.items.forEach(item => {
          tbody.append(`
            <tr>
              <td><strong>${escHtml(item.activity)}</strong></td>
              <td>${escHtml(item.module)}</td>
              <td>${escHtml(item.user)}</td>
              <td><span class="badge-qa ${badgeClass(item.status)}">${escHtml(item.status)}</span></td>
              <td style="color:var(--text-muted);font-size:.8rem;">${escHtml(item.date)}</td>
              <td>
                <button class="btn-outline-qa view-activity-btn"
                        data-id="${escHtml(item.id)}"
                        style="padding:4px 10px;font-size:.75rem;">
                  View
                </button>
              </td>
            </tr>
          `);
        });
        const total = data.total ?? data.items.length;
        $('#activity-count').text(`${total} record${total !== 1 ? 's' : ''}`);
        renderPagination(data.page ?? 1, data.total_pages ?? 1);
      },
      error() {
        $('#activity-tbody').html(`<tr><td colspan="6">${errorState('Failed to load activity.')}</td></tr>`);
      }
    });
  }

  function renderPagination(current, total) {
    const wrap = $('#activity-pages').empty();
    for (let p = 1; p <= total; p++) {
      const active = p === current ? 'background:var(--primary);color:#fff;border-color:var(--primary);' : '';
      wrap.append(`
        <button class="btn-outline-qa page-btn"
                data-page="${p}"
                style="padding:4px 10px;font-size:.75rem;min-width:32px;${active}">${p}</button>
      `);
    }
  }

  // Pagination click
  $(document).on('click', '.page-btn', function () {
    loadActivity(+$(this).data('page'), $('#activity-search').val());
  });

  // Activity search
  let searchTimer;
  $('#activity-search').on('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadActivity(1, $(this).val()), 400);
  });

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

  /* ── Tab filter ──────────────────────────────────────────── */
  $('.tab-btn').on('click', function () {
    $('.tab-btn').css({
      'color'        : 'var(--text-secondary)',
      'border-bottom': '2px solid transparent',
      'font-weight'  : '500'
    });
    $(this).css({
      'color'        : 'var(--primary)',
      'border-bottom': '2px solid var(--primary)',
      'font-weight'  : '600'
    });
    // Reload with filter
    const tab = $(this).data('tab');
    loadActivity(1, tab === 'all' ? '' : tab);
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
      url     : '../../backend/api/qa/create_item.php',
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
