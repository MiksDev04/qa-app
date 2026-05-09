<?php
/**
 * Dashboard Page
 * frontend/pages/dashboard.php
 *
 * Main QA overview — stat cards, recent activity table,
 * quick actions. Content is loaded via AJAX from backend/api/qa/*.php
 */

session_start();

// Auth guard
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Survey Form';

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


    <div class="qa-page">
        <div class="container" style="max-width: 800px;">
            <div class="card">
                <div class="card-header-custom">
                    <h5 class="card-title mb-0" id="surveyTitle">Loading Survey...</h5>
                </div>
                <div class="card-body-custom" id="surveyContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3">Loading survey...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>



    <script>
        const apiBase = '../../backend/api';
        const surveyApiUrl = `${apiBase}/survey_api.php`;
        const responseApiUrl = `${apiBase}/survey_responses_api.php`;
        const surveysPageUrl = 'surveys.php';

        let currentSurvey = null;

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const surveyId = urlParams.get('token');

            if (!surveyId) {
                toast.error('Survey not found');
                setTimeout(() => window.location.href = surveysPageUrl, 2000);
                return;
            }

            loadSurvey(surveyId);
        });

        function loadSurvey(surveyId) {
            $.ajax({
                url: `${surveyApiUrl}?action=get_public&token=${encodeURIComponent(surveyId)}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        currentSurvey = response.data;
                        displaySurvey(response.data);
                        return;
                    }

                    $('#surveyContent').html(`
                        <div class="text-center py-5">
                            <i class="fa-solid fa-exclamation-triangle" style="font-size: 64px; color: var(--accent-orange);"></i>
                            <h4 class="mt-3">Survey not available</h4>
                            <p class="text-muted">${escapeHtml(response.message || 'The survey could not be loaded.')}</p>
                            <button class="btn-primary-qa mt-3" onclick="window.location.href='${surveysPageUrl}'">
                                Back to Surveys
                            </button>
                        </div>
                    `);
                    toast.error(response.message || 'Survey not available');
                },
                error: function() {
                    $('#surveyContent').html(`
                        <div class="text-center py-5">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 64px; color: var(--accent-orange);"></i>
                            <h4 class="mt-3">Unable to load survey</h4>
                            <p class="text-muted">Please refresh the page and try again.</p>
                            <button class="btn-primary-qa mt-3" onclick="window.location.reload()">
                                Retry
                            </button>
                        </div>
                    `);
                    toast.error('Error loading survey');
                }
            });
        }

        function displaySurvey(survey) {
            $('#surveyTitle').text(survey.title);

            const questions = Array.isArray(survey.questions) ? survey.questions : [];

            let html = `
                <div class="mb-4">
                    <p class="text-muted">${escapeHtml(survey.description || 'No description provided')}</p>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fa-regular fa-calendar"></i>
                            ${survey.start_date ? `Start: ${survey.start_date}` : 'No start date'}
                            ${survey.end_date ? ` | End: ${survey.end_date}` : ''}
                        </small>
                    </div>
                </div>
                <form id="surveyResponseForm" novalidate>
                    <input type="hidden" name="respondent_role" id="respondentRole" required>
            `;

            if (questions.length === 0) {
                html += `
                    <div class="alert alert-info">
                        This survey does not have any questions yet.
                    </div>
                `;
            }

            questions.forEach((question, index) => {
                const isRequired = String(question.is_required) === '1' || question.is_required === 1 || question.is_required === true;

                html += `
                    <div class="mb-4">
                        <label class="form-label-qa">
                            ${index + 1}. ${escapeHtml(question.question_text)}
                            ${isRequired ? '<span class="text-danger">*</span>' : ''}
                        </label>
                        ${renderQuestionInput(question, isRequired)}
                    </div>
                `;
            });

            html += `
                    <div class="mb-4">
                        <label class="form-label-qa">Your Role <span class="text-danger">*</span></label>
                        <input disabled id="respondentRoleSelect" class="form-control-qa" value="${survey.target_group}"></input>
                        
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn-outline-qa" onclick="window.location.href='${surveysPageUrl}'">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary-qa">
                            <i class="fa-solid fa-paper-plane"></i> Submit Survey
                        </button>
                    </div>
                </form>
            `;

            $('#surveyContent').html(html);

            $('#surveyResponseForm').on('submit', function(e) {
                e.preventDefault();
                submitResponse();
            });
        }

        function renderQuestionInput(question, isRequired) {
            const requiredAttr = isRequired ? 'required' : '';

            switch (question.question_type) {
                case 'text':
                    return `<input type="text" class="form-control-qa" name="q_${question.question_id}" ${requiredAttr}>`;
                case 'open_ended':
                    return `<textarea class="form-control-qa" name="q_${question.question_id}" rows="4" ${requiredAttr}></textarea>`;
                case 'rating_5':
                    return `<div class="rating-group">${[1, 2, 3, 4, 5].map(function(value) {
                        return `
                            <label class="rating-option">
                                <input type="radio" name="q_${question.question_id}" value="${value}" ${requiredAttr}>
                                <span>${value}</span>
                            </label>
                        `;
                    }).join('')}</div>`;
                case 'rating_10':
                    return `<div class="rating-group">${Array.from({ length: 10 }, function(_, index) {
                        const value = index + 1;
                        return `
                            <label class="rating-option">
                                <input type="radio" name="q_${question.question_id}" value="${value}" ${requiredAttr}>
                                <span>${value}</span>
                            </label>
                        `;
                    }).join('')}</div>`;
                case 'yes_no':
                    return `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q_${question.question_id}" value="yes" id="yes_${question.question_id}" ${requiredAttr}>
                            <label class="form-check-label" for="yes_${question.question_id}">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q_${question.question_id}" value="no" id="no_${question.question_id}" ${requiredAttr}>
                            <label class="form-check-label" for="no_${question.question_id}">No</label>
                        </div>
                    `;
                case 'multiple_choice':
                    return (question.options || []).map(function(option) {
                        return `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="q_${question.question_id}" value="${option.option_id}" id="opt_${option.option_id}" data-option-id="${option.option_id}" ${requiredAttr}>
                                <label class="form-check-label" for="opt_${option.option_id}">${escapeHtml(option.option_text)}</label>
                            </div>
                        `;
                    }).join('');
                case 'checkbox':
                    return (question.options || []).map(function(option) {
                        return `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="q_${question.question_id}[]" value="${option.option_id}" id="chk_${option.option_id}" data-option-id="${option.option_id}">
                                <label class="form-check-label" for="chk_${option.option_id}">${escapeHtml(option.option_text)}</label>
                            </div>
                        `;
                    }).join('');
                default:
                    return `<input type="text" class="form-control-qa" name="q_${question.question_id}" ${requiredAttr}>`;
            }
        }

        function submitResponse() {
            if (!currentSurvey || !Array.isArray(currentSurvey.questions)) {
                toast.error('Survey data is not ready yet');
                return;
            }

            const form = $('#surveyResponseForm');
            const questions = currentSurvey.questions;
            const answers = [];
            const respondentRole = ($('#respondentRole').val() || $('#respondentRoleSelect').val() || '').trim();

            form.find('.is-invalid').removeClass('is-invalid');

            let isValid = true;

            if (!respondentRole) {
                isValid = false;
                $('#respondentRoleSelect').addClass('is-invalid');
            }

            questions.forEach(function(question) {
                const fieldName = `q_${question.question_id}`;
                const isRequired = String(question.is_required) === '1' || question.is_required === 1 || question.is_required === true;

                if (question.question_type === 'checkbox') {
                    const selected = form.find(`input[name="${fieldName}[]"]:checked`);

                    if (isRequired && selected.length === 0) {
                        isValid = false;
                        form.find(`input[name="${fieldName}[]"]`).first().addClass('is-invalid');
                        return;
                    }

                    selected.each(function() {
                        answers.push({
                            question_id: question.question_id,
                            answer: $(this).val(),
                            answer_type: 'checkbox',
                            option_id: $(this).data('option-id') || $(this).val()
                        });
                    });

                    return;
                }

                if (question.question_type === 'multiple_choice' || question.question_type === 'yes_no' || question.question_type === 'rating_5' || question.question_type === 'rating_10') {
                    const selected = form.find(`input[name="${fieldName}"]:checked`);
                    const value = selected.val() || '';

                    if (isRequired && value === '') {
                        isValid = false;
                        form.find(`input[name="${fieldName}"]`).first().addClass('is-invalid');
                        return;
                    }

                    if (value !== '') {
                        const answer = {
                            question_id: question.question_id,
                            answer: value,
                            answer_type: (question.question_type === 'rating_5' || question.question_type === 'rating_10') ? 'rating' : question.question_type
                        };

                        if (question.question_type === 'multiple_choice') {
                            answer.option_id = selected.data('option-id') || value;
                        }

                        answers.push(answer);
                    }

                    return;
                }

                const value = (form.find(`[name="${fieldName}"]`).val() || '').trim();

                if (isRequired && value === '') {
                    isValid = false;
                    form.find(`[name="${fieldName}"]`).first().addClass('is-invalid');
                    return;
                }

                if (value !== '') {
                    answers.push({
                        question_id: question.question_id,
                        answer: value,
                        answer_type: 'text'
                    });
                }
            });

            if (!isValid) {
                toast.error('Please fill in all required fields');
                return;
            }

            const formData = {
                survey_id: currentSurvey.survey_id,
                respondent_role: respondentRole,
                answers: answers
            };

            $.ajax({
                url: responseApiUrl,
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        toast.success('Survey response submitted successfully!');
                        setTimeout(function() {
                            window.location.href = surveysPageUrl;
                        }, 2000);
                    } else {
                        toast.error(response.message || 'Error submitting survey response');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toast.error(response?.message || 'Error submitting survey response');
                }
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }
    </script>

    <style>
        .rating-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rating-option {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }

        .rating-option input {
            margin-bottom: 5px;
        }

        .rating-option span {
            font-size: 14px;
            font-weight: 600;
        }

        .form-check {
            margin-bottom: 8px;
        }
    </style>
</body>

</html>