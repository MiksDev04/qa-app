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
                                        <th>ID</th>
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

            <!-- View Responses Modal -->
            <div class="modal fade" id="responsesModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content" style="border-radius: var(--radius-lg);">
                        <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                            <h5 class="modal-title" id="responsesModalTitle">Survey Responses</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="responsesModalBody" style="max-height: 70vh; overflow-y: auto;">
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

                $(document).ready(function() {
                    loadSurveys();

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
                <td>${survey.survey_id}</td>
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
                    if (confirm('Are you sure you want to delete this survey? This will also delete all questions and responses.')) {
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
                            }
                        });
                    }
                }

                function viewResponses(surveyId) {
                    $.ajax({
                        url: '../../backend/api/survey_responses_api.php',
                        type: 'GET',
                        data: {
                            action: 'get_responses',
                            survey_id: surveyId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data) {
                                displayResponses(response.data);
                            } else {
                                displayResponses(null, 'No responses yet');
                            }
                        }
                    });
                }

                function displayResponses(responses, message = null) {
                    let html = '';

                    if (message || !responses || responses.length === 0) {
                        html = `<div class="text-center py-5">${message || 'No responses found'}</div>`;
                    } else {
                        html = '<div class="table-responsive"><table class="table-qa"><thead><tr>';

                        // Build headers
                        if (responses[0] && responses[0].answers) {
                            html += '<th>Respondent</th><th>Role</th><th>Submitted At</th>';
                            responses[0].answers.forEach(answer => {
                                html += `<th>${escapeHtml(answer.question_text.substring(0, 50))}...</th>`;
                            });
                            html += '</tr></thead><tbody>';

                            // Build rows
                            responses.forEach(resp => {
                                html += `<tr>
                    <td>${resp.respondent_id}</td>
                    <td>${resp.respondent_role}</td>
                    <td>${resp.submitted_at}</td>`;

                                resp.answers.forEach(answer => {
                                    let answerText = '';
                                    if (answer.rating_value) answerText = answer.rating_value;
                                    else if (answer.text_answer) answerText = answer.text_answer.substring(0, 100);
                                    else if (answer.option_text) answerText = answer.option_text;
                                    else answerText = '-';
                                    html += `<td>${escapeHtml(answerText)}</td>`;
                                });
                                html += `</tr>`;
                            });
                            html += '</tbody></table></div>';
                        }
                    }

                    $('#responsesModalBody').html(html);
                    $('#responsesModalTitle').text('Survey Responses');
                    $('#responsesModal').modal('show');
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
            </style>