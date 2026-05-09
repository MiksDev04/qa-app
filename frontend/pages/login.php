<?php

session_start();

if (!empty($_SESSION['logged_in'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — QA Management System</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Custom -->
  <link rel="stylesheet" href="../assets/css/styles.css">

  <style>
    /* Login-specific overrides */
    .login-bg {
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, #f5f3ff 0%, #ede9fd 40%, #e0e7ff 100%);
      z-index: 0;
    }

    /* Decorative shapes */
    .login-bg::before {
      content: '';
      position: absolute;
      top: -120px;
      right: -80px;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(108,92,231,.12) 0%, transparent 70%);
      border-radius: 50%;
    }

    .login-bg::after {
      content: '';
      position: absolute;
      bottom: -100px;
      left: -60px;
      width: 380px;
      height: 380px;
      background: radial-gradient(circle, rgba(9,132,227,.08) 0%, transparent 70%);
      border-radius: 50%;
    }

    .login-page { position: relative; z-index: 1; }

    .login-card {
      animation: fadeUp .4s ease both;
    }

    @keyframes fadeUp {
      from { opacity:0; transform:translateY(18px); }
      to   { opacity:1; transform:translateY(0); }
    }

    .input-group-qa {
      position: relative;
    }

    .input-group-qa .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: .82rem;
      z-index: 2;
      pointer-events: none;
    }

    .input-group-qa .form-control-qa {
      padding-left: 36px;
    }

    .input-group-qa .toggle-pw {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: .82rem;
      z-index: 2;
      padding: 4px;
    }

    .input-group-qa .toggle-pw:hover { color: var(--text-primary); }

    .login-footer {
      text-align: center;
      font-size: .75rem;
      color: var(--text-muted);
      margin-top: 24px;
    }

    .alert-login {
      border-radius: var(--radius-sm);
      font-size: .83rem;
      padding: 10px 14px;
      margin-bottom: 16px;
      display: none;
      animation: fadeUp .2s ease;
    }
  </style>
</head>
<body>

<div class="login-bg"></div>

<main class="login-page">
  <div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
      <i class="fa-solid fa-shield-halved"></i>
    </div>

    <h1 class="login-title">Welcome back</h1>
    <p class="login-sub">Sign in to the QA Management System</p>

    <!-- Server-side error banner (for non-JS fallback) -->
    <div id="login-alert" class="alert alert-danger alert-login" role="alert">
      <i class="fa-solid fa-circle-xmark me-1"></i>
      <span id="login-alert-msg"></span>
    </div>

    <!-- Login Form -->
    <form id="login-form" novalidate autocomplete="off">

      <!-- Username -->
      <div class="mb-3">
        <label class="form-label-qa" for="username">Username</label>
        <div class="input-group-qa">
          <span class="input-icon"><i class="fa-regular fa-user"></i></span>
          <input type="text"
                 id="username"
                 name="username"
                 class="form-control-qa"
                 placeholder="Enter your username"
                 autocomplete="username"
                 maxlength="50"
                 required>
        </div>
        <div class="form-error-msg" id="err-username"></div>
      </div>

      <!-- Password -->
      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="form-label-qa mb-0" for="password">Password</label>
          <a href="forgot_password.php" style="font-size:.75rem; color:var(--primary);">
            Forgot password?
          </a>
        </div>
        <div class="input-group-qa">
          <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
          <input type="password"
                 id="password"
                 name="password"
                 class="form-control-qa"
                 placeholder="Enter your password"
                 autocomplete="current-password"
                 maxlength="128"
                 required>
          <button type="button"
                  class="toggle-pw"
                  id="toggle-pw"
                  aria-label="Show/hide password"
                  tabindex="-1">
            <i class="fa-regular fa-eye" id="pw-eye"></i>
          </button>
        </div>
        <div class="form-error-msg" id="err-password"></div>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-login" id="login-btn">
        <i class="fa-solid fa-right-to-bracket"></i>
        Sign In
      </button>

    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Quality Assurance Management System
      &bull; All rights reserved
    </div>

  </div>
</main>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
$(function () {

  /* ── Toggle password visibility ──────────────────────────── */
  $('#toggle-pw').on('click', function () {
    const inp = $('#password');
    const eye = $('#pw-eye');
    if (inp.attr('type') === 'password') {
      inp.attr('type', 'text');
      eye.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      inp.attr('type', 'password');
      eye.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  /* ── Login form submission ────────────────────────────────── */
  $('#login-form').on('submit', function (e) {
    e.preventDefault();

    // Hide previous alert
    $('#login-alert').hide();

    // Client-side validation
    const isValid = validateForm('#login-form', {
      username: { required: 'Username is required.' },
      password: { required: 'Password is required.' },
    });

    if (!isValid) return;

    const btn = document.getElementById('login-btn');
    btnLoading(btn, 'Signing in…');

    $.ajax({
      url     : '../../backend/api/auth/login_api.php',
      type    : 'POST',
      data    : {
        username: $('#username').val().trim(),
        password: $('#password').val(),
      },
      dataType: 'json',
      success(data) {
        if (data.success) {
          toast.success('Login successful! Redirecting…', 'Welcome', 0);
          setTimeout(() => {
            window.location.href = data.redirect || 'dashboard.php';
          }, 800);
        } else {
          showLoginError(data.message || 'Login failed.');
          if (data.errors) applyServerErrors('#login-form', data.errors);
          btnReset(btn);
        }
      },
      error(xhr) {
        let msg = 'A server error occurred. Please try again.';
        try {
          const d = JSON.parse(xhr.responseText);
          msg = d.message || msg;
          if (d.errors) applyServerErrors('#login-form', d.errors);
        } catch (err) { /* ignore */ }
        showLoginError(msg);
        btnReset(btn);
      }
    });
  });

  function showLoginError(msg) {
    $('#login-alert-msg').text(msg);
    $('#login-alert').fadeIn(200);
  }

  /* Auto-hide alert on input */
  $('#username, #password').on('input', function () {
    $('#login-alert').hide();
    $(this).removeClass('is-invalid');
    $(this).closest('.mb-3, .mb-4').find('.form-error-msg').removeClass('show');
  });

});
</script>
</body>
</html>
