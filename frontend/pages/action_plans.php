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
        .status-Open { background: #ffe5e5; color: #dc3545; }
        .status-In-Progress { background: #fff3e0; color: #ff9800; }
        .status-Resolved { background: #e3f2fd; color: #2196f3; }
        .status-Closed { background: #e8f5e9; color: #4caf50; }
    </style>
</head>

<body>

    <div class="qa-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">
            <?php include '../partials/header.php'; ?>

            <div class="qa-page">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0" style="font-size:1.5rem;font-weight:700;letter-spacing:-.4px;">
                            Performance Improvement & Action Plans
                        </h2>
                        <p class="text-muted-qa mb-0" style="font-size:.875rem; margin-top:4px;">
                            Manage your quality assurance action plans to ensure continuous improvement across your institution.
                        </p>
                    </div>
                    <div>
                        <button class="btn-primary-qa" data-bs-toggle="modal" data-bs-target="#actionPlanModal" onclick="resetActionPlanForm()">
                            <i class="fa-solid fa-plus"></i> Add Action Plan
                        </button>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div class="d-flex gap-2">
                        <div class="header-search" style="width: 300px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search action plans by title or description..." class="form-control-qa" style="padding-left: 34px;">
                        </div>
                        <select id="statusFilter" class="form-control-qa" style="width: 150px;">
                            <option value="all">All Status</option>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Action Plans List -->
                <div id="actionPlansList"></div>

            </div><!-- /.qa-page -->
        </div><!-- /.qa-content -->
    </div><!-- /.qa-wrapper -->

    <!-- Action Plan MODAL -->
    <div class="modal fade" id="actionPlanModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom-color: var(--border-light);">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="fa-solid fa-list-check me-2" style="color: var(--primary);"></i>
                        <span id="actionPlanModalTitle">Add Action Plan</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="actionPlanForm">
                        <input type="hidden" id="plan_id" name="plan_id">
                        
                        <div class="mb-3">
                            <label class="form-label-qa">Audit ID <span class="text-danger">*</span></label>
                            <select class="form-control-qa" id="audit_id" name="audit_id" required>
                                <option value="">Select Audit</option>
                                <?php
                                // You can populate this from your audits table
                                ?>
                                <option value="1">Audit 1 - CHED</option>
                                <option value="2">Audit 2 - ISO</option>
                                <option value="3">Audit 3 - Institutional</option>
                            </select>
                            <div class="form-error-msg" id="err-audit_id"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-qa">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-qa" id="title" name="title" required maxlength="150">
                            <div class="form-error-msg" id="err-title"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-qa">Description</label>
                            <textarea class="form-control-qa" id="description" name="description" rows="3"></textarea>
                            <div class="form-error-msg" id="err-description"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-qa">Root Cause <span class="text-danger">*</span></label>
                            <textarea class="form-control-qa" id="root_cause" name="root_cause" rows="3" required></textarea>
                            <div class="form-error-msg" id="err-root_cause"></div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Target Date</label>
                                <input type="date" class="form-control-qa" id="target_date" name="target_date">
                                <div class="form-error-msg" id="err-target_date"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Status</label>
                                <select class="form-control-qa" id="status" name="status">
                                    <option value="Open">Open</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-light);">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="saveActionPlanBtn">Save Action Plan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this action plan?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="confirmDeleteBtn" style="background: var(--accent-orange);">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        let deleteId = null;
        const ACTION_PLANS_API = '../../backend/api/action_plans_api.php';

        $(document).ready(function() {
            loadActionPlans();

            // Search and filter
            $('#searchInput').on('keyup', function() {
                loadActionPlans();
            });

            $('#statusFilter').on('change', function() {
                loadActionPlans();
            });

            $('#saveActionPlanBtn').click(function() {
                saveActionPlan();
            });

            $('#confirmDeleteBtn').click(function() {
                if (deleteId) {
                    deleteActionPlan(deleteId);
                }
            });
        });

        function loadActionPlans() {
            const search = $('#searchInput').val();
            const status = $('#statusFilter').val();

            $.ajax({
                url: ACTION_PLANS_API + '?action=list',
                type: 'GET',
                data: {
                    search: search,
                    status: status === 'all' ? '' : status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderActionPlans(response.data || []);
                    } else {
                        $('#actionPlansList').html('<div class="alert alert-warning">Failed to load action plans</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#actionPlansList').html('<div class="alert alert-danger">Error loading action plans</div>');
                }
            });
        }

        function renderActionPlans(plans) {
            if (!plans || !plans.length) {
                $('#actionPlansList').html('<div class="text-center py-5 text-muted">No action plans found</div>');
                return;
            }

            let html = '<div class="row">';
            plans.forEach(plan => {
                const statusClass = `status-${plan.status.replace(/ /g, '-')}`;
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card action-plan-card" style="border-radius: var(--radius);">
                            <div class="card-body-custom p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge-qa new" style="background: var(--primary-light); color: var(--primary);">
                                        Audit #${plan.audit_id}
                                    </span>
                                    <span class="status-badge ${statusClass}">${plan.status}</span>
                                </div>
                                <h6 class="fw-600 mb-2" style="font-size: 1rem;">${escapeHtml(plan.title)}</h6>
                                <p class="text-muted small mb-2">${plan.description ? escapeHtml(plan.description.substring(0, 100)) : 'No description'}</p>
                                <div class="mb-2">
                                    <small class="text-muted"><strong>Root Cause:</strong> ${escapeHtml(plan.root_cause.substring(0, 80))}${plan.root_cause.length > 80 ? '...' : ''}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">Target: ${plan.target_date || 'Not set'}</small>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="editActionPlan(${plan.plan_id})" style="padding: 4px 8px;">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${plan.plan_id})" style="padding: 4px 8px;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#actionPlansList').html(html);
        }

        function resetActionPlanForm() {
            $('#actionPlanForm')[0].reset();
            $('#plan_id').val('');
            $('#actionPlanModalTitle').text('Add Action Plan');
            clearFormErrors('#actionPlanForm');
        }

        function clearFormErrors(form) {
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.form-error-msg').text('');
        }

        function saveActionPlan() {
            const planId = $('#plan_id').val();
            const formData = {
                plan_id: planId || undefined,
                audit_id: $('#audit_id').val(),
                title: $('#title').val(),
                description: $('#description').val(),
                root_cause: $('#root_cause').val(),
                target_date: $('#target_date').val(),
                status: $('#status').val(),
                action: planId ? 'update' : 'create'
            };

            // Validation
            if (!formData.audit_id) {
                toast.error('Please select an audit');
                return;
            }
            if (!formData.title) {
                toast.error('Title is required');
                return;
            }
            if (!formData.root_cause) {
                toast.error('Root cause is required');
                return;
            }

            const btn = $('#saveActionPlanBtn');
            btnLoading(btn[0], 'Saving...');

            $.ajax({
                url: ACTION_PLANS_API,
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btnReset(btn[0]);
                    if (response.success) {
                        $('#actionPlanModal').modal('hide');
                        toast.success(response.message);
                        loadActionPlans();
                    } else {
                        if (response.errors) {
                            applyServerErrors('#actionPlanForm', response.errors);
                        }
                        toast.error(response.message || 'Failed to save action plan');
                    }
                },
                error: function(xhr, response) {
                    btnReset(btn[0]);
                    console.error('Error:', xhr.responseText);
                    toast.error('An error occurred while saving');
                }
            });
        }

        function editActionPlan(id) {
            $.ajax({
                url: ACTION_PLANS_API + '?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const plan = response.data;
                        $('#plan_id').val(plan.plan_id);
                        $('#audit_id').val(plan.audit_id);
                        $('#title').val(plan.title);
                        $('#description').val(plan.description || '');
                        $('#root_cause').val(plan.root_cause);
                        $('#target_date').val(plan.target_date || '');
                        $('#status').val(plan.status);
                        $('#actionPlanModalTitle').text('Edit Action Plan');
                        $('#actionPlanModal').modal('show');
                        clearFormErrors('#actionPlanForm');
                    } else {
                        toast.error('Failed to load action plan data');
                    }
                },
                error: function() {
                    toast.error('Error loading action plan');
                }
            });
        }

        function confirmDelete(id) {
            deleteId = id;
            $('#deleteModal').modal('show');
        }

        function deleteActionPlan(id) {
            const btn = $('#confirmDeleteBtn');
            btnLoading(btn[0], 'Deleting...');

            $.ajax({
                url: ACTION_PLANS_API,
                type: 'POST',
                data: JSON.stringify({
                    action: 'delete',
                    id: id
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btnReset(btn[0]);
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toast.success(response.message);
                        loadActionPlans();
                        deleteId = null;
                    } else {
                        toast.error(response.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('Delete failed');
                }
            });
        }

        function applyServerErrors(form, errors) {
            for (const [field, msg] of Object.entries(errors)) {
                const input = $(form).find(`[name="${field}"]`);
                if (input.length) {
                    input.addClass('is-invalid');
                    const errEl = $(form).find(`#err-${field}`);
                    if (errEl.length) {
                        errEl.text(msg);
                    }
                }
            }
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

    </script>

</body>

</html>