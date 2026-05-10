<?php
session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle   = 'Thank You';
$surveyToken = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — QA System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <div class="qa-page d-flex align-items-center justify-content-center"
       style="min-height:100vh;background:linear-gradient(180deg,#f8faff 0%,#ffffff 100%);">
    <div class="container" style="max-width:640px;">
      <div class="card"
           style="border:1px solid rgba(63,81,181,.10);box-shadow:0 24px 60px rgba(15,23,42,.08);overflow:hidden;">
        <div class="card-body text-center p-5">

          <!-- Success icon -->
          <div class="mb-4 mx-auto d-flex align-items-center justify-content-center"
               style="width:84px;height:84px;border-radius:50%;background:rgba(34,197,94,.10);color:#16a34a;">
            <i class="fa-solid fa-circle-check" style="font-size:2.2rem;"></i>
          </div>

          <h1 class="mb-3" style="font-size:2rem;font-weight:800;letter-spacing:-.04em;">
            Thank you for submitting
          </h1>
          <p class="text-muted mb-5" style="font-size:1rem;">
            Your survey response has been recorded.
          </p>

          <!-- Action buttons -->
          <div class="d-flex gap-3 justify-content-center flex-wrap">

            <?php if (!empty($surveyToken)): ?>
              <!-- Review the survey (view-only) -->
              <a href="survey_form.php?token=<?= htmlspecialchars(urlencode($surveyToken)) ?>&view=1"
                 class="btn-outline-qa text-decoration-none d-inline-flex align-items-center gap-2">
                <i class="fa-regular fa-eye"></i>
                Review my response
              </a>

              <!-- Submit another response (fresh form, no view flag) -->
              <a href="survey_form.php?token=<?= htmlspecialchars(urlencode($surveyToken)) ?>"
                 class="btn-primary-qa text-decoration-none d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-rotate-right"></i>
                Submit another response
              </a>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>