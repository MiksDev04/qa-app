<?php
/**
 * Survey Form Page – public, no login required
 * frontend/pages/survey_form.php
 */

$pageTitle = 'Survey Form';
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

  <div class="qa-page">
    <div class="container" style="max-width:800px;">
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

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>

  <script>
    const apiBase         = '../../backend/api';
    const surveyApiUrl    = `${apiBase}/survey_api.php`;
    const responseApiUrl  = `${apiBase}/survey_responses_api.php`;
    const thankYouPageUrl = 'thank_you.php';

    let currentSurvey      = null;
    let currentSurveyToken = '';
    let isSubmittingSurvey = false;
    let viewOnlyMode       = false;

    /* ── localStorage helpers ───────────────────────── */
    function respondentKey(token) {
      return 'survey_respondent_' + String(token || '').trim();
    }
    function saveRespondentId(token, id) {
      if (token && id) localStorage.setItem(respondentKey(token), String(id));
    }
    function getRespondentId(token) {
      if (!token) return null;
      return localStorage.getItem(respondentKey(token)) || null;
    }

    /* ── Boot ───────────────────────────────────────── */
    $(document).ready(function () {
      const urlParams    = new URLSearchParams(window.location.search);
      const surveyId     = urlParams.get('token');
      viewOnlyMode       = urlParams.get('view') === '1';
      currentSurveyToken = surveyId || '';

      if (!surveyId) {
        showErrorState('No survey token provided.');
        return;
      }

      if (viewOnlyMode) {
        $('#surveyTitle').text('My Response');
      }

      loadSurvey(surveyId);
    });

    /* ── Load survey ────────────────────────────────── */
    function loadSurvey(surveyId) {
      $.ajax({
        url:      surveyApiUrl + '?action=get_public&token=' + encodeURIComponent(surveyId),
        type:     'GET',
        dataType: 'json',
        success: function (response) {
          if (!response.success || !response.data) {
            showErrorState(response.message || 'The survey could not be loaded.');
            return;
          }

          currentSurvey = response.data;
          const status  = response.data.status;

          // ── Status gate: only Active surveys accept responses ────────
          if (status === 'Draft') {
            showStatusMessage('draft', response.data);
            return;
          }

          if (status === 'Closed') {
            showStatusMessage('closed', response.data);
            return;
          }

          // Active – render normally
          displaySurvey(response.data);

          if (viewOnlyMode) {
            var respondentId = getRespondentId(currentSurveyToken);
            if (respondentId) {
              loadAndFillAnswers(respondentId);
            } else {
              showNoAnswersNotice();
            }
          }
        },
        error: function () {
          showErrorState('Unable to load survey. Please refresh the page and try again.');
        }
      });
    }

    /* ── Status-aware screens ───────────────────────── */

    /**
     * Shows a friendly, non-form screen when the survey is Draft or Closed.
     * @param {'draft'|'closed'} type
     * @param {object}           survey  survey data (title, end_date, …)
     */
    function showStatusMessage(type, survey) {
      const title = escapeHtml(survey.title || 'Survey');

      $('#surveyTitle').text(title);

      if (type === 'draft') {
        $('#surveyContent').html(
          '<div class="text-center py-5">' +
            '<div style="font-size:64px;margin-bottom:16px;">🕐</div>' +
            '<h4 style="font-weight:700;margin-bottom:8px;">Survey Not Yet Available</h4>' +
            '<p class="text-muted" style="max-width:480px;margin:0 auto 24px;">' +
              'This survey is still being prepared and hasn\'t been published yet. ' +
              'Please check back later or contact the administrator.' +
            '</p>' +
            (survey.start_date
              ? '<div class="badge-qa pending" style="font-size:.85rem;padding:8px 16px;">' +
                  '<i class="fa-regular fa-calendar me-1"></i> Opens: ' + escapeHtml(survey.start_date) +
                '</div>'
              : '') +
          '</div>'
        );
        return;
      }

      if (type === 'closed') {
        $('#surveyContent').html(
          '<div class="text-center py-5">' +
            '<div style="font-size:64px;margin-bottom:16px;">🔒</div>' +
            '<h4 style="font-weight:700;margin-bottom:8px;">Survey Closed</h4>' +
            '<p class="text-muted" style="max-width:480px;margin:0 auto 24px;">' +
              'This survey is no longer accepting responses. ' +
              'Thank you for your interest!' +
            '</p>' +
            (survey.end_date
              ? '<div class="badge-qa cancelled" style="font-size:.85rem;padding:8px 16px;">' +
                  '<i class="fa-regular fa-calendar-xmark me-1"></i> Closed on: ' + escapeHtml(survey.end_date) +
                '</div>'
              : '') +
          '</div>'
        );
      }
    }

    function showErrorState(message) {
      $('#surveyTitle').text('Survey Unavailable');
      $('#surveyContent').html(
        '<div class="text-center py-5">' +
          '<i class="fa-solid fa-triangle-exclamation" style="font-size:64px;color:var(--accent-orange);"></i>' +
          '<h4 class="mt-3">Survey Not Found</h4>' +
          '<p class="text-muted">' + escapeHtml(message) + '</p>' +
        '</div>'
      );
    }

    /* ── Fetch and pre-fill saved answers (view mode) ── */
    function loadAndFillAnswers(respondentId) {
      $.ajax({
        url:      responseApiUrl + '?action=get_respondent_answers&respondent_id=' + encodeURIComponent(respondentId),
        type:     'GET',
        dataType: 'json',
        success: function (response) {
          if (response.success && response.data && response.data.answers) {
            fillAnswers(response.data.answers);
          } else {
            showNoAnswersNotice();
          }
        },
        error: showNoAnswersNotice
      });
    }

    function fillAnswers(answers) {
      var byQuestion = {};
      answers.forEach(function (a) {
        var qid = String(a.question_id);
        if (!byQuestion[qid]) byQuestion[qid] = [];
        byQuestion[qid].push(a);
      });

      Object.keys(byQuestion).forEach(function (qid) {
        var list  = byQuestion[qid];
        var first = list[0];
        var type  = first.question_type || '';

        if (type === 'rating_5' || type === 'rating_10') {
          $('input[name="q_' + qid + '"][value="' + first.rating_value + '"]').prop('checked', true);
        } else if (type === 'yes_no') {
          var val = first.text_answer || (first.option_id ? String(first.option_id) : '');
          $('input[name="q_' + qid + '"][value="' + val + '"]').prop('checked', true);
        } else if (type === 'multiple_choice') {
          $('input[name="q_' + qid + '"][value="' + String(first.option_id || '') + '"]').prop('checked', true);
        } else if (type === 'checkbox') {
          list.forEach(function (a) {
            $('input[name="q_' + qid + '[]"][value="' + String(a.option_id || '') + '"]').prop('checked', true);
          });
        } else {
          $('[name="q_' + qid + '"]').val(first.text_answer || '');
        }
      });

      disableAllInputs();
    }

    function disableAllInputs() {
      $('#surveyResponseForm input, #surveyResponseForm textarea, #surveyResponseForm select')
        .prop('disabled', true);
    }

    function showNoAnswersNotice() {
      $('#surveyContent').prepend(
        '<div class="alert alert-warning mb-4">' +
          '<i class="fa-solid fa-circle-info me-2"></i>' +
          'Your answers could not be retrieved for this session.' +
        '</div>'
      );
      disableAllInputs();
    }

    /* ── Render survey form ─────────────────────────── */
    function displaySurvey(survey) {
      var questions = Array.isArray(survey.questions) ? survey.questions : [];

      $('#surveyTitle').text(escapeHtml(survey.title || 'Survey'));

      var html =
        '<div class="mb-4">' +
          '<p class="text-muted">' + escapeHtml(survey.description || 'No description provided') + '</p>' +
          '<div class="mb-3"><small class="text-muted">' +
            '<i class="fa-regular fa-calendar"></i> ' +
            (survey.start_date ? 'Start: ' + escapeHtml(survey.start_date) : 'No start date') +
            (survey.end_date   ? ' | End: '  + escapeHtml(survey.end_date)   : '') +
          '</small></div>' +
        '</div>' +
        '<form id="surveyResponseForm" novalidate>' +
          '<input type="hidden" name="respondent_role" id="respondentRole" required>';

      if (questions.length === 0) {
        html += '<div class="alert alert-info">This survey does not have any questions yet.</div>';
      }

      questions.forEach(function (question, index) {
        var isRequired = String(question.is_required) === '1' ||
                         question.is_required === 1 ||
                         question.is_required === true;
        html +=
          '<div class="mb-4">' +
            '<label class="form-label-qa">' +
              (index + 1) + '. ' + escapeHtml(question.question_text) +
              (isRequired ? ' <span class="text-danger">*</span>' : '') +
            '</label>' +
            renderQuestionInput(question, isRequired) +
          '</div>';
      });

      html +=
        '<div class="mb-4">' +
          '<label class="form-label-qa">Your Role <span class="text-danger">*</span></label>' +
          '<input disabled id="respondentRoleSelect" class="form-control-qa" value="' + escapeHtml(survey.target_group || '') + '">' +
        '</div>';

      if (!viewOnlyMode) {
        html +=
          '<div class="d-flex justify-content-end">' +
            '<button type="submit" class="btn-primary-qa" id="surveySubmitBtn">' +
              '<i class="fa-solid fa-paper-plane"></i> Submit Survey' +
            '</button>' +
          '</div>';
      }

      html += '</form>';

      $('#surveyContent').html(html);

      if (!viewOnlyMode) {
        $('#surveyResponseForm').on('submit', function (e) {
          e.preventDefault();
          submitResponse();
        });
      }
    }

    /* ── Question renderers ─────────────────────────── */
    function renderQuestionInput(question, isRequired) {
      var req = isRequired ? 'required' : '';
      var qid = question.question_id;

      switch (question.question_type) {
        case 'text':
          return '<input type="text" class="form-control-qa" name="q_' + qid + '" ' + req + '>';

        case 'open_ended':
          return '<textarea class="form-control-qa" name="q_' + qid + '" rows="4" ' + req + '></textarea>';

        case 'rating_5':
          return '<div class="rating-group">' + [1,2,3,4,5].map(function (v) {
            return '<label class="rating-option"><input type="radio" name="q_' + qid + '" value="' + v + '" ' + req + '><span>' + v + '</span></label>';
          }).join('') + '</div>';

        case 'rating_10':
          return '<div class="rating-group">' + [1,2,3,4,5,6,7,8,9,10].map(function (v) {
            return '<label class="rating-option"><input type="radio" name="q_' + qid + '" value="' + v + '" ' + req + '><span>' + v + '</span></label>';
          }).join('') + '</div>';

        case 'yes_no':
          return (
            '<div class="form-check"><input class="form-check-input" type="radio" name="q_' + qid + '" value="yes" id="yes_' + qid + '" ' + req + '><label class="form-check-label" for="yes_' + qid + '">Yes</label></div>' +
            '<div class="form-check"><input class="form-check-input" type="radio" name="q_' + qid + '" value="no"  id="no_'  + qid + '" ' + req + '><label class="form-check-label" for="no_'  + qid + '">No</label></div>'
          );

        case 'multiple_choice':
          return (question.options || []).map(function (option) {
            return (
              '<div class="form-check">' +
                '<input class="form-check-input" type="radio" name="q_' + qid + '" value="' + option.option_id + '" ' +
                       'id="opt_' + option.option_id + '" data-option-id="' + option.option_id + '" ' + req + '>' +
                '<label class="form-check-label" for="opt_' + option.option_id + '">' + escapeHtml(option.option_text) + '</label>' +
              '</div>'
            );
          }).join('');

        case 'checkbox':
          return (question.options || []).map(function (option) {
            return (
              '<div class="form-check">' +
                '<input class="form-check-input" type="checkbox" name="q_' + qid + '[]" value="' + option.option_id + '" ' +
                       'id="chk_' + option.option_id + '" data-option-id="' + option.option_id + '">' +
                '<label class="form-check-label" for="chk_' + option.option_id + '">' + escapeHtml(option.option_text) + '</label>' +
              '</div>'
            );
          }).join('');

        default:
          return '<input type="text" class="form-control-qa" name="q_' + qid + '" ' + req + '>';
      }
    }

    /* ── Submit ─────────────────────────────────────── */
    function submitResponse() {
      if (!currentSurvey || !Array.isArray(currentSurvey.questions)) {
        toast.error('Survey data is not ready yet');
        return;
      }
      if (isSubmittingSurvey) return;

      var form           = $('#surveyResponseForm');
      var questions      = currentSurvey.questions;
      var answers        = [];
      var respondentRole = ($('#respondentRole').val() || $('#respondentRoleSelect').val() || '').trim();
      var submitBtn      = $('#surveySubmitBtn');

      form.find('.is-invalid').removeClass('is-invalid');
      var isValid = true;

      if (!respondentRole) {
        isValid = false;
        $('#respondentRoleSelect').addClass('is-invalid');
      }

      questions.forEach(function (question) {
        var fieldName  = 'q_' + question.question_id;
        var isRequired = String(question.is_required) === '1' ||
                         question.is_required === 1 ||
                         question.is_required === true;

        if (question.question_type === 'checkbox') {
          var selected = form.find('input[name="' + fieldName + '[]"]:checked');
          if (isRequired && selected.length === 0) {
            isValid = false;
            form.find('input[name="' + fieldName + '[]"]').first().addClass('is-invalid');
            return;
          }
          selected.each(function () {
            answers.push({
              question_id: question.question_id,
              answer:      $(this).val(),
              answer_type: 'checkbox',
              option_id:   $(this).data('option-id') || $(this).val()
            });
          });
          return;
        }

        if (['multiple_choice','yes_no','rating_5','rating_10'].indexOf(question.question_type) !== -1) {
          var sel   = form.find('input[name="' + fieldName + '"]:checked');
          var val   = sel.val() || '';
          if (isRequired && val === '') {
            isValid = false;
            form.find('input[name="' + fieldName + '"]').first().addClass('is-invalid');
            return;
          }
          if (val !== '') {
            var answer = {
              question_id: question.question_id,
              answer:      val,
              answer_type: (question.question_type === 'rating_5' || question.question_type === 'rating_10')
                             ? 'rating' : question.question_type
            };
            if (question.question_type === 'multiple_choice') {
              answer.option_id = sel.data('option-id') || val;
            }
            answers.push(answer);
          }
          return;
        }

        var textVal = (form.find('[name="' + fieldName + '"]').val() || '').trim();
        if (isRequired && textVal === '') {
          isValid = false;
          form.find('[name="' + fieldName + '"]').first().addClass('is-invalid');
          return;
        }
        if (textVal !== '') {
          answers.push({ question_id: question.question_id, answer: textVal, answer_type: 'text' });
        }
      });

      if (!isValid) {
        toast.error('Please fill in all required fields');
        return;
      }

      isSubmittingSurvey = true;
      submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Submitting…');

      $.ajax({
        url:         responseApiUrl,
        type:        'POST',
        contentType: 'application/json',
        dataType:    'json',
        data:        JSON.stringify({
          survey_id:       currentSurvey.survey_id,
          respondent_role: respondentRole,
          answers:         answers
        }),
        success: function (response) {
          if (response.success) {
            var respondentId = response.data && response.data.respondent_id
              ? response.data.respondent_id : null;
            if (respondentId) {
              saveRespondentId(currentSurveyToken || currentSurvey.survey_id, respondentId);
            }
            submitBtn.html('<i class="fa-solid fa-check"></i> Submitted!');
            toast.success('Survey submitted successfully!');
            setTimeout(function () {
              window.location.href = thankYouPageUrl + '?token=' + encodeURIComponent(currentSurveyToken || currentSurvey.survey_id);
            }, 1500);
          } else {
            isSubmittingSurvey = false;
            submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Submit Survey');
            toast.error(response.message || 'Error submitting survey response');
          }
        },
        error: function (xhr) {
          isSubmittingSurvey = false;
          submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Submit Survey');
          toast.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error submitting survey response');
        }
      });
    }

    /* ── Helpers ────────────────────────────────────── */
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
    .rating-option input { margin-bottom: 5px; }
    .rating-option span  { font-size: 14px; font-weight: 600; }
    .form-check          { margin-bottom: 8px; }

    #surveyResponseForm input:disabled,
    #surveyResponseForm textarea:disabled {
      opacity: 0.75;
      cursor: default;
      pointer-events: none;
    }
  </style>

</body>
</html>