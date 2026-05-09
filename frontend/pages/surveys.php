<?php

/**
 * Survey Management Page
 * frontend/pages/surveys.php
 *
 * View, create, edit, delete surveys with questions and responses
 */

session_start();

// Auth guard
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

        <!-- ── Sidebar ─────────────────────────────────────────────── -->
        <?php include '../partials/sidebar.php'; ?>

        <!-- ── Main content ─────────────────────────────────────────── -->
        <div class="qa-content">

            <!-- ── Header ───────────────────────────────────────────── -->
            <?php include '../partials/header.php'; ?>


            <div class="qa-page">
                <ul class="nav nav-tabs-custom" id="surveyPageTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="survey-tab" data-bs-toggle="tab" data-bs-target="#survey-tab-pane" type="button" role="tab" aria-controls="survey-tab-pane" aria-selected="true">
                            <i class="fa-solid fa-clipboard-list me-2"></i> Survey
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="responses-tab" data-bs-toggle="tab" data-bs-target="#responses-tab-pane" type="button" role="tab" aria-controls="responses-tab-pane" aria-selected="false">
                            <i class="fa-solid fa-comments me-2"></i> Response
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="surveyPageTabContent">
                    <div class="tab-pane fade show active" id="survey-tab-pane" role="tabpanel" aria-labelledby="survey-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-1" style="font-size: 1.5rem; font-weight: 700;">Survey Management</h2>
                                <p class="text-muted-qa">Create and manage surveys, view responses and analytics</p>
                            </div>
                            <button class="btn-primary-qa" id="createSurveyBtn">
                                <i class="fa-solid fa-plus"></i> Create Survey
                            </button>
                        </div>

                        <!-- Surveys List -->
                        <div class="card">
                            <div class="card-header-custom">
                                <div class="card-title">
                                    <i class="fa-solid fa-chart-bar me-2"></i> All Surveys
                                </div>
                                <div class="header-search" style="width: 250px;">
                                    <i class="fa-solid fa-search search-icon"></i>
                                    <input type="text" id="searchSurvey" placeholder="Search surveys..." class="form-control-qa" style="padding-left: 34px;">
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
                                                <td colspan="8" class="text-center">Loading surveys...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="responses-tab-pane" role="tabpanel" aria-labelledby="responses-tab">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                            <div>
                                <h2 class="mb-1" style="font-size: 1.5rem; font-weight: 700;">Survey Responses</h2>
                                <p class="text-muted-qa">Review every survey response and the answers submitted by respondents</p>
                            </div>
                            <button class="btn-outline-qa" id="refreshResponsesBtn" style="padding: 10px 16px;">
                                <i class="fa-solid fa-rotate-right"></i> Refresh Responses
                            </button>
                        </div>

                        <div id="responsesSummary" class="row g-3 mb-4"></div>
                        <div id="allResponsesContainer">
                            <div class="card">
                                <div class="card-body-custom text-center py-5">
                                    Loading responses...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Details Modal -->
            <div class="modal fade" id="responseDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                            <div>
                                <h5 class="modal-title mb-1" id="responseDetailsModalTitle" style="font-weight: 700;">Response Details</h5>
                                <div class="text-muted-qa" id="responseDetailsModalSubtitle" style="font-size: .83rem;"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="responseDetailsModalBody" style="padding: 24px;"></div>
                    </div>
                </div>
            </div>

            <!-- Delete Survey Confirmation Modal -->
            <div class="modal fade" id="deleteSurveyConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                            <h5 class="modal-title" style="font-weight: 700; color: var(--accent-orange);">Delete Survey</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 24px;">
                            <p class="mb-0">Are you sure you want to delete this survey? This will also delete all questions and responses. <strong>This action cannot be undone.</strong></p>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="background-color: var(--accent-orange); color: #fff; border: none; padding: 8px 16px; border-radius: var(--radius); font-weight: 600;">Delete Survey</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Survey Modal -->
            <div class="modal fade" id="surveyModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                            <h5 class="modal-title" style="font-weight: 700;" id="surveyModalTitle">Create Survey</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 24px;">
                            <form id="surveyForm">
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
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-qa">Status</label>
                                    <select class="form-control-qa" name="status" id="surveyStatus">
                                        <option value="Draft">Draft</option>
                                        <option value="Active">Active</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label-qa mb-0" style="font-size: 1rem; font-weight: 700;">Survey Questions</label>
                                    <button type="button" class="btn-outline-qa" id="addQuestionBtn" style="padding: 6px 12px;">
                                        <i class="fa-solid fa-plus"></i> Add Question
                                    </button>
                                </div>

                                <div id="questionsContainer"></div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px;">
                            <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-primary-qa" id="saveSurveyBtn">Save Survey</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Template -->
            <script id="questionTemplate" type="text/template">
                <div class="question-item card mb-3" style="background: var(--bg-main); border: 1px solid var(--border);" data-question-index="{index}">
        <div class="card-body-custom" style="padding: 16px;">
            <input type="hidden" name="questions[{index}][question_id]" class="question_id">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <label class="form-label-qa mb-0" style="font-weight: 600;">Question {displayIndex}</label>
                <button type="button" class="btn-outline-qa remove-question-btn" style="padding: 4px 8px; font-size: 0.75rem;">
                    <i class="fa-solid fa-trash"></i> Remove
                </button>
            </div>
            <div class="mb-2">
                <textarea class="form-control-qa" name="questions[{index}][question_text]" placeholder="Enter question text" rows="2" required></textarea>
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
                        <input type="checkbox" class="form-check-input" name="questions[{index}][is_required]" value="1" checked>
                        <label class="form-check-label" style="font-size: 0.85rem;">Required question</label>
                    </div>
                </div>
            </div>
            <div class="options-container" style="display: none;"></div>
        </div>
    </div>
