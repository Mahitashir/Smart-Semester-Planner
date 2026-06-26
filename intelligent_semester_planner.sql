-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2026 at 12:54 AM
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
-- Database: `intelligent_semester_planner`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` varchar(20) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `password`) VALUES
('22201855', 'fghjkl');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `difficulty_level` varchar(30) NOT NULL,
  `admin_id` varchar(20) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_name`, `course_code`, `credit_hours`, `difficulty_level`, `admin_id`, `dept_id`) VALUES
(1, 'Programming Language I', 'CSE110', 3, 'Moderate', NULL, 1),
(2, 'Programming Language 2', 'CSE111', 3, 'Moderate', NULL, 1),
(3, 'Data Structure', 'CSE220', 3, 'Moderate', NULL, 1),
(4, 'Algorithm Analysis & Design', 'CSE221', 3, 'Difficult', NULL, 1),
(5, 'Discrete Mathematics', 'CSE230', 3, 'Moderate', NULL, 1),
(6, 'Circuits and Electronics', 'CSE250', 3, 'Moderate', NULL, 1),
(7, 'Electronic Devices and Circuits', 'CSE251', 3, 'Moderate', NULL, 1),
(8, 'Digital Logic Design', 'CSE260', 3, 'Moderate', NULL, 1),
(9, 'Data Communications', 'CSE320', 3, 'Moderate', NULL, 1),
(10, 'Operating Systems', 'CSE321', 3, 'Difficult', NULL, 1),
(11, 'Numerical Methods', 'CSE330', 3, 'Moderate', NULL, 1),
(12, 'Automata and Computability', 'CSE331', 3, 'Difficult', NULL, 1),
(13, 'Computer Architecture', 'CSE340', 3, 'Difficult', NULL, 1),
(14, 'MICROPROCESSORS', 'CSE341', 3, 'Difficult', NULL, 1),
(15, 'Digital Electronics and Pulse Techniques', 'CSE350', 3, 'Moderate', NULL, 1),
(16, 'Computer Interfacing', 'CSE360', 3, 'Difficult', NULL, 1),
(17, 'Database', 'CSE370', 3, 'Moderate', NULL, 1),
(18, 'Compiler Design', 'CSE420', 3, 'Difficult', NULL, 1),
(19, 'Computer Networks', 'CSE421', 3, 'Moderate', NULL, 1),
(20, 'Artificial Intelligence', 'CSE422', 3, 'Difficult', NULL, 1),
(21, 'Computer Graphics', 'CSE423', 3, 'Moderate', NULL, 1),
(22, 'VLSI Design', 'CSE460', 3, 'Difficult', NULL, 1),
(23, 'Introduction to Robotics', 'CSE461', 3, 'Difficult', NULL, 1),
(24, 'Software Engineering', 'CSE470', 3, 'Moderate', NULL, 1),
(25, 'System Analysis and Design', 'CSE471', 3, 'Moderate', NULL, 1),
(26, 'Principles of Physics', 'PHY111', 3, 'Moderate', NULL, 2),
(27, 'Fundamental of Physics II', 'PHY112', 3, 'Moderate', NULL, 2),
(28, 'Mathematics I: Differential Calculus & Coordinate Geometry', 'MAT110', 3, 'Moderate', NULL, 2),
(29, 'Mathematics II: Integral Calculus & Differential Equations', 'MAT120', 3, 'Moderate', NULL, 2),
(30, 'Mathematics III: Complex Variables & Laplace Transformations', 'MAT215', 3, 'Difficult', NULL, 2),
(31, 'Mathematics IV: Linear Algebra & Fourier Analysis', 'MAT216', 3, 'Difficult', NULL, 2),
(32, 'Elements of Statistics and Probability', 'STA201', 3, 'Moderate', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `course_completed`
--

CREATE TABLE `course_completed` (
  `student_id` varchar(20) NOT NULL,
  `course_id` int(11) NOT NULL,
  `gpa` decimal(2,1) NOT NULL,
  `completed_semester` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_completed`
--

INSERT INTO `course_completed` (`student_id`, `course_id`, `gpa`, `completed_semester`) VALUES
('22201855', 1, 2.7, 'Fall 2024'),
('22201855', 2, 1.7, 'Spring 2025'),
('22201855', 3, 1.3, 'Summer 2025'),
('22201855', 6, 2.7, 'Spring 2024'),
('22201855', 7, 2.7, 'Summer 2025'),
('22201855', 26, 3.7, 'Fall 2024'),
('22201855', 28, 4.0, 'Fall 2024'),
('22201855', 32, 3.7, 'Fall 2024'),
('22201877', 1, 2.0, NULL),
('22201877', 2, 1.3, NULL),
('22201877', 3, 3.0, NULL),
('22201877', 4, 2.3, NULL),
('22201877', 5, 0.7, NULL),
('22201877', 6, 2.3, NULL),
('22201877', 26, 4.0, NULL),
('22201877', 28, 1.3, NULL),
('22201877', 29, 1.7, NULL),
('22201877', 32, 2.7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`dept_id`, `dept_name`) VALUES
(1, 'CSE'),
(2, 'MNS'),
(3, 'English'),
(4, 'BBA'),
(5, 'GenEd'),
(6, 'Computer Science'),
(7, 'Physics'),
(8, 'Mathematics'),
(9, 'Statistics');

-- --------------------------------------------------------

--
-- Table structure for table `prerequisite`
--

CREATE TABLE `prerequisite` (
  `course_id` int(11) NOT NULL,
  `prereq_course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prerequisite`
--

INSERT INTO `prerequisite` (`course_id`, `prereq_course_id`) VALUES
(2, 1),
(3, 2),
(4, 3),
(7, 6),
(8, 7),
(10, 4),
(11, 31),
(12, 4),
(13, 8),
(14, 8),
(15, 7),
(16, 14),
(17, 4),
(18, 4),
(18, 12),
(18, 13),
(20, 4),
(21, 31),
(22, 8),
(23, 8),
(23, 14),
(23, 16),
(24, 17),
(25, 17),
(27, 26),
(29, 28),
(30, 29),
(31, 29);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` varchar(20) NOT NULL,
  `no_of_courses` int(11) NOT NULL,
  `cgpa` decimal(3,2) NOT NULL,
  `desired_semester_load` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `no_of_courses`, `cgpa`, `desired_semester_load`) VALUES
('22201855', 4, 3.00, 'Moderate'),
('22201877', 4, 3.00, 'Low');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`) VALUES
('22201855', 'SAMIN MOHAMMAD TASHIR MAHI', 'samin.mohammad.tashir@g.bracu.ac.bd'),
('22201877', 'kskl', 'samin.mohammad.tashir@g.bracu.ac.bd');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `course_completed`
--
ALTER TABLE `course_completed`
  ADD PRIMARY KEY (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `prerequisite`
--
ALTER TABLE `prerequisite`
  ADD PRIMARY KEY (`course_id`,`prereq_course_id`),
  ADD KEY `prereq_course_id` (`prereq_course_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`id`) REFERENCES `user` (`id`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `course_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`);

--
-- Constraints for table `course_completed`
--
ALTER TABLE `course_completed`
  ADD CONSTRAINT `course_completed_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_completed_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `prerequisite`
--
ALTER TABLE `prerequisite`
  ADD CONSTRAINT `prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prerequisite_ibfk_2` FOREIGN KEY (`prereq_course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
