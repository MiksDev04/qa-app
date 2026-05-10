<?php
session_start();

// Check authentication
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Action Plans';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - QA System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        .action-plan-card {
            transition: all 0.2s ease;
            border-left: 3px solid var(--primary);
        }

        .action-plan-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .filter-bar {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-Open {
            background: #ffe5e5;
            color: #dc3545;
        }

        .status-In-Progress {
            background: #fff3e0;
            color: #ff9800;
        }

        .status-Resolved {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-Closed {
            background: #e8f5e9;
            color: #4caf50;
        }
    </style>
</head>

<body>

    <div class="qa-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">
            <?php include '../partials/header.php'; ?>

            <div class="qa-page">
                <!-- ============================================================ -->
                <!-- USER MANUAL - QUALITY ASSURANCE MANAGEMENT SYSTEM            -->
                <!-- ============================================================ -->

                <!-- Hero Section -->
                <div class="text-center mb-5" style="max-width: 800px; margin: 0 auto 40px auto;">
                    <div class="stat-icon purple mx-auto mb-3" style="width: 64px; height: 64px; font-size: 28px;">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <h1 class="fw-600" style="font-size: 2rem; letter-spacing: -0.5px;">System User Manual</h1>
                    <p class="text-muted-qa mt-2" style="font-size: 1rem;">Quality Assurance Management System — Complete guide to audits, tasks, and accreditation workflows</p>
                    <div class="mt-3">
                        <span class="badge-qa active">Version 1.0</span>
                        <span class="badge-qa pending ms-2">Last Updated: 2026</span>
                    </div>
                </div>

                <!-- Quick Navigation Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="stat-card text-center" style="cursor: pointer;" onclick="document.getElementById('overviewSection').scrollIntoView({behavior: 'smooth'})">
                            <div class="stat-icon purple mx-auto mb-2">🏠</div>
                            <div class="stat-label">01</div>
                            <div class="stat-value" style="font-size: 1.1rem;">System Overview</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center" style="cursor: pointer;" onclick="document.getElementById('auditsSection').scrollIntoView({behavior: 'smooth'})">
                            <div class="stat-icon blue mx-auto mb-2">📋</div>
                            <div class="stat-label">02</div>
                            <div class="stat-value" style="font-size: 1.1rem;">Managing Audits</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center" style="cursor: pointer;" onclick="document.getElementById('tasksSection').scrollIntoView({behavior: 'smooth'})">
                            <div class="stat-icon green mx-auto mb-2">✅</div>
                            <div class="stat-label">03</div>
                            <div class="stat-value" style="font-size: 1.1rem;">Task Management</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center" style="cursor: pointer;" onclick="document.getElementById('faqSection').scrollIntoView({behavior: 'smooth'})">
                            <div class="stat-icon orange mx-auto mb-2">❓</div>
                            <div class="stat-label">04</div>
                            <div class="stat-value" style="font-size: 1.1rem;">FAQ & Support</div>
                        </div>
                    </div>
                </div>

                <!-- 1. SYSTEM OVERVIEW -->
                <div class="card mb-4" id="overviewSection">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-info-circle me-2" style="color: var(--primary);"></i>1. System Overview</h3>
                    </div>
                    <div class="card-body-custom">
                        <p>The Quality Assurance (QA) Management System is an integrated module within the college ERP ecosystem. It provides a centralized platform for managing accreditation standards, audit workflows, corrective action tasks, and quality KPIs.</p>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5 class="fw-600 mb-3"><i class="fa-solid fa-chart-line me-2" style="color: var(--accent-blue);"></i>Core Features</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Audit Planning & Scheduling</li>
                                    <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Task Assignment & Tracking</li>
                                    <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Accreditation Standard Mapping</li>
                                    <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Progress Monitoring with Visual Reports</li>
                                    <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Integration with LMS, Faculty Eval & HRIS</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-600 mb-3"><i class="fa-solid fa-database me-2" style="color: var(--accent-green);"></i>Data Integration</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa-solid fa-graduation-cap me-2"></i> <strong>LMS:</strong> Student performance & grades</li>
                                    <li class="mb-2"><i class="fa-solid fa-chalkboard-user me-2"></i> <strong>Faculty Eval:</strong> Teaching assessment scores</li>
                                    <li class="mb-2"><i class="fa-solid fa-briefcase me-2"></i> <strong>HRIS:</strong> Training & improvement plans</li>
                                    <li class="mb-2"><i class="fa-solid fa-chart-simple me-2"></i> <strong>Real-time:</strong> AJAX-driven data fetching</li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3" style="background: var(--primary-xlight); border: none; border-radius: var(--radius);">
                            <i class="fa-solid fa-lightbulb me-2" style="color: var(--primary);"></i>
                            <strong>How it works:</strong> Data flows from source modules → QA Backend APIs → Aggregation & Analysis → Dashboard visualizations & Actionable tasks.
                        </div>
                    </div>
                </div>

                <!-- 2. NAVIGATION GUIDE -->
                <div class="card mb-4">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-compass me-2" style="color: var(--primary);"></i>2. Navigation Guide</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-3 h-100" style="border-color: var(--border-light);">
                                    <div class="stat-icon purple mb-2" style="width: 40px; height: 40px;"><i class="fa-solid fa-chart-line"></i></div>
                                    <h5 class="fw-600">Dashboard</h5>
                                    <p class="text-muted-qa small">Overall quality metrics, KPI summaries, and quick status overview of all active audits.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-3 h-100" style="border-color: var(--border-light);">
                                    <div class="stat-icon blue mb-2" style="width: 40px; height: 40px;"><i class="fa-solid fa-clipboard-list"></i></div>
                                    <h5 class="fw-600">Audits & Tasks</h5>
                                    <p class="text-muted-qa small">Create and manage audits, assign tasks, track completion, and map to accreditation standards.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 border rounded-3 h-100" style="border-color: var(--border-light);">
                                    <div class="stat-icon green mb-2" style="width: 40px; height: 40px;"><i class="fa-solid fa-chart-pie"></i></div>
                                    <h5 class="fw-600">Reports</h5>
                                    <p class="text-muted-qa small">Generate detailed quality reports for academic councils or accreditation bodies.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 p-3" style="background: var(--bg-main); border-radius: var(--radius);">
                            <i class="fa-solid fa-magnifying-glass-chart me-2"></i>
                            <strong>Quick Tip:</strong> Use the global search bar in the header to quickly find audits, tasks, or standards by keyword.
                        </div>
                    </div>
                </div>

                <!-- 3. MANAGING AUDITS -->
                <div class="card mb-4" id="auditsSection">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-clipboard-list me-2" style="color: var(--primary);"></i>3. Managing Audits</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-lg-7">
                                <h5 class="fw-600 mb-3">Creating a New Audit</h5>
                                <ol class="mb-4" style="padding-left: 1.2rem;">
                                    <li class="mb-2">Click the <span class="btn-primary-qa" style="padding: 2px 10px; font-size: 0.75rem;"><i class="fa-solid fa-plus"></i> New Audit</span> button at the top right</li>
                                    <li class="mb-2">Fill in the audit details:
                                        <ul class="mt-1" style="padding-left: 1.2rem;">
                                            <li><strong>Title:</strong> Clear, descriptive name (e.g., "Accreditation Spring 2026")</li>
                                            <li><strong>Audit Type:</strong> Internal / External / Accreditation</li>
                                            <li><strong>Status:</strong> Scheduled / In Progress / Completed / Cancelled</li>
                                            <li><strong>Dates:</strong> Scheduled and expected completion dates</li>
                                            <li><strong>Notes:</strong> Any additional context or instructions</li>
                                        </ul>
                                    </li>
                                    <li class="mb-2">Click <strong>Save Audit</strong> — a success toast notification will confirm creation</li>
                                </ol>

                                <h5 class="fw-600 mb-3">Searching & Filtering Audits</h5>
                                <ul>
                                    <li><strong>Search:</strong> Type any keyword in the search box to filter audit titles</li>
                                    <li><strong>Status Filter:</strong> Show only audits that are Scheduled, In Progress, Completed, or Cancelled</li>
                                    <li><strong>Type Filter:</strong> Quickly find Internal, External, or Accreditation audits</li>
                                </ul>
                            </div>
                            <div class="col-lg-5">
                                <div class="p-3 rounded-3" style="background: var(--primary-xlight); border-radius: var(--radius);">
                                    <i class="fa-solid fa-tag me-2" style="color: var(--primary);"></i>
                                    <strong>Audit Status Meanings</strong>
                                    <hr class="my-2" style="border-color: var(--border);">
                                    <div class="mb-2"><span class="badge-qa pending me-2">Scheduled</span> — Planned, not yet started</div>
                                    <div class="mb-2"><span class="badge-qa in-progress me-2">In Progress</span> — Active, work underway</div>
                                    <div class="mb-2"><span class="badge-qa completed me-2">Completed</span> — Finished successfully</div>
                                    <div><span class="badge-qa cancelled me-2">Cancelled</span> — Aborted or no longer relevant</div>
                                </div>

                                <div class="mt-3 p-3 rounded-3" style="background: var(--bg-main);">
                                    <i class="fa-solid fa-pen me-2"></i>
                                    <strong>Editing an Audit</strong>
                                    <p class="small mt-1 mb-0">Click the Edit button <i class="fa-regular fa-pen-to-square"></i> on any audit card to modify its details. Changes are saved instantly.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TASK MANAGEMENT -->
                <div class="card mb-4" id="tasksSection">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-tasks me-2" style="color: var(--primary);"></i>4. Task Management</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="fw-600 mb-3">Adding Tasks to an Audit</h5>
                                <ol>
                                    <li class="mb-2">Click on the audit header to expand and view its tasks section</li>
                                    <li class="mb-2">Click the <span class="btn-primary-qa" style="padding: 2px 10px; font-size: 0.75rem;"><i class="fa-solid fa-plus"></i> Add Task</span> button</li>
                                    <li class="mb-2">Fill in task details:
                                        <ul>
                                            <li><strong>Task Title:</strong> Clear description of what needs to be done</li>
                                            <li><strong>Standard (Optional):</strong> Link to an accreditation standard</li>
                                            <li><strong>Due Date:</strong> When the task should be completed</li>
                                            <li><strong>Status:</strong> Pending / In Progress / Completed</li>
                                            <li><strong>Remarks:</strong> Additional notes or findings</li>
                                        </ul>
                                    </li>
                                    <li>Save the task — it will appear in the audit's task list</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-600 mb-3">Working with Tasks</h5>
                                <ul>
                                    <li class="mb-2"><i class="fa-regular fa-square-check me-2" style="color: var(--accent-green);"></i> <strong>Mark Complete:</strong> Check the checkbox next to any task — status updates automatically</li>
                                    <li class="mb-2"><i class="fa-regular fa-pen-to-square me-2"></i> <strong>Edit:</strong> Click the edit icon to modify task details, status, or due date</li>
                                    <li class="mb-2"><i class="fa-regular fa-trash-can me-2"></i> <strong>Delete:</strong> Remove tasks that are no longer needed (cannot be undone)</li>
                                </ul>

                                <div class="mt-3 p-3" style="background: var(--primary-xlight); border-radius: var(--radius);">
                                    <i class="fa-solid fa-chart-simple me-2"></i>
                                    <strong>Progress Tracking:</strong>
                                    <p class="small mb-0 mt-1">Each audit shows a progress bar that automatically updates as tasks are completed. The bar fills proportionally to the number of completed tasks vs. total tasks.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Task Status Badge Reference -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="fw-600 mb-2">Task Status Badges</h6>
                                <div>
                                    <span class="badge-qa pending me-3">Pending</span>
                                    <span class="badge-qa in-progress me-3">In Progress</span>
                                    <span class="badge-qa completed">Completed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. DELETE OPERATIONS -->
                <div class="card mb-4">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-trash-can me-2" style="color: var(--accent-orange);"></i>5. Delete Operations</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="alert alert-warning" style="background: #fff3e3; border: none; border-radius: var(--radius);">
                            <i class="fa-solid fa-triangle-exclamation me-2" style="color: var(--accent-orange);"></i>
                            <strong>Caution:</strong> Deletion is permanent and cannot be undone. Always verify before deleting.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="fw-600">Deleting an Audit</h5>
                                <p>Click the red Delete button <i class="fa-regular fa-trash-can" style="color: var(--accent-orange);"></i> on any audit card. A confirmation modal will appear — confirm to permanently remove the audit and all its associated tasks.</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-600">Deleting a Task</h5>
                                <p>Click the delete icon next to any task in the expanded audit view. Confirm the deletion in the popup modal. The task will be removed and the audit's progress bar will update.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. NOTIFICATION SYSTEM -->
                <div class="card mb-4">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-bell me-2" style="color: var(--primary);"></i>6. Notification System (Toasts)</h3>
                    </div>
                    <div class="card-body-custom">
                        <p>The system uses Bootstrap toast notifications to provide real-time feedback for all user actions. Notifications appear in the top-right corner and auto-dismiss after a few seconds.</p>

                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <div class="p-2 border rounded" style="border-left: 3px solid var(--accent-green);">
                                    <i class="fa-solid fa-circle-check" style="color: var(--accent-green);"></i>
                                    <div class="small fw-600 mt-1">Success (Green)</div>
                                    <div class="small text-muted">"Survey saved successfully"</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <div class="p-2 border rounded" style="border-left: 3px solid var(--accent-orange);">
                                    <i class="fa-solid fa-circle-exclamation" style="color: var(--accent-orange);"></i>
                                    <div class="small fw-600 mt-1">Error (Orange)</div>
                                    <div class="small text-muted">"Failed to connect to LMS"</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <div class="p-2 border rounded" style="border-left: 3px solid var(--accent-blue);">
                                    <i class="fa-solid fa-circle-info" style="color: var(--accent-blue);"></i>
                                    <div class="small fw-600 mt-1">Info (Blue)</div>
                                    <div class="small text-muted">"Data syncing in progress"</div>
                                </div>
                            </div>
                        </div>

                        <p class="small text-muted mb-0"><i class="fa-regular fa-clock me-1"></i> Toasts appear automatically after successful saves, updates, deletions, or when errors occur during API calls.</p>
                    </div>
                </div>

                <!-- 7. INTEGRATION WITH OTHER MODULES -->
                <div class="card mb-4">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-plug me-2" style="color: var(--primary);"></i>7. System Integrations</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded-3 h-100" style="background: #dbeafe;">
                                    <i class="fa-solid fa-graduation-cap fa-xl mb-2" style="color: var(--accent-blue);"></i>
                                    <h6 class="fw-600">LMS Integration</h6>
                                    <p class="small">Automatically imports student grades, completion rates, and outcome data to feed KPI calculations.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded-3 h-100" style="background: #d1fae5;">
                                    <i class="fa-solid fa-chalkboard-user fa-xl mb-2" style="color: var(--accent-green);"></i>
                                    <h6 class="fw-600">Faculty Evaluation</h6>
                                    <p class="small">Pulls teaching assessment scores and peer review results into quality dashboards.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded-3 h-100" style="background: #fee2e2;">
                                    <i class="fa-solid fa-briefcase fa-xl mb-2" style="color: var(--accent-orange);"></i>
                                    <h6 class="fw-600">HRIS Integration</h6>
                                    <p class="small">Exports training needs and improvement plans to HR for staff development tracking.</p>
                                </div>
                            </div>
                        </div>

                        <div class="small text-muted mt-2">
                            <i class="fa-solid fa-code-branch me-1"></i> All integrations use secure API endpoints with server-side validation. Data is fetched in real-time via AJAX calls to ensure up-to-date information.
                        </div>
                    </div>
                </div>

                <!-- 8. FAQ SECTION -->
                <div class="card mb-4" id="faqSection">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-circle-question me-2" style="color: var(--primary);"></i>8. Frequently Asked Questions</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item mb-2 border rounded-3" style="border-color: var(--border-light);">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" style="background: transparent; font-weight: 600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How do I link a task to an accreditation standard?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted">When creating or editing a task, use the "Standard" dropdown menu. It automatically loads all available accreditation standards from the system. Select the relevant standard to establish the link for compliance tracking.</div>
                                </div>
                            </div>
                            <div class="accordion-item mb-2 border rounded-3" style="border-color: var(--border-light);">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" style="background: transparent; font-weight: 600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        What happens when I check a task as completed?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted">The task status changes to "Completed", the checkbox becomes checked, and the parent audit's progress bar updates immediately. If all tasks are completed, the audit progress bar reaches 100%.</div>
                                </div>
                            </div>
                            <div class="accordion-item mb-2 border rounded-3" style="border-color: var(--border-light);">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" style="background: transparent; font-weight: 600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Can I recover a deleted audit or task?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted">No, deletions are permanent. The system shows a confirmation modal before any deletion to prevent accidental removal. Always double-check before confirming a delete action.</div>
                                </div>
                            </div>
                            <div class="accordion-item mb-2 border rounded-3" style="border-color: var(--border-light);">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" style="background: transparent; font-weight: 600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        How often is data synced from LMS and other modules?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted">Data is fetched in real-time via API calls whenever you load a page or perform a refresh action. For KPIs and reports, you can manually trigger a data sync using the refresh button on the respective dashboards.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 9. TECHNICAL SUPPORT -->
                <div class="card mb-4">
                    <div class="card-header-custom">
                        <h3 class="card-title"><i class="fa-solid fa-headset me-2" style="color: var(--primary);"></i>9. Technical Support</h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <p class="mb-2">Need assistance? Our support team is available to help with any issues or questions about the QA Management System.</p>
                                <ul class="list-unstyled">
                                    <li class="mb-1"><i class="fa-regular fa-envelope me-2" style="color: var(--primary);"></i> <strong>Email:</strong> qa-support@college.edu</li>
                                    <li class="mb-1"><i class="fa-solid fa-phone me-2" style="color: var(--primary);"></i> <strong>Phone:</strong> (555) 123-4567 (ext. 8900)</li>
                                    <li><i class="fa-regular fa-clock me-2" style="color: var(--primary);"></i> <strong>Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</li>
                                </ul>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="stat-icon purple mx-auto mb-2" style="width: 50px; height: 50px; font-size: 24px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div class="small text-muted">Documentation v1.0</div>
                                <div class="small text-muted">Last updated: May 2026</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back to Top Button -->
                <div class="text-center mt-4">
                    <a href="#" class="btn-outline-qa" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                        <i class="fa-solid fa-arrow-up me-2"></i> Back to Top
                    </a>
                </div>

            </div>
        </div><!-- /.qa-content -->
    </div><!-- /.qa-wrapper -->


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>


</body>

</html>