</script>

            <script id="optionsTemplate" type="text/template">
                <div class="mt-2">
        <label class="form-label-qa" style="font-size: 0.75rem;">Options (one per line)</label>
        <textarea class="form-control-qa options-text" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
    </div>
</script>

            <!-- Scripts -->
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../assets/js/app.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                let currentSurveyId = null;
                let responsesLoaded = false;
                let responseSurveyCache = [];
                let pendingDeleteSurveyId = null;

                $(document).ready(function() {
                    loadSurveys();
                    loadAllResponses();

                    $('#createSurveyBtn').click(function() {
                        $('#surveyForm')[0].reset();
                        $('#survey_id').val('');
                        $('#questionsContainer').empty();
                        $('#surveyModalTitle').text('Create Survey');
                        $('#surveyModal').modal('show');
                        addQuestion(); // Add one empty question by default
                    });

                    $('#saveSurveyBtn').click(function() {
                        saveSurvey();
                    });

                    $('#addQuestionBtn').click(function() {
                        addQuestion();
                    });

                    $('#refreshResponsesBtn').click(function() {
                        loadAllResponses(true);
                    });

                    $('#surveyPageTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(event) {
                        const target = $(event.target).attr('id');
                        if (target === 'responses-tab' && !responsesLoaded) {
                            loadAllResponses();
                        }
                    });

                    $('#searchSurvey').on('keyup', function() {
                        loadSurveys($(this).val());
                    });

                    $(document).on('click', '.remove-question-btn', function() {
                        $(this).closest('.question-item').remove();
                        reindexQuestions();
                    });

                    $(document).on('change', '.question-type', function() {
                        const container = $(this).closest('.question-item').find('.options-container');
                        const type = $(this).val();

                        if (type === 'multiple_choice' || type === 'checkbox') {
                            container.show();
                            if (container.html() === '') {
                                container.html($('#optionsTemplate').html());
                            }
                        } else {
                            container.hide();
                            container.html('');
                        }
                    });

                    $(document).on('click', '.view-response-details', function() {
                        openResponseDetailsModal($(this).data('id'));
                    });

                    $('#confirmDeleteBtn').click(function() {
                        if (pendingDeleteSurveyId) {
                            performDeleteSurvey(pendingDeleteSurveyId);
                            pendingDeleteSurveyId = null;
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSurveyConfirmModal')).hide();
                        }
                    });

                    $('#deleteSurveyConfirmModal').on('hidden.bs.modal', function() {
                        pendingDeleteSurveyId = null;
                    });
                });

                function loadSurveys(search = '') {
                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'GET',
                        data: {
                            action: 'list',
                            search: search
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data) {
                                renderSurveysTable(response.data);
                            } else {
                                $('#surveysTableBody').html('<tr><td colspan="8" class="text-center">No surveys found</td></tr>');
                            }
                        },
                        error: function() {
                            $('#surveysTableBody').html('<tr><td colspan="8" class="text-center">Error loading surveys</td></tr>');
                        }
                    });
                }

                function renderSurveysTable(surveys) {
                    let html = '';
                    surveys.forEach(survey => {
                        const statusBadge = getStatusBadge(survey.status);
                        html += `
            <tr>
                <td><strong>${escapeHtml(survey.title)}</strong></td>
                <td>${survey.target_group}</td>
                <td>${survey.questions_count || 0}</td>
                <td>${survey.responses_count || 0}</td>
                <td>${survey.start_date || 'N/A'} → ${survey.end_date || 'N/A'}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-outline-qa btn-sm edit-survey" data-id="${survey.survey_id}" style="padding: 4px 8px; font-size: 0.75rem;">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button class="btn-outline-qa btn-sm view-responses" data-id="${survey.survey_id}" style="padding: 4px 8px; font-size: 0.75rem;">
                        <i class="fa-solid fa-chart-bar"></i>
                    </button>
                    <button class="btn-outline-qa btn-sm copy-link" data-id="${survey.survey_id}" style="padding: 4px 8px; font-size: 0.75rem;">
                        <i class="fa-solid fa-link"></i>
                    </button>
                    <button class="btn-outline-qa btn-sm delete-survey" data-id="${survey.survey_id}" style="padding: 4px 8px; font-size: 0.75rem; color: var(--accent-orange);">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
                    });

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

                function loadAllResponses(forceReload = false) {
                    if (responsesLoaded && !forceReload) {
                        return $.Deferred().resolve(responseSurveyCache).promise();
                    }

                    $('#responsesSummary').html('');
                    $('#allResponsesContainer').html(`
                        <div class="card">
                            <div class="card-body-custom text-center py-5">
                                Loading responses...
                            </div>
                        </div>
                    `);

                    return $.ajax({
                        url: '../../backend/api/survey_responses_api.php',
                        type: 'GET',
                        data: {
                            action: 'get_all_responses'
                        },
                        dataType: 'json'
                    }).done(function(response) {
                        responsesLoaded = true;
                        if (response.success && Array.isArray(response.data)) {
                            responseSurveyCache = response.data;
                            renderAllResponses(response.data);
                        } else {
                            responseSurveyCache = [];
                            renderAllResponses([]);
                        }
                    }).fail(function() {
                        responsesLoaded = true;
                        responseSurveyCache = [];
                        $('#responsesSummary').html('');
                        $('#allResponsesContainer').html(`
                            <div class="card">
                                <div class="card-body-custom text-center py-5">
                                    Error loading responses.
                                </div>
                            </div>
                        `);
                    });
                }

                function renderAllResponses(surveys) {
                    const summary = buildResponseSummary(surveys);
                    $('#responsesSummary').html(summary);

                    if (!surveys || surveys.length === 0) {
                        $('#allResponsesContainer').html(`
                            <div class="card">
                                <div class="card-body-custom text-center py-5">
                                    No surveys or responses found.
                                </div>
                            </div>
                        `);
                        return;
                    }

                    // Show all respondent answers for all surveys
                    let html = '';
                    surveys.forEach(survey => {
                        html += renderSurveyAnswers(survey);
                    });

                    $('#allResponsesContainer').html(html);
                }

                function renderSurveyAnswers(survey) {
                    const respondents = Array.isArray(survey.respondents) ? survey.respondents : [];
                    const statusBadge = getStatusBadge(survey.status);
                    const surveyId = `survey_${survey.survey_id}`;

                    let respondentHtml = '';

                    if (respondents.length === 0) {
                        respondentHtml = `
                            <div class="text-center py-5 text-muted-qa">
                                No responses submitted for this survey yet.
                            </div>
                        `;
                    } else {
                        respondentHtml = `
                            <div class="accordion" id="respondentsAccordion_${survey.survey_id}">
                        `;

                        respondents.forEach((respondent, idx) => {
                            const answers = Array.isArray(respondent.answers) ? respondent.answers : [];
                            const collapseId = `respondent_${survey.survey_id}_${idx}`;

                            let answerRows = '';
                            if (answers.length === 0) {
                                answerRows = `
                                    <tr>
                                        <td colspan="3" class="text-center text-muted-qa">No answers recorded.</td>
                                    </tr>
                                `;
                            } else {
                                answers.forEach(answer => {
                                    answerRows += `
                                        <tr>
                                            <td>${escapeHtml(answer.question_text || 'Unknown')}</td>
                                            <td>${escapeHtml(formatAnswerValue(answer))}</td>
                                            <td><small class="text-muted-qa">${escapeHtml(answer.question_type || '-')}</small></td>
                                        </tr>
                                    `;
                                });
                            }

                            respondentHtml += `
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}" style="padding: 12px 16px; font-size: 0.95rem;">
                                            <strong>Respondent ${idx + 1}</strong>
                                            <span class="text-muted-qa" style="font-size: 0.85rem; margin-left: 12px;">  ${escapeHtml(respondent.submitted_at || 'N/A')}</span>
                                        </button>
                                    </h2>
                                    <div id="${collapseId}" class="accordion-collapse collapse" data-bs-parent="#respondentsAccordion_${survey.survey_id}">
                                        <div class="accordion-body" style="padding: 16px;">
                                            <div class="text-muted-qa small mb-2">
                                               Role: ${escapeHtml(respondent.respondent_role || 'N/A')}${respondent.student_id ? ` · Student ID: ${escapeHtml(respondent.student_id)}` : ''}${respondent.employee_id ? ` · Employee ID: ${escapeHtml(respondent.employee_id)}` : ''}
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-qa table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Question</th>
                                                            <th>Answer</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${answerRows}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        respondentHtml += `</div>`;
                    }

                    return `
                        <div class="card mb-4">
                            <div class="card-header-custom">
                                <div class="card-title mb-1">
                                    <i class="fa-solid fa-file-lines me-2"></i> ${escapeHtml(survey.title || 'Untitled Survey')}
                                </div>
                                <div class="text-muted-qa small">
                                    Survey ID ${survey.survey_id} · Target: ${escapeHtml(survey.target_group || 'N/A')} · ${survey.start_date || 'N/A'} → ${survey.end_date || 'N/A'} · ${respondents.length} response(s) · ${statusBadge}
                                </div>
                            </div>
                            <div class="card-body-custom">
                                ${respondentHtml}
                            </div>
                        </div>
                    `;
                }

                function buildResponseSummary(surveys) {
                    const totalSurveys = surveys.length;
                    let totalResponses = 0;
                    let totalAnswers = 0;

                    surveys.forEach(survey => {
                        totalResponses += survey.responses_count || 0;
                        (survey.respondents || []).forEach(respondent => {
                            totalAnswers += (respondent.answers || []).length;
                        });
                    });

                    return `
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body-custom">
                                    <div class="text-muted-qa small">Surveys</div>
                                    <div style="font-size: 1.6rem; font-weight: 700;">${totalSurveys}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body-custom">
                                    <div class="text-muted-qa small">Total Responses</div>
                                    <div style="font-size: 1.6rem; font-weight: 700;">${totalResponses}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body-custom">
                                    <div class="text-muted-qa small">Total Answers Loaded</div>
                                    <div style="font-size: 1.6rem; font-weight: 700;">${totalAnswers}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                function renderSurveyResponsesCard(survey) {
                    const statusBadge = getStatusBadge(survey.status);

                    return `
                        <div class="card mb-4 response-survey-card" data-survey-id="${survey.survey_id}">
                            <div class="card-header-custom d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <div class="card-title mb-1">
                                        <i class="fa-solid fa-file-lines me-2"></i> ${escapeHtml(survey.title || 'Untitled Survey')}
                                    </div>
                                    <div class="text-muted-qa small">
                                        Survey ID ${survey.survey_id} · Target Group: ${escapeHtml(survey.target_group || 'N/A')} · ${escapeHtml(survey.start_date || 'N/A')} → ${escapeHtml(survey.end_date || 'N/A')}
                                    </div>
                                </div>
                                <div class="text-end d-flex flex-column align-items-end gap-2">
                                    <div class="mb-1">${statusBadge}</div>
                                    <div class="text-muted-qa small">${survey.responses_count || 0} response(s)</div>
                                    <button type="button" class="btn-primary-qa view-response-details" data-id="${survey.survey_id}" style="padding: 8px 14px; font-size: .875rem;">
                                        <i class="fa-solid fa-eye me-1"></i> View Response Info
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                function openResponseDetailsModal(surveyId) {
                    const survey = responseSurveyCache.find(item => String(item.survey_id) === String(surveyId));

                    if (!survey) {
                        toast.error('Response details are not loaded yet. Please refresh the Response tab.');
                        return;
                    }

                    // Compute global totals
                    const totalSurveys = responseSurveyCache.length;
                    let totalResponses = 0;
                    let totalAnswers = 0;
                    responseSurveyCache.forEach(s => {
                        totalResponses += s.responses_count || 0;
                        (s.respondents || []).forEach(r => {
                            totalAnswers += (r.answers || []).length;
                        });
                    });

                    $('#responseDetailsModalTitle').text(`${survey.title || 'Untitled Survey'}`);
                    $('#responseDetailsModalSubtitle').text(`Survey ID ${survey.survey_id} · ${survey.responses_count || 0} response(s)`);

                    // Show questions only
                    const questions = Array.isArray(survey.questions) ? survey.questions : [];
                    let questionsHtml = '';

                    if (questions.length === 0) {
                        questionsHtml = `
                            <div class="text-center py-5 text-muted-qa">
                                No questions defined for this survey.
                            </div>
                        `;
                    } else {
                        questions.forEach((q, idx) => {
                            const optionsText = Array.isArray(q.options) && q.options.length > 0 ?
                                q.options.map(opt => `• ${escapeHtml(opt.option_text || opt)}`).join('<br>') :
                                'N/A';
                            const required = q.is_required ? '<span class="badge" style="background-color: var(--accent-orange); color: white;">Required</span>' : '';

                            questionsHtml += `
                                <div class="border rounded-3 p-3 mb-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div style="font-weight: 700; flex: 1;">Q${idx + 1}. ${escapeHtml(q.question_text || 'Untitled')}</div>
                                        <div>${required}</div>
                                    </div>
                                    <div class="text-muted-qa small mb-2">Type: <strong>${escapeHtml(q.question_type || 'Unknown')}</strong></div>
                                    ${q.options && q.options.length > 0 ? `<div class="text-muted-qa small">Options:<br>${optionsText}</div>` : ''}
                                </div>
                            `;
                        });
                    }

                    $('#responseDetailsModalBody').html(questionsHtml);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('responseDetailsModal')).show();
                }

                function renderResponseDetailsModalBody(survey) {
                    const respondents = Array.isArray(survey.respondents) ? survey.respondents : [];

                    if (respondents.length === 0) {
                        return `
                            <div class="text-center py-5">
                                No responses have been submitted for this survey yet.
                            </div>
                        `;
                    }

                    let html = '';
                    respondents.forEach((respondent, index) => {
                        const answers = Array.isArray(respondent.answers) ? respondent.answers : [];

                        let answerRows = '';
                        if (answers.length === 0) {
                            answerRows = `
                                <tr>
                                    <td colspan="3" class="text-center text-muted-qa">No answers recorded for this response.</td>
                                </tr>
                            `;
                        } else {
                            answers.forEach(answer => {
                                answerRows += `
                                    <tr>
                                        <td>${escapeHtml(answer.question_text || 'Unknown question')}</td>
                                        <td>${escapeHtml(formatAnswerValue(answer))}</td>
                                        <td>${escapeHtml(answer.question_type || '-')}</td>
                                    </tr>
                                `;
                            });
                        }

                        html += `
                            <div class="border rounded-3 p-3 mb-3 bg-white">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                    <div>
                                        <div style="font-weight: 700;">Respondent ${index + 1}</div>
                                        <div class="text-muted-qa small">
                                            ID ${respondent.respondent_id} · Role: ${escapeHtml(respondent.respondent_role || 'N/A')}${respondent.student_id ? ` · Student ID: ${escapeHtml(respondent.student_id)}` : ''}${respondent.employee_id ? ` · Employee ID: ${escapeHtml(respondent.employee_id)}` : ''}
                                        </div>
                                    </div>
                                    <div class="text-muted-qa small">Submitted at ${escapeHtml(respondent.submitted_at || 'N/A')}</div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-qa table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Question</th>
                                                <th>Answer</th>
                                                <th>Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${answerRows}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    });

                    return html;
                }

                function formatAnswerValue(answer) {
                    if (answer.display_value !== undefined && answer.display_value !== null && String(answer.display_value).trim() !== '') {
                        return answer.display_value;
                    }

                    if (answer.rating_value !== null && answer.rating_value !== undefined && String(answer.rating_value).trim() !== '') {
                        return answer.rating_value;
                    }

                    if (answer.option_text) {
                        return answer.option_text;
                    }

                    if (answer.text_answer) {
                        return answer.text_answer;
                    }

                    return '-';
                }

                function getStatusBadge(status) {
                    const badges = {
                        'Active': 'badge-qa active',
                        'Draft': 'badge-qa pending',
                        'Closed': 'badge-qa cancelled'
                    };
                    return `<span class="${badges[status] || 'badge-qa'}">${status}</span>`;
                }

                function editSurvey(id) {
                    $.ajax({
                        url: '../../backend/api/survey_api.php',
                        type: 'GET',
                        data: {
                            action: 'get',
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data) {
                                const survey = response.data;
                                $('#survey_id').val(survey.survey_id);
                                $('#surveyTitle').val(survey.title);
                                $('#surveyDescription').val(survey.description);
                                $('#surveyTargetGroup').val(survey.target_group);
                                $('#surveyStartDate').val(survey.start_date);
                                $('#surveyEndDate').val(survey.end_date);
                                $('#surveyStatus').val(survey.status);

                                $('#questionsContainer').empty();

                                if (survey.questions && survey.questions.length > 0) {
                                    survey.questions.forEach((q, idx) => {
                                        addQuestionWithData(q, idx);
                                    });
                                } else {
                                    addQuestion();
                                }

                                $('#surveyModalTitle').text('Edit Survey');
                                $('#surveyModal').modal('show');
                            }
                        }
                    });
                }

                function addQuestion() {
                    const index = $('#questionsContainer .question-item').length;
                    let html = $('#questionTemplate').html();
                    html = html.replace(/{index}/g, index);
                    html = html.replace('{displayIndex}', index + 1);
                    $('#questionsContainer').append(html);
                }

                function addQuestionWithData(question, index) {
                    let html = $('#questionTemplate').html();
                    html = html.replace(/{index}/g, index);
                    html = html.replace('{displayIndex}', index + 1);
                    $('#questionsContainer').append(html);

                    const $item = $('#questionsContainer .question-item:last');
                    $item.find('.question_id').val(question.question_id);
                    $item.find('textarea[name*="[question_text]"]').val(question.question_text);
                    $item.find('.question-type').val(question.question_type);
                    if (question.is_required) {
                        $item.find('input[name*="[is_required]"]').prop('checked', true);
                    }

                    if (question.options && question.options.length > 0) {
                        $item.find('.question-type').trigger('change');
                        const optionsText = question.options.map(opt => opt.option_text).join('\n');
                        $item.find('.options-text').val(optionsText);
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

                function saveSurvey() {
                    const formData = new FormData($('#surveyForm')[0]);
                    const questions = [];

                    $('#questionsContainer .question-item').each(function() {
                        const q = {
                            question_text: $(this).find('textarea[name*="[question_text]"]').val(),
                            question_type: $(this).find('.question-type').val(),
                            is_required: $(this).find('input[name*="[is_required]"]').is(':checked') ? 1 : 0
                        };

                        const qid = $(this).find('.question_id').val();
                        if (qid) q.question_id = qid;

                        const optionsText = $(this).find('.options-text').val();
                        if (optionsText) {
                            q.options = optionsText.split('\n').filter(opt => opt.trim());
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
                        questions: questions
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
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSurveyConfirmModal')).show();
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

                function viewResponses(surveyId) {
                    // Do not switch to the Responses tab; load response data if needed and show details inline
                    loadAllResponses().done(function() {
                        openResponseDetailsModal(surveyId);
                    }).fail(function() {
                        toast.error('Failed to load response data.');
                    });
                }

                function copySurveyLink(surveyId) {
                    const link = `${window.location.origin}${window.location.pathname.replace('surveys.php', 'survey_form.php')}?token=${surveyId}`;

                    // Create modal content with QR code and link
                    const modalContent = `
                        <div class="text-center">
                            <div class="mb-4">
                                <div id="qrCodeContainer" style="display: flex; justify-content: center; margin-bottom: 20px;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-qa" style="font-weight: 600;">Survey Link:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control-qa" id="surveyLinkInput" value="${link}" readonly>
                                    <button class="btn-primary-qa" id="copyLinkBtn" style="padding: 8px 16px;">
                                        <i class="fa-solid fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div id="copyMessage" class="mt-2" style="display: none; color: var(--success); font-size: 0.875rem;">
                                    <i class="fa-solid fa-check-circle"></i> Link copied to clipboard!
                                </div>
                            </div>
                        </div>
                    `;

                    // Create and show modal
                    const modalHtml = `
                        <div class="modal fade" id="qrModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius: var(--radius-lg);">
                                    <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                                        <h5 class="modal-title" style="font-weight: 700;">
                                            <i class="fa-solid fa-qrcode me-2"></i> Survey QR Code & Link
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="padding: 24px;">
                                        ${modalContent}
                                    </div>
                                    <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px;">
                                        <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Remove existing modal if present
                    if ($('#qrModal').length) {
                        $('#qrModal').remove();
                    }

                    // Append modal to body
                    $('body').append(modalHtml);

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
                    modal.show();

                    // Generate QR code
                    setTimeout(() => {
                        if (typeof QRCode !== 'undefined') {
                            new QRCode(document.getElementById("qrCodeContainer"), {
                                text: link,
                                width: 200,
                                height: 200,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        } else {
                            // Fallback if QRCode library isn't loaded
                            $('#qrCodeContainer').html(`
                <div class="alert alert-info">
                    <i class="fa-solid fa-qrcode"></i> QR Code: ${link}
                </div>
            `);
                        }
                    }, 100);

                    // Handle copy button click
                    $('#copyLinkBtn').off('click').on('click', function() {
                        const copyInput = document.getElementById('surveyLinkInput');
                        copyInput.select();
                        copyInput.setSelectionRange(0, 99999);
                        document.execCommand('copy');

                        $('#copyMessage').fadeIn().delay(2000).fadeOut();

                        // Also try modern clipboard API
                        navigator.clipboard.writeText(link).catch(err => {
                            console.error('Could not copy text: ', err);
                        });
                    });

                    // Clean up modal when hidden
                    $('#qrModal').on('hidden.bs.modal', function() {
                        $('#qrModal').remove();
                    });
                }

                function escapeHtml(input) {
                    if (input === null || input === undefined) return '';
                    const str = String(input);
                    return str.replace(/[&<>]/g, function(m) {
                        if (m === '&') return '&amp;';
                        if (m === '<') return '&lt;';
                        if (m === '>') return '&gt;';
                        return m;
                    });
                }
            </script>

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

                @media (max-width: 768px) {
                    .nav-tabs-custom .nav-link {
                        padding: 0.5rem 0.75rem;
                        font-size: 0.85rem;
                    }
                }
            </style>