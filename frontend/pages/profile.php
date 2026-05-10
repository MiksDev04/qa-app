<?php
/**
 * Profile Page – View & Edit Current User Profile
 * frontend/pages/profile.php
 */

session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'My Profile';
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

  <style>
    /* ── Profile-specific additions (all tokens from styles.css) ── */

    .profile-hero {
      background: var(--primary, #2d5a3d);
      border-radius: var(--radius, 10px);
      padding: 28px 32px;
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }
    .profile-hero::before {
      content: '';
      position: absolute;
      top: -50px; right: -50px;
      width: 200px; height: 200px;
      background: rgba(255,255,255,.05);
      border-radius: 50%;
      pointer-events: none;
    }
    .profile-hero::after {
      content: '';
      position: absolute;
      bottom: -70px; left: 140px;
      width: 260px; height: 260px;
      background: rgba(255,255,255,.04);
      border-radius: 50%;
      pointer-events: none;
    }

    .profile-avatar {
      flex-shrink: 0;
      width: 68px; height: 68px;
      border-radius: 50%;
      background: rgba(255,255,255,.18);
      border: 2px solid rgba(255,255,255,.28);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: -.5px;
      position: relative; z-index: 1;
    }

    .profile-hero-info { position: relative; z-index: 1; flex: 1; min-width: 0; }
    .profile-hero-info .hero-name {
      font-size: 1.12rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 2px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .profile-hero-info .hero-email {
      font-size: .79rem;
      color: rgba(255,255,255,.72);
      margin-bottom: 9px;
    }
    .hero-badges { display: flex; gap: 7px; flex-wrap: wrap; }
    .badge-hero {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .68rem; font-weight: 600;
      letter-spacing: .04em; text-transform: uppercase;
      background: rgba(255,255,255,.18);
      color: #fff;
      border: 1px solid rgba(255,255,255,.2);
    }

    .hero-stats { display: flex; gap: 12px; position: relative; z-index: 1; flex-shrink: 0; }
    .hero-stat-box {
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.15);
      border-radius: 8px;
      padding: 10px 18px;
      text-align: center; min-width: 76px;
    }
    .hero-stat-box .stat-n {
      font-size: 1.25rem; font-weight: 700;
      color: #fff; line-height: 1;
    }
    .hero-stat-box .stat-lbl {
      font-size: .61rem; color: rgba(255,255,255,.68);
      margin-top: 3px; text-transform: uppercase; letter-spacing: .05em;
    }

    /* ── Form helpers ─────────────────────────────────────── */
    .form-label-qa {
      font-size: .74rem; font-weight: 600;
      color: var(--text-secondary, #6b6860);
      text-transform: uppercase; letter-spacing: .06em;
      margin-bottom: 5px; display: block;
    }
    .form-control-qa {
      width: 100%;
      padding: 9px 13px;
      border: 1.5px solid var(--border, #e2ddd4);
      border-radius: 7px;
      font-family: var(--font, inherit);
      font-size: .88rem;
      color: var(--text-primary, #1a1a18);
      background: #fff;
      transition: border-color .18s, box-shadow .18s;
      outline: none;
    }
    .form-control-qa:focus {
      border-color: var(--primary, #2d5a3d);
      box-shadow: 0 0 0 3px rgba(45,90,61,.1);
    }
    .form-control-qa.is-invalid { border-color: #c0392b; }
    .form-control-qa:disabled,
    .form-control-qa[readonly] {
      background: var(--bg, #f5f4f0);
      color: var(--text-secondary, #6b6860);
      cursor: default;
    }
    .invalid-feedback-qa {
      font-size: .74rem; color: #c0392b;
      margin-top: 3px; display: none;
    }
    .invalid-feedback-qa.show { display: block; }

    /* ── Password strength ─────────────────────────────────── */
    .strength-bar {
      height: 4px; border-radius: 2px;
      background: var(--border, #e2ddd4);
      overflow: hidden; margin-top: 6px;
    }
    .strength-fill {
      height: 100%; border-radius: 2px;
      transition: width .25s, background .25s; width: 0%;
    }
    .strength-text {
      font-size: .72rem;
      color: var(--text-secondary, #6b6860);
      margin-top: 3px;
    }

    /* ── Account meta list ─────────────────────────────────── */
    .meta-list { list-style: none; padding: 0; margin: 0; }
    .meta-list li {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border-light, #f0ede6);
      font-size: .85rem;
    }
    .meta-list li:last-child { border-bottom: none; }
    .meta-key { color: var(--text-secondary, #6b6860); }
    .meta-val { font-weight: 600; color: var(--text-primary, #1a1a18); }

    @media (max-width: 640px) {
      .profile-hero  { flex-direction: column; text-align: center; }
      .hero-stats    { justify-content: center; }
      .hero-badges   { justify-content: center; }
    }
  </style>
</head>
<body>

<div class="qa-wrapper">

  <!-- ── Sidebar ───────────────────────────────────────────── -->
  <?php include '../partials/sidebar.php'; ?>

  <!-- ── Main content ─────────────────────────────────────── -->
  <div class="qa-content">

    <?php include '../partials/header.php'; ?>

    <main class="qa-page">

      <!-- ── Page heading ─────────────────────────────────── -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
            My Profile
          </h2>
          <p class="text-muted-qa mb-0" style="font-size:.83rem;margin-top:2px;">
            Manage your account details and security settings
          </p>
        </div>
      </div>

      <!-- ── Hero card ─────────────────────────────────────── -->
      <div class="profile-hero">
        <div class="profile-avatar" id="heroAvatar">…</div>
        <div class="profile-hero-info">
          <div class="hero-name" id="heroName">
            <span class="placeholder-wave">
              <span class="placeholder col-5 bg-light rounded"></span>
            </span>
          </div>
          <div class="hero-email" id="heroEmail">
            <span class="placeholder-wave">
              <span class="placeholder col-4 bg-light rounded" style="height:10px;display:inline-block;"></span>
            </span>
          </div>
          <div class="hero-badges" id="heroBadges"></div>
        </div>
        <div class="hero-stats" id="heroStats"></div>
      </div>

      <!-- ── Forms row ─────────────────────────────────────── -->
      <div class="row g-3 mb-3">

        <!-- Personal Information -->
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2"
                      style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                Personal Information
              </h3>
            </div>
            <div class="card-body-custom">
              <form id="infoForm" novalidate>

                <div class="mb-3">
                  <label class="form-label-qa">Username</label>
                  <input type="text" class="form-control-qa" id="usernameField" disabled>
                </div>

                <div class="mb-3">
                  <label class="form-label-qa" for="fullName">Full Name</label>
                  <input type="text" class="form-control-qa" id="fullName"
                         placeholder="Your full name">
                  <div class="invalid-feedback-qa" id="errFullName"></div>
                </div>

                <div class="mb-3">
                  <label class="form-label-qa" for="emailField">Email Address</label>
                  <input type="email" class="form-control-qa" id="emailField"
                         placeholder="your@email.com">
                  <div class="invalid-feedback-qa" id="errEmail"></div>
                </div>

                <div class="mb-3">
                  <label class="form-label-qa">Role</label>
                  <input type="text" class="form-control-qa" id="roleField" disabled>
                </div>

                <div class="d-flex justify-content-end mt-3">
                  <button type="submit" class="btn-primary-qa" id="btnSaveInfo" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>

        <!-- Change Password -->
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2"
                      style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
                Change Password
              </h3>
            </div>
            <div class="card-body-custom">
              <form id="pwdForm" novalidate>

                <div class="mb-3">
                  <label class="form-label-qa" for="currentPwd">Current Password</label>
                  <input type="password" class="form-control-qa" id="currentPwd"
                         placeholder="Enter current password">
                  <div class="invalid-feedback-qa" id="errCurrentPwd"></div>
                </div>

                <div class="mb-3">
                  <label class="form-label-qa" for="newPwd">New Password</label>
                  <input type="password" class="form-control-qa" id="newPwd"
                         placeholder="Min. 8 characters">
                  <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                  </div>
                  <div class="strength-text" id="strengthText"></div>
                  <div class="invalid-feedback-qa" id="errNewPwd"></div>
                </div>

                <div class="mb-3">
                  <label class="form-label-qa" for="confirmPwd">Confirm New Password</label>
                  <input type="password" class="form-control-qa" id="confirmPwd"
                         placeholder="Repeat new password">
                  <div class="invalid-feedback-qa" id="errConfirmPwd"></div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                  <button type="submit" class="btn-primary-qa" id="btnChangePwd">
                    <i class="fa-solid fa-lock me-1"></i> Update Password
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Account Details ───────────────────────────────── -->
      <div class="row g-3">
        <div class="col-12">
          <div class="card">
            <div class="card-header-custom">
              <h3 class="card-title">
                <span class="me-2"
                      style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                Account Details
              </h3>
            </div>
            <div class="card-body-custom">
              <ul class="meta-list" id="metaList">
                <!-- Skeleton placeholder rows -->
                <?php for ($i = 0; $i < 5; $i++): ?>
                <li>
                  <span class="meta-key">
                    <span class="placeholder-wave">
                      <span class="placeholder col-3 bg-secondary rounded"></span>
                    </span>
                  </span>
                  <span class="meta-val">
                    <span class="placeholder-wave">
                      <span class="placeholder col-2 bg-secondary rounded"></span>
                    </span>
                  </span>
                </li>
                <?php endfor; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Toast container (app.js expects this id) -->
<div id="toast-container"></div>

<!-- ── Scripts ─────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>

<script>
$(function () {

  const API = '../../backend/api/profile_api.php';

  /* ── Utilities ──────────────────────────────────────────── */
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function initials(name) {
    return String(name || '?')
      .split(' ').map(w => w[0] || '').join('').slice(0, 2).toUpperCase();
  }

  function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric'
    });
  }

  function roleLabel(role) {
    return String(role || '').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
  }

  function setFieldError(inputId, errId, msg) {
    const $f = $('#' + inputId);
    const $e = $('#' + errId);
    if (msg) {
      $f.addClass('is-invalid');
      $e.text(msg).addClass('show');
    } else {
      $f.removeClass('is-invalid');
      $e.text('').removeClass('show');
    }
  }

  function clearErrors(pairs) {
    pairs.forEach(([f, e]) => setFieldError(f, e, ''));
  }

  /* ── Load & render profile ──────────────────────────────── */
  function loadProfile() {
    $.ajax({ url: API, type: 'GET', data: { action: 'get' }, dataType: 'json' })
      .done(function (res) {
        if (!res.success) {
          toast.error(res.message || 'Failed to load profile.');
          return;
        }
        renderProfile(res.data);
      })
      .fail(function () {
        toast.error('Network error loading profile.');
      });
  }

  function renderProfile(u) {
    const act = u.activity || {};

    // ── Hero ──
    $('#heroAvatar').text(initials(u.full_name));
    $('#heroName').text(u.full_name);
    $('#heroEmail').text(u.email);

    $('#heroBadges').html(`
      <span class="badge-hero">
        <i class="fa-solid fa-shield-halved" style="font-size:.62rem;"></i>
        ${esc(roleLabel(u.role))}
      </span>
      <span class="badge-hero">
        <i class="fa-solid fa-circle${u.is_active ? '-check' : '-xmark'}" style="font-size:.62rem;"></i>
        ${u.is_active ? 'Active' : 'Inactive'}
      </span>
    `);

    $('#heroStats').html(`
      <div class="hero-stat-box">
        <div class="stat-n">${esc(act.surveys_created ?? 0)}</div>
        <div class="stat-lbl">Surveys</div>
      </div>
      <div class="hero-stat-box">
        <div class="stat-n">${esc(act.reports_generated ?? 0)}</div>
        <div class="stat-lbl">Reports</div>
      </div>
    `);

    // ── Form fields ──
    $('#usernameField').val(u.username);
    $('#fullName').val(u.full_name);
    $('#emailField').val(u.email);
    $('#roleField').val(roleLabel(u.role));
    $('#btnSaveInfo').prop('disabled', false);

    // ── Account details ──
    const statusBadge = u.is_active
      ? '<span class="badge-qa active"><i class="fa-solid fa-check me-1"></i>Active</span>'
      : '<span class="badge-qa cancelled"><i class="fa-solid fa-xmark me-1"></i>Inactive</span>';

    $('#metaList').html(`
      <li>
        <span class="meta-key">
          <i class="fa-solid fa-fingerprint me-2" style="color:var(--text-muted,#aaa);"></i>User ID
        </span>
        <span class="meta-val">#${esc(u.user_id)}</span>
      </li>
      <li>
        <span class="meta-key">
          <i class="fa-solid fa-circle-check me-2" style="color:var(--text-muted,#aaa);"></i>Account Status
        </span>
        <span class="meta-val">${statusBadge}</span>
      </li>
      <li>
        <span class="meta-key">
          <i class="fa-regular fa-calendar me-2" style="color:var(--text-muted,#aaa);"></i>Member Since
        </span>
        <span class="meta-val">${formatDate(u.created_at)}</span>
      </li>
      <li>
        <span class="meta-key">
          <i class="fa-solid fa-paper-plane me-2" style="color:var(--text-muted,#aaa);"></i>Surveys Created
        </span>
        <span class="meta-val">${esc(act.surveys_created ?? 0)}</span>
      </li>
      <li>
        <span class="meta-key">
          <i class="fa-solid fa-file-lines me-2" style="color:var(--text-muted,#aaa);"></i>Reports Generated
        </span>
        <span class="meta-val">${esc(act.reports_generated ?? 0)}</span>
      </li>
    `);
  }

  /* ── Save profile info ──────────────────────────────────── */
  $('#infoForm').on('submit', function (e) {
    e.preventDefault();
    clearErrors([['fullName','errFullName'], ['emailField','errEmail']]);

    const btn = document.getElementById('btnSaveInfo');
    btnLoading(btn, 'Saving…');

    $.ajax({
      url: API, type: 'POST',
      contentType: 'application/json', dataType: 'json',
      data: JSON.stringify({
        action:    'update_info',
        full_name: $('#fullName').val().trim(),
        email:     $('#emailField').val().trim(),
      })
    })
    .done(function (res) {
      if (res.success) {
        toast.success('Profile updated successfully.', 'Saved');
        loadProfile();
      } else {
        const errs = res.data?.errors || {};
        if (errs.full_name) setFieldError('fullName',   'errFullName', errs.full_name);
        if (errs.email)     setFieldError('emailField', 'errEmail',    errs.email);
        if (!errs.full_name && !errs.email) toast.error(res.message || 'Update failed.');
      }
    })
    .fail(function () { toast.error('Network error. Please try again.'); })
    .always(function () { btnReset(btn); });
  });

  /* ── Password strength meter ────────────────────────────── */
  $('#newPwd').on('input', function () {
    const val = this.value;
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const map = [
      { w:'0%',   bg:'transparent', lbl:'' },
      { w:'25%',  bg:'#c0392b',     lbl:'Weak' },
      { w:'50%',  bg:'#e67e22',     lbl:'Fair' },
      { w:'75%',  bg:'#f1c40f',     lbl:'Good' },
      { w:'100%', bg:'#27ae60',     lbl:'Strong' },
    ];
    const m = map[score];
    $('#strengthFill').css({ width: m.w, background: m.bg });
    $('#strengthText').text(m.lbl);
  });

  /* ── Change password ────────────────────────────────────── */
  $('#pwdForm').on('submit', function (e) {
    e.preventDefault();
    clearErrors([
      ['currentPwd','errCurrentPwd'],
      ['newPwd','errNewPwd'],
      ['confirmPwd','errConfirmPwd'],
    ]);

    const btn = document.getElementById('btnChangePwd');
    btnLoading(btn, 'Updating…');

    $.ajax({
      url: API, type: 'POST',
      contentType: 'application/json', dataType: 'json',
      data: JSON.stringify({
        action:           'change_password',
        current_password: $('#currentPwd').val(),
        new_password:     $('#newPwd').val(),
        confirm_password: $('#confirmPwd').val(),
      })
    })
    .done(function (res) {
      if (res.success) {
        toast.success('Password changed successfully.', 'Updated');
        $('#pwdForm')[0].reset();
        $('#strengthFill').css({ width:'0%' });
        $('#strengthText').text('');
      } else {
        const errs = res.data?.errors || {};
        if (errs.current_password) setFieldError('currentPwd', 'errCurrentPwd', errs.current_password);
        if (errs.new_password)     setFieldError('newPwd',     'errNewPwd',     errs.new_password);
        if (errs.confirm_password) setFieldError('confirmPwd', 'errConfirmPwd', errs.confirm_password);
        if (!Object.keys(errs).length) toast.error(res.message || 'Password change failed.');
      }
    })
    .fail(function () { toast.error('Network error. Please try again.'); })
    .always(function () { btnReset(btn); });
  });

  /* ── Init ───────────────────────────────────────────────── */
  loadProfile();

});
</script>

</body>
</html>