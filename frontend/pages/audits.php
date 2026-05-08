<?php

/**
 * Audits Management Page
 * frontend/pages/audits.php
 *
 * Page for managing audits, including creating, viewing, and updating audit records.
 */

session_start();

// Auth guard
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Audits & Tasks';
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


            <div class="qa-page">
                <!-- Header Actions -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-600 mb-1" style="font-size: 1.5rem;">Audits & Tasks</h2>
                        <p class="text-muted-qa" style="font-size: 0.85rem;">Manage quality audits and their associated tasks</p>
                    </div>
                    <button class="btn-primary-qa" id="createAuditBtn">
                        <i class="fa-solid fa-plus"></i> New Audit
                    </button>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body-custom">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-qa">Search Audits</label>
                                <div class="header-search" style="width: 100%;">
                                    <input type="text" id="searchAudit" placeholder="Search by title..." class="form-control-qa" style="padding-left: 34px;">
                                    <span class="search-icon"><i class="fa-solid fa-search"></i></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-qa">Filter by Status</label>
                                <select id="filterAuditStatus" class="form-control-qa">
                                    <option value="">All Status</option>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-qa">Filter by Type</label>
                                <select id="filterAuditType" class="form-control-qa">
                                    <option value="">All Types</option>
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                    <option value="Accreditation">Accreditation</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audits List with Nested Tasks -->
                <div id="auditsContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading audits...</p>
                    </div>
                </div>
            </div>

            <!-- Audit Modal -->
            <div class="modal fade" id="auditModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                            <h5 class="modal-title fw-600" id="auditModalTitle">Create New Audit</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="auditForm">
                            <div class="modal-body" style="padding: 24px;">
                                <input type="hidden" name="audit_id" id="audit_id">
                                <div class="mb-3">
                                    <label class="form-label-qa">Title *</label>
                                    <input type="text" name="title" id="title" class="form-control-qa" required>
                                    <div class="form-error-msg" id="err-title"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Audit Type *</label>
                                        <select name="audit_type" id="audit_type" class="form-control-qa" required>
                                            <option value="">Select Type</option>
                                            <option value="Internal">Internal</option>
                                            <option value="External">External</option>
                                            <option value="Accreditation">Accreditation</option>
                                        </select>
                                        <div class="form-error-msg" id="err-audit_type"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Status</label>
                                        <select name="status" id="status" class="form-control-qa">
                                            <option value="Scheduled">Scheduled</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Scheduled Date</label>
                                        <input type="date" name="scheduled_date" id="scheduled_date" class="form-control-qa">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Completion Date</label>
                                        <input type="date" name="completion_date" id="completion_date" class="form-control-qa">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-qa">Notes</label>
                                    <textarea name="notes" id="notes" rows="3" class="form-control-qa"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px;">
                                <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-primary-qa">Save Audit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Task Modal -->
            <div class="modal fade" id="taskModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                            <h5 class="modal-title fw-600" id="taskModalTitle">Add New Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="taskForm">
                            <div class="modal-body" style="padding: 24px;">
                                <input type="hidden" name="task_id" id="task_id">
                                <input type="hidden" name="audit_id" id="task_audit_id">
                                <div class="mb-3">
                                    <label class="form-label-qa">Task Title *</label>
                                    <input type="text" name="title" id="task_title" class="form-control-qa" required>
                                    <div class="form-error-msg" id="err-task_title"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Standard</label>
                                        <select name="standard_id" id="standard_id" class="form-control-qa">
                                            <option value="">Select Standard</option>
                                        </select>
                                        <div class="form-error-msg" id="err-standard_id"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Due Date</label>
                                        <input type="date" name="due_date" id="due_date" class="form-control-qa">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Status</label>
                                        <select name="status" id="task_status" class="form-control-qa">
                                            <option value="Pending">Pending</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-qa">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control-qa"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px;">
                                <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-primary-qa">Save Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this item?</p>
                            <p class="text-muted" style="font-size: 0.85rem;">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-primary-qa" id="confirmDeleteBtn" style="background: var(--accent-orange);">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Scripts -->
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../assets/js/app.js"></script>

            <script>
                let currentDeleteType = null;
                let currentDeleteId = null;
                let currentAuditIdForTask = null;
                let allAudits = [];
                let allTasks = [];

                $(document).ready(function() {
                    loadStandards(); // Add this line
                    loadAllData();

                    // Setup event listeners
                    $('#createAuditBtn').click(() => resetAndShowAuditModal());
                    $('#searchAudit').on('keyup', filterAudits);
                    $('#filterAuditStatus').on('change', filterAudits);
                    $('#filterAuditType').on('change', filterAudits);

                    // Form submissions
                    $('#auditForm').submit(handleAuditSubmit);
                    $('#taskForm').submit(handleTaskSubmit);
                    $('#confirmDeleteBtn').click(handleDelete);
                });

                function loadStandards() {
                    $.ajax({
                        url: '../../backend/api/standards_api.php',
                        type: 'GET',
                        data: {
                            action: 'list'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data) {
                                const standardsSelect = $('#standard_id');
                                standardsSelect.empty();
                                standardsSelect.append('<option value="">Select Standard</option>');

                                response.data.forEach(standard => {
                                    // Display title and body/type for better context
                                    const displayText = `${escapeHtml(standard.title)} (${escapeHtml(standard.body)})`;
                                    standardsSelect.append(`<option value="${standard.standard_id}">${displayText}</option>`);
                                });
                            } else {
                                console.error('Failed to load standards:', response.message);
                                // Still show empty dropdown with error message option
                                const standardsSelect = $('#standard_id');
                                standardsSelect.empty();
                                standardsSelect.append('<option value="">Error loading standards</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Failed to load standards:', error);
                            toast.error('Failed to load standards list');
                            const standardsSelect = $('#standard_id');
                            standardsSelect.empty();
                            standardsSelect.append('<option value="">Select Standard (load error)</option>');
                        }
                    });
                }

                function loadAllData() {
                    // Load audits
                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'GET',
                        data: {
                            action: 'get_all_audits'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                allAudits = response.data;
                                // After loading audits, load tasks
                                loadTasks();
                            } else {
                                toast.error(response.message);
                            }
                        },
                        error: function() {
                            toast.error('Failed to load audits');
                        }
                    });
                }

                function loadTasks() {
                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'GET',
                        data: {
                            action: 'get_all_tasks'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                allTasks = response.data;
                                renderAuditsWithTasks();
                            } else {
                                toast.error(response.message);
                            }
                        },
                        error: function() {
                            toast.error('Failed to load tasks');
                        }
                    });
                }

                function renderAuditsWithTasks() {
                    const container = $('#auditsContainer');
                    container.empty();

                    if (allAudits.length === 0) {
                        container.html(`
            <div class="card">
                <div class="card-body-custom text-center py-5">
                    <i class="fa-solid fa-clipboard-list" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <p class="text-muted">No audits found. Click "New Audit" to create one.</p>
                </div>
            </div>
        `);
                        return;
                    }

                    // Filter audits
                    let filteredAudits = [...allAudits];
                    const searchTerm = $('#searchAudit').val().toLowerCase();
                    const statusFilter = $('#filterAuditStatus').val();
                    const typeFilter = $('#filterAuditType').val();

                    if (searchTerm) {
                        filteredAudits = filteredAudits.filter(a => a.title.toLowerCase().includes(searchTerm));
                    }
                    if (statusFilter) {
                        filteredAudits = filteredAudits.filter(a => a.status === statusFilter);
                    }
                    if (typeFilter) {
                        filteredAudits = filteredAudits.filter(a => a.audit_type === typeFilter);
                    }

                    // Render each audit card
                    filteredAudits.forEach(audit => {
                        const auditTasks = allTasks.filter(task => task.audit_id == audit.audit_id);
                        const completedTasks = auditTasks.filter(task => task.status === 'Completed').length;
                        const totalTasks = auditTasks.length;
                        const taskProgress = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;
                        const statusClass = getStatusClass(audit.status);

                        const auditCard = `
            <div class="card mb-4 audit-card" data-audit-id="${audit.audit_id}">
                <div class="card-header-custom" style="cursor: pointer;" onclick="toggleAudit(${audit.audit_id})">
                    <div style="flex: 1;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h3 class="card-title mb-1">
                                    <i class="fa-solid fa-chevron-down me-2" id="chevron-${audit.audit_id}" style="transition: transform 0.2s;"></i>
                                    ${escapeHtml(audit.title)}
                                </h3>
                                <div class="mt-2">
                                    <span class="badge-qa ${audit.audit_type.toLowerCase()}">${audit.audit_type}</span>
                                    <span class="badge-qa ${statusClass} ms-2">${audit.status}</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn-outline-qa btn-sm" onclick="event.stopPropagation(); editAudit(${audit.audit_id})">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <button class="btn-outline-qa btn-sm" onclick="event.stopPropagation(); deleteAudit(${audit.audit_id})" style="background: #fee2e2;">
                                    <i class="fa-regular fa-trash-can"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fa-regular fa-calendar me-1"></i> 
                                    Scheduled: ${audit.scheduled_date || 'Not set'}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fa-regular fa-flag-checkered me-1"></i> 
                                    Completion: ${audit.completion_date || 'Not set'}
                                </small>
                            </div>
                        </div>
                        ${audit.notes ? `<div class="mt-2"><small class="text-muted"><i class="fa-regular fa-note-sticky me-1"></i> ${escapeHtml(audit.notes)}</small></div>` : ''}
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Task Progress</small>
                                <small class="text-muted">${completedTasks}/${totalTasks} tasks completed (${Math.round(taskProgress)}%)</small>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill ${statusClass}" style="width: ${taskProgress}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tasks-${audit.audit_id}" class="collapse">
                    <div class="card-body-custom" style="border-top: 1px solid var(--border-light);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-600" style="font-size: 1rem;">
                                <i class="fa-solid fa-tasks me-2"></i>Tasks
                            </h4>
                            <button class="btn-primary-qa btn-sm" onclick="showAddTaskModal(${audit.audit_id})">
                                <i class="fa-solid fa-plus"></i> Add Task
                            </button>
                        </div>
                        <div id="tasks-list-${audit.audit_id}">
                            ${renderTasksList(auditTasks, audit.audit_id)}
                        </div>
                    </div>
                </div>
            </div>
        `;
                        container.append(auditCard);
                    });
                }

                function renderTasksList(tasks, auditId) {
                    if (tasks.length === 0) {
                        return `
                            <div class="text-center py-4 text-muted">
                                <i class="fa-regular fa-circle-check" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                <p>No tasks yet. Click "Add Task" to create one.</p>
                            </div>
                        `;
                    }

                    let tasksHtml = '<div class="list-group">';
                    tasks.forEach(task => {
                        const isCompleted = task.status === 'Completed';
                        const statusClass = getTaskStatusClass(task.status);

                        // Get standard name if available
                        const standardName = task.standard_title || (task.standard_id ? `Standard #${task.standard_id}` : 'No standard');

                        tasksHtml += `
                        <div class="list-group-item" style="border: 1px solid var(--border-light); border-radius: var(--radius); margin-bottom: 10px; padding: 15px;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-start gap-3 flex-grow-1">
                                    <input type="checkbox" 
                                        class="task-checkbox" 
                                        data-task-id="${task.task_id}" 
                                        ${isCompleted ? 'checked' : ''}
                                        onchange="toggleTaskStatus(${task.task_id}, this.checked)"
                                        style="margin-top: 3px; width: 18px; height: 18px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="fw-600 mb-0" style="font-size: 0.95rem; ${isCompleted ? 'text-decoration: line-through; color: var(--text-muted);' : ''}">
                                                ${escapeHtml(task.title)}
                                            </h5>
                                            <div class="d-flex gap-2 ms-3">
                                                <button class="btn-outline-qa btn-sm" onclick="editTask(${task.task_id})" style="padding: 4px 8px;">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                                <button class="btn-outline-qa btn-sm" onclick="deleteTask(${task.task_id})" style="padding: 4px 8px; background: #fee2e2;">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fa-regular fa-bookmark me-1"></i> 
                                                    Standard: ${escapeHtml(standardName)}
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fa-regular fa-calendar me-1"></i> 
                                                    Due: ${task.due_date || 'Not set'}
                                                </small>
                                            </div>
                                        </div>
                                        ${task.remarks ? `<div class="mt-2"><small class="text-muted"><i class="fa-regular fa-comment me-1"></i> ${escapeHtml(task.remarks)}</small></div>` : ''}
                                        <div class="mt-2">
                                            <span class="badge-qa ${statusClass}">${task.status}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    });
                    tasksHtml += '</div>';
                    return tasksHtml;
                }

                function toggleTaskStatus(taskId, isChecked) {
                    const newStatus = isChecked ? 'Completed' : 'Pending';

                    // Optimistically update UI
                    const task = allTasks.find(t => t.task_id == taskId);
                    if (task) {
                        task.status = newStatus;
                    }

                    // Update via API
                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'POST',
                        data: {
                            action: 'update_task',
                            task_id: taskId,
                            status: newStatus,
                            title: task.title,
                            audit_id: task.audit_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(`Task marked as ${newStatus}`);
                                // Reload data to refresh progress bars
                                loadAllData();
                            } else {
                                toast.error(response.message);
                                // Revert optimistic update
                                task.status = newStatus === 'Completed' ? 'Pending' : 'Completed';
                                loadAllData();
                            }
                        },
                        error: function() {
                            toast.error('Failed to update task status');
                            // Revert optimistic update
                            task.status = newStatus === 'Completed' ? 'Pending' : 'Completed';
                            loadAllData();
                        }
                    });
                }

                function toggleAudit(auditId) {
                    const tasksDiv = $(`#tasks-${auditId}`);
                    const chevron = $(`#chevron-${auditId}`);

                    tasksDiv.collapse('toggle');

                    tasksDiv.on('shown.bs.collapse', function() {
                        chevron.css('transform', 'rotate(90deg)');
                    });

                    tasksDiv.on('hidden.bs.collapse', function() {
                        chevron.css('transform', 'rotate(0deg)');
                    });
                }

                function showAddTaskModal(auditId) {
                    currentAuditIdForTask = auditId;
                    $('#taskForm')[0].reset();
                    $('#task_id').val('');
                    $('#task_audit_id').val(auditId);
                    $('#task_status').val('Pending'); // Set default status
                    $('#taskModalTitle').text('Add New Task');
                    clearFormErrors('#taskForm');
                    $('#taskModal').modal('show');
                }

                function resetAndShowAuditModal() {
                    $('#auditForm')[0].reset();
                    $('#audit_id').val('');
                    $('#auditModalTitle').text('Create New Audit');
                    clearFormErrors('#auditForm');
                    $('#auditModal').modal('show');
                }

                function editAudit(id) {
                    const audit = allAudits.find(a => a.audit_id == id);
                    if (audit) {
                        $('#audit_id').val(audit.audit_id);
                        $('#title').val(audit.title);
                        $('#audit_type').val(audit.audit_type);
                        $('#status').val(audit.status);
                        $('#scheduled_date').val(audit.scheduled_date);
                        $('#completion_date').val(audit.completion_date);
                        $('#notes').val(audit.notes);
                        $('#auditModalTitle').text('Edit Audit');
                        clearFormErrors('#auditForm');
                        $('#auditModal').modal('show');
                    }
                }

                function editTask(id) {
                    const task = allTasks.find(t => t.task_id == id);
                    if (task) {
                        $('#task_id').val(task.task_id);
                        $('#task_title').val(task.title);
                        $('#task_audit_id').val(task.audit_id);
                        $('#standard_id').val(task.standard_id);
                        $('#due_date').val(task.due_date);
                        $('#task_status').val(task.status); // Add this line for status
                        $('#remarks').val(task.remarks);
                        $('#taskModalTitle').text('Edit Task');
                        clearFormErrors('#taskForm');
                        $('#taskModal').modal('show');
                    }
                }

                function handleAuditSubmit(e) {
                    e.preventDefault();

                    const formData = $('#auditForm').serialize();
                    const auditId = $('#audit_id').val();
                    const action = auditId ? 'update_audit' : 'create_audit';

                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'POST',
                        data: formData + '&action=' + action,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(response.message);
                                $('#auditModal').modal('hide');
                                loadAllData();
                            } else {
                                if (response.errors) {
                                    applyServerErrors('#auditForm', response.errors);
                                }
                                toast.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            const response = JSON.parse(xhr.responseText);
                            toast.error(response.message || 'An error occurred');
                        }
                    });
                }

                function handleTaskSubmit(e) {
                    e.preventDefault();

                    const taskId = $('#task_id').val();
                    const action = taskId ? 'update_task' : 'create_task';

                    // Collect form data including status
                    const formData = {
                        action: action,
                        task_id: $('#task_id').val(),
                        audit_id: $('#task_audit_id').val(),
                        title: $('#task_title').val(),
                        standard_id: $('#standard_id').val(),
                        due_date: $('#due_date').val(),
                        status: $('#task_status').val(),
                        remarks: $('#remarks').val()
                    };

                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(response.message);
                                $('#taskModal').modal('hide');
                                loadAllData();
                            } else {
                                if (response.errors) {
                                    applyServerErrors('#taskForm', response.errors);
                                }
                                toast.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                toast.error(response.message || 'An error occurred');
                            } catch (e) {
                                toast.error('An error occurred');
                            }
                        }
                    });
                }

                function deleteAudit(id) {
                    currentDeleteType = 'audit';
                    currentDeleteId = id;
                    $('#deleteModal').modal('show');
                }

                function deleteTask(id) {
                    currentDeleteType = 'task';
                    currentDeleteId = id;
                    $('#deleteModal').modal('show');
                }

                function handleDelete() {
                    const action = currentDeleteType === 'audit' ? 'delete_audit' : 'delete_task';
                    const id = currentDeleteId;

                    $.ajax({
                        url: '../../backend/api/audits_api.php',
                        type: 'POST',
                        data: {
                            action: action,
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(response.message);
                                $('#deleteModal').modal('hide');
                                loadAllData();
                            } else {
                                toast.error(response.message);
                            }
                        },
                        error: function() {
                            toast.error('Failed to delete item');
                        }
                    });
                }

                function filterAudits() {
                    renderAuditsWithTasks();
                }

                function getStatusClass(status) {
                    const classes = {
                        'Scheduled': 'pending',
                        'In Progress': 'in-progress',
                        'Completed': 'completed',
                        'Cancelled': 'cancelled'
                    };
                    return classes[status] || 'pending';
                }

                function getTaskStatusClass(status) {
                    const classes = {
                        'Pending': 'pending',
                        'In Progress': 'in-progress',
                        'Completed': 'completed'
                    };
                    return classes[status] || 'pending';
                }

                function escapeHtml(str) {
                    if (!str) return '';
                    return str.replace(/[&<>]/g, function(m) {
                        if (m === '&') return '&amp;';
                        if (m === '<') return '&lt;';
                        if (m === '>') return '&gt;';
                        return m;
                    });
                }

                function clearFormErrors(formId) {
                    $(formId + ' .is-invalid').removeClass('is-invalid');
                    $(formId + ' .form-error-msg').removeClass('show').text('');
                }

                function applyServerErrors(formId, errors) {
                    for (const [field, message] of Object.entries(errors)) {
                        const input = $(formId + ` [name="${field}"]`);
                        input.addClass('is-invalid');
                        $(formId + ` #err-${field}`).text(message).addClass('show');
                    }
                }
            </script>

            <style>
                .badge-qa.internal {
                    background: #dbeafe;
                    color: #1e40af;
                }

                .badge-qa.external {
                    background: #fed7aa;
                    color: #92400e;
                }

                .badge-qa.accreditation {
                    background: #ddd6fe;
                    color: #5b21b6;
                }

                .btn-sm {
                    font-size: 0.75rem;
                    padding: 5px 12px;
                }

                .audit-card {
                    transition: box-shadow var(--transition);
                }

                .audit-card:hover {
                    box-shadow: var(--shadow);
                }

                .list-group-item {
                    transition: background var(--transition);
                }

                .list-group-item:hover {
                    background: var(--primary-xlight);
                }

                .task-checkbox {
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .task-checkbox:hover {
                    transform: scale(1.1);
                }

                .collapse {
                    transition: all 0.3s ease;
                }

                .progress-bar-wrap {
                    background-color: #e5e7eb;
                    border-radius: 9999px;
                    height: 8px;
                    overflow: hidden;
                }

                .progress-bar-fill {
                    background-color: #3b82f6;
                    border-radius: 9999px;
                    height: 100%;
                    transition: width 0.3s ease;
                }

                .progress-bar-fill.completed {
                    background-color: #10b981;
                }

                .progress-bar-fill.in-progress {
                    background-color: #f59e0b;
                }
            </style>

</body>

</html>