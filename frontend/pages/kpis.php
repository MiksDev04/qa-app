<?php

/**
 * KPIs Management Page
 * Main container with tabs for Indicators and KPI Records
 */

session_start();
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'KPIs Management';
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">

    <!-- Global App JS -->
    <script src="../assets/js/app.js"></script>

    <style>
        .nav-tabs-custom {
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all var(--transition);
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--primary);
            background: transparent;
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: transparent;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
        }

        .preview-card {
            background: var(--primary-xlight);
            border-radius: var(--radius);
            padding: 15px;
            margin-top: 15px;
        }

        .import-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Toast container positioning */
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>

<body>

    <div class="qa-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">
            <?php include '../partials/header.php'; ?>

            <div class="qa-page">

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs-custom" id="kpiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="indicators-tab" data-bs-toggle="tab"
                            data-bs-target="#indicators" type="button" role="tab">
                            <i class="fa-solid fa-chart-line me-2"></i>Performance Indicators
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="records-tab" data-bs-toggle="tab"
                            data-bs-target="#records" type="button" role="tab">
                            <i class="fa-solid fa-table-list me-2"></i>KPI Records
                        </button>
                    </li>
                </ul>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
                            <i class="fa-solid fa-chart-line me-2"></i>KPIs Management
                        </h2>
                        <p class="text-muted-qa mb-0" style="font-size:.83rem; margin-top:2px;">
                            Create and manage KPIs to track performance across various categories
                        </p>
                    </div>
                </div>


                <!-- Tab Content -->
                <div class="tab-content" id="kpiTabContent">
                    <!-- Indicators Tab -->
                    <div class="tab-pane fade show active" id="indicators" role="tabpanel">
                        <div class="card">
                            <div class="card-header-custom">
                                <h3 class="card-title mb-0">
                                    <i class="fa-solid fa-chart-simple me-2"></i>Performance Indicators
                                </h3>
                                <button class="btn-primary-qa" onclick="openIndicatorModal()">
                                    <i class="fa-solid fa-plus"></i> Add Indicator
                                </button>
                            </div>
                            <div class="card-body-custom">
                                <div class="table-responsive">
                                    <table class="table-qa" id="indicators-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Unit</th>
                                                <th>Target Value</th>
                                                <th>Benchmark Source</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="indicators-tbody">
                                            <tr>
                                                <td colspan="7" class="text-center">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Records Tab -->
                    <div class="tab-pane fade" id="records" role="tabpanel">
                        <div class="card">
                            <div class="card-header-custom">
                                <h3 class="card-title mb-0">
                                    <i class="fa-solid fa-table me-2"></i>KPI Records
                                </h3>
                                <div>
                                    <button class="btn-outline-qa me-2" onclick="openImportModal()">
                                        <i class="fa-solid fa-file-import"></i> Import External Data
                                    </button>
                                    <button class="btn-primary-qa" onclick="openRecordModal()">
                                        <i class="fa-solid fa-plus"></i> Add Record
                                    </button>
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <!-- Filter Section -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label-qa">Filter by Year</label>
                                        <select class="form-control-qa" id="filter-year">
                                            <option value="">All Years</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-qa">Filter by Term</label>
                                        <select class="form-control-qa" id="filter-term">
                                            <option value="">All Terms</option>
                                            <option value="1st Semester">1st Semester</option>
                                            <option value="2nd Semester">2nd Semester</option>
                                            <option value="Summer">Summer</option>
                                            <option value="Annual">Annual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-qa">Filter by Indicator</label>
                                        <select class="form-control-qa" id="filter-indicator">
                                            <option value="">All Indicators</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table-qa" id="records-table">
                                        <thead>
                                            <tr>
                                                <th>Indicator</th>
                                                <th>School Year</th>
                                                <th>Term</th>
                                                <th>Actual Value</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="records-tbody">
                                            <tr>
                                                <td colspan="8" class="text-center">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicator Modal -->
    <div class="modal fade" id="indicatorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="indicatorModalTitle">Add Indicator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="indicatorForm">
                        <input type="hidden" id="indicator_id" name="indicator_id">
                        <div class="mb-3">
                            <label class="form-label-qa">Indicator Name *</label>
                            <input type="text" class="form-control-qa" id="indicator_name" name="name" required>
                            <div class="form-error-msg"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Description</label>
                            <textarea class="form-control-qa" id="indicator_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Category *</label>
                                <select class="form-control-qa" id="indicator_category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Student Performance">Student Performance</option>
                                    <option value="Faculty Performance">Faculty Performance</option>
                                    <option value="Research">Research</option>
                                    <option value="Extension">Extension</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Facilities">Facilities</option>
                                    <option value="Finance">Finance</option>
                                </select>
                                <div class="form-error-msg"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Unit of Measure *</label>
                                <select class="form-control-qa" id="indicator_unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="Percentage (%)">Percentage (%)</option>
                                    <option value="Rate (1-5)">Rate (1-5)</option>
                                    <option value="Rate (1-10)">Rate (1-10)</option>
                                    <option value="Number">Number</option>
                                    <option value="Score">Score</option>
                                </select>
                                <div class="form-error-msg"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Target Value *</label>
                                <input type="number" step="0.01" class="form-control-qa" id="indicator_target" name="target_value" required>
                                <div class="form-error-msg"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Benchmark Source</label>
                                <input type="text" class="form-control-qa" id="indicator_benchmark" name="benchmark_source">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" onclick="saveIndicator()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Record Modal -->
    <div class="modal fade" id="recordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordModalTitle">Add KPI Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="recordForm">
                        <input type="hidden" id="record_id" name="record_id">
                        <div class="mb-3">
                            <label class="form-label-qa">Indicator *</label>
                            <select class="form-control-qa" id="record_indicator_id" name="indicator_id" required>
                                <option value="">Select Indicator</option>
                            </select>
                            <div class="form-error-msg"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Year *</label>
                                <select class="form-control-qa" id="record_year" name="school_year" required>
                                    <option value="">Select Year</option>
                                </select>
                                <div class="form-error-msg"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Term *</label>
                                <select class="form-control-qa" id="record_term" name="period_term" required>
                                    <option value="">Select Term</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Annual">Annual</option>
                                </select>
                                <div class="form-error-msg"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Actual Value *</label>
                            <input type="number" step="0.01" class="form-control-qa" id="record_actual" name="actual_value" required>
                            <div class="form-error-msg"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Remarks</label>
                            <textarea class="form-control-qa" id="record_remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" onclick="saveRecord()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="confirmDeleteBtn" style="background:#dc3545;border-color:#dc3545;">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import External Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label-qa">Data Source</label>
                        <select class="form-control-qa" id="import_source">
                            <option value="lms">ArtisansLMS - Student Performance</option>
                            <option value="faculty_eval">Faculty Evaluation System</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-qa">Select Indicator to Map Data</label>
                        <select class="form-control-qa" id="import_indicator_id" required>
                            <option value="">Select Indicator</option>
                        </select>
                        <div class="form-error-msg"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-qa">School Year</label>
                            <select class="form-control-qa" id="import_year">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-qa">Period Term / Semester</label>
                            <select class="form-control-qa" id="import_term">
                                <option value="">Select Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                                <option value="Annual">Annual</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Data Field Selector for LMS -->
                    <div class="mb-3" id="lms-field-selector" style="display:none;">
                        <label class="form-label-qa">Select Data Field to Import</label>
                        <select class="form-control-qa" id="import_lms_field">
                            <option value="">Select Field</option>
                            <option value="avg_grade">Average Grade (%)</option>
                            <option value="submission_rate">Submission Rate (%)</option>
                            <option value="quiz_pass_rate">Quiz Pass Rate (%)</option>
                            <option value="quiz_attempts">Quiz Attempts</option>
                            <option value="quiz_passed">Quiz Passed</option>
                            <option value="total_quizzes">Total Quizzes</option>
                            <option value="total_submitted">Total Submitted</option>
                            <option value="total_tasks">Total Tasks</option>
                            <option value="total_students">Total Students</option>
                            <option value="total_expected">Total Expected Submissions</option>
                            <option value="total_classes">Total Classes</option>
                        </select>
                        <div class="form-error-msg"></div>
                    </div>

                    <!-- Faculty Evaluation field selector -->
                    <div class="mb-3" id="faculty-field-selector" style="display:none;">
                        <label class="form-label-qa">Select Data Field to Import</label>
                        <select class="form-control-qa" id="import_faculty_field">
                            <option value="">Select Field</option>
                            <option value="avg_rating">Average Rating</option>
                            <option value="response_rate">Response Rate (%)</option>
                            <option value="total_responses">Total Responses</option>
                        </select>
                        <div class="form-error-msg"></div>
                    </div>

                    <div id="import-data-preview" class="preview-card" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="fetchBtn" onclick="fetchExternalData()">
                        <i class="fa-solid fa-magnifying-glass"></i> Fetch Data
                    </button>
                    <button type="button" class="btn-primary-qa" id="importBtn" onclick="importData()" style="display:none;">
                        <i class="fa-solid fa-download"></i> Import This Data
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
        let indicatorsData = [];
        let deleteType = null;
        let deleteId = null;
        let externalDataCache = null;

        // Listen to source change
        $(document).on('change', '#import_source', function() {
            toggleFieldSelector();
            $('#import-data-preview').hide().html('');
            $('#fetchBtn').show();
            $('#importBtn').hide();
            externalDataCache = null;
        });

        // Load Indicators Table
        function loadIndicators() {
            $.ajax({
                url: '../../backend/api/kpi_indicators_api.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        indicatorsData = response.data;
                        renderIndicatorsTable(response.data);
                    } else {
                        $('#indicators-tbody').html('<tr><td colspan="7" class="text-center">No indicators found</td></tr>');
                    }
                },
                error: function() {
                    $('#indicators-tbody').html('<tr><td colspan="7" class="text-center">Error loading data</td></tr>');
                    toast.error('Failed to load indicators');
                }
            });
        }

        function renderIndicatorsTable(data) {
            if (!data || data.length === 0) {
                $('#indicators-tbody').html('<tr><td colspan="7" class="text-center">No indicators found</td></tr>');
                return;
            }

            let html = '';
            data.forEach(indicator => {
                html += `
                <tr>
                    <td><strong>${escapeHtml(indicator.name)}</strong></td>
                    <td>${escapeHtml(indicator.category || '-')}</td>
                    <td>${escapeHtml(indicator.unit || '-')}</td>
                    <td>${indicator.target_value || '-'}</td>
                    <td>${escapeHtml(indicator.benchmark_source || '-')}</td>
                    <td>
                        <button class="btn-outline-qa btn-sm" onclick="editIndicator(${indicator.indicator_id})" style="padding:4px 8px;margin-right:5px;">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn-outline-qa btn-sm" onclick="deleteIndicator(${indicator.indicator_id})" style="padding:4px 8px;color:#dc3545;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            });
            $('#indicators-tbody').html(html);
        }

        // Load Records Table
        function loadRecords() {
            const year = $('#filter-year').val();
            const term = $('#filter-term').val();
            const indicator = $('#filter-indicator').val();

            $.ajax({
                url: '../../backend/api/kpi_records_api.php',
                type: 'GET',
                data: {
                    year: year,
                    term: term,
                    indicator_id: indicator
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        renderRecordsTable(response.data);
                    } else {
                        $('#records-tbody').html('<tr><td colspan="8" class="text-center">No records found</td></tr>');
                    }
                },
                error: function() {
                    $('#records-tbody').html('<tr><td colspan="8" class="text-center">Error loading data</td></tr>');
                    toast.error('Failed to load records');
                }
            });
        }

        function renderRecordsTable(data) {
            if (!data || data.length === 0) {
                $('#records-tbody').html('<tr><td colspan="8" class="text-center">No records found</td></tr>');
                return;
            }

            let html = '';
            data.forEach(record => {
                const targetValue = parseFloat(record.target_value);
                const actualValue = parseFloat(record.actual_value);
                let status = '';
                let statusClass = '';

                if (targetValue && !isNaN(targetValue) && !isNaN(actualValue) && actualValue >= targetValue) {
                    status = 'Achieved';
                    statusClass = 'badge-qa active';
                } else if (targetValue && !isNaN(targetValue) && !isNaN(actualValue) && actualValue < targetValue) {
                    status = 'Below Target';
                    statusClass = 'badge-qa pending';
                } else {
                    status = 'No Target';
                    statusClass = 'badge-qa';
                }

                html += `
                <tr>
                    <td><strong>${escapeHtml(record.indicator_name)}</strong></td>
                    <td>${record.school_year}</td>
                    <td>${escapeHtml(record.period_term || '-')}</td>
                    <td>${record.actual_value} ${record.unit ? record.unit : ''}</td>
                    <td><span class="${statusClass}">${status}</span></td>
                    <td>${escapeHtml(record.remarks || '-')}</td>
                    <td>
                        <button class="btn-outline-qa btn-sm" onclick="editRecord(${record.record_id})" style="padding:4px 8px;margin-right:5px;">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn-outline-qa btn-sm" onclick="deleteRecord(${record.record_id})" style="padding:4px 8px;color:#dc3545;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            });
            $('#records-tbody').html(html);
        }

        // Indicator CRUD
        function openIndicatorModal() {
            $('#indicatorModalTitle').text('Add Indicator');
            $('#indicatorForm')[0].reset();
            $('#indicator_id').val('');
            $('#indicatorModal').modal('show');
        }

        function editIndicator(id) {
            $.ajax({
                url: `../../backend/api/kpi_indicators_api.php?id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const indicator = response.data;
                        $('#indicatorModalTitle').text('Edit Indicator');
                        $('#indicator_id').val(indicator.indicator_id);
                        $('#indicator_name').val(indicator.name);
                        $('#indicator_description').val(indicator.description || '');
                        $('#indicator_category').val(indicator.category || '');
                        $('#indicator_unit').val(indicator.unit || '');
                        $('#indicator_target').val(indicator.target_value || '');
                        $('#indicator_benchmark').val(indicator.benchmark_source || '');
                        $('#indicatorModal').modal('show');
                    }
                },
                error: function() {
                    toast.error('Error loading indicator data');
                }
            });
        }

        function saveIndicator() {
            const id = $('#indicator_id').val();
            const data = {
                name: $('#indicator_name').val(),
                description: $('#indicator_description').val(),
                category: $('#indicator_category').val(),
                unit: $('#indicator_unit').val(),
                target_value: $('#indicator_target').val(),
                benchmark_source: $('#indicator_benchmark').val()
            };

            if (!data.name || !data.category || !data.unit || !data.target_value) {
                toast.error('Please fill in all required fields');
                return;
            }

            const url = id ? `../../backend/api/kpi_indicators_api.php?id=${id}` : '../../backend/api/kpi_indicators_api.php';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toast.success(response.message || (id ? 'Indicator updated successfully' : 'Indicator added successfully'));
                        $('#indicatorModal').modal('hide');
                        loadIndicators();
                        loadIndicatorDropdowns();
                    } else {
                        toast.error(response.message || 'Operation failed');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toast.error(response?.message || 'An error occurred');
                }
            });
        }

        function deleteIndicator(id) {
            deleteType = 'indicator';
            deleteId = id;
            $('#deleteModal').modal('show');
        }

        // Record CRUD
        function loadIndicatorDropdowns() {
            $.ajax({
                url: '../../backend/api/kpi_indicators_api.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        let options = '<option value="">Select Indicator</option>';
                        response.data.forEach(ind => {
                            options += `<option value="${ind.indicator_id}">${escapeHtml(ind.name)} (${escapeHtml(ind.unit || 'no unit')})</option>`;
                        });
                        $('#record_indicator_id').html(options);
                        $('#filter-indicator').html('<option value="">All Indicators</option>' + options);
                        $('#import_indicator_id').html('<option value="">Select Indicator</option>' + options);
                    }
                }
            });
        }

        function loadYearDropdowns() {
            const currentYear = new Date().getFullYear();
            let options = '<option value="">Select Year</option>';
            for (let i = currentYear - 5; i <= currentYear + 2; i++) {
                let nextYear = i;
                nextYear++;
                options += `<option value="${i} - ${nextYear}">${i} - ${nextYear}</option>`;
            }
            $('#record_year, #import_year, #filter-year').html(options);

            // Auto-select current year
            $('#import_year').val(currentYear);
        }

        function openRecordModal() {
            $('#recordModalTitle').text('Add KPI Record');
            $('#recordForm')[0].reset();
            $('#record_id').val('');
            $('#recordModal').modal('show');
        }

        function editRecord(id) {
            $.ajax({
                url: `../../backend/api/kpi_records_api.php?id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const record = response.data;
                        $('#recordModalTitle').text('Edit KPI Record');
                        $('#record_id').val(record.record_id);
                        $('#record_indicator_id').val(record.indicator_id);
                        $('#record_year').val(record.school_year);
                        $('#record_term').val(record.period_term);
                        $('#record_actual').val(record.actual_value);
                        $('#record_remarks').val(record.remarks || '');
                        $('#recordModal').modal('show');
                    }
                },
                error: function() {
                    toast.error('Error loading record data');
                }
            });
        }

        function saveRecord() {
            const id = $('#record_id').val();
            const data = {
                indicator_id: $('#record_indicator_id').val(),
                school_year: $('#record_year').val(),
                period_term: $('#record_term').val(),
                actual_value: $('#record_actual').val(),
                remarks: $('#record_remarks').val()
            };

            if (!data.indicator_id || !data.school_year || !data.period_term || !data.actual_value) {
                toast.error('Please fill in all required fields');
                return;
            }

            const url = id ? `../../backend/api/kpi_records_api.php?id=${id}` : '../../backend/api/kpi_records_api.php';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toast.success(response.message || (id ? 'Record updated successfully' : 'Record added successfully'));
                        $('#recordModal').modal('hide');
                        loadRecords();
                    } else {
                        toast.error(response.message || 'Operation failed');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toast.error(response?.message || 'An error occurred');
                }
            });
        }

        function deleteRecord(id) {
            deleteType = 'record';
            deleteId = id;
            $('#deleteModal').modal('show');
        }

        // Delete confirmation handler
        $('#confirmDeleteBtn').on('click', function() {
            if (deleteType === 'indicator') {
                $.ajax({
                    url: `../../backend/api/kpi_indicators_api.php?id=${deleteId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toast.success(response.message || 'Indicator deleted successfully');
                            $('#deleteModal').modal('hide');
                            loadIndicators();
                            loadIndicatorDropdowns();
                        } else {
                            toast.error(response.message || 'Delete failed');
                            $('#deleteModal').modal('hide');
                        }
                    },
                    error: function() {
                        toast.error('Error deleting indicator');
                        $('#deleteModal').modal('hide');
                    }
                });
            } else if (deleteType === 'record') {
                $.ajax({
                    url: `../../backend/api/kpi_records_api.php?id=${deleteId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toast.success(response.message || 'Record deleted successfully');
                            $('#deleteModal').modal('hide');
                            loadRecords();
                        } else {
                            toast.error(response.message || 'Delete failed');
                            $('#deleteModal').modal('hide');
                        }
                    },
                    error: function() {
                        toast.error('Error deleting record');
                        $('#deleteModal').modal('hide');
                    }
                });
            }
        });

        // Import External Data
        function openImportModal() {
            $('#import_source').val('lms');
            $('#import_indicator_id').val('');
            $('#import_term').val('');
            $('#import-data-preview').hide().html('');
            $('#lms-field-selector').hide();
            $('#faculty-field-selector').hide();
            $('#fetchBtn').show();
            $('#importBtn').hide();

            // Auto-select current year
            const currentYear = new Date().getFullYear();
            $('#import_year').val(currentYear);

            toggleFieldSelector();
            $('#importModal').modal('show');
        }

        function toggleFieldSelector() {
            const source = $('#import_source').val();
            if (source === 'lms') {
                $('#lms-field-selector').show();
                $('#faculty-field-selector').hide();
            } else if (source === 'faculty_eval') {
                $('#lms-field-selector').hide();
                $('#faculty-field-selector').show();
            } else {
                $('#lms-field-selector').hide();
                $('#faculty-field-selector').hide();
            }
        }

        function fetchExternalData() {
            const source = $('#import_source').val();
            const indicatorId = $('#import_indicator_id').val();
            const year = $('#import_year').val();
            const term = $('#import_term').val();

            let selectedField = '';
            if (source === 'lms') {
                selectedField = $('#import_lms_field').val();
                if (!selectedField) {
                    toast.error('Please select a data field to import');
                    return;
                }
            } else if (source === 'faculty_eval') {
                selectedField = $('#import_faculty_field').val();
                if (!selectedField) {
                    toast.error('Please select a data field to import');
                    return;
                }
            }

            if (!indicatorId) {
                toast.error('Please select an indicator');
                return;
            }

            if (!year) {
                toast.error('Please select a year');
                return;
            }

            const fetchBtn = $('#fetchBtn');
            const originalText = fetchBtn.html();
            fetchBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

            $.ajax({
                url: '../../backend/api/lms_external_api.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'fetch_external',
                    source: source,
                    indicator_id: indicatorId,
                    year: year,
                    term: term,
                    field: selectedField
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    fetchBtn.prop('disabled', false).html(originalText);

                    if (response.success && response.data) {
                        let actualValue = null;
                        let fieldLabel = '';

                        if (source === 'lms') {
                            fieldLabel = $('#import_lms_field option:selected').text();
                            actualValue = response.data[selectedField];
                        } else {
                            fieldLabel = $('#import_faculty_field option:selected').text();
                            actualValue = response.data[selectedField];
                        }

                        if (actualValue === undefined || actualValue === null) {
                            toast.error(`Field "${fieldLabel}" not found in the response data`);
                            return;
                        }

                        externalDataCache = {
                            actual_value: actualValue,
                            year: year,
                            term: term,
                            source: source,
                            field: selectedField,
                            field_label: fieldLabel,
                            full_data: response.data
                        };

                        let previewHtml = `
                        <div style="padding:15px;">
                            <h6><i class="fa-solid fa-check-circle" style="color:var(--accent-green);"></i> Data Retrieved:</h6>
                            <hr>
                            <p><strong>Source:</strong> ${source === 'lms' ? 'ArtisansLMS - Student Performance' : 'Faculty Evaluation'}</p>
                            <p><strong>Field:</strong> ${fieldLabel}</p>
                            <p><strong>Value:</strong> <span class="import-value">${actualValue}</span></p>
                            <hr>
                            <details>
                                <summary style="cursor:pointer;color:var(--primary);">View Full API Response</summary>
                                <pre style="background:#f8f9fa;padding:10px;border-radius:8px;font-size:12px;max-height:300px;overflow:auto;margin-top:10px;">${JSON.stringify(response.data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                        $('#import-data-preview').html(previewHtml).show();
                        $('#fetchBtn').hide();
                        $('#importBtn').show();
                        toast.success('External data retrieved successfully');
                    } else {
                        toast.error(response.message || 'No data available for the selected period');
                        $('#import-data-preview').html('<div class="alert alert-warning">No data found for the selected criteria</div>').show();
                    }
                },
                error: function(xhr) {
                    fetchBtn.prop('disabled', false).html(originalText);
                    console.error('API Error:', xhr);
                    let errorMsg = 'Error fetching external data';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch (e) {}
                    toast.error(errorMsg);
                }
            });
        }

        function importData() {
            if (!externalDataCache) {
                toast.error('No data to import');
                return;
            }

            const data = {
                indicator_id: $('#import_indicator_id').val(),
                school_year: externalDataCache.year,
                period_term: externalDataCache.term,
                actual_value: externalDataCache.actual_value,
                remarks: `Imported from ${$('#import_source').val()} - Field: ${externalDataCache.field_label}`
            };

            const importBtn = $('#importBtn');
            const originalText = importBtn.html();
            importBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Importing...');

            $.ajax({
                url: '../../backend/api/kpi_records_api.php',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    importBtn.prop('disabled', false).html(originalText);

                    if (response.success) {
                        toast.success('Data imported successfully');
                        $('#importModal').modal('hide');
                        loadRecords();
                        externalDataCache = null;
                        $('#fetchBtn').show();
                        $('#importBtn').hide();
                    } else {
                        toast.error(response.message || 'Import failed');
                    }
                },
                error: function(xhr) {
                    importBtn.prop('disabled', false).html(originalText);
                    const response = xhr.responseJSON;
                    toast.error(response?.message || 'Error importing data');
                }
            });
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

        // Event Listeners
        $(document).ready(function() {
            loadIndicators();
            loadRecords();
            loadIndicatorDropdowns();
            loadYearDropdowns();

            $('#filter-year, #filter-term, #filter-indicator').on('change', loadRecords);
        });
    </script>

</body>

</html>