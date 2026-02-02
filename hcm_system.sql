-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 30, 2025 at 01:01 AM
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
-- Database: `hcm_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowance_types`
--

CREATE TABLE `allowance_types` (
  `id` int(11) NOT NULL,
  `allowance_code` varchar(20) NOT NULL,
  `allowance_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_taxable` tinyint(1) DEFAULT 1,
  `calculation_type` enum('Fixed','Percentage','Formula') DEFAULT 'Fixed',
  `default_amount` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allowance_types`
--

INSERT INTO `allowance_types` (`id`, `allowance_code`, `allowance_name`, `description`, `is_taxable`, `calculation_type`, `default_amount`, `is_active`, `created_at`) VALUES
(1, 'MEAL', 'Meal Allowance', NULL, 0, 'Fixed', 0.00, 1, '2025-09-14 04:43:15'),
(2, 'TRANSPORT', 'Transportation Allowance', NULL, 0, 'Fixed', 0.00, 1, '2025-09-14 04:43:15'),
(3, 'COMM', 'Communication Allowance', NULL, 1, 'Fixed', 0.00, 1, '2025-09-14 04:43:15'),
(4, 'CLOTHING', 'Clothing Allowance', NULL, 1, 'Fixed', 0.00, 1, '2025-09-14 04:43:15'),
(5, 'OVERTIME', 'Overtime Pay', NULL, 1, 'Formula', 0.00, 1, '2025-09-14 04:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `total_hours` decimal(4,2) DEFAULT 0.00,
  `regular_hours` decimal(4,2) DEFAULT 0.00,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `late_minutes` int(11) DEFAULT 0,
  `undertime_minutes` int(11) DEFAULT 0,
  `status` enum('Present','Absent','Late','Half Day','On Leave') DEFAULT 'Present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `employee_id`, `attendance_date`, `time_in`, `time_out`, `break_start`, `break_end`, `total_hours`, `regular_hours`, `overtime_hours`, `late_minutes`, `undertime_minutes`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-03-01', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(2, 2, '2024-03-01', '08:30:00', '18:30:00', NULL, NULL, 9.00, 8.00, 1.00, 30, 0, 'Late', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, 3, '2024-03-01', '09:00:00', '18:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(4, 4, '2024-03-01', '08:00:00', '19:00:00', NULL, NULL, 10.00, 8.00, 2.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 5, '2024-03-01', '08:15:00', '17:15:00', NULL, NULL, 8.00, 8.00, 0.00, 15, 0, 'Late', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 1, '2024-03-04', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(7, 2, '2024-03-04', '08:00:00', '19:00:00', NULL, NULL, 10.00, 8.00, 2.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(8, 3, '2024-03-04', '09:00:00', '18:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(9, 4, '2024-03-04', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(10, 5, '2024-03-04', NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0, 0, 'Absent', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(26, 1, '2024-01-15', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(27, 2, '2024-01-15', '08:30:00', '17:30:00', NULL, NULL, 8.00, 8.00, 0.00, 30, 0, 'Late', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(28, 3, '2024-01-15', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(29, 1, '2024-01-20', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(30, 2, '2024-01-20', NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0, 0, 'Absent', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(31, 1, '2024-02-10', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(32, 2, '2024-02-10', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(33, 3, '2024-02-10', '08:30:00', '17:30:00', NULL, NULL, 8.00, 8.00, 0.00, 30, 0, 'Late', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(34, 1, '2024-04-15', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(35, 2, '2024-04-15', '09:00:00', '18:00:00', NULL, NULL, 8.00, 8.00, 0.00, 60, 0, 'Late', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(36, 1, '2024-05-10', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(37, 2, '2024-05-10', '08:00:00', '19:00:00', NULL, NULL, 10.00, 8.00, 2.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(38, 1, '2024-12-15', '08:00:00', '17:00:00', NULL, NULL, 8.00, 8.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(39, 2, '2024-12-15', '08:15:00', '17:15:00', NULL, NULL, 8.00, 8.00, 0.00, 15, 0, 'Late', NULL, '2025-09-15 16:03:37', '2025-09-15 16:03:37'),
(40, 1, '2025-09-15', '18:44:43', '18:44:46', NULL, NULL, 0.00, 0.00, 0.00, 644, 0, 'Late', '[2025-09-15 18:55:40] (general) - admin:\ngood', '2025-09-15 16:44:43', '2025-09-15 16:55:40'),
(41, 1, '2025-09-16', '08:00:00', NULL, NULL, NULL, 0.00, 0.00, 0.00, 0, 0, 'Present', NULL, '2025-09-15 16:46:07', '2025-09-15 16:46:07'),
(42, 2, '2025-09-16', '08:30:00', '17:30:00', NULL, NULL, 9.00, 8.00, 1.00, 30, 0, 'Late', '[2025-09-16 08:30:00] (explanation) - admin: Employee was late due to traffic on main highway. Approved by supervisor.', '2025-09-15 16:46:07', '2025-09-15 16:54:44'),
(43, 3, '2025-09-16', '08:00:00', '17:00:00', NULL, NULL, 9.00, 8.00, 1.00, 0, 0, 'Present', '[2025-09-16 08:30:00] (explanation) - admin: Employee was late due to traffic on main highway. Approved by supervisor.', '2025-09-15 16:46:07', '2025-09-15 16:54:44'),
(45, 1, '2025-09-29', '22:42:35', '22:42:41', NULL, NULL, 0.00, 0.00, 0.00, 882, 0, 'Late', NULL, '2025-09-29 20:42:35', '2025-09-29 20:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensations`
--

CREATE TABLE `compensations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `position_title` varchar(255) NOT NULL,
  `plan_type` enum('Salary','Bonus','Allowance') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `compensations`
--

INSERT INTO `compensations` (`id`, `employee_id`, `position_title`, `plan_type`, `amount`, `effective_date`, `remarks`, `created_at`, `updated_at`) VALUES
(5, 12, 'Finance Manager', 'Bonus', 0.00, '2025-09-21', 'rytyy', '2025-09-29 22:21:46', '2025-09-29 22:39:04'),
(8, 11, 'Marketing Specialist', 'Bonus', 43.00, '2025-09-14', 'gfrh', '2025-09-29 22:32:16', '2025-09-29 22:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `deduction_types`
--

CREATE TABLE `deduction_types` (
  `id` int(11) NOT NULL,
  `deduction_code` varchar(20) NOT NULL,
  `deduction_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `deduction_category` enum('Tax','Government','Company','Loan','Other') DEFAULT 'Company',
  `calculation_type` enum('Fixed','Percentage','Formula') DEFAULT 'Fixed',
  `default_amount` decimal(10,2) DEFAULT 0.00,
  `is_mandatory` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deduction_types`
--

INSERT INTO `deduction_types` (`id`, `deduction_code`, `deduction_name`, `description`, `deduction_category`, `calculation_type`, `default_amount`, `is_mandatory`, `is_active`, `created_at`) VALUES
(1, 'SSS', 'Social Security System', NULL, 'Government', 'Fixed', 0.00, 1, 1, '2025-09-14 04:43:15'),
(2, 'PHILHEALTH', 'PhilHealth', NULL, 'Government', 'Fixed', 0.00, 1, 1, '2025-09-14 04:43:15'),
(3, 'PAGIBIG', 'Pag-IBIG Fund', NULL, 'Government', 'Fixed', 0.00, 1, 1, '2025-09-14 04:43:15'),
(4, 'WITHHOLDING_TAX', 'Withholding Tax', NULL, 'Tax', 'Fixed', 0.00, 1, 1, '2025-09-14 04:43:15'),
(5, 'LATE', 'Late Deduction', NULL, 'Company', 'Fixed', 0.00, 0, 1, '2025-09-14 04:43:15'),
(6, 'ABSENT', 'Absences', NULL, 'Company', 'Fixed', 0.00, 0, 1, '2025-09-14 04:43:15'),
(7, 'CASH_ADVANCE', 'Cash Advance', NULL, 'Loan', 'Fixed', 0.00, 0, 1, '2025-09-14 04:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `parent_dept_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `budget_allocation` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `parent_dept_id`, `manager_id`, `budget_allocation`, `is_active`, `created_at`) VALUES
(1, 'CORP', 'Corporate Office', NULL, NULL, 5000000.00, 1, '2025-09-14 05:47:43'),
(2, 'IT', 'Information Technology', 1, NULL, 800000.00, 1, '2025-09-14 05:47:43'),
(3, 'HR', 'Human Resources', 1, NULL, 500000.00, 1, '2025-09-14 05:47:43'),
(4, 'FIN', 'Finance & Accounting', 1, NULL, 600000.00, 1, '2025-09-14 05:47:43'),
(5, 'MKT', 'Marketing', 1, NULL, 700000.00, 1, '2025-09-14 05:47:43'),
(6, 'OPS', 'Operations', 1, NULL, 1200000.00, 1, '2025-09-14 05:47:43'),
(7, 'SALES', 'Sales', 1, NULL, 900000.00, 1, '2025-09-14 05:47:43'),
(8, 'LEGAL', 'Legal Affairs', 1, NULL, 300000.00, 1, '2025-09-14 05:47:43'),
(9, 'ADMIN', 'Administration', 1, NULL, 400000.00, 1, '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('M','F','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Philippines',
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `employment_status` enum('Active','Inactive','Terminated','On Leave') DEFAULT 'Active',
  `employee_type` enum('Regular','Contractual','Probationary','Part-time') DEFAULT 'Regular',
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `government_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`government_ids`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `marital_status`, `address`, `city`, `state`, `zip_code`, `country`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `hire_date`, `termination_date`, `employment_status`, `employee_type`, `department_id`, `position_id`, `supervisor_id`, `profile_picture`, `government_ids`, `created_at`, `updated_at`) VALUES
(1, 'EMP-001', 1, 'Robert', 'Bob', 'Sentry', 'robert.johnson@company.com', '+63917-123-4567', '1975-03-15', 'M', 'Married', '123 Makati Ave, Makati City', 'Makati City', 'Metro Manila', '1226', 'Philippines', 'Maria Johnson', '+63917-765-4321', 'Spouse', '2020-01-15', '2025-09-30', 'Terminated', 'Regular', 1, 15, NULL, NULL, '{\"sss\":\"123456789\",\"philhealth\":\"PH123456789\",\"pagibig\":\"PG123456789\",\"tin\":\"123-456-789-000\"}', '2025-09-14 05:47:43', '2025-09-29 20:45:46'),
(2, 'EMP-002', 2, 'John', 'Michael', 'Doe', 'john.doe@company.com', '+63917-234-5678', '1985-07-22', 'M', 'Single', '456 Ortigas Ave, Pasig City', 'Pasig City', 'Metro Manila', '1605', 'Philippines', 'Jane Doe', '+63917-876-5432', '', '2021-03-01', NULL, 'Active', 'Regular', 2, 3, NULL, NULL, '{\"sss\":\"234567890\",\"philhealth\":\"PH234567890\",\"pagibig\":\"PG234567890\",\"tin\":\"234-567-890-000\"}', '2025-09-14 05:47:43', '2025-09-15 05:03:49'),
(3, 'EMP-003', 3, 'Jane', 'Marie', 'Smith', 'jane.smith@company.com', '+63917-345-6789', '1990-11-08', 'F', 'Married', '789 BGC Taguig City', 'Taguig City', 'Metro Manila', '1634', 'Philippines', 'Mark Smith', '+63917-987-6543', 'Spouse', '2021-06-15', NULL, 'Active', 'Regular', 2, 1, NULL, NULL, '{\"sss\":\"345678901\",\"philhealth\":\"PH345678901\",\"pagibig\":\"PG345678901\",\"tin\":\"345-678-901-000\"}', '2025-09-14 05:47:43', '2025-09-15 05:03:31'),
(4, 'EMP-004', NULL, 'Michael', 'David', 'Brown', 'michael.brown@company.com', '+63917-456-7890', '1988-02-28', 'M', 'Single', '321 Quezon Ave, Quezon City', 'Quezon City', 'Metro Manila', '1103', 'Philippines', 'Sarah Brown', '+63917-098-7654', 'Mother', '2022-01-10', NULL, 'Active', 'Regular', 2, 2, NULL, NULL, '{\"sss\": \"456789012\", \"philhealth\": \"PH456789012\", \"pagibig\": \"PG456789012\", \"tin\": \"456-789-012-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 'EMP-005', 4, 'Sarah', 'Lynn', 'Wilson', 'sarah.wilson@company.com', '+63917-567-8901', '1987-09-12', 'F', 'Married', '654 Manila Ave, Manila', 'Manila', 'Metro Manila', '1000', 'Philippines', 'James Wilson', '+63917-109-8765', 'Spouse', '2020-08-20', NULL, 'Active', 'Regular', 3, 6, NULL, NULL, '{\"sss\": \"567890123\", \"philhealth\": \"PH567890123\", \"pagibig\": \"PG567890123\", \"tin\": \"567-890-123-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 'EMP-006', NULL, 'Emily', 'Grace', 'Davis', 'emily.davis@company.com', '+63917-678-9012', '1992-05-30', 'F', 'Single', '987 Alabang, Muntinlupa', 'Muntinlupa', 'Metro Manila', '1770', 'Philippines', 'Robert Davis', '+63917-210-9876', 'Father', '2021-11-01', NULL, 'Active', 'Regular', 3, 4, NULL, NULL, '{\"sss\": \"678901234\", \"philhealth\": \"PH678901234\", \"pagibig\": \"PG678901234\", \"tin\": \"678-901-234-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(7, 'EMP-007', NULL, 'David', 'Andrew', 'Miller', 'david.miller@company.com', '+63917-789-0123', '1989-12-03', 'M', 'Married', '147 Marikina Heights', 'Marikina City', 'Metro Manila', '1800', 'Philippines', 'Lisa Miller', '+63917-321-0987', 'Spouse', '2022-02-15', NULL, 'Active', 'Regular', 3, 5, NULL, NULL, '{\"sss\": \"789012345\", \"philhealth\": \"PH789012345\", \"pagibig\": \"PG789012345\", \"tin\": \"789-012-345-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(8, 'EMP-008', NULL, 'Lisa', 'Catherine', 'Taylor', 'lisa.taylor@company.com', '+63917-890-1234', '1986-04-18', 'F', 'Single', '258 San Juan City', 'San Juan City', 'Metro Manila', '1500', 'Philippines', 'Mary Taylor', '+63917-432-1098', 'Mother', '2020-12-01', NULL, 'Active', 'Regular', 4, 9, NULL, NULL, '{\"sss\": \"890123456\", \"philhealth\": \"PH890123456\", \"pagibig\": \"PG890123456\", \"tin\": \"890-123-456-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(9, 'EMP-009', NULL, 'James', 'Robert', 'Anderson', 'james.anderson@company.com', '+63917-901-2345', '1984-08-25', 'M', 'Married', '369 Paranaque City', 'Paranaque City', 'Metro Manila', '1700', 'Philippines', 'Jennifer Anderson', '+63917-543-2109', 'Spouse', '2021-04-12', NULL, 'Active', 'Regular', 4, 7, NULL, NULL, '{\"sss\": \"901234567\", \"philhealth\": \"PH901234567\", \"pagibig\": \"PG901234567\", \"tin\": \"901-234-567-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(10, 'EMP-010', NULL, 'Jennifer', 'Nicole', 'Thomas', 'jennifer.thomas@company.com', '+63917-012-3456', '1991-10-14', 'F', 'Single', '741 Las Pinas City', 'Las Pinas City', 'Metro Manila', '1740', 'Philippines', 'Patricia Thomas', '+63917-654-3210', 'Mother', '2022-07-01', NULL, 'Active', 'Regular', 4, 8, NULL, NULL, '{\"sss\": \"012345678\", \"philhealth\": \"PH012345678\", \"pagibig\": \"PG012345678\", \"tin\": \"012-345-678-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(11, 'EMP-011', NULL, 'Christopher', 'Paul', 'Jackson', 'chris.jackson@company.com', '+63917-123-4560', '1988-06-07', 'M', 'Married', '852 Caloocan City', 'Caloocan City', 'Metro Manila', '1400', 'Philippines', 'Amanda Jackson', '+63917-765-4320', 'Spouse', '2021-09-20', NULL, 'Active', 'Regular', 5, 11, NULL, NULL, '{\"sss\": \"123450678\", \"philhealth\": \"PH123450678\", \"pagibig\": \"PG123450678\", \"tin\": \"123-450-678-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(12, 'EMP-012', NULL, 'Amanda', 'Rose', 'White', 'amanda.white@company.com', '+63917-234-5601', '1993-01-19', 'F', 'Single', '963 Malabon City', 'Malabon City', 'Metro Manila', '1470', 'Philippines', 'Helen White', '+63917-876-5431', 'Mother', '2022-03-15', '2025-09-16', 'Terminated', 'Regular', 5, 10, NULL, NULL, '{\"sss\": \"234506789\", \"philhealth\": \"PH234506789\", \"pagibig\": \"PG234506789\", \"tin\": \"234-506-789-000\"}', '2025-09-14 05:47:43', '2025-09-16 04:16:28'),
(13, 'EMP-013', NULL, 'Matthew', 'Charles', 'Harris', 'matthew.harris@company.com', '+63917-345-6012', '1985-11-02', 'M', 'Married', '147 Valenzuela City', 'Valenzuela City', 'Metro Manila', '1440', 'Philippines', 'Rebecca Harris', '+63917-987-6542', 'Spouse', '2020-05-10', NULL, 'Active', 'Regular', 6, 13, NULL, NULL, '{\"sss\": \"345067890\", \"philhealth\": \"PH345067890\", \"pagibig\": \"PG345067890\", \"tin\": \"345-067-890-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(14, 'EMP-014', NULL, 'Rebecca', 'Michelle', 'Clark', 'rebecca.clark@company.com', '+63917-456-0123', '1987-03-26', 'F', 'Single', '258 Navotas City', 'Navotas City', 'Metro Manila', '1485', 'Philippines', 'William Clark', '+63917-098-7653', 'Father', '2021-08-05', NULL, 'Active', 'Regular', 6, 12, NULL, NULL, '{\"sss\": \"450678901\", \"philhealth\": \"PH450678901\", \"pagibig\": \"PG450678901\", \"tin\": \"450-678-901-000\"}', '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(15, 'EMP-015', NULL, 'Daniel', 'Joseph', 'Rodriguez', 'daniel.rodriguez@company.com', '+63917-567-0134', '1990-07-11', 'M', 'Married', '369 Mandaluyong City', 'Mandaluyong City', 'Metro Manila', '1550', 'Philippines', 'Maria Rodriguez', '+63917-109-8764', 'Spouse', '2021-12-01', NULL, 'Active', 'Regular', 7, 14, NULL, NULL, '{\"sss\":\"567089012\",\"philhealth\":\"PH567089012\",\"pagibig\":\"PG567089012\",\"tin\":\"567-089-012-000\"}', '2025-09-14 05:47:43', '2025-09-15 05:04:09'),
(16, 'EMP-016', NULL, 'Maria', 'Elizabeth', 'Lewis', 'maria.lewis@company.com', '+63917-678-0145', '1989-09-23', 'F', 'Single', '741 Pasay City', 'Pasay City', 'Metro Manila', '1300', 'Philippines', 'Carlos Lewis', '+63917-210-9875', 'Brother', '2022-01-20', '2025-09-15', 'Terminated', 'Regular', 7, 13, NULL, NULL, '{\"sss\": \"678090123\", \"philhealth\": \"PH678090123\", \"pagibig\": \"PG678090123\", \"tin\": \"678-090-123-000\"}', '2025-09-14 05:47:43', '2025-09-15 04:51:26'),
(17, 'EMP-TEST-TERMINATED', NULL, 'Test', NULL, 'Terminated', 'test.terminated@company.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Philippines', NULL, NULL, NULL, '2023-01-15', '2025-09-15', 'Terminated', 'Regular', 2, 1, NULL, NULL, NULL, '2025-09-14 11:57:02', '2025-09-15 04:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `employee_allowances`
--

CREATE TABLE `employee_allowances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `allowance_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_allowances`
--

INSERT INTO `employee_allowances` (`id`, `employee_id`, `allowance_type_id`, `amount`, `effective_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, 1, 1, 5000.00, '2020-01-15', '2025-09-14', 0, '2025-09-14 05:47:43'),
(2, 2, 1, 3000.00, '2021-03-01', '2025-09-14', 0, '2025-09-14 05:47:43'),
(3, 3, 1, 2000.00, '2021-06-15', '2025-09-14', 0, '2025-09-14 05:47:43'),
(4, 4, 1, 2000.00, '2022-01-10', NULL, 1, '2025-09-14 05:47:43'),
(5, 5, 1, 3000.00, '2020-08-20', NULL, 1, '2025-09-14 05:47:43'),
(6, 6, 1, 2000.00, '2021-11-01', NULL, 1, '2025-09-14 05:47:43'),
(7, 7, 1, 2000.00, '2022-02-15', NULL, 1, '2025-09-14 05:47:43'),
(8, 8, 1, 3000.00, '2020-12-01', NULL, 1, '2025-09-14 05:47:43'),
(9, 9, 1, 2000.00, '2021-04-12', NULL, 1, '2025-09-14 05:47:43'),
(10, 10, 1, 2000.00, '2022-07-01', NULL, 1, '2025-09-14 05:47:43'),
(11, 11, 1, 3000.00, '2021-09-20', NULL, 1, '2025-09-14 05:47:43'),
(12, 12, 1, 2000.00, '2022-03-15', '2025-09-16', 0, '2025-09-14 05:47:43'),
(13, 13, 1, 3000.00, '2020-05-10', NULL, 1, '2025-09-14 05:47:43'),
(14, 14, 1, 2500.00, '2021-08-05', NULL, 1, '2025-09-14 05:47:43'),
(15, 15, 1, 3000.00, '2021-12-01', '2025-09-14', 0, '2025-09-14 05:47:43'),
(16, 16, 1, 2000.00, '2022-01-20', '2025-09-14', 0, '2025-09-14 05:47:43'),
(17, 1, 2, 8000.00, '2020-01-15', '2025-09-14', 0, '2025-09-14 05:47:43'),
(18, 2, 2, 5000.00, '2021-03-01', '2025-09-14', 0, '2025-09-14 05:47:43'),
(19, 5, 2, 5000.00, '2020-08-20', NULL, 1, '2025-09-14 05:47:43'),
(20, 8, 2, 5000.00, '2020-12-01', NULL, 1, '2025-09-14 05:47:43'),
(21, 11, 2, 4000.00, '2021-09-20', NULL, 1, '2025-09-14 05:47:43'),
(22, 13, 2, 5000.00, '2020-05-10', NULL, 1, '2025-09-14 05:47:43'),
(23, 15, 2, 4000.00, '2021-12-01', '2025-09-14', 0, '2025-09-14 05:47:43'),
(24, 1, 3, 3000.00, '2020-01-15', '2025-09-14', 0, '2025-09-14 05:47:43'),
(25, 2, 3, 2000.00, '2021-03-01', '2025-09-14', 0, '2025-09-14 05:47:43'),
(26, 5, 3, 2000.00, '2020-08-20', NULL, 1, '2025-09-14 05:47:43'),
(27, 8, 3, 2000.00, '2020-12-01', NULL, 1, '2025-09-14 05:47:43'),
(28, 11, 3, 1500.00, '2021-09-20', NULL, 1, '2025-09-14 05:47:43'),
(29, 13, 3, 2000.00, '2020-05-10', NULL, 1, '2025-09-14 05:47:43'),
(30, 15, 3, 1500.00, '2021-12-01', '2025-09-14', 0, '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `employee_compensation`
--

CREATE TABLE `employee_compensation` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `salary_grade_id` int(11) NOT NULL,
  `current_step` int(11) DEFAULT 1,
  `basic_salary` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `change_reason` varchar(100) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_compensation`
--

INSERT INTO `employee_compensation` (`id`, `employee_id`, `salary_grade_id`, `current_step`, `basic_salary`, `effective_date`, `end_date`, `change_reason`, `approved_by`, `notes`, `is_active`, `created_at`) VALUES
(1, 1, 8, 3, 200000.00, '2020-01-15', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(2, 2, 6, 4, 110000.00, '2021-03-01', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(3, 3, 3, 2, 40000.00, '2021-06-15', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(4, 4, 4, 1, 52000.00, '2022-01-10', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(5, 5, 6, 3, 95000.00, '2020-08-20', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(6, 6, 3, 2, 38000.00, '2021-11-01', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(7, 7, 2, 3, 31000.00, '2022-02-15', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(8, 8, 6, 4, 105000.00, '2020-12-01', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(9, 9, 3, 3, 30000.00, '2025-09-30', NULL, NULL, NULL, ' Updated via API on 2025-09-15 13:18:44 Updated via API on 2025-09-15 13:18:59 Updated via API on 2025-09-15 13:23:05 Updated via API on 2025-09-30 05:00:11', 1, '2025-09-14 05:47:43'),
(10, 10, 3, 4, 48000.00, '2022-07-01', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(11, 11, 5, 2, 78000.00, '2021-09-20', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(12, 12, 2, 1, 27000.00, '2022-03-15', '2025-09-16', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(13, 13, 6, 2, 100000.00, '2020-05-10', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(14, 14, 4, 3, 60000.00, '2021-08-05', NULL, NULL, NULL, NULL, 1, '2025-09-14 05:47:43'),
(15, 15, 5, 3, 82000.00, '2021-12-01', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(16, 16, 2, 2, 29000.00, '2022-01-20', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 05:47:43'),
(17, 2, 6, 4, 110000.00, '2025-09-14', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 07:23:09'),
(18, 2, 6, 4, 110000.00, '2025-09-14', '2025-09-14', NULL, NULL, NULL, 0, '2025-09-14 07:23:31'),
(19, 3, 3, 2, 49999.99, '2025-09-15', NULL, NULL, NULL, NULL, 1, '2025-09-15 05:03:31'),
(20, 2, 6, 4, 110000.00, '2025-09-15', NULL, NULL, NULL, NULL, 1, '2025-09-15 05:03:49'),
(21, 1, 8, 3, 200000.00, '2025-09-15', '2025-09-30', NULL, NULL, NULL, 0, '2025-09-15 05:03:58'),
(22, 15, 5, 3, 82000.00, '2025-09-15', NULL, NULL, NULL, NULL, 1, '2025-09-15 05:04:09');

-- --------------------------------------------------------

--
-- Table structure for table `employee_dependents`
--

CREATE TABLE `employee_dependents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `dependent_name` varchar(100) NOT NULL,
  `relationship` enum('Spouse','Child','Parent','Sibling','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('M','F','Other') DEFAULT NULL,
  `is_beneficiary` tinyint(1) DEFAULT 0,
  `beneficiary_percentage` decimal(5,2) DEFAULT 0.00,
  `is_hmo_covered` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_dependents`
--

INSERT INTO `employee_dependents` (`id`, `employee_id`, `dependent_name`, `relationship`, `date_of_birth`, `gender`, `is_beneficiary`, `beneficiary_percentage`, `is_hmo_covered`, `created_at`) VALUES
(1, 1, 'Maria Johnson', 'Spouse', '1978-05-20', 'F', 1, 60.00, 1, '2025-09-14 05:47:43'),
(2, 1, 'Robert Jr. Johnson', 'Child', '2005-08-15', 'M', 1, 40.00, 1, '2025-09-14 05:47:43'),
(3, 5, 'James Wilson', 'Spouse', '1985-12-10', 'M', 1, 100.00, 1, '2025-09-14 05:47:43'),
(4, 13, 'Rebecca Harris', 'Spouse', '1987-01-15', 'F', 1, 50.00, 1, '2025-09-14 05:47:43'),
(5, 13, 'Michael Harris', 'Child', '2010-06-20', 'M', 1, 25.00, 1, '2025-09-14 05:47:43'),
(6, 13, 'Sarah Harris', 'Child', '2012-09-12', 'F', 1, 25.00, 1, '2025-09-14 05:47:43'),
(7, 1, 'API Test Child', 'Child', '2010-01-01', 'M', 0, 0.00, 1, '2025-09-16 01:30:17'),
(8, 2, 'JEjay', 'Child', '2001-03-15', 'M', 1, 30.00, 1, '2025-09-16 01:36:25');

-- --------------------------------------------------------

--
-- Table structure for table `employee_insurance`
--

CREATE TABLE `employee_insurance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `insurance_plan_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `effective_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Cancelled','Suspended') DEFAULT 'Active',
  `employee_premium` decimal(10,2) NOT NULL,
  `employer_premium` decimal(10,2) NOT NULL,
  `dependents_count` int(11) DEFAULT 0,
  `beneficiary_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`beneficiary_info`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_insurance`
--

INSERT INTO `employee_insurance` (`id`, `employee_id`, `insurance_plan_id`, `enrollment_date`, `effective_date`, `termination_date`, `status`, `employee_premium`, `employer_premium`, `dependents_count`, `beneficiary_info`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2020-01-15', '2020-02-01', NULL, 'Active', 1100.00, 4400.00, 2, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(2, 2, 1, '2021-03-01', '2021-04-01', NULL, 'Active', 700.00, 2800.00, 0, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, 5, 2, '2020-08-20', '2020-09-01', NULL, 'Active', 1100.00, 4400.00, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(4, 8, 1, '2020-12-01', '2021-01-01', NULL, 'Active', 700.00, 2800.00, 0, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 11, 3, '2021-09-20', '2021-10-01', NULL, 'Active', 500.00, 2000.00, 0, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 13, 2, '2020-05-10', '2020-06-01', NULL, 'Active', 1100.00, 4400.00, 3, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(7, 1, 1, '2024-09-01', '2024-09-15', NULL, 'Active', 1000.00, 1500.00, 0, NULL, '2025-09-15 10:53:44', '2025-09-15 10:53:44'),
(8, 2, 2, '2024-09-05', '2024-09-15', NULL, 'Active', 720.00, 1080.00, 0, NULL, '2025-09-15 10:53:44', '2025-09-15 10:53:44'),
(9, 3, 3, '2024-09-10', '2024-10-01', NULL, '', 1200.00, 2000.00, 0, NULL, '2025-09-15 10:53:44', '2025-09-15 10:53:44'),
(10, 1, 4, '2024-08-15', '2024-09-01', NULL, 'Active', 0.00, 800.00, 0, NULL, '2025-09-15 10:53:44', '2025-09-15 10:53:44'),
(11, 1, 6, '2025-09-15', '2025-09-15', NULL, 'Active', 720.00, 1080.00, 0, '{\"primary_beneficiary\":{\"name\":\"\",\"relationship\":\"\",\"percentage\":100}}', '2025-09-15 12:37:09', '2025-09-15 12:37:09');

-- --------------------------------------------------------

--
-- Table structure for table `employee_leaves`
--

CREATE TABLE `employee_leaves` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(4,1) NOT NULL,
  `reason` text DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `applied_date` date NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_leaves`
--

INSERT INTO `employee_leaves` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `emergency_contact`, `status`, `applied_date`, `approved_by`, `approved_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '2024-03-15', '2024-03-17', 3.0, 'Family vacation', NULL, 'Approved', '2024-03-01', 2, '2024-03-02', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(2, 4, 2, '2024-03-20', '2024-03-22', 3.0, 'Medical checkup', NULL, 'Approved', '2024-03-10', 2, '2024-03-11', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, 6, 1, '2024-04-01', '2024-04-05', 5.0, 'Personal matters', NULL, 'Approved', '2024-03-20', 1, '2025-09-16', '', '2025-09-14 05:47:43', '2025-09-15 23:49:48'),
(4, 7, 3, '2024-03-25', '2024-03-25', 1.0, 'Family emergency', NULL, 'Approved', '2024-03-25', 5, '2024-03-25', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 9, 1, '2024-04-10', '2024-04-12', 3.0, 'Rest and relaxation', NULL, 'Approved', '2024-03-25', 8, '2024-03-26', NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 9, 1, '2025-09-16', '2025-09-20', 5.0, 'kapoy', NULL, 'Rejected', '2025-09-16', 1, '2025-09-16', 'no', '2025-09-15 23:54:12', '2025-09-15 23:54:26'),
(7, 9, 6, '2025-09-16', '2025-09-18', 3.0, '123', NULL, 'Rejected', '2025-09-16', 1, '2025-09-16', 'Yes', '2025-09-15 23:56:22', '2025-09-15 23:56:37'),
(11, 2, 3, '2025-09-16', '2025-09-18', 3.0, '12341', '1234567891', 'Rejected', '2025-09-16', 1, '2025-09-16', 'NO reason', '2025-09-16 00:12:51', '2025-09-16 00:15:56'),
(12, 2, 3, '2025-09-16', '2025-09-18', 3.0, '123', '1234567891', 'Pending', '2025-09-16', NULL, NULL, NULL, '2025-09-16 00:16:28', '2025-09-16 00:16:28'),
(13, 3, 3, '2025-09-17', '2025-09-19', 3.0, '12321', '1234567891', 'Pending', '2025-09-16', NULL, NULL, NULL, '2025-09-16 00:17:41', '2025-09-16 00:17:41'),
(14, 10, 4, '2025-09-16', '2025-10-24', 39.0, '123', '1234567891', 'Pending', '2025-09-16', NULL, NULL, NULL, '2025-09-16 00:19:58', '2025-09-16 00:19:58'),
(15, 8, 5, '2025-09-17', '2025-09-20', 4.0, '123', '1234567891', 'Pending', '2025-09-16', NULL, NULL, NULL, '2025-09-16 00:23:56', '2025-09-16 00:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `employment_history`
--

CREATE TABLE `employment_history` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `change_reason` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_plans`
--

CREATE TABLE `insurance_plans` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `plan_code` varchar(20) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_type` enum('Individual','Family','Dependent') DEFAULT 'Individual',
  `coverage_amount` decimal(12,2) DEFAULT NULL,
  `monthly_premium` decimal(10,2) NOT NULL,
  `employer_contribution` decimal(10,2) DEFAULT 0.00,
  `employee_contribution` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `benefits_coverage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits_coverage`)),
  `is_active` tinyint(1) DEFAULT 1,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_plans`
--

INSERT INTO `insurance_plans` (`id`, `provider_id`, `plan_code`, `plan_name`, `plan_type`, `coverage_amount`, `monthly_premium`, `employer_contribution`, `employee_contribution`, `description`, `benefits_coverage`, `is_active`, `effective_date`, `expiry_date`, `created_at`) VALUES
(1, 1, 'MAXI-EXEC', 'Maxicare Executive Plan', 'Individual', 500000.00, 3500.00, 2800.00, 700.00, NULL, NULL, 1, '2024-01-01', NULL, '2025-09-14 05:47:43'),
(2, 1, 'MAXI-FAMILY', 'Maxicare Family Plan', 'Family', 500000.00, 5500.00, 4400.00, 1100.00, NULL, NULL, 1, '2024-01-01', NULL, '2025-09-14 05:47:43'),
(3, 2, 'MEDI-GOLD', 'Medicard Gold Plan', 'Individual', 300000.00, 2500.00, 2000.00, 500.00, NULL, NULL, 1, '2024-01-01', NULL, '2025-09-14 05:47:43'),
(4, 3, 'PHIL-TERM', 'PhilamLife Term Life', 'Individual', 1000000.00, 1200.00, 800.00, 400.00, NULL, NULL, 1, '2024-01-01', NULL, '2025-09-14 05:47:43'),
(5, 1, 'MAXI_PRIME', 'Maxicare Prime', 'Individual', 150000.00, 2500.00, 1500.00, 1000.00, 'Comprehensive healthcare coverage with nationwide network', '{\"benefits\": [\"Inpatient\", \"Outpatient\", \"Emergency\", \"Pharmacy\", \"Dental\", \"Optical\"]}', 1, '2024-01-01', NULL, '2025-09-15 10:53:32'),
(6, 1, 'MAXI_BASIC', 'Maxicare Basic', 'Dependent', 100000.00, 2500.00, 1080.00, 720.00, 'Basic healthcare coverage for employees', '{\"benefits\": [\"Inpatient\", \"Outpatient\", \"Emergency\", \"Pharmacy\"]}', 1, '2024-01-01', NULL, '2025-09-15 10:53:32'),
(7, 2, 'MEDI_GOLD', 'Medicard Gold', 'Family', 200000.00, 3200.00, 2000.00, 1200.00, 'Premium family healthcare plan', '{\"benefits\": [\"Inpatient\", \"Outpatient\", \"Emergency\", \"Maternity\", \"Dental\", \"Optical\"]}', 1, '2024-01-01', NULL, '2025-09-15 10:53:32'),
(8, 3, 'PHIL_LIFE', 'PhilamLife Group Term', 'Individual', 500000.00, 800.00, 800.00, 0.00, 'Group term life insurance with accidental death benefit', '{\"benefits\": [\"Life Insurance\", \"Accidental Death\", \"Total Disability\"]}', 1, '2024-01-01', NULL, '2025-09-15 10:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_providers`
--

CREATE TABLE `insurance_providers` (
  `id` int(11) NOT NULL,
  `provider_code` varchar(20) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `provider_type` enum('HMO','Life Insurance','Accident Insurance','Other') NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_providers`
--

INSERT INTO `insurance_providers` (`id`, `provider_code`, `provider_name`, `provider_type`, `contact_person`, `contact_email`, `contact_phone`, `address`, `is_active`, `created_at`) VALUES
(1, 'MAXICARE', 'Maxicare Healthcare Corporation', 'HMO', 'John Santos', 'john.santos@maxicare.com.ph', '+632-8888-MAXI', NULL, 1, '2025-09-14 05:47:43'),
(2, 'MEDICARD', 'Medicard Philippines Inc.', 'HMO', 'Maria Cruz', 'maria.cruz@medicard.com.ph', '+632-8687-9999', NULL, 1, '2025-09-14 05:47:43'),
(3, 'PHILAM', 'PhilamLife', 'Life Insurance', 'Robert Tan', 'robert.tan@philamlife.com', '+632-8845-9000', NULL, 1, '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `leave_documents`
--

CREATE TABLE `leave_documents` (
  `id` int(11) NOT NULL,
  `leave_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_documents`
--

INSERT INTO `leave_documents` (`id`, `leave_id`, `file_name`, `original_name`, `file_size`, `file_type`, `uploaded_at`) VALUES
(1, 14, 'leave_doc_68c8ad2ee8e89_1757981998.png', 'earth.png', 40934, 'image/png', '2025-09-16 00:19:58'),
(2, 15, 'leave_doc_68c8ae1c9c1f2_1757982236.jpg', 'ESTUDYANTE.jpg', 808286, 'image/jpeg', '2025-09-16 00:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL,
  `leave_code` varchar(20) NOT NULL,
  `leave_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `max_days_per_year` int(11) DEFAULT 0,
  `is_paid` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `leave_code`, `leave_name`, `description`, `max_days_per_year`, `is_paid`, `requires_approval`, `is_active`, `created_at`) VALUES
(1, 'VL', 'Vacation Leave', NULL, 15, 1, 1, 1, '2025-09-14 04:43:15'),
(2, 'SL', 'Sick Leave', NULL, 15, 1, 1, 1, '2025-09-14 04:43:15'),
(3, 'EL', 'Emergency Leave', NULL, 3, 1, 1, 1, '2025-09-14 04:43:15'),
(4, 'ML', 'Maternity Leave', NULL, 105, 1, 1, 1, '2025-09-14 04:43:15'),
(5, 'PL', 'Paternity Leave', NULL, 7, 1, 1, 1, '2025-09-14 04:43:15'),
(6, 'LWOP', 'Leave Without Pay', NULL, 0, 0, 1, 1, '2025-09-14 04:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_audit_log`
--

CREATE TABLE `payroll_audit_log` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_audit_log`
--

INSERT INTO `payroll_audit_log` (`id`, `employee_id`, `action`, `old_status`, `new_status`, `reason`, `created_by`, `created_at`) VALUES
(1, 9, 'Status Changed', 'Pending', 'Approved', 'Test approval', 1, '2025-09-15 05:29:52'),
(2, 9, 'Status Changed', 'Approved', 'Approved', 'Payroll approved by manager', 1, '2025-09-15 05:31:18'),
(3, 9, 'Status Changed', 'Approved', 'Paid', NULL, 1, '2025-09-15 05:31:28'),
(4, 4, 'Status Changed', 'Pending', 'Rejected', 'Pait pa ang company', 1, '2025-09-15 05:31:54'),
(5, 4, 'Status Changed', 'Rejected', 'Emailed', NULL, 1, '2025-09-15 05:31:58'),
(6, 6, 'Status Changed', 'Pending', 'Rejected', 'Sorry Trabaho lang', 1, '2025-09-15 05:32:27');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL,
  `payroll_record_id` int(11) NOT NULL,
  `deduction_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_earnings`
--

CREATE TABLE `payroll_earnings` (
  `id` int(11) NOT NULL,
  `payroll_record_id` int(11) NOT NULL,
  `earning_type` varchar(50) NOT NULL,
  `earning_code` varchar(20) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_taxable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL,
  `period_code` varchar(20) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date NOT NULL,
  `period_type` enum('Monthly','Semi-monthly','Bi-weekly','Weekly') DEFAULT 'Monthly',
  `status` enum('Draft','Processing','Approved','Paid','Closed') DEFAULT 'Draft',
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `period_code`, `period_name`, `start_date`, `end_date`, `pay_date`, `period_type`, `status`, `total_gross`, `total_deductions`, `total_net`, `processed_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, '2024-01', 'January 2024', '2024-01-01', '2024-01-31', '2024-02-05', 'Monthly', 'Paid', NULL, NULL, NULL, 4, 1, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(2, '2024-02', 'February 2024', '2024-02-01', '2024-02-29', '2024-03-05', 'Monthly', 'Paid', NULL, NULL, NULL, 4, 1, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, '2024-03', 'March 2024', '2024-03-01', '2024-03-31', '2024-04-05', 'Monthly', 'Paid', 834000.00, 125100.00, 708900.00, 4, 1, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(4, '2024-04', 'April 2024', '2024-04-01', '2024-04-30', '2024-05-05', 'Monthly', 'Approved', NULL, NULL, NULL, 4, 1, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, '2024-05', 'May 2024', '2024-05-01', '2024-05-31', '2024-06-05', 'Monthly', 'Processing', NULL, NULL, NULL, 4, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 'PAY-2024-05-303', 'May 2024 Payroll', '2024-05-01', '2024-05-31', '2024-06-05', 'Monthly', 'Draft', 0.00, 0.00, 0.00, NULL, NULL, '2025-09-15 05:40:50', '2025-09-15 05:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `payroll_period_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `total_deductions` decimal(10,2) NOT NULL,
  `net_pay` decimal(10,2) NOT NULL,
  `days_worked` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `late_hours` decimal(5,2) DEFAULT 0.00,
  `late_deductions` decimal(10,2) DEFAULT 0.00,
  `absent_days` decimal(5,2) DEFAULT 0.00,
  `absent_deductions` decimal(10,2) DEFAULT 0.00,
  `status` enum('Draft','Calculated','Approved','Paid') DEFAULT 'Draft',
  `calculation_date` datetime DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `payroll_period_id`, `employee_id`, `basic_salary`, `gross_pay`, `total_deductions`, `net_pay`, `days_worked`, `overtime_hours`, `overtime_pay`, `late_hours`, `late_deductions`, `absent_days`, `absent_deductions`, `status`, `calculation_date`, `approved_date`, `paid_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 200000.00, 216000.00, 32400.00, 183600.00, 22.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(2, 3, 2, 110000.00, 120000.00, 18000.00, 102000.00, 22.00, 8.00, 2500.00, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, 3, 3, 40000.00, 44000.00, 6600.00, 37400.00, 22.00, 4.00, 909.09, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(4, 3, 4, 52000.00, 56000.00, 8400.00, 47600.00, 22.00, 6.00, 1418.18, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 3, 5, 95000.00, 105000.00, 15750.00, 89250.00, 22.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 3, 6, 38000.00, 42000.00, 6300.00, 35700.00, 22.00, 2.00, 863.64, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(7, 3, 7, 31000.00, 35000.00, 5250.00, 29750.00, 22.00, 3.00, 706.82, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(8, 3, 8, 105000.00, 115000.00, 17250.00, 97750.00, 22.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(9, 3, 9, 45000.00, 49000.00, 7350.00, 41650.00, 22.00, 5.00, 1022.73, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(10, 3, 10, 48000.00, 52000.00, 7800.00, 44200.00, 22.00, 3.00, 1090.91, 0.00, 0.00, 0.00, 0.00, 'Paid', '2024-03-30 10:00:00', '2024-03-31 14:00:00', NULL, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_status`
--

CREATE TABLE `payroll_status` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Paid','Emailed') DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_status`
--

INSERT INTO `payroll_status` (`id`, `employee_id`, `status`, `reason`, `updated_by`, `updated_at`) VALUES
(1, 9, 'Paid', NULL, 1, '2025-09-15 05:31:28'),
(4, 4, 'Emailed', NULL, 1, '2025-09-15 05:31:58'),
(6, 6, 'Rejected', 'Sorry Trabaho lang', 1, '2025-09-15 05:32:27');

-- --------------------------------------------------------

--
-- Table structure for table `performance_evaluations`
--

CREATE TABLE `performance_evaluations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `evaluation_period_start` date NOT NULL,
  `evaluation_period_end` date NOT NULL,
  `overall_rating` decimal(3,2) NOT NULL,
  `goals_achievement` decimal(3,2) DEFAULT NULL,
  `teamwork` decimal(3,2) DEFAULT NULL,
  `communication` decimal(3,2) DEFAULT NULL,
  `technical_skills` decimal(3,2) DEFAULT NULL,
  `leadership` decimal(3,2) DEFAULT NULL,
  `punctuality` decimal(3,2) DEFAULT NULL,
  `evaluator_id` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('Draft','Completed','Approved') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_evaluations`
--

INSERT INTO `performance_evaluations` (`id`, `employee_id`, `evaluation_period_start`, `evaluation_period_end`, `overall_rating`, `goals_achievement`, `teamwork`, `communication`, `technical_skills`, `leadership`, `punctuality`, `evaluator_id`, `comments`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-01-01', '2024-06-30', 4.50, 4.30, 4.70, 4.20, 4.80, 4.00, 4.60, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(2, 2, '2024-01-01', '2024-06-30', 3.80, 3.50, 4.00, 3.90, 3.70, 3.50, 3.20, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(3, 3, '2024-01-01', '2024-06-30', 4.20, 4.00, 4.30, 4.10, 4.40, 3.80, 4.50, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(4, 4, '2024-01-01', '2024-06-30', 3.90, 3.70, 4.10, 3.80, 4.00, 3.60, 3.80, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(5, 5, '2024-01-01', '2024-06-30', 4.10, 4.20, 3.90, 4.00, 4.30, 3.70, 4.00, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(6, 6, '2024-01-01', '2024-06-30', 4.60, 4.50, 4.80, 4.70, 4.40, 4.30, 4.90, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(7, 7, '2024-01-01', '2024-06-30', 3.50, 3.20, 3.80, 3.60, 3.40, 3.10, 3.30, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40'),
(8, 8, '2024-01-01', '2024-06-30', 4.30, 4.10, 4.50, 4.20, 4.40, 4.00, 4.60, 1, NULL, 'Completed', '2025-09-15 16:04:40', '2025-09-15 16:04:40');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `position_code` varchar(20) NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `job_level` int(11) DEFAULT 1,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `position_code`, `position_title`, `department_id`, `job_level`, `min_salary`, `max_salary`, `job_description`, `requirements`, `is_active`, `created_at`) VALUES
(1, 'IT-DEV-01', 'Software Developer', 2, 2, 25000.00, 50000.00, 'Develops and maintains software applications', NULL, 1, '2025-09-14 05:47:43'),
(2, 'IT-SA-01', 'System Administrator', 2, 3, 35000.00, 60000.00, 'Manages IT infrastructure and systems', NULL, 1, '2025-09-14 05:47:43'),
(3, 'IT-MGR-01', 'IT Manager', 2, 6, 80000.00, 120000.00, 'Oversees IT department operations', NULL, 1, '2025-09-14 05:47:43'),
(4, 'HR-GEN-01', 'HR Generalist', 3, 3, 30000.00, 50000.00, 'Handles general HR functions', NULL, 1, '2025-09-14 05:47:43'),
(5, 'HR-REC-01', 'Recruiter', 3, 2, 25000.00, 40000.00, 'Manages recruitment and hiring process', NULL, 1, '2025-09-14 05:47:43'),
(6, 'HR-MGR-01', 'HR Manager', 3, 6, 70000.00, 100000.00, 'Manages HR department and policies', NULL, 1, '2025-09-14 05:47:43'),
(7, 'FIN-ACC-01', 'Accountant', 4, 3, 30000.00, 55000.00, 'Handles accounting and financial records', NULL, 1, '2025-09-14 05:47:43'),
(8, 'FIN-ANA-01', 'Financial Analyst', 4, 3, 35000.00, 60000.00, 'Performs financial analysis and reporting', NULL, 1, '2025-09-14 05:47:43'),
(9, 'FIN-MGR-01', 'Finance Manager', 4, 6, 80000.00, 120000.00, 'Oversees financial operations', NULL, 1, '2025-09-14 05:47:43'),
(10, 'MKT-SPE-01', 'Marketing Specialist', 5, 2, 25000.00, 45000.00, 'Executes marketing campaigns', NULL, 1, '2025-09-14 05:47:43'),
(11, 'MKT-MGR-01', 'Marketing Manager', 5, 5, 60000.00, 90000.00, 'Manages marketing strategies', NULL, 1, '2025-09-14 05:47:43'),
(12, 'OPS-SUP-01', 'Operations Supervisor', 6, 4, 45000.00, 70000.00, 'Supervises daily operations', NULL, 1, '2025-09-14 05:47:43'),
(13, 'OPS-MGR-01', 'Operations Manager', 6, 6, 80000.00, 120000.00, 'Manages operational processes', NULL, 1, '2025-09-14 05:47:43'),
(14, 'SAL-REP-01', 'Sales Representative', 7, 2, 22000.00, 40000.00, 'Handles sales activities', NULL, 1, '2025-09-14 05:47:43'),
(15, 'SAL-MGR-01', 'Sales Manager', 7, 5, 60000.00, 95000.00, 'Manages sales team and targets', NULL, 1, '2025-09-14 05:47:43'),
(16, 'EXEC-CEO', 'Chief Executive Officer', 1, 8, 180000.00, 250000.00, 'Chief executive officer', NULL, 1, '2025-09-14 05:47:43'),
(17, 'EXEC-CTO', 'Chief Technology Officer', 2, 8, 160000.00, 220000.00, 'Chief technology officer', NULL, 1, '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `permissions`, `created_at`) VALUES
(1, 'Super Admin', 'Full system access', '[\"all\"]', '2025-09-14 04:43:15'),
(2, 'HR Manager', 'HR management access', '[\"employees\", \"payroll\", \"compensation\", \"benefits\", \"reports\"]', '2025-09-14 04:43:15'),
(3, 'HR Staff', 'Basic HR operations', '[\"employees\", \"attendance\", \"leaves\"]', '2025-09-14 04:43:15'),
(4, 'Payroll Officer', 'Payroll processing access', '[\"payroll\", \"compensation\", \"reports\"]', '2025-09-14 04:43:15'),
(5, 'Employee', 'Self-service access', '[\"profile\", \"attendance\", \"leaves\", \"payslip\"]', '2025-09-14 04:43:15'),
(6, 'Department Manager', 'Department staff management', '[\"team_management\", \"attendance\", \"leaves_approval\"]', '2025-09-14 04:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `salary_grades`
--

CREATE TABLE `salary_grades` (
  `id` int(11) NOT NULL,
  `grade_code` varchar(20) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `min_salary` decimal(10,2) NOT NULL,
  `max_salary` decimal(10,2) NOT NULL,
  `step_increment` decimal(10,2) DEFAULT 0.00,
  `total_steps` int(11) DEFAULT 1,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salary_grades`
--

INSERT INTO `salary_grades` (`id`, `grade_code`, `grade_name`, `min_salary`, `max_salary`, `step_increment`, `total_steps`, `effective_date`, `expiry_date`, `is_active`, `created_at`) VALUES
(1, 'SG-01', 'Entry Level', 20000.00, 25000.00, 1000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(2, 'SG-02', 'Junior', 25000.00, 35000.00, 2000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(3, 'SG-03', 'Mid Level', 35000.00, 50000.00, 3000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(4, 'SG-04', 'Senior', 50000.00, 70000.00, 4000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(5, 'SG-05', 'Supervisor', 70000.00, 90000.00, 4000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(6, 'SG-06', 'Manager', 90000.00, 120000.00, 5000.00, 7, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(7, 'SG-07', 'Senior Manager', 120000.00, 150000.00, 6000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43'),
(8, 'SG-08', 'Director', 150000.00, 200000.00, 10000.00, 6, '2024-01-01', NULL, 1, '2025-09-14 05:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_category` enum('company','system','payroll','leave') NOT NULL,
  `data_type` enum('string','number','boolean','json') DEFAULT 'string',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_category`, `data_type`, `is_public`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'company_name', 'We are COmpany', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(2, 'company_email', 'info@techcorp.com', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(3, 'company_phone', '+1 (555) 123-4567', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(4, 'company_address', '123 Business Street, Tech City, TC 12345', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(5, 'company_website', 'https://techcorp.com', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(6, 'company_logo', 'assets/uploads/company_logo_1757999365.png', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:09:25', 1),
(7, 'timezone', 'Asia/Manila', 'company', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(8, 'currency', 'PHP', 'company', 'string', 1, '2025-09-16 01:47:06', '2025-09-16 05:08:01', NULL),
(9, 'date_format', 'Y-m-d', 'company', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 01:47:06', NULL),
(10, 'time_format', '24', 'company', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 01:47:06', NULL),
(11, 'maintenance_mode', 'false', 'system', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(12, 'user_registration', '1', 'system', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(13, 'email_notifications', '1', 'system', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(14, 'sms_notifications', 'false', 'system', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 01:47:06', NULL),
(15, 'backup_frequency', 'daily', 'system', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(16, 'session_timeout', '30', 'system', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(17, 'max_login_attempts', '5', 'system', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(18, 'password_expiry_days', '90', 'system', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(19, 'two_factor_auth', 'false', 'system', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:38', NULL),
(20, 'pay_frequency', 'monthly', 'payroll', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(21, 'pay_day', 'last_day', 'payroll', 'string', 0, '2025-09-16 01:47:06', '2025-09-16 01:47:06', NULL),
(22, 'overtime_rate', '2', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(23, 'holiday_rate', '2', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(24, 'late_deduction_rate', '0.1', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(25, 'tax_rate', '12', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(26, 'sss_rate', '3.63', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(27, 'philhealth_rate', '1.25', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(28, 'pagibig_rate', '1', 'payroll', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:09:49', NULL),
(29, 'annual_leave_days', '21', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(30, 'sick_leave_days', '10', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(31, 'personal_leave_days', '7', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(32, 'maternity_leave_days', '90', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(33, 'paternity_leave_days', '7', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(34, 'emergency_leave_days', '5', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(35, 'auto_approve_threshold', '1', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL),
(36, 'require_medical_cert', 'true', 'leave', 'boolean', 0, '2025-09-16 01:47:06', '2025-09-16 01:47:06', NULL),
(37, 'advance_leave_days', '30', 'leave', 'number', 0, '2025-09-16 01:47:06', '2025-09-16 05:10:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role_id`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'robert.johnson@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 1, 1, '2025-09-16 07:11:09', '2025-09-14 05:47:43', '2025-09-16 05:11:09'),
(2, 'hr_manager', 'hr.manager@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 2, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(3, 'hr_staff', 'hr.staff@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 3, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(4, 'payroll_officer', 'payroll@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 4, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(5, 'john_doe', 'john.doe@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 5, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43'),
(6, 'jane_smith', 'jane.smith@company.com', '$2y$10$qF3fQcmUnLBT1o8v7RhrQ.qPfWQiXVGvMQQ0WUgOrMnmvh6BKkSzG', 5, 1, NULL, '2025-09-14 05:47:43', '2025-09-14 05:47:43');
-- --------------------------------------------------------

--
-- Stand-in structure for view `v_current_payroll_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_current_payroll_summary` (
`payroll_period_id` int(11)
,`period_name` varchar(50)
,`start_date` date
,`end_date` date
,`status` enum('Draft','Processing','Approved','Paid','Closed')
,`total_employees` bigint(21)
,`total_gross` decimal(32,2)
,`total_deductions` decimal(32,2)
,`total_net` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_employee_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_employee_summary` (
`id` int(11)
,`employee_id` varchar(20)
,`full_name` varchar(101)
,`email` varchar(100)
,`employment_status` enum('Active','Inactive','Terminated','On Leave')
,`employee_type` enum('Regular','Contractual','Probationary','Part-time')
,`dept_name` varchar(100)
,`position_title` varchar(100)
,`basic_salary` decimal(10,2)
,`salary_grade` varchar(50)
,`tenure_days` int(7)
);

-- --------------------------------------------------------

--
-- Structure for view `v_current_payroll_summary`
--
DROP TABLE IF EXISTS `v_current_payroll_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_current_payroll_summary`  AS SELECT `pp`.`id` AS `payroll_period_id`, `pp`.`period_name` AS `period_name`, `pp`.`start_date` AS `start_date`, `pp`.`end_date` AS `end_date`, `pp`.`status` AS `status`, count(`pr`.`id`) AS `total_employees`, sum(`pr`.`gross_pay`) AS `total_gross`, sum(`pr`.`total_deductions`) AS `total_deductions`, sum(`pr`.`net_pay`) AS `total_net` FROM (`payroll_periods` `pp` left join `payroll_records` `pr` on(`pp`.`id` = `pr`.`payroll_period_id`)) GROUP BY `pp`.`id`, `pp`.`period_name`, `pp`.`start_date`, `pp`.`end_date`, `pp`.`status` ;

-- --------------------------------------------------------

--
-- Structure for view `v_employee_summary`
--
DROP TABLE IF EXISTS `v_employee_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_employee_summary`  AS SELECT `e`.`id` AS `id`, `e`.`employee_id` AS `employee_id`, concat(`e`.`first_name`,' ',`e`.`last_name`) AS `full_name`, `e`.`email` AS `email`, `e`.`employment_status` AS `employment_status`, `e`.`employee_type` AS `employee_type`, `d`.`dept_name` AS `dept_name`, `p`.`position_title` AS `position_title`, `ec`.`basic_salary` AS `basic_salary`, `sg`.`grade_name` AS `salary_grade`, to_days(curdate()) - to_days(`e`.`hire_date`) AS `tenure_days` FROM ((((`employees` `e` left join `departments` `d` on(`e`.`department_id` = `d`.`id`)) left join `positions` `p` on(`e`.`position_id` = `p`.`id`)) left join `employee_compensation` `ec` on(`e`.`id` = `ec`.`employee_id` and `ec`.`is_active` = 1)) left join `salary_grades` `sg` on(`ec`.`salary_grade_id` = `sg`.`id`)) WHERE `e`.`employment_status` = 'Active' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowance_types`
--
ALTER TABLE `allowance_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `allowance_code` (`allowance_code`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_employee_attendance` (`employee_id`,`attendance_date`),
  ADD KEY `idx_attendance_employee_month` (`employee_id`,`attendance_date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_table` (`table_name`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `compensations`
--
ALTER TABLE `compensations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `deduction_types`
--
ALTER TABLE `deduction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `deduction_code` (`deduction_code`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dept_code` (`dept_code`),
  ADD KEY `parent_dept_id` (`parent_dept_id`),
  ADD KEY `idx_dept_code` (`dept_code`),
  ADD KEY `fk_dept_manager` (`manager_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_employment_status` (`employment_status`),
  ADD KEY `idx_employee_status_dept` (`employment_status`,`department_id`);

--
-- Indexes for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `allowance_type_id` (`allowance_type_id`),
  ADD KEY `idx_employee_allowances` (`employee_id`,`is_active`);

--
-- Indexes for table `employee_compensation`
--
ALTER TABLE `employee_compensation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salary_grade_id` (`salary_grade_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_employee_compensation` (`employee_id`,`is_active`),
  ADD KEY `idx_compensation_employee_active` (`employee_id`,`is_active`);

--
-- Indexes for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_dependents` (`employee_id`);

--
-- Indexes for table `employee_insurance`
--
ALTER TABLE `employee_insurance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `insurance_plan_id` (`insurance_plan_id`),
  ADD KEY `idx_employee_insurance` (`employee_id`,`status`);

--
-- Indexes for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_employee_leaves` (`employee_id`),
  ADD KEY `idx_leave_dates` (`start_date`,`end_date`),
  ADD KEY `idx_leaves_employee_status` (`employee_id`,`status`);

--
-- Indexes for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `insurance_plans`
--
ALTER TABLE `insurance_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_plan_code` (`plan_code`);

--
-- Indexes for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `provider_code` (`provider_code`);

--
-- Indexes for table `leave_documents`
--
ALTER TABLE `leave_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_id` (`leave_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_code` (`leave_code`);

--
-- Indexes for table `payroll_audit_log`
--
ALTER TABLE `payroll_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deduction_type_id` (`deduction_type_id`),
  ADD KEY `idx_payroll_deductions` (`payroll_record_id`);

--
-- Indexes for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_earnings` (`payroll_record_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `period_code` (`period_code`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_period_dates` (`start_date`,`end_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll_employee` (`payroll_period_id`,`employee_id`),
  ADD KEY `idx_payroll_employee` (`employee_id`),
  ADD KEY `idx_payroll_period` (`payroll_period_id`),
  ADD KEY `idx_payroll_period_employee` (`payroll_period_id`,`employee_id`);

--
-- Indexes for table `payroll_status`
--
ALTER TABLE `payroll_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee` (`employee_id`);

--
-- Indexes for table `performance_evaluations`
--
ALTER TABLE `performance_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `position_code` (`position_code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_position_code` (`position_code`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `salary_grades`
--
ALTER TABLE `salary_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_code` (`grade_code`),
  ADD KEY `idx_grade_code` (`grade_code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowance_types`
--
ALTER TABLE `allowance_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensations`
--
ALTER TABLE `compensations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `deduction_types`
--
ALTER TABLE `deduction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `employee_compensation`
--
ALTER TABLE `employee_compensation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employee_insurance`
--
ALTER TABLE `employee_insurance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `employment_history`
--
ALTER TABLE `employment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_plans`
--
ALTER TABLE `insurance_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `leave_documents`
--
ALTER TABLE `leave_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payroll_audit_log`
--
ALTER TABLE `payroll_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payroll_status`
--
ALTER TABLE `payroll_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `performance_evaluations`
--
ALTER TABLE `performance_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `salary_grades`
--
ALTER TABLE `salary_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `compensations`
--
ALTER TABLE `compensations`
  ADD CONSTRAINT `fk_compensations_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_dept_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_dept_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`),
  ADD CONSTRAINT `employees_ibfk_4` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  ADD CONSTRAINT `employee_allowances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_allowances_ibfk_2` FOREIGN KEY (`allowance_type_id`) REFERENCES `allowance_types` (`id`);

--
-- Constraints for table `employee_compensation`
--
ALTER TABLE `employee_compensation`
  ADD CONSTRAINT `employee_compensation_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_compensation_ibfk_2` FOREIGN KEY (`salary_grade_id`) REFERENCES `salary_grades` (`id`),
  ADD CONSTRAINT `employee_compensation_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  ADD CONSTRAINT `employee_dependents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_insurance`
--
ALTER TABLE `employee_insurance`
  ADD CONSTRAINT `employee_insurance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_insurance_ibfk_2` FOREIGN KEY (`insurance_plan_id`) REFERENCES `insurance_plans` (`id`);

--
-- Constraints for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  ADD CONSTRAINT `employee_leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_leaves_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `employee_leaves_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD CONSTRAINT `employment_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employment_history_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `employment_history_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`),
  ADD CONSTRAINT `employment_history_ibfk_4` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `insurance_plans`
--
ALTER TABLE `insurance_plans`
  ADD CONSTRAINT `insurance_plans_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `insurance_providers` (`id`);

--
-- Constraints for table `leave_documents`
--
ALTER TABLE `leave_documents`
  ADD CONSTRAINT `leave_documents_ibfk_1` FOREIGN KEY (`leave_id`) REFERENCES `employee_leaves` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_audit_log`
--
ALTER TABLE `payroll_audit_log`
  ADD CONSTRAINT `payroll_audit_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD CONSTRAINT `payroll_deductions_ibfk_1` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_deductions_ibfk_2` FOREIGN KEY (`deduction_type_id`) REFERENCES `deduction_types` (`id`);

--
-- Constraints for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD CONSTRAINT `payroll_earnings_ibfk_1` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payroll_periods_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `payroll_status`
--
ALTER TABLE `payroll_status`
  ADD CONSTRAINT `payroll_status_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_evaluations`
--
ALTER TABLE `performance_evaluations`
  ADD CONSTRAINT `performance_evaluations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `performance_evaluations_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
