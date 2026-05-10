<?php

/**
 * Survey Management Page
 * frontend/pages/surveys.php
 *
 * Status lifecycle:
 *  – Users pick Draft or Active only; Closed is system-managed.
 *  – end_date cannot be earlier than start_date (client + server enforced).
 *  – Active is blocked outside the start/end date window.
 */

session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Survey Management';
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

        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">

            <?php include '../partials/header.php'; ?>

            <div class="qa-page">
                <ul class="nav nav-tabs-custom" id="surveyPageTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="survey-tab"
                            data-bs-toggle="tab" data-bs-target="#survey-tab-pane"
                            type="button" role="tab" aria-selected="true">
                            <i class="fa-solid fa-clipboard-list me-2"></i> Survey
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="responses-tab"
                            data-bs-toggle="tab" data-bs-target="#responses-tab-pane"
                            type="button" role="tab" aria-selected="false">
                            <i class="fa-solid fa-comments me-2"></i> Response
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="surveyPageTabContent">

                    <!-- ── Survey Tab ────────────────────────────────────────── -->
                    <div class="tab-pane fade show active" id="survey-tab-pane"
                        role="tabpanel" aria-labelledby="survey-tab">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-1" style="font-size:1.5rem;font-weight:700;">Survey Management</h2>
                                <p class="text-muted-qa">Create and manage surveys, view responses and analytics</p>
                            </div>
                            <button class="btn-primary-qa" id="createSurveyBtn">
                                <i class="fa-solid fa-plus"></i> Create Survey
                            </button>
                        </div>

                        <div class="card">
                            <div class="card-header-custom">
                                <div class="card-title">
                                    <i class="fa-solid fa-chart-bar me-2"></i> All Surveys
                                </div>
                                <div class="header-search" style="width:250px;">
                                    <i class="fa-solid fa-search search-icon"></i>
                                    <input type="text" id="searchSurvey"
                                        placeholder="Search surveys..."
                                        class="form-control-qa" style="padding-left:34px;">
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <div class="table-responsive">
                                    <table class="table-qa">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Target Group</th>
                                                <th>Questions</th>
                                                <th>Responses</th>
                                                <th>Period</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="surveysTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">Loading surveys…</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Responses Tab ─────────────────────────────────────── -->
                    <div class="tab-pane fade" id="responses-tab-pane"
                        role="tabpanel" aria-labelledby="responses-tab">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                            <div>
                                <h2 class="mb-1" style="font-size:1.5rem;font-weight:700;">Survey Responses</h2>
                                <p class="text-muted-qa">Review every survey response and the answers submitted by respondents</p>
                            </div>
                            <button class="btn-outline-qa" id="refreshResponsesBtn" style="padding:10px 16px;">
                                <i class="fa-solid fa-rotate-right"></i> Refresh Responses
                            </button>
                        </div>

                        <div id="responsesSummary" class="row g-3 mb-4"></div>
                        <div id="allResponsesContainer">
                            <div class="card">
                                <div class="card-body-custom text-center py-5">Loading responses…</div>
                            </div>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div><!-- /qa-page -->

            <!-- ── Modals ────────────────────────────────────────────────────── -->

            <!-- Response Details -->
            <div class="modal fade" id="responseDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content" style="border-radius:var(--radius-lg);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);padding:20px 24px;">
                            <div>
                                <h5 class="modal-title mb-1" id="responseDetailsModalTitle" style="font-weight:700;">Response Details</h5>
                                <div class="text-muted-qa" id="responseDetailsModalSubtitle" style="font-size:.83rem;"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="responseDetailsModalBody" style="padding:24px;"></div>
                    </div>
                </div>
            </div>

            <!-- Delete Survey Confirm -->
            <div class="modal fade" id="deleteSurveyConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:var(--radius-lg);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);padding:20px 24px;">
                            <h5 class="modal-title" style="font-weight:700;color:var(--accent-orange);">Delete Survey</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding:24px;">
                            <p class="mb-0">Are you sure you want to delete this survey? This will also delete all questions and responses.
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border);padding:16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmDeleteBtn"
                                style="background-color:var(--accent-orange);color:#fff;border:none;
                                   padding:8px 16px;border-radius:var(--radius);font-weight:600;">
                                Delete Survey
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Response Confirm -->
            <div class="modal fade" id="deleteResponseConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:var(--radius-lg);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);padding:20px 24px;">
                            <h5 class="modal-title" style="font-weight:700;color:var(--accent-orange);">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>Delete Response
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding:24px;">
                            <p class="mb-0">Are you sure you want to delete this respondent's submission?
                                All their answers will be permanently removed.
                                <strong>This cannot be undone.</strong>
                            </p>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border);padding:16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmDeleteResponseBtn"
                                style="background-color:var(--accent-orange);color:#fff;border:none;
                                   padding:8px 16px;border-radius:var(--radius);font-weight:600;">
                                Delete Response
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Survey -->
            <div class="modal fade" id="surveyModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius:var(--radius-lg);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);padding:20px 24px;">
                            <h5 class="modal-title" id="surveyModalTitle" style="font-weight:700;">Create Survey</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding:24px;">
                            <form id="surveyForm" novalidate>
                                <input type="hidden" name="survey_id" id="survey_id">

                                <div class="mb-3">
                                    <label class="form-label-qa">Survey Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control-qa" name="title" id="surveyTitle" required>
                                    <div class="form-error-msg"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-qa">Description</label>
                                    <textarea class="form-control-qa" name="description" id="surveyDescription" rows="3"></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label-qa">Target Group <span class="text-danger">*</span></label>
                                        <select class="form-control-qa" name="target_group" id="surveyTargetGroup" required>
                                            <option value="">Select Target Group</option>
                                            <option value="Student">Student</option>
                                            <option value="Alumni">Alumni</option>
                                            <option value="Employer">Employer</option>
                                            <option value="Faculty">Faculty</option>
                                            <option value="Staff">Staff</option>
                                            <option value="All">All</option>
                                        </select>
                                        <div class="form-error-msg"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-qa">Start Date</label>
                                        <input type="date" class="form-control-qa" name="start_date" id="surveyStartDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-qa">End Date</label>
                                        <input type="date" class="form-control-qa" name="end_date" id="surveyEndDate">
                                        <!-- Inline error for date conflict -->
                                        <div id="endDateError" class="text-danger mt-1"
                                            style="font-size:.8rem;display:none;">
                                            End date cannot be before start date.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-qa">Status</label>
                                    <!--
                                    IMPORTANT: Only Draft and Active are offered here.
                                    'Closed' is set automatically by the system when
                                    end_date passes – it is not a manual choice.
                                -->
                                    <select class="form-control-qa" name="status" id="surveyStatus">
                                        <option value="Draft">Draft</option>
                                        <option value="Active">Active</option>
                                    </select>
                                    <div id="statusHint" class="mt-1"
                                        style="font-size:.78rem;color:var(--text-secondary);">
                                        <i class="fa-solid fa-circle-info me-1"></i>
                                        Surveys are automatically marked <strong>Closed</strong>
                                        by the system once the end date has passed.
                                    </div>
                                    <!-- Shown when user picks Active but dates are invalid -->
                                    <div id="activeRangeError" class="text-danger mt-1"
                                        style="font-size:.8rem;display:none;"></div>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label-qa mb-0"
                                        style="font-size:1rem;font-weight:700;">Survey Questions</label>
                                    <button type="button" class="btn-outline-qa" id="addQuestionBtn"
                                        style="padding:6px 12px;">
                                        <i class="fa-solid fa-plus"></i> Add Question
                                    </button>
                                </div>

                                <div id="questionsContainer"></div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border);padding:16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-primary-qa" id="saveSurveyBtn">Save Survey</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Template -->
            <script id="questionTemplate" type="text/template">
                <div class="question-item card mb-3"
                 style="background:var(--bg-main);border:1px solid var(--border);"
                 data-question-index="{index}">
                <div class="card-body-custom" style="padding:16px;">
                    <input type="hidden" name="questions[{index}][question_id]" class="question_id">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <label class="form-label-qa mb-0" style="font-weight:600;">Question {displayIndex}</label>
                        <button type="button" class="btn-outline-qa remove-question-btn"
                                style="padding:4px 8px;font-size:.75rem;">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control-qa" name="questions[{index}][question_text]"
                                  placeholder="Enter question text" rows="2" required></textarea>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <select class="form-control-qa question-type" name="questions[{index}][question_type]">
                                <option value="rating_5">Rating (1-5)</option>
                                <option value="rating_10">Rating (1-10)</option>
                                <option value="yes_no">Yes/No</option>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="open_ended">Open Ended</option>
                                <option value="text">Text Input</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input"
                                       name="questions[{index}][is_required]" value="1" checked>
                                <label class="form-check-label" style="font-size:.85rem;">Required question</label>
                            </div>
                        </div>
                    </div>
                    <div class="options-container" style="display:none;"></div>
                </div>
            </div>
        </script>

            <script id="optionsTemplate" type="text/template">
                <div class="mt-2">
                <label class="form-label-qa" style="font-size:.75rem;">Options (one per line)</label>
                <textarea class="form-control-qa options-text" rows="3"
                          placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
        </script>

            <!-- Scripts -->
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../assets/js/app.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

            <script>
                // ── State ────────────────────────────────────────────────────────────
                let responsesLoaded = false;
                let responseSurveyCache = [];
                let pendingDeleteSurveyId = null;

                // ── Init ─────────────────────────────────────────────────────────────
                $(document).ready(function() {

                    loadSurveys();
                    loadAllResponses();

                    // Create
                    $('#createSurveyBtn').click(function() {
                        resetSurveyModal();
                        $('#surveyModalTitle').text('Create Survey');
                        $('#surveyModal').modal('show');
                        addQuestion();
                    });

                    // Save
                    $('#saveSurveyBtn').click(saveSurvey);

                    // Add question
                    $('#addQuestionBtn').click(addQuestion);

                    // Refresh responses
                    $('#refreshResponsesBtn').click(function() {
                        loadAllResponses(true);
                    });

                    // Tab switch – lazy-load responses
                    $('#surveyPageTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                        if ($(e.target).attr('id') === 'responses-tab' && !responsesLoaded) {
                            loadAllResponses();
                        }
                    });

                    // Search
                    $('#searchSurvey').on('keyup', function() {
                        loadSurveys($(this).val());
                    });

                    // Remove question
                    $(document).on('click', '.remove-question-btn', function() {
                        $(this).closest('.question-item').remove();
                        reindexQuestions();
                    });

                    // Question type change
                    $(document).on('change', '.question-type', function() {
                        const $container = $(this).closest('.question-item').find('.options-container');
                        const type = $(this).val();
                        if (type === 'multiple_choice' || type === 'checkbox') {
                            $container.show();
                            if ($container.html() === '') {
                                $container.html($('#optionsTemplate').html());
                            }
                        } else {
                            $container.hide().html('');
                        }
                    });

                    // ── Date validation – end_date cannot precede start_date ──────────
                    $('#surveyStartDate, #surveyEndDate').on('change', function() {
                        validateDateRange();
                        validateActiveStatus(); // re-check activation eligibility
                    });

                    // ── Status change – re-check eligibility for Active ───────────────
                    $('#surveyStatus').on('change', validateActiveStatus);

                    // ── Response details ──────────────────────────────────────────────
                    $(document).on('click', '.view-response-details', function() {
                        openResponseDetailsModal($(this).data('id'));
                    });

                    // ── Delete survey ─────────────────────────────────────────────────
                    $('#confirmDeleteBtn').click(function() {
                        if (pendingDeleteSurveyId) {
                            performDeleteSurvey(pendingDeleteSurveyId);
                            pendingDeleteSurveyId = null;
                            bootstrap.Modal.getOrCreateInstance(
                                document.getElementById('deleteSurveyConfirmModal')
                            ).hide();
                        }
                    });
                    $('#deleteSurveyConfirmModal').on('hidden.bs.modal', function() {
                        pendingDeleteSurveyId = null;
                    });

                    // ── Delete respondent ─────────────────────────────────────────────
                    let pendingDeleteRespondentId = null;

                    $(document).on('click', '.delete-respondent-btn', function() {
                        pendingDeleteRespondentId = $(this).data('id');
                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('deleteResponseConfirmModal')
                        ).show();
                    });

                    $('#confirmDeleteResponseBtn').on('click', function() {
                        if (!pendingDeleteRespondentId) return;
                        const id = pendingDeleteRespondentId;
                        pendingDeleteRespondentId = null;
                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('deleteResponseConfirmModal')
                        ).hide();
                        performDeleteRespondent(id);
                    });

                    $('#deleteResponseConfirmModal').on('hidden.bs.modal', function() {
                        pendingDeleteRespondentId = null;
                    });
                });

                // ── Client-side date validation ──────────────────────────────────────

                /**
                 * Shows/hides the inline end-date error and sets a data flag.
                 * Returns true if dates are valid (or incomplete).
                 */
                function validateDateRange() {
                    const start = $('#surveyStartDate').val();
                    const end = $('#surveyEndDate').val();
                    const $err = $('#endDateError');

                    if (start && end && end < start) {
                        $err.show();
                        $('#surveyEndDate').addClass('is-invalid');
                        return false;
                    }

                    $err.hide();
                    $('#surveyEndDate').removeClass('is-invalid');
                    return true;
                }

                /**
                 * Warns the user inline if they pick Active but the date window is wrong.
                 * The server will also reject such a request.
                 * Returns true if status selection is valid (or Draft).
                 */
                function validateActiveStatus() {
                    const status = $('#surveyStatus').val();
                    const $err = $('#activeRangeError');

                    if (status !== 'Active') {
                        $err.hide().text('');
                        return true;
                    }

                    const today = new Date().toISOString().slice(0, 10);
                    const start = $('#surveyStartDate').val();
                    const end = $('#surveyEndDate').val();

                    if (end && today > end) {
                        $err.text(
                            'Cannot activate: the end date (' + end + ') has already passed. ' +
                            'The system will automatically mark it as Closed.'
                        ).show();
                        return false;
                    }

                    if (start && today < start) {
                        $err.text(
                            'Cannot activate before the start date (' + start + '). ' +
                            'Save as Draft and activate it on or after that date.'
                        ).show();
                        return false;
                    }

                    $err.hide().text('');
                    return true;
                }

                // ── Survey CRUD ──────────────────────────────────────────────────────

                function loadSurveys(search = '') {
                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'GET',
                        data: {
                            action: 'list',
                            search
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data) {
                                renderSurveysTable(response.data);
                            } else {
                                $('#surveysTableBody').html(
                                    '<tr><td colspan="7" class="text-center">No surveys found</td></tr>'
                                );
                            }
                        },
                        error: function() {
                            $('#surveysTableBody').html(
                                '<tr><td colspan="7" class="text-center">Error loading surveys</td></tr>'
                            );
                        }
                    });
                }

                function renderSurveysTable(surveys) {
                    let html = '';
                    surveys.forEach(function(survey) {
                        html += `
                <tr>
                    <td><strong>${escapeHtml(survey.title)}</strong></td>
                    <td>${escapeHtml(survey.target_group)}</td>
                    <td>${survey.questions_count || 0}</td>
                    <td>${survey.responses_count || 0}</td>
                    <td>${escapeHtml(survey.start_date || 'N/A')} → ${escapeHtml(survey.end_date || 'N/A')}</td>
                    <td>${getStatusBadge(survey.status)}</td>
                    <td>
                        <button class="btn-outline-qa btn-sm edit-survey" data-id="${survey.survey_id}"
                                style="padding:4px 8px;font-size:.75rem;"
                                title="Edit survey"
                                ${survey.status === 'Closed' ? '' : ''}>
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn-outline-qa btn-sm view-responses" data-id="${survey.survey_id}"
                                style="padding:4px 8px;font-size:.75rem;" title="View responses">
                            <i class="fa-solid fa-chart-bar"></i>
                        </button>
                        <button class="btn-outline-qa btn-sm copy-link" data-id="${survey.survey_id}"
                                style="padding:4px 8px;font-size:.75rem;" title="Get QR / link">
                            <i class="fa-solid fa-link"></i>
                        </button>
                        <button class="btn-outline-qa btn-sm delete-survey" data-id="${survey.survey_id}"
                                style="padding:4px 8px;font-size:.75rem;color:var(--accent-orange);"
                                title="Delete survey">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
                    });

                    if (!html) {
                        html = '<tr><td colspan="7" class="text-center">No surveys found</td></tr>';
                    }

                    $('#surveysTableBody').html(html);

                    $('.edit-survey').click(function() {
                        editSurvey($(this).data('id'));
                    });
                    $('.view-responses').click(function() {
                        viewResponses($(this).data('id'));
                    });
                    $('.copy-link').click(function() {
                        copySurveyLink($(this).data('id'));
                    });
                    $('.delete-survey').click(function() {
                        deleteSurvey($(this).data('id'));
                    });
                }

                function editSurvey(id) {
                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'GET',
                        data: {
                            action: 'get',
                            id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (!response.success || !response.data) return;

                            const survey = response.data;
                            const isClosed = survey.status === 'Closed';

                            resetSurveyModal();

                            $('#survey_id').val(survey.survey_id);
                            $('#surveyTitle').val(survey.title);
                            $('#surveyDescription').val(survey.description);
                            $('#surveyTargetGroup').val(survey.target_group);
                            $('#surveyStartDate').val(survey.start_date);
                            $('#surveyEndDate').val(survey.end_date);

                            // Closed is shown read-only; users cannot select it manually.
                            const $statusSelect = $('#surveyStatus');
                            if (isClosed) {
                                $statusSelect.val('Draft').prop('disabled', false);
                                $('#statusHint').html(
                                    '<i class="fa-solid fa-triangle-exclamation me-1" style="color:var(--accent-orange);"></i>' +
                                    'This survey is currently <strong>Closed</strong> (its end date has passed). ' +
                                    'It will be saved as <strong>Draft</strong> unless you choose otherwise. ' +
                                    'To reactivate it, update the end date and set status to <strong>Active</strong>.'
                                );
                                $('#statusHint').html(
                                    '<i class="fa-solid fa-lock me-1"></i>' +
                                    'This survey is <strong>Closed</strong> because its end date has passed. ' +
                                    'Update the end date and set it to Active to reopen it.'
                                );
                            } else {
                                $statusSelect.val(survey.status).prop('disabled', false);
                            }

                            if (survey.questions && survey.questions.length > 0) {
                                survey.questions.forEach(function(q, idx) {
                                    addQuestionWithData(q, idx);
                                });
                            } else {
                                addQuestion();
                            }

                            $('#surveyModalTitle').text(isClosed ? 'View / Edit Survey (Closed)' : 'Edit Survey');
                            $('#surveyModal').modal('show');
                        }
                    });
                }

                function saveSurvey() {
                    // Run client-side validations before sending
                    const dateOk = validateDateRange();
                    const statusOk = validateActiveStatus();

                    if (!dateOk || !statusOk) {
                        toast.error('Please fix the highlighted errors before saving.', 'Validation');
                        return;
                    }

                    const questions = [];
                    $('#questionsContainer .question-item').each(function() {
                        const q = {
                            question_text: $(this).find('textarea[name*="[question_text]"]').val(),
                            question_type: $(this).find('.question-type').val(),
                            is_required: $(this).find('input[name*="[is_required]"]').is(':checked') ? 1 : 0,
                        };
                        const qid = $(this).find('.question_id').val();
                        if (qid) q.question_id = qid;

                        const optionsText = $(this).find('.options-text').val();
                        if (optionsText) {
                            q.options = optionsText
                                .split('\n')
                                .map(option => option.trim())
                                .filter(Boolean);
                        }
                        questions.push(q);
                    });

                    const surveyData = {
                        survey_id: $('#survey_id').val(),
                        title: $('#surveyTitle').val(),
                        description: $('#surveyDescription').val(),
                        target_group: $('#surveyTargetGroup').val(),
                        start_date: $('#surveyStartDate').val(),
                        end_date: $('#surveyEndDate').val(),
                        status: $('#surveyStatus').val(),
                        action: $('#survey_id').val() ? 'update' : 'create',
                        questions,
                    };

                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'POST',
                        data: JSON.stringify(surveyData),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(response.message, 'Success');
                                $('#surveyModal').modal('hide');
                                loadSurveys();
                            } else {
                                toast.error(response.message, 'Error');
                            }
                        },
                        error: function() {
                            toast.error('Failed to save survey', 'Error');
                        }
                    });
                }

                function deleteSurvey(id) {
                    pendingDeleteSurveyId = id;
                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById('deleteSurveyConfirmModal')
                    ).show();
                }

                function performDeleteSurvey(id) {
                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'DELETE',
                        data: JSON.stringify({
                            survey_id: id
                        }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toast.success(response.message, 'Deleted');
                                loadSurveys();
                            } else {
                                toast.error(response.message, 'Error');
                            }
                        },
                        error: function() {
                            toast.error('Failed to delete survey', 'Error');
                        }
                    });
                }

                // ── Responses ────────────────────────────────────────────────────────

                function loadAllResponses(forceReload = false) {
                    if (responsesLoaded && !forceReload) {
                        return $.Deferred().resolve(responseSurveyCache).promise();
                    }

                    $('#responsesSummary').html('');
                    $('#allResponsesContainer').html(
                        '<div class="card"><div class="card-body-custom text-center py-5">Loading responses…</div></div>'
                    );

                    return $.ajax({
                        url: '../../backend/api/survey_responses_api.php',
                        type: 'GET',
                        data: {
                            action: 'get_all_responses'
                        },
                        dataType: 'json',
                    }).done(function(response) {
                        responsesLoaded = true;
                        const data = (response.success && Array.isArray(response.data)) ? response.data : [];
                        responseSurveyCache = data;
                        renderAllResponses(data);
                    }).fail(function() {
                        responsesLoaded = true;
                        responseSurveyCache = [];
                        $('#allResponsesContainer').html(
                            '<div class="card"><div class="card-body-custom text-center py-5">Error loading responses.</div></div>'
                        );
                    });
                }

                function renderAllResponses(surveys) {
                    $('#responsesSummary').html(buildResponseSummary(surveys));

                    if (!surveys || surveys.length === 0) {
                        $('#allResponsesContainer').html(
                            '<div class="card"><div class="card-body-custom text-center py-5">No surveys or responses found.</div></div>'
                        );
                        return;
                    }

                    $('#allResponsesContainer').html(surveys.map(renderSurveyAnswers).join(''));
                }

                function renderSurveyAnswers(survey) {
                    const respondents = Array.isArray(survey.respondents) ? survey.respondents : [];
                    const statusBadge = getStatusBadge(survey.status);

                    let respondentHtml = '';

                    if (respondents.length === 0) {
                        respondentHtml = '<div class="text-center py-5 text-muted-qa">No responses submitted for this survey yet.</div>';
                    } else {
                        respondentHtml = `<div class="accordion" id="respondentsAccordion_${survey.survey_id}">`;

                        respondents.forEach(function(respondent, idx) {
                            const answers = Array.isArray(respondent.answers) ? respondent.answers : [];
                            const collapseId = `respondent_${survey.survey_id}_${idx}`;

                            const answerRows = answers.length === 0 ?
                                `<tr><td colspan="3" class="text-center text-muted-qa">No answers recorded.</td></tr>` :
                                answers.map(a => `
                            <tr>
                                <td>${escapeHtml(a.question_text || 'Unknown')}</td>
                                <td>${escapeHtml(formatAnswerValue(a))}</td>
                                <td><small class="text-muted-qa">${escapeHtml(a.question_type || '-')}</small></td>
                            </tr>`).join('');

                            respondentHtml += `
                    <div class="accordion-item" id="accordion-respondent-${respondent.respondent_id}">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#${collapseId}"
                                aria-expanded="false"
                                style="padding:12px 16px;font-size:.95rem;">
                                <strong>Respondent ${idx + 1}</strong>
                                <span class="text-muted-qa" style="font-size:.85rem;margin-left:12px;">
                                    ${escapeHtml(respondent.submitted_at || 'N/A')}
                                </span>
                            </button>
                        </h2>
                        <div id="${collapseId}" class="accordion-collapse collapse"
                             data-bs-parent="#respondentsAccordion_${survey.survey_id}">
                            <div class="accordion-body" style="padding:16px;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div class="text-muted-qa small">
                                        Role: ${escapeHtml(respondent.respondent_role || 'N/A')}
                                        ${respondent.student_id  ? ` · Student ID: ${escapeHtml(String(respondent.student_id))}`  : ''}
                                        ${respondent.employee_id ? ` · Employee ID: ${escapeHtml(String(respondent.employee_id))}` : ''}
                                    </div>
                                    <button class="delete-respondent-btn" data-id="${respondent.respondent_id}"
                                        style="padding:5px 12px;font-size:.78rem;font-weight:600;
                                               border:1px solid var(--accent-orange);border-radius:var(--radius);
                                               background:transparent;color:var(--accent-orange);cursor:pointer;">
                                        <i class="fa-solid fa-trash me-1"></i> Delete Response
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-qa table-sm mb-0">
                                        <thead><tr><th>Question</th><th>Answer</th><th>Type</th></tr></thead>
                                        <tbody>${answerRows}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>`;
                        });

                        respondentHtml += '</div>';
                    }

                    return `
            <div class="card mb-4">
                <div class="card-header-custom">
                    <div class="card-title mb-1">
                        <i class="fa-solid fa-file-lines me-2"></i> ${escapeHtml(survey.title || 'Untitled Survey')}
                    </div>
                    <div class="text-muted-qa small">
                        Survey ID ${survey.survey_id} · Target: ${escapeHtml(survey.target_group || 'N/A')} ·
                        ${escapeHtml(survey.start_date || 'N/A')} → ${escapeHtml(survey.end_date || 'N/A')} ·
                        ${respondents.length} response(s) · ${statusBadge}
                    </div>
                </div>
                <div class="card-body-custom">${respondentHtml}</div>
            </div>`;
                }

                function buildResponseSummary(surveys) {
                    let totalResponses = 0;
                    let totalAnswers = 0;
                    surveys.forEach(function(s) {
                        totalResponses += s.responses_count || 0;
                        (s.respondents || []).forEach(function(r) {
                            totalAnswers += (r.answers || []).length;
                        });
                    });

                    return `
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body-custom">
                    <div class="text-muted-qa small">Surveys</div>
                    <div style="font-size:1.6rem;font-weight:700;">${surveys.length}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body-custom">
                    <div class="text-muted-qa small">Total Responses</div>
                    <div style="font-size:1.6rem;font-weight:700;">${totalResponses}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body-custom">
                    <div class="text-muted-qa small">Total Answers Loaded</div>
                    <div style="font-size:1.6rem;font-weight:700;">${totalAnswers}</div>
                </div></div>
            </div>`;
                }

                function viewResponses(surveyId) {
                    loadAllResponses().done(function() {
                        openResponseDetailsModal(surveyId);
                    }).fail(function() {
                        toast.error('Failed to load response data.');
                    });
                }

                function performDeleteRespondent(respondentId) {
                    $.ajax({
                        url: '../../backend/api/survey_responses_api.php',
                        type: 'DELETE',
                        data: JSON.stringify({
                            respondent_id: respondentId
                        }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#accordion-respondent-' + respondentId).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                toast.success('Response deleted successfully', 'Deleted');
                                responsesLoaded = false;
                                loadAllResponses(true);
                            } else {
                                toast.error(response.message || 'Could not delete response', 'Error');
                            }
                        },
                        error: function(xhr) {
                            console.error('Delete response error:', xhr.responseText);
                            toast.error('Failed to delete response', 'Error');
                        }
                    });
                }

                // ── Response details modal ───────────────────────────────────────────

                function openResponseDetailsModal(surveyId) {
                    const survey = responseSurveyCache.find(s => String(s.survey_id) === String(surveyId));
                    if (!survey) {
                        toast.error('Response details not loaded yet. Please refresh the Response tab.');
                        return;
                    }

                    $('#responseDetailsModalTitle').text(survey.title || 'Untitled Survey');
                    $('#responseDetailsModalSubtitle').text(
                        `Survey ID ${survey.survey_id} · ${survey.responses_count || 0} response(s)`
                    );

                    const questions = Array.isArray(survey.questions) ? survey.questions : [];
                    let questionsHtml = '';

                    if (questions.length === 0) {
                        questionsHtml = '<div class="text-center py-5 text-muted-qa">No questions defined for this survey.</div>';
                    } else {
                        questions.forEach(function(q, idx) {
                            const required = q.is_required ?
                                '<span class="badge" style="background-color:var(--accent-orange);color:#fff;">Required</span>' :
                                '';
                            const optionsList = Array.isArray(q.options) && q.options.length > 0 ?
                                '<div class="text-muted-qa small">Options:<br>' +
                                q.options
                                    .map(o => getOptionText(o))
                                    .filter(Boolean)
                                    .map(option => '• ' + escapeHtml(option))
                                    .join('<br>') +
                                '</div>' :
                                '';

                            questionsHtml += `
                    <div class="border rounded-3 p-3 mb-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div style="font-weight:700;flex:1;">Q${idx + 1}. ${escapeHtml(q.question_text || 'Untitled')}</div>
                            <div>${required}</div>
                        </div>
                        <div class="text-muted-qa small mb-2">Type: <strong>${escapeHtml(q.question_type || 'Unknown')}</strong></div>
                        ${optionsList}
                    </div>`;
                        });
                    }

                    $('#responseDetailsModalBody').html(questionsHtml);
                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById('responseDetailsModal')
                    ).show();
                }

                // ── Question builder helpers ──────────────────────────────────────────

                function resetSurveyModal() {
                    $('#surveyForm')[0].reset();
                    $('#survey_id').val('');
                    $('#questionsContainer').empty();
                    $('#endDateError').hide();
                    $('#activeRangeError').hide().text('');
                    $('#surveyStatus')
                        .find('#closedOption').remove().end()
                        .prop('disabled', false);
                    $('#statusHint').html(
                        '<i class="fa-solid fa-circle-info me-1"></i>' +
                        'Surveys are automatically marked <strong>Closed</strong> ' +
                        'by the system once the end date has passed.'
                    );
                }

                function addQuestion() {
                    const index = $('#questionsContainer .question-item').length;
                    let html = $('#questionTemplate').html()
                        .replace(/{index}/g, index)
                        .replace('{displayIndex}', index + 1);
                    $('#questionsContainer').append(html);
                }

                function addQuestionWithData(question, index) {
                    let html = $('#questionTemplate').html()
                        .replace(/{index}/g, index)
                        .replace('{displayIndex}', index + 1);
                    $('#questionsContainer').append(html);

                    const $item = $('#questionsContainer .question-item:last');
                    $item.find('.question_id').val(question.question_id);
                    $item.find('textarea[name*="[question_text]"]').val(question.question_text);
                    $item.find('input[name*="[is_required]"]').prop('checked', !!parseInt(question.is_required));
                    $item.find('.question-type').val(question.question_type).trigger('change');

                    if (
                        (question.question_type === 'multiple_choice' || question.question_type === 'checkbox') &&
                        Array.isArray(question.options) && question.options.length > 0
                    ) {
                        $item.find('.options-text').val(question.options.map(getOptionText).filter(Boolean).join('\n'));
                    }
                }

                function reindexQuestions() {
                    $('#questionsContainer .question-item').each(function(idx) {
                        $(this).find('.mb-3 label').first().text('Question ' + (idx + 1));
                        $(this).find('[name*="[question_text]"]').attr('name', `questions[${idx}][question_text]`);
                        $(this).find('[name*="[question_type]"]').attr('name', `questions[${idx}][question_type]`);
                        $(this).find('[name*="[is_required]"]').attr('name', `questions[${idx}][is_required]`);
                        $(this).find('.question_id').attr('name', `questions[${idx}][question_id]`);
                    });
                }

                // ── Utilities ────────────────────────────────────────────────────────

                function getStatusBadge(status) {
                    const map = {
                        Active: 'badge-qa active',
                        Draft: 'badge-qa pending',
                        Closed: 'badge-qa cancelled'
                    };
                    return `<span class="${map[status] || 'badge-qa'}">${escapeHtml(status)}</span>`;
                }

                function formatAnswerValue(answer) {
                    if (answer.display_value !== undefined && String(answer.display_value).trim() !== '') return answer.display_value;
                    if (answer.rating_value !== undefined && String(answer.rating_value).trim() !== '') return answer.rating_value;
                    if (answer.option_text) return answer.option_text;
                    if (answer.text_answer) return answer.text_answer;
                    return '-';
                }

                function getOptionText(option) {
                    if (option === null || option === undefined) return '';
                    if (typeof option === 'object') {
                        return String(option.option_text || option.text || option.value || '').trim();
                    }
                    return String(option).trim();
                }

                function escapeHtml(input) {
                    if (input === null || input === undefined) return '';
                    return String(input).replace(/[&<>]/g, m => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;'
                    } [m]));
                }

                function copySurveyLink(surveyId) {
                    const link = `${window.location.origin}${window.location.pathname.replace('surveys.php', 'survey_form.php')}?token=${surveyId}`;

                    if ($('#qrModal').length) $('#qrModal').remove();

                    $('body').append(`
            <div class="modal fade" id="qrModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:var(--radius-lg);">
                        <div class="modal-header" style="border-bottom:1px solid var(--border);padding:20px 24px;">
                            <h5 class="modal-title" style="font-weight:700;">
                                <i class="fa-solid fa-qrcode me-2"></i> Survey QR Code &amp; Link
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding:24px;">
                            <div class="text-center">
                                <div id="qrCodeContainer" style="display:flex;justify-content:center;margin-bottom:20px;"></div>
                                <div class="mb-3">
                                    <label class="form-label-qa" style="font-weight:600;">Survey Link:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control-qa" id="surveyLinkInput"
                                               value="${escapeHtml(link)}" readonly>
                                        <button class="btn-primary-qa" id="copyLinkBtn" style="padding:8px 16px;">
                                            <i class="fa-solid fa-copy"></i> Copy
                                        </button>
                                    </div>
                                    <div id="copyMessage" class="mt-2"
                                         style="display:none;color:var(--success);font-size:.875rem;">
                                        <i class="fa-solid fa-check-circle"></i> Link copied to clipboard!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border);padding:16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`);

                    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
                    modal.show();

                    setTimeout(function() {
                        if (typeof QRCode !== 'undefined') {
                            new QRCode(document.getElementById('qrCodeContainer'), {
                                text: link,
                                width: 200,
                                height: 200,
                                colorDark: '#000000',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.H,
                            });
                        } else {
                            $('#qrCodeContainer').html(`<div class="alert alert-info"><i class="fa-solid fa-qrcode"></i> ${escapeHtml(link)}</div>`);
                        }
                    }, 100);

                    $('#copyLinkBtn').off('click').on('click', function() {
                        const el = document.getElementById('surveyLinkInput');
                        el.select();
                        el.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        $('#copyMessage').fadeIn().delay(2000).fadeOut();
                        navigator.clipboard.writeText(link).catch(() => {});
                    });

                    $('#qrModal').on('hidden.bs.modal', function() {
                        $('#qrModal').remove();
                    });
                }
            </script>

        </div><!-- /qa-content -->
    </div><!-- /qa-wrapper -->

    <style>
        .btn-sm {
            margin: 0 2px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .nav-tabs-custom {
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            padding: .75rem 1.5rem;
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

        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: .5rem .75rem;
                font-size: .85rem;
            }
        }
    </style>

</body>

</html>
