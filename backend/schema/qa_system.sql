-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 05:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qa_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `qa_accreditation_tasks`
--

CREATE TABLE `qa_accreditation_tasks` (
  `task_id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `standard_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_accreditation_tasks`
--

INSERT INTO `qa_accreditation_tasks` (`task_id`, `audit_id`, `standard_id`, `title`, `due_date`, `status`, `remarks`) VALUES
(8, 5, 4, 'gd', NULL, 'Completed', NULL),
(9, 5, 7, 'sdfsd', NULL, 'Completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `qa_action_plans`
--

CREATE TABLE `qa_action_plans` (
  `plan_id` int(11) NOT NULL,
  `audit_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `resolution` text DEFAULT NULL,
  `created_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qa_audits`
--

CREATE TABLE `qa_audits` (
  `audit_id` int(11) NOT NULL,
  `audit_type` enum('Internal','External','Accreditation') NOT NULL,
  `title` varchar(150) NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_audits`
--

INSERT INTO `qa_audits` (`audit_id`, `audit_type`, `title`, `scheduled_date`, `completion_date`, `status`, `notes`) VALUES
(5, 'Internal', 'adsa', '2026-05-04', '2026-05-07', 'Scheduled', 'saddsa');

-- --------------------------------------------------------

--
-- Table structure for table `qa_indicators`
--

CREATE TABLE `qa_indicators` (
  `indicator_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `benchmark_source` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_indicators`
--

INSERT INTO `qa_indicators` (`indicator_id`, `name`, `description`, `category`, `unit`, `target_value`, `benchmark_source`) VALUES
(8, 'Student Pass Rate', 'Check Student Pass Rate every semester', 'Student Performance', 'Percentage (%)', 75.00, 'LMS Integratgfion');

-- --------------------------------------------------------

--
-- Table structure for table `qa_kpi_records`
--

CREATE TABLE `qa_kpi_records` (
  `record_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `period_year` year(4) NOT NULL,
  `period_term` varchar(20) DEFAULT NULL,
  `actual_value` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_kpi_records`
--

INSERT INTO `qa_kpi_records` (`record_id`, `indicator_id`, `period_year`, `period_term`, `actual_value`, `remarks`) VALUES
(12, 8, '2022', '1st Semester', 94.00, 'Imported from lms for 2022 1st Semester'),
(16, 8, '2026', '1st Semester', 87.50, 'Imported from lms - Field: Average Grade (%)');

-- --------------------------------------------------------

--
-- Table structure for table `qa_policies`
--

CREATE TABLE `qa_policies` (
  `policy_id` int(11) NOT NULL,
  `standard_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `content` text DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_policies`
--

INSERT INTO `qa_policies` (`policy_id`, `standard_id`, `title`, `content`, `document_url`, `created_date`, `status`) VALUES
(8, 4, 'CHED Curriculum Compliance Policy', 'All academic programs must comply with CHED prescribed curriculum standards and outcomes-based education requirements.', 'https://example.com/docs/ched-curriculum-policy.pdf', '2024-02-01', 'Active'),
(9, 4, 'CHED Faculty Qualification Policy', 'Faculty members must meet minimum qualification requirements set by CHED for teaching assignments.', 'https://example.com/docs/ched-faculty-policy.pdf', '2024-02-10', 'Active'),
(10, 5, 'ISO Documentation Control Policy', 'All documents must be properly controlled, versioned, and approved before distribution.', 'https://example.com/docs/iso-document-control.pdf', '2016-01-05', 'Active'),
(11, 5, 'ISO Internal Audit Policy', 'Internal audits must be conducted at planned intervals to ensure compliance with ISO standards.', 'https://example.com/docs/iso-audit-policy.pdf', '2016-03-20', 'Active'),
(12, 6, 'Institutional Performance Review Policy', 'Academic and administrative performance shall be reviewed every semester.', 'https://example.com/docs/institutional-performance.pdf', '2023-06-15', 'Active'),
(13, 7, 'Institutional Student Feedback Policy', 'Student feedback must be collected and analyzed for continuous improvement.', 'https://example.com/docs/student-feedback-policy.pdf', '2023-07-01', 'Active'),
(14, 4, 'Research Ethics Compliance Policy', 'All research must follow ethical guidelines and undergo ethics board approval.', 'https://example.com/docs/research-ethics.pdf', '2022-04-01', 'Archived');

-- --------------------------------------------------------

--
-- Table structure for table `qa_question_options`
--

CREATE TABLE `qa_question_options` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_question_options`
--

INSERT INTO `qa_question_options` (`option_id`, `question_id`, `option_text`, `sort_order`) VALUES
(8, 7, '3A', 0),
(9, 7, '3B', 1),
(10, 7, '3C', 2),
(11, 7, '3D', 3),
(12, 7, '3E', 4),
(13, 7, '3F', 5);

-- --------------------------------------------------------

--
-- Table structure for table `qa_reports`
--

CREATE TABLE `qa_reports` (
  `report_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `report_type` enum('KPI Summary','Audit Report','Survey Report','Accreditation','Improvement') NOT NULL,
  `period_year` year(4) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `file_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qa_standards`
--

CREATE TABLE `qa_standards` (
  `standard_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` enum('CHED','ISO','Institutional','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_standards`
--

INSERT INTO `qa_standards` (`standard_id`, `title`, `body`, `description`, `version`, `effective_date`, `status`) VALUES
(4, 'CHED Quality Assurance Guidelines', 'CHED', 'Standards set by the Commission on Higher Education for academic quality assurance in higher education institutions.', '2024-1.0', '2024-01-15', 'Active'),
(5, 'ISO 9001:2015 Quality Management', 'ISO', 'International standard for quality management systems focusing on customer satisfaction and continuous improvement.', '2015', '2015-09-23', 'Active'),
(6, 'Institutional Academic Standards', 'Institutional', 'Internal quality assurance standards used by the institution to monitor academic performance and services.', '2023-2.1', '2023-06-01', 'Active'),
(7, 'Research Compliance Standards', 'Other', 'Specialized standards for ensuring ethical compliance and research quality.', '2022-1.0', '2022-03-10', 'Archived');

-- --------------------------------------------------------

--
-- Table structure for table `qa_surveys`
--

CREATE TABLE `qa_surveys` (
  `survey_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `target_group` enum('Student','Alumni','Employer','Faculty','Staff','All') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Draft','Active','Closed') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `qr_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_surveys`
--

INSERT INTO `qa_surveys` (`survey_id`, `title`, `description`, `target_group`, `start_date`, `end_date`, `status`, `created_by`, `qr_token`) VALUES
(11, 'Professor Evaluation Survey', 'Every semester, professor evaluation survey is done', 'Student', '2026-05-04', '2026-05-07', 'Active', 3, '4e52d24be913c4f39e3f094cae34d984'),
(12, 'hghg', 'gfhfg', 'Employer', '2026-05-04', '2026-05-13', 'Active', 3, '81adf25d1e39f099bebc9928a90c2d30');

-- --------------------------------------------------------

--
-- Table structure for table `qa_survey_answers`
--

CREATE TABLE `qa_survey_answers` (
  `answer_id` int(11) NOT NULL,
  `respondent_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_id` int(11) DEFAULT NULL,
  `rating_value` tinyint(4) DEFAULT NULL,
  `text_answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qa_survey_questions`
--

CREATE TABLE `qa_survey_questions` (
  `question_id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('rating_5','rating_10','yes_no','multiple_choice','checkbox','open_ended','likert','text') NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_survey_questions`
--

INSERT INTO `qa_survey_questions` (`question_id`, `survey_id`, `question_text`, `question_type`, `is_required`, `sort_order`) VALUES
(6, 11, 'Name', 'text', 1, 0),
(7, 11, 'Section', 'checkbox', 1, 1),
(8, 11, 'How do you rate from 1-5 your experiences with your professors', 'rating_5', 1, 2),
(9, 12, 'gfhg', 'rating_5', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `qa_survey_respondents`
--

CREATE TABLE `qa_survey_respondents` (
  `respondent_id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `respondent_role` enum('Student','Alumni','Employer','Faculty','Staff') NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qa_users`
--

CREATE TABLE `qa_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','qa_officer','viewer') DEFAULT 'viewer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_users`
--

INSERT INTO `qa_users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `is_active`, `created_at`) VALUES
(3, 'admin', '$2y$10$i.tKztmu0mETtxw1kDpkZueaoKjcESw9ZMrDlXD2ls7mpTQLEqffG', 'System Administrator', 'admin@qa.local', 'admin', 1, '2026-05-03 20:55:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `qa_accreditation_tasks`
--
ALTER TABLE `qa_accreditation_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_task_audit` (`audit_id`),
  ADD KEY `fk_task_standard` (`standard_id`);

--
-- Indexes for table `qa_action_plans`
--
ALTER TABLE `qa_action_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `fk_plan_audit` (`audit_id`);

--
-- Indexes for table `qa_audits`
--
ALTER TABLE `qa_audits`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `qa_indicators`
--
ALTER TABLE `qa_indicators`
  ADD PRIMARY KEY (`indicator_id`);

--
-- Indexes for table `qa_kpi_records`
--
ALTER TABLE `qa_kpi_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `fk_kpi_indicator` (`indicator_id`);

--
-- Indexes for table `qa_policies`
--
ALTER TABLE `qa_policies`
  ADD PRIMARY KEY (`policy_id`),
  ADD KEY `fk_policy_standard` (`standard_id`);

--
-- Indexes for table `qa_question_options`
--
ALTER TABLE `qa_question_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `fk_option_question` (`question_id`);

--
-- Indexes for table `qa_reports`
--
ALTER TABLE `qa_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_user` (`generated_by`);

--
-- Indexes for table `qa_standards`
--
ALTER TABLE `qa_standards`
  ADD PRIMARY KEY (`standard_id`);

--
-- Indexes for table `qa_surveys`
--
ALTER TABLE `qa_surveys`
  ADD PRIMARY KEY (`survey_id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `fk_survey_creator` (`created_by`);

--
-- Indexes for table `qa_survey_answers`
--
ALTER TABLE `qa_survey_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `fk_ans_respondent` (`respondent_id`),
  ADD KEY `fk_ans_question` (`question_id`),
  ADD KEY `fk_ans_option` (`option_id`);

--
-- Indexes for table `qa_survey_questions`
--
ALTER TABLE `qa_survey_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `fk_question_survey` (`survey_id`);

--
-- Indexes for table `qa_survey_respondents`
--
ALTER TABLE `qa_survey_respondents`
  ADD PRIMARY KEY (`respondent_id`),
  ADD KEY `fk_resp_survey` (`survey_id`);

--
-- Indexes for table `qa_users`
--
ALTER TABLE `qa_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `qa_accreditation_tasks`
--
ALTER TABLE `qa_accreditation_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `qa_action_plans`
--
ALTER TABLE `qa_action_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qa_audits`
--
ALTER TABLE `qa_audits`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `qa_indicators`
--
ALTER TABLE `qa_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `qa_kpi_records`
--
ALTER TABLE `qa_kpi_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `qa_policies`
--
ALTER TABLE `qa_policies`
  MODIFY `policy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `qa_question_options`
--
ALTER TABLE `qa_question_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `qa_reports`
--
ALTER TABLE `qa_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qa_standards`
--
ALTER TABLE `qa_standards`
  MODIFY `standard_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `qa_surveys`
--
ALTER TABLE `qa_surveys`
  MODIFY `survey_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `qa_survey_answers`
--
ALTER TABLE `qa_survey_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qa_survey_questions`
--
ALTER TABLE `qa_survey_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `qa_survey_respondents`
--
ALTER TABLE `qa_survey_respondents`
  MODIFY `respondent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qa_users`
--
ALTER TABLE `qa_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `qa_accreditation_tasks`
--
ALTER TABLE `qa_accreditation_tasks`
  ADD CONSTRAINT `fk_task_audit` FOREIGN KEY (`audit_id`) REFERENCES `qa_audits` (`audit_id`),
  ADD CONSTRAINT `fk_task_standard` FOREIGN KEY (`standard_id`) REFERENCES `qa_standards` (`standard_id`);

--
-- Constraints for table `qa_action_plans`
--
ALTER TABLE `qa_action_plans`
  ADD CONSTRAINT `fk_plan_audit` FOREIGN KEY (`audit_id`) REFERENCES `qa_audits` (`audit_id`);

--
-- Constraints for table `qa_kpi_records`
--
ALTER TABLE `qa_kpi_records`
  ADD CONSTRAINT `fk_kpi_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `qa_indicators` (`indicator_id`);

--
-- Constraints for table `qa_policies`
--
ALTER TABLE `qa_policies`
  ADD CONSTRAINT `fk_policy_standard` FOREIGN KEY (`standard_id`) REFERENCES `qa_standards` (`standard_id`);

--
-- Constraints for table `qa_question_options`
--
ALTER TABLE `qa_question_options`
  ADD CONSTRAINT `fk_option_question` FOREIGN KEY (`question_id`) REFERENCES `qa_survey_questions` (`question_id`);

--
-- Constraints for table `qa_reports`
--
ALTER TABLE `qa_reports`
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`generated_by`) REFERENCES `qa_users` (`user_id`);

--
-- Constraints for table `qa_surveys`
--
ALTER TABLE `qa_surveys`
  ADD CONSTRAINT `fk_survey_creator` FOREIGN KEY (`created_by`) REFERENCES `qa_users` (`user_id`);

--
-- Constraints for table `qa_survey_answers`
--
ALTER TABLE `qa_survey_answers`
  ADD CONSTRAINT `fk_ans_option` FOREIGN KEY (`option_id`) REFERENCES `qa_question_options` (`option_id`),
  ADD CONSTRAINT `fk_ans_question` FOREIGN KEY (`question_id`) REFERENCES `qa_survey_questions` (`question_id`),
  ADD CONSTRAINT `fk_ans_respondent` FOREIGN KEY (`respondent_id`) REFERENCES `qa_survey_respondents` (`respondent_id`);

--
-- Constraints for table `qa_survey_questions`
--
ALTER TABLE `qa_survey_questions`
  ADD CONSTRAINT `fk_question_survey` FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`);

--
-- Constraints for table `qa_survey_respondents`
--
ALTER TABLE `qa_survey_respondents`
  ADD CONSTRAINT `fk_resp_survey` FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
