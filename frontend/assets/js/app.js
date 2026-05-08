/**
 * QA Management System — Global JS
 * Toast, AJAX helpers, form validation utilities, session guard
 */

/* ── Toast System ───────────────────────────────────────────── */
const QAToast = (() => {
  let container;

  function ensureContainer() {
    if (!container) {
      container = document.getElementById('toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
      }
    }
    return container;
  }

  const ICONS = {
    success : 'fa-circle-check',
    error   : 'fa-circle-xmark',
    warning : 'fa-triangle-exclamation',
    info    : 'fa-circle-info',
  };

  const TITLES = {
    success : 'Success',
    error   : 'Error',
    warning : 'Warning',
    info    : 'Information',
  };

  /**
   * Show a toast
   * @param {string} type     - success | error | warning | info
   * @param {string} message  - Body message
   * @param {string} [title]  - Optional title override
   * @param {number} [duration=4000] - ms before auto-hide (0 = sticky)
   */
  function show(type = 'info', message = '', title = '', duration = 4000) {
    const c   = ensureContainer();
    const el  = document.createElement('div');
    el.className = `qa-toast ${type}`;

    el.innerHTML = `
      <span class="toast-icon"><i class="fa-solid ${ICONS[type] || ICONS.info}"></i></span>
      <div class="toast-body">
        <div class="toast-title">${title || TITLES[type]}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    `;

    c.appendChild(el);

    el.querySelector('.toast-close').addEventListener('click', () => hide(el));

    if (duration > 0) {
      setTimeout(() => hide(el), duration);
    }

    return el;
  }

  function hide(el) {
    if (!el || el.classList.contains('hiding')) return;
    el.classList.add('hiding');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
    setTimeout(() => el.remove(), 400); // fallback
  }

  return { show, hide };
})();

/* Shorthand helpers */
const toast = {
  success : (msg, title, dur) => QAToast.show('success', msg, title, dur),
  error   : (msg, title, dur) => QAToast.show('error',   msg, title, dur),
  warning : (msg, title, dur) => QAToast.show('warning', msg, title, dur),
  info    : (msg, title, dur) => QAToast.show('info',    msg, title, dur),
};

/* ── AJAX Helper ────────────────────────────────────────────── */
/**
 * Wrapper around $.ajax that always returns a Promise.
 * Handles common error patterns & shows toasts automatically.
 *
 * @param {object} options  - jQuery $.ajax options
 * @param {boolean} [quiet] - If true, suppress auto toasts on error
 */
function qaAjax(options, quiet = false) {
  return new Promise((resolve, reject) => {
    $.ajax({
      dataType: 'json',
      ...options,
      success(data) {
        resolve(data);
      },
      error(xhr) {
        let data;
        try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }

        const msg = data?.message || 'An unexpected error occurred. Please try again.';

        if (!quiet) {
          toast.error(msg);
        }

        reject(data || { success: false, message: msg });
      },
    });
  });
}

/* ── Form Validation ────────────────────────────────────────── */
/**
 * Minimal client-side validator
 * Clears previous errors then validates each rule.
 *
 * @param {string|Element} formSel  - CSS selector or DOM element
 * @param {object}         rules    - { fieldName: { required, minLength, maxLength, email, match } }
 * @returns {boolean} isValid
 */
function validateForm(formSel, rules = {}) {
  const form = typeof formSel === 'string' ? document.querySelector(formSel) : formSel;
  if (!form) return false;

  // Clear previous errors
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.form-error-msg').forEach(el => {
    el.textContent = '';
    el.classList.remove('show');
  });

  let valid = true;

  for (const [name, rule] of Object.entries(rules)) {
    const field = form.querySelector(`[name="${name}"]`);
    if (!field) continue;

    const value = field.value.trim();
    let error   = '';

    if (rule.required && value === '') {
      error = rule.required === true ? `${titleCase(name)} is required.` : rule.required;
    } else if (rule.minLength && value.length < rule.minLength) {
      error = `Must be at least ${rule.minLength} characters.`;
    } else if (rule.maxLength && value.length > rule.maxLength) {
      error = `Must be no more than ${rule.maxLength} characters.`;
    } else if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      error = 'Enter a valid email address.';
    } else if (rule.match) {
      const match = form.querySelector(`[name="${rule.match}"]`);
      if (match && match.value !== field.value) {
        error = 'Passwords do not match.';
      }
    } else if (rule.custom) {
      error = rule.custom(value, form) || '';
    }

    if (error) {
      valid = false;
      field.classList.add('is-invalid');
      const errEl = field.parentElement?.querySelector('.form-error-msg')
                  || document.getElementById(`err-${name}`);
      if (errEl) {
        errEl.textContent = error;
        errEl.classList.add('show');
      }
    }
  }

  return valid;
}

/** Clear all validation errors on a form */
function clearFormErrors(formSel) {
  const form = typeof formSel === 'string' ? document.querySelector(formSel) : formSel;
  if (!form) return;
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.form-error-msg').forEach(el => {
    el.textContent = '';
    el.classList.remove('show');
  });
}

/** Show server-returned field errors */
function applyServerErrors(formSel, errors = {}) {
  const form = typeof formSel === 'string' ? document.querySelector(formSel) : formSel;
  if (!form) return;
  for (const [name, msg] of Object.entries(errors)) {
    const field = form.querySelector(`[name="${name}"]`);
    if (field) {
      field.classList.add('is-invalid');
      const errEl = field.parentElement?.querySelector('.form-error-msg')
                  || document.getElementById(`err-${name}`);
      if (errEl) {
        errEl.textContent = msg;
        errEl.classList.add('show');
      }
    }
  }
}

/* ── Utilities ──────────────────────────────────────────────── */
function titleCase(str) {
  return str.replace(/_/g, ' ').replace(/\w\S*/g, t =>
    t.charAt(0).toUpperCase() + t.substring(1).toLowerCase()
  );
}

/** Set a button to loading state */
function btnLoading(btn, loadText = 'Please wait…') {
  if (!btn) return;
  btn.disabled = true;
  btn._origHTML = btn.innerHTML;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span>${loadText}`;
}

/** Restore a button from loading state */
function btnReset(btn) {
  if (!btn) return;
  btn.disabled = false;
  if (btn._origHTML) btn.innerHTML = btn._origHTML;
}

/** Confirm dialog (returns Promise<boolean>) */
function qaConfirm(message = 'Are you sure?') {
  return new Promise(resolve => {
    // Simple native fallback — replace with a custom modal if desired
    resolve(window.confirm(message));
  });
}

/* ── Sidebar toggle (mobile) ────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const sidebar   = document.querySelector('.qa-sidebar');
  const toggleBtn = document.getElementById('sidebar-toggle');
  const overlay   = document.getElementById('sidebar-overlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar?.classList.remove('open');
      overlay.style.display = 'none';
    });
  }

  // Mark active nav link
  const current = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') === current) {
      link.classList.add('active');
    }
  });
});
