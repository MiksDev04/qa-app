-- ============================================================
-- QA System Database
-- Compatible with: MySQL Workbench / MySQL 8.0+
-- Generated: 2026-05-08
-- ============================================================

CREATE DATABASE IF NOT EXISTS `qa_system`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE `qa_system`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE: qa_users
-- ============================================================
DROP TABLE IF EXISTS `qa_users`;
CREATE TABLE `qa_users` (
  `user_id`       INT          NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) NOT NULL,
  `role`          ENUM('admin','qa_officer','viewer') DEFAULT 'viewer',
  `is_active`     TINYINT(1)   DEFAULT 1,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_users`
  (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `is_active`, `created_at`)
VALUES
  (3, 'admin',
   '$2y$10$i.tKztmu0mETtxw1kDpkZueaoKjcESw9ZMrDlXD2ls7mpTQLEqffG',
   'System Administrator', 'admin@qa.local', 'admin', 1,
   '2026-05-03 20:55:14');


-- ============================================================
-- TABLE: qa_standards
-- ============================================================
DROP TABLE IF EXISTS `qa_standards`;
CREATE TABLE `qa_standards` (
  `standard_id`   INT          NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(150) NOT NULL,
  `body`          ENUM('CHED','ISO','Institutional','Other') NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `version`       VARCHAR(20)  DEFAULT NULL,
  `effective_date` DATE        DEFAULT NULL,
  `status`        ENUM('Active','Archived') DEFAULT 'Active',
  PRIMARY KEY (`standard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_standards`
  (`standard_id`, `title`, `body`, `description`, `version`, `effective_date`, `status`)
VALUES
  (4, 'CHED Quality Assurance Guidelines', 'CHED',
   'Standards set by the Commission on Higher Education for academic quality assurance in higher education institutions.',
   '2024-1.0', '2024-01-15', 'Active'),
  (5, 'ISO 9001:2015 Quality Management', 'ISO',
   'International standard for quality management systems focusing on customer satisfaction and continuous improvement.',
   '2015', '2015-09-23', 'Active'),
  (6, 'Institutional Academic Standards', 'Institutional',
   'Internal quality assurance standards used by the institution to monitor academic performance and services.',
   '2023-2.1', '2023-06-01', 'Active'),
  (7, 'Research Compliance Standards', 'Other',
   'Specialized standards for ensuring ethical compliance and research quality.',
   '2022-1.0', '2022-03-10', 'Archived');


-- ============================================================
-- TABLE: qa_policies
-- ============================================================
DROP TABLE IF EXISTS `qa_policies`;
CREATE TABLE `qa_policies` (
  `policy_id`    INT          NOT NULL AUTO_INCREMENT,
  `standard_id`  INT          DEFAULT NULL,
  `title`        VARCHAR(150) NOT NULL,
  `content`      TEXT         DEFAULT NULL,
  `document_url` VARCHAR(255) DEFAULT NULL,
  `created_date` DATE         DEFAULT NULL,
  `status`       ENUM('Active','Archived') DEFAULT 'Active',
  PRIMARY KEY (`policy_id`),
  KEY `fk_policy_standard` (`standard_id`),
  CONSTRAINT `fk_policy_standard`
    FOREIGN KEY (`standard_id`) REFERENCES `qa_standards` (`standard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_policies`
  (`policy_id`, `standard_id`, `title`, `content`, `document_url`, `created_date`, `status`)
VALUES
  (8,  4, 'CHED Curriculum Compliance Policy',
   'All academic programs must comply with CHED prescribed curriculum standards and outcomes-based education requirements.',
   'https://example.com/docs/ched-curriculum-policy.pdf', '2024-02-01', 'Active'),
  (9,  4, 'CHED Faculty Qualification Policy',
   'Faculty members must meet minimum qualification requirements set by CHED for teaching assignments.',
   'https://example.com/docs/ched-faculty-policy.pdf', '2024-02-10', 'Active'),
  (10, 5, 'ISO Documentation Control Policy',
   'All documents must be properly controlled, versioned, and approved before distribution.',
   'https://example.com/docs/iso-document-control.pdf', '2016-01-05', 'Active'),
  (11, 5, 'ISO Internal Audit Policy',
   'Internal audits must be conducted at planned intervals to ensure compliance with ISO standards.',
   'https://example.com/docs/iso-audit-policy.pdf', '2016-03-20', 'Active'),
  (12, 6, 'Institutional Performance Review Policy',
   'Academic and administrative performance shall be reviewed every semester.',
   'https://example.com/docs/institutional-performance.pdf', '2023-06-15', 'Active'),
  (13, 7, 'Institutional Student Feedback Policy',
   'Student feedback must be collected and analyzed for continuous improvement.',
   'https://example.com/docs/student-feedback-policy.pdf', '2023-07-01', 'Active'),
  (14, 4, 'Research Ethics Compliance Policy',
   'All research must follow ethical guidelines and undergo ethics board approval.',
   'https://example.com/docs/research-ethics.pdf', '2022-04-01', 'Archived');


-- ============================================================
-- TABLE: qa_audits
-- ============================================================
DROP TABLE IF EXISTS `qa_audits`;
CREATE TABLE `qa_audits` (
  `audit_id`        INT          NOT NULL AUTO_INCREMENT,
  `audit_type`      ENUM('Internal','External','Accreditation') NOT NULL,
  `title`           VARCHAR(150) NOT NULL,
  `scheduled_date`  DATE         DEFAULT NULL,
  `completion_date` DATE         DEFAULT NULL,
  `status`          ENUM('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes`           TEXT         DEFAULT NULL,
  PRIMARY KEY (`audit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_audits`
  (`audit_id`, `audit_type`, `title`, `scheduled_date`, `completion_date`, `status`, `notes`)
VALUES
  (5, 'Internal', 'adsa', '2026-05-04', '2026-05-07', 'Scheduled', 'saddsa');


-- ============================================================
-- TABLE: qa_accreditation_tasks
-- ============================================================
DROP TABLE IF EXISTS `qa_accreditation_tasks`;
CREATE TABLE `qa_accreditation_tasks` (
  `task_id`     INT          NOT NULL AUTO_INCREMENT,
  `audit_id`    INT          NOT NULL,
  `standard_id` INT          DEFAULT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `due_date`    DATE         DEFAULT NULL,
  `status`      ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
  `remarks`     TEXT         DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  KEY `fk_task_audit`     (`audit_id`),
  KEY `fk_task_standard`  (`standard_id`),
  CONSTRAINT `fk_task_audit`
    FOREIGN KEY (`audit_id`)    REFERENCES `qa_audits`     (`audit_id`),
  CONSTRAINT `fk_task_standard`
    FOREIGN KEY (`standard_id`) REFERENCES `qa_standards`  (`standard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_accreditation_tasks`
  (`task_id`, `audit_id`, `standard_id`, `title`, `due_date`, `status`, `remarks`)
VALUES
  (8, 5, 4, 'gd',    NULL, 'Completed', NULL),
  (9, 5, 7, 'sdfsd', NULL, 'Completed', NULL);


-- ============================================================
-- TABLE: qa_action_plans
-- ============================================================
DROP TABLE IF EXISTS `qa_action_plans`;
CREATE TABLE `qa_action_plans` (
  `plan_id`      INT          NOT NULL AUTO_INCREMENT,
  `audit_id`     INT          DEFAULT NULL,
  `title`        VARCHAR(150) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `root_cause`   TEXT         DEFAULT NULL,
  `target_date`  DATE         DEFAULT NULL,
  `status`       ENUM('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `resolution`   TEXT         DEFAULT NULL,
  `created_date` DATE         DEFAULT (CURDATE()),
  PRIMARY KEY (`plan_id`),
  KEY `fk_plan_audit` (`audit_id`),
  CONSTRAINT `fk_plan_audit`
    FOREIGN KEY (`audit_id`) REFERENCES `qa_audits` (`audit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (no records)


-- ============================================================
-- TABLE: qa_indicators
-- ============================================================
DROP TABLE IF EXISTS `qa_indicators`;
CREATE TABLE `qa_indicators` (
  `indicator_id`     INT          NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL,
  `description`      TEXT         DEFAULT NULL,
  `category`         VARCHAR(80)  DEFAULT NULL,
  `unit`             VARCHAR(30)  DEFAULT NULL,
  `target_value`     DECIMAL(10,2) DEFAULT NULL,
  `benchmark_source` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`indicator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_indicators`
  (`indicator_id`, `name`, `description`, `category`, `unit`, `target_value`, `benchmark_source`)
VALUES
  (8, 'Student Pass Rate', 'Check Student Pass Rate every semester',
   'Student Performance', 'Percentage (%)', 75.00, 'LMS Integratgfion');


-- ============================================================
-- TABLE: qa_kpi_records
-- ============================================================
DROP TABLE IF EXISTS `qa_kpi_records`;
CREATE TABLE `qa_kpi_records` (
  `record_id`    INT          NOT NULL AUTO_INCREMENT,
  `indicator_id` INT          NOT NULL,
  `period_year`  YEAR         NOT NULL,
  `period_term`  VARCHAR(20)  DEFAULT NULL,
  `actual_value` DECIMAL(10,2) DEFAULT NULL,
  `remarks`      TEXT         DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `fk_kpi_indicator` (`indicator_id`),
  CONSTRAINT `fk_kpi_indicator`
    FOREIGN KEY (`indicator_id`) REFERENCES `qa_indicators` (`indicator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_kpi_records`
  (`record_id`, `indicator_id`, `period_year`, `period_term`, `actual_value`, `remarks`)
VALUES
  (12, 8, '2022', '1st Semester', 94.00,
   'Imported from lms for 2022 1st Semester'),
  (16, 8, '2026', '1st Semester', 87.50,
   'Imported from lms - Field: Average Grade (%)');


-- ============================================================
-- TABLE: qa_surveys
-- ============================================================
DROP TABLE IF EXISTS `qa_surveys`;
CREATE TABLE `qa_surveys` (
  `survey_id`    INT          NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(150) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `target_group` ENUM('Student','Alumni','Employer','Faculty','Staff','All') NOT NULL,
  `start_date`   DATE         DEFAULT NULL,
  `end_date`     DATE         DEFAULT NULL,
  `status`       ENUM('Draft','Active','Closed') DEFAULT 'Draft',
  `created_by`   INT          DEFAULT NULL,
  `qr_token`     VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`survey_id`),
  UNIQUE KEY `uq_qr_token` (`qr_token`),
  KEY `fk_survey_creator` (`created_by`),
  CONSTRAINT `fk_survey_creator`
    FOREIGN KEY (`created_by`) REFERENCES `qa_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_surveys`
  (`survey_id`, `title`, `description`, `target_group`, `start_date`, `end_date`, `status`, `created_by`, `qr_token`)
VALUES
  (11, 'Professor Evaluation Survey',
   'Every semester, professor evaluation survey is done',
   'Student', '2026-05-04', '2026-05-07', 'Active', 3,
   '4e52d24be913c4f39e3f094cae34d984'),
  (12, 'hghg', 'gfhfg',
   'Employer', '2026-05-04', '2026-05-13', 'Active', 3,
   '81adf25d1e39f099bebc9928a90c2d30');


-- ============================================================
-- TABLE: qa_survey_questions
-- ============================================================
DROP TABLE IF EXISTS `qa_survey_questions`;
CREATE TABLE `qa_survey_questions` (
  `question_id`   INT  NOT NULL AUTO_INCREMENT,
  `survey_id`     INT  NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('rating_5','rating_10','yes_no','multiple_choice',
                       'checkbox','open_ended','likert','text') NOT NULL,
  `is_required`   TINYINT(1) DEFAULT 1,
  `sort_order`    INT        DEFAULT 0,
  PRIMARY KEY (`question_id`),
  KEY `fk_question_survey` (`survey_id`),
  CONSTRAINT `fk_question_survey`
    FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_survey_questions`
  (`question_id`, `survey_id`, `question_text`, `question_type`, `is_required`, `sort_order`)
VALUES
  (6, 11, 'Name',    'text',     1, 0),
  (7, 11, 'Section', 'checkbox', 1, 1),
  (8, 11, 'How do you rate from 1-5 your experiences with your professors',
          'rating_5', 1, 2),
  (9, 12, 'gfhg',   'rating_5', 1, 0);


-- ============================================================
-- TABLE: qa_question_options
-- ============================================================
DROP TABLE IF EXISTS `qa_question_options`;
CREATE TABLE `qa_question_options` (
  `option_id`   INT          NOT NULL AUTO_INCREMENT,
  `question_id` INT          NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `sort_order`  INT          DEFAULT 0,
  PRIMARY KEY (`option_id`),
  KEY `fk_option_question` (`question_id`),
  CONSTRAINT `fk_option_question`
    FOREIGN KEY (`question_id`) REFERENCES `qa_survey_questions` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qa_question_options`
  (`option_id`, `question_id`, `option_text`, `sort_order`)
VALUES
  ( 8, 7, '3A', 0),
  ( 9, 7, '3B', 1),
  (10, 7, '3C', 2),
  (11, 7, '3D', 3),
  (12, 7, '3E', 4),
  (13, 7, '3F', 5);


-- ============================================================
-- TABLE: qa_survey_respondents
-- ============================================================
DROP TABLE IF EXISTS `qa_survey_respondents`;
CREATE TABLE `qa_survey_respondents` (
  `respondent_id`   INT  NOT NULL AUTO_INCREMENT,
  `survey_id`       INT  NOT NULL,
  `respondent_role` ENUM('Student','Alumni','Employer','Faculty','Staff') NOT NULL,
  `student_id`      INT  DEFAULT NULL,
  `employee_id`     INT  DEFAULT NULL,
  `submitted_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`respondent_id`),
  KEY `fk_resp_survey` (`survey_id`),
  CONSTRAINT `fk_resp_survey`
    FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (no records)


-- ============================================================
-- TABLE: qa_survey_answers
-- ============================================================
DROP TABLE IF EXISTS `qa_survey_answers`;
CREATE TABLE `qa_survey_answers` (
  `answer_id`     INT        NOT NULL AUTO_INCREMENT,
  `respondent_id` INT        NOT NULL,
  `question_id`   INT        NOT NULL,
  `option_id`     INT        DEFAULT NULL,
  `rating_value`  TINYINT(4) DEFAULT NULL,
  `text_answer`   TEXT       DEFAULT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `fk_ans_respondent` (`respondent_id`),
  KEY `fk_ans_question`   (`question_id`),
  KEY `fk_ans_option`     (`option_id`),
  CONSTRAINT `fk_ans_respondent`
    FOREIGN KEY (`respondent_id`) REFERENCES `qa_survey_respondents` (`respondent_id`),
  CONSTRAINT `fk_ans_question`
    FOREIGN KEY (`question_id`)   REFERENCES `qa_survey_questions`   (`question_id`),
  CONSTRAINT `fk_ans_option`
    FOREIGN KEY (`option_id`)     REFERENCES `qa_question_options`   (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (no records)


-- ============================================================
-- TABLE: qa_reports
-- ============================================================
DROP TABLE IF EXISTS `qa_reports`;
CREATE TABLE `qa_reports` (
  `report_id`    INT          NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(150) NOT NULL,
  `report_type`  ENUM('KPI Summary','Audit Report','Survey Report',
                      'Accreditation','Improvement') NOT NULL,
  `period_year`  YEAR         DEFAULT NULL,
  `generated_by` INT          DEFAULT NULL,
  `generated_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `file_url`     VARCHAR(255) DEFAULT NULL,
  `notes`        TEXT         DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `fk_report_user` (`generated_by`),
  CONSTRAINT `fk_report_user`
    FOREIGN KEY (`generated_by`) REFERENCES `qa_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (no records)


-- ============================================================
-- Fix AUTO_INCREMENT counters to match original data
-- ============================================================
ALTER TABLE `qa_users`                AUTO_INCREMENT = 4;
ALTER TABLE `qa_standards`            AUTO_INCREMENT = 12;
ALTER TABLE `qa_policies`             AUTO_INCREMENT = 15;
ALTER TABLE `qa_audits`               AUTO_INCREMENT = 6;
ALTER TABLE `qa_accreditation_tasks`  AUTO_INCREMENT = 10;
ALTER TABLE `qa_action_plans`         AUTO_INCREMENT = 1;
ALTER TABLE `qa_indicators`           AUTO_INCREMENT = 17;
ALTER TABLE `qa_kpi_records`          AUTO_INCREMENT = 17;
ALTER TABLE `qa_surveys`              AUTO_INCREMENT = 13;
ALTER TABLE `qa_survey_questions`     AUTO_INCREMENT = 10;
ALTER TABLE `qa_question_options`     AUTO_INCREMENT = 14;
ALTER TABLE `qa_survey_respondents`   AUTO_INCREMENT = 1;
ALTER TABLE `qa_survey_answers`       AUTO_INCREMENT = 1;
ALTER TABLE `qa_reports`              AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Done. All tables and records restored.
-- ============================================================
