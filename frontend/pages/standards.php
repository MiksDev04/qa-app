<?php
session_start();

// Check authentication
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Standards & Policies';
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
        /* Additional page-specific styles */
        .standard-card {
            transition: all 0.2s ease;
            border-left: 3px solid var(--primary);
        }

        .standard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .policy-item {
            transition: all 0.2s ease;
            border-left: 2px solid var(--border);
        }

        .policy-item:hover {
            background: var(--primary-xlight);
            border-left-color: var(--primary);
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-pane {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filter-bar {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="qa-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">
            <?php include '../partials/header.php'; ?>

            <div class="qa-page">
                <!-- Tabs -->

                <ul class="nav nav-tabs mb-4" id="mainTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="standards-tab" data-bs-toggle="tab" data-bs-target="#standards" type="button" role="tab">
                            <i class="fa-solid fa-book-bookmark me-2"></i>Standards
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="policies-tab" data-bs-toggle="tab" data-bs-target="#policies" type="button" role="tab">
                            <i class="fa-solid fa-file-signature me-2"></i>Policies
                        </button>
                    </li>
                </ul>
                <div class="mb-2">
                    <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
                        Standards & Policies
                    </h2>
                    <p class="text-muted-qa mb-0" style="font-size:.83rem; margin-top:2px;">
                        Manage your quality assurance standards and policies in one place. Create, edit, and organize all your QA guidelines to ensure compliance and continuous improvement across your institution.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <button class="btn-primary-qa" id="addStandardBtn" data-bs-toggle="modal" data-bs-target="#standardModal" onclick="resetStandardForm()">
                            <i class="fa-solid fa-plus"></i> Add Standard
                        </button>
                        <button class="btn-primary-qa d-none" id="addPolicyBtn" data-bs-toggle="modal" data-bs-target="#policyModal" onclick="resetPolicyForm()">
                            <i class="fa-solid fa-plus"></i> Add Policy
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="header-search" style="width: 250px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search standards or policies..." class="form-control-qa" style="padding-left: 34px;">
                        </div>
                        <select id="statusFilter" class="form-control-qa" style="width: 130px;">
                            <option value="all">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>



                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Standards Tab -->
                    <div class="tab-pane fade show active" id="standards" role="tabpanel">
                        <div id="standardsList"></div>
                    </div>

                    <!-- Policies Tab -->
                    <div class="tab-pane fade" id="policies" role="tabpanel">
                        <div id="policiesList"></div>
                    </div>
                </div>

            </div><!-- /.qa-page -->
        </div><!-- /.qa-content -->
    </div><!-- /.qa-wrapper -->

    <!-- STANDARD MODAL -->
    <div class="modal fade" id="standardModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom-color: var(--border-light);">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="fa-solid fa-book-bookmark me-2" style="color: var(--primary);"></i>
                        <span id="standardModalTitle">Add Standard</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="standardForm">
                        <input type="hidden" id="standard_id" name="standard_id">
                        <div class="mb-3">
                            <label class="form-label-qa">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-qa" id="title" name="title" required>
                            <div class="form-error-msg" id="err-title"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Body/Type <span class="text-danger">*</span></label>
                            <select class="form-control-qa" id="body" name="body">
                                <option value="CHED">CHED</option>
                                <option value="ISO">ISO</option>
                                <option value="Institutional">Institutional</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="form-error-msg" id="err-body"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Description</label>
                            <textarea class="form-control-qa" id="description" name="description" rows="3"></textarea>
                            <div class="form-error-msg" id="err-description"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Version</label>
                                <input type="text" class="form-control-qa" id="version" name="version" placeholder="e.g., v1.0">
                                <div class="form-error-msg" id="err-version"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Effective Date</label>
                                <input type="date" class="form-control-qa" id="effective_date" name="effective_date">
                                <div class="form-error-msg" id="err-effective_date"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Status</label>
                            <select class="form-control-qa" id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-light);">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="saveStandardBtn">Save Standard</button>
                </div>
            </div>
        </div>
    </div>

    <!-- POLICY MODAL -->
    <div class="modal fade" id="policyModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom-color: var(--border-light);">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="fa-solid fa-file-signature me-2" style="color: var(--primary);"></i>
                        <span id="policyModalTitle">Add Policy</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="policyForm">
                        <input type="hidden" id="policy_id" name="policy_id">
                        <div class="mb-3">
                            <label class="form-label-qa">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-qa" id="policy_title" name="title" required>
                            <div class="form-error-msg" id="err-policy_title"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Associated Standard</label>
                            <select class="form-control-qa" id="standard_id_select" name="standard_id">
                                <option value="">-- None / General Policy --</option>
                            </select>
                            <div class="form-error-msg" id="err-standard_id"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control-qa" id="content" name="content" rows="5" placeholder="Policy content..."></textarea>
                            <div class="form-error-msg" id="err-content"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Document URL (Optional)</label>
                            <input type="url" class="form-control-qa" id="document_url" name="document_url" placeholder="https://...">
                            <div class="form-error-msg" id="err-document_url"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Created Date</label>
                                <input type="date" class="form-control-qa" id="created_date" name="created_date">
                                <div class="form-error-msg" id="err-created_date"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Status</label>
                                <select class="form-control-qa" id="policy_status" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-light);">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="savePolicyBtn">Save Policy</button>
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
                    <p>Are you sure you want to delete this item?</p>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        let deleteType = null;
        let deleteId = null;

        $(document).ready(function() {
            loadStandards();
            loadPolicies();
            loadStandardsForDropdown();
            updateActionButtons();

            // Search and filter
            $('#searchInput').on('keyup', function() {
                if ($('#standards-tab').hasClass('active')) {
                    loadStandards();
                } else {
                    loadPolicies();
                }
            });

            $('#statusFilter').on('change', function() {
                if ($('#standards-tab').hasClass('active')) {
                    loadStandards();
                } else {
                    loadPolicies();
                }
            });

            $('#saveStandardBtn').click(function() {
                saveStandard();
            });
            $('#savePolicyBtn').click(function() {
                savePolicy();
            });

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                updateActionButtons();
            });
        });

        function updateActionButtons() {
            const isStandardsTab = $('#standards-tab').hasClass('active');

            $('#addStandardBtn').toggleClass('d-none', !isStandardsTab);
            $('#addPolicyBtn').toggleClass('d-none', isStandardsTab);
        }

        function loadStandards() {
            const search = $('#searchInput').val();
            const status = $('#statusFilter').val();

            $.ajax({
                url: '../../backend/api/standards_api.php?action=list',
                type: 'GET',
                data: {
                    search: search,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderStandards(response.data);
                    } else {
                        $('#standardsList').html('<div class="alert alert-warning">Failed to load standards</div>');
                    }
                },
                error: function() {
                    $('#standardsList').html('<div class="alert alert-danger">Error loading standards</div>');
                }
            });
        }

        function renderStandards(standards) {
            if (!standards.length) {
                $('#standardsList').html('<div class="text-center py-5 text-muted">No standards found</div>');
                return;
            }

            let html = '<div class="row">';
            standards.forEach(standard => {
                const statusBadge = standard.status === 'Active' ?
                    '<span class="badge-qa active">Active</span>' :
                    '<span class="badge-qa cancelled">Archived</span>';

                html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card standard-card" style="border-radius: var(--radius);">
                    <div class="card-body-custom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge-qa new" style="background: var(--primary-light); color: var(--primary);">
                                ${standard.body}
                            </span>
                            ${statusBadge}
                        </div>
                        <h6 class="fw-600 mb-2" style="font-size: 1rem;">${escapeHtml(standard.title)}</h6>
                        <p class="text-muted small mb-2">${standard.description ? escapeHtml(standard.description.substring(0, 100)) : 'No description'}</p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">v${standard.version || '1.0'} | ${standard.effective_date || 'N/A'}</small>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-1" onclick="editStandard(${standard.standard_id})" style="padding: 4px 8px;">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('standard', ${standard.standard_id})" style="padding: 4px 8px;">
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
            $('#standardsList').html(html);
        }

        function loadPolicies() {
            const search = $('#searchInput').val();
            const status = $('#statusFilter').val();

            $.ajax({
                url: '../../backend/api/policies_api.php?action=list',
                type: 'GET',
                data: {
                    search: search,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderPolicies(response.data);
                    } else {
                        $('#policiesList').html('<div class="alert alert-warning">Failed to load policies</div>');
                    }
                },
                error: function() {
                    $('#policiesList').html('<div class="alert alert-danger">Error loading policies</div>');
                }
            });
        }

        function renderPolicies(policies) {
            if (!policies.length) {
                $('#policiesList').html('<div class="text-center py-5 text-muted">No policies found</div>');
                return;
            }

            let html = '<div class="list-group">';
            policies.forEach(policy => {
                const statusBadge = policy.status === 'Active' ?
                    '<span class="badge-qa active">Active</span>' :
                    '<span class="badge-qa cancelled">Archived</span>';

                html += `
            <div class="list-group-item policy-item" style="border-radius: var(--radius); margin-bottom: 8px; background: var(--bg-card);">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            ${statusBadge}
                            ${policy.standard_title ? `<span class="badge-qa in-progress">${escapeHtml(policy.standard_title)}</span>` : '<span class="badge-qa pending">General</span>'}
                        </div>
                        <h6 class="fw-600 mb-1">${escapeHtml(policy.title)}</h6>
                        <p class="text-muted small mb-1">${policy.content ? escapeHtml(policy.content.substring(0, 150)) : 'No content'}</p>
                        ${policy.document_url ? `<small class="text-primary"><i class="fa-solid fa-link"></i> <a href="${policy.document_url}" target="_blank">Document Link</a></small>` : ''}
                        <div><small class="text-muted">Created: ${policy.created_date || 'N/A'}</small></div>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="editPolicy(${policy.policy_id})" style="padding: 4px 8px;">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('policy', ${policy.policy_id})" style="padding: 4px 8px;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
            });
            html += '</div>';
            $('#policiesList').html(html);
        }

        function loadStandardsForDropdown() {
            $.ajax({
                url: '../../backend/api/standards_api.php?action=list',
                type: 'GET',
                data: {
                    status: 'Active'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">-- None / General Policy --</option>';
                        response.data.forEach(standard => {
                            options += `<option value="${standard.standard_id}">${escapeHtml(standard.title)}</option>`;
                        });
                        $('#standard_id_select').html(options);
                    }
                }
            });
        }

        function resetStandardForm() {
            $('#standardForm')[0].reset();
            $('#standard_id').val('');
            $('#standardModalTitle').text('Add Standard');
            clearFormErrors('#standardForm');
        }

        function resetPolicyForm() {
            $('#policyForm')[0].reset();
            $('#policy_id').val('');
            $('#policyModalTitle').text('Add Policy');
            $('#standard_id_select').val('');
            clearFormErrors('#policyForm');
        }

        function clearFormErrors(form) {
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.form-error-msg').text('').removeClass('show');
        }

        function saveStandard() {
            const formData = {
                standard_id: $('#standard_id').val(),
                title: $('#title').val(),
                body: $('#body').val(),
                description: $('#description').val(),
                version: $('#version').val(),
                effective_date: $('#effective_date').val(),
                status: $('#status').val(),
                action: $('#standard_id').val() ? 'update' : 'create'
            };

            if (!formData.title) {
                toast.error('Title is required');
                return;
            }

            const btn = $('#saveStandardBtn');
            btnLoading(btn[0], 'Saving...');

            $.ajax({
                url: '../../backend/api/standards_api.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btnReset(btn[0]);
                    if (response.success) {
                        $('#standardModal').modal('hide');
                        toast.success(response.message);
                        loadStandards();
                        loadStandardsForDropdown();
                    } else {
                        if (response.errors) {
                            applyServerErrors('#standardForm', response.errors);
                        }
                        toast.error(response.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('An error occurred');
                }
            });
        }

        function savePolicy() {
            const formData = {
                policy_id: $('#policy_id').val(),
                title: $('#policy_title').val(),
                standard_id: $('#standard_id_select').val(),
                content: $('#content').val(),
                document_url: $('#document_url').val(),
                created_date: $('#created_date').val(),
                status: $('#policy_status').val(),
                action: $('#policy_id').val() ? 'update' : 'create'
            };

            if (!formData.title || !formData.content) {
                toast.error('Title and Content are required');
                return;
            }

            const btn = $('#savePolicyBtn');
            btnLoading(btn[0], 'Saving...');

            $.ajax({
                url: '../../backend/api/policies_api.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btnReset(btn[0]);
                    if (response.success) {
                        $('#policyModal').modal('hide');
                        toast.success(response.message);
                        loadPolicies();
                        if ($('#standards-tab').hasClass('active')) {
                            loadStandards();
                        }
                    } else {
                        if (response.errors) {
                            applyServerErrors('#policyForm', response.errors);
                        }
                        toast.error(response.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('An error occurred');
                }
            });
        }

        function editStandard(id) {
            $.ajax({
                url: '../../backend/api/standards_api.php?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const std = response.data;
                        $('#standard_id').val(std.standard_id);
                        $('#title').val(std.title);
                        $('#body').val(std.body);
                        $('#description').val(std.description);
                        $('#version').val(std.version);
                        $('#effective_date').val(std.effective_date);
                        $('#status').val(std.status);
                        $('#standardModalTitle').text('Edit Standard');
                        $('#standardModal').modal('show');
                        clearFormErrors('#standardForm');
                    } else {
                        toast.error('Failed to load standard data');
                    }
                },
                error: function() {
                    toast.error('Error loading standard');
                }
            });
        }

        function editPolicy(id) {
            $.ajax({
                url: '../../backend/api/policies_api.php?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const pol = response.data;
                        $('#policy_id').val(pol.policy_id);
                        $('#policy_title').val(pol.title);
                        $('#standard_id_select').val(pol.standard_id);
                        $('#content').val(pol.content);
                        $('#document_url').val(pol.document_url);
                        $('#created_date').val(pol.created_date);
                        $('#policy_status').val(pol.status);
                        $('#policyModalTitle').text('Edit Policy');
                        $('#policyModal').modal('show');
                        clearFormErrors('#policyForm');
                    } else {
                        toast.error('Failed to load policy data');
                    }
                },
                error: function() {
                    toast.error('Error loading policy');
                }
            });
        }

        function confirmDelete(type, id) {
            deleteType = type;
            deleteId = id;
            $('#deleteModal').modal('show');
        }

        $('#confirmDeleteBtn').click(function() {
            if (!deleteType || !deleteId) return;

            const apiUrl = deleteType === 'standard' ? '../../backend/api/standards_api.php' : '../../backend/api/policies_api.php';
            const btn = $('#confirmDeleteBtn');
            btnLoading(btn[0], 'Deleting...');

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: JSON.stringify({
                    action: 'delete',
                    id: deleteId
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btnReset(btn[0]);
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toast.success(response.message);
                        if (deleteType === 'standard') {
                            loadStandards();
                            loadStandardsForDropdown();
                            loadPolicies();
                        } else {
                            loadPolicies();
                        }
                        deleteType = null;
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
        });

        function applyServerErrors(form, errors) {
            for (const [field, msg] of Object.entries(errors)) {
                const input = $(form).find(`[name="${field}"]`);
                if (input.length) {
                    input.addClass('is-invalid');
                    const errEl = input.closest('.mb-3').find('.form-error-msg');
                    if (errEl.length) {
                        errEl.text(msg).addClass('show');
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
