-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql301.infinityfree.com
-- Generation Time: Oct 02, 2025 at 12:48 PM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38435297_calendar`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `responsible_unit` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `responsible_unit`, `notes`, `is_hidden`, `created_at`, `updated_at`, `created_by_user_id`) VALUES
(11, 'ตามกลิ่นกุหลาบ', 'สนง.บุคลากร', '', 0, '2025-09-27 15:11:28', '2025-10-02 12:59:26', 1),
(12, 'ค่าย PreCamp พี่เลี้ยงลูกเสือ', 'งานลูกเสือ', '', 0, '2025-09-27 15:12:12', '2025-10-02 12:21:05', 1),
(13, 'อบรมการเขียนงานวิจัย ครั้งที่ 1', 'งานประกันคุณภาพฯ', '', 0, '2025-09-27 15:12:38', '2025-10-02 12:21:05', 1),
(14, 'การอบรมสัมนาครูบุคลากรและลูกจ้าง', 'กลุ่มบริหารทั่วไป', '', 0, '2025-09-27 15:13:18', '2025-10-02 12:21:05', 1),
(15, 'อบรมเสริมทักษะครู-นักเรียนแกนนำ', 'งานแนะแนวการศึกษา', '', 0, '2025-09-27 15:13:34', '2025-10-02 12:21:05', 1),
(16, 'งานวันปิยมหาราช', 'งานวันสำคัญ', '', 0, '2025-09-27 15:14:32', '2025-10-02 12:21:05', 1),
(17, 'ค่ายลูกเสือและทัศนศึกษา ม.3', 'ระดับชั้น', '', 0, '2025-09-27 15:16:05', '2025-10-02 12:21:05', 1),
(18, 'พิธีเปิดการแข่งขันจตุรมิตร', 'เชียร์และแปรอักษร', '', 0, '2025-09-27 15:16:38', '2025-10-02 12:21:05', 1),
(19, 'ค่ายก้าวแรกสู่เส้นทางวิทย์ Gate ม.1', 'โครงการห้องเรียนพิเศษ (GATE)', '', 0, '2025-09-27 15:17:15', '2025-10-02 12:21:05', 1),
(20, 'การสอบธรรมสนามหลวง', 'กลุ่มสาระสังคมฯ', '', 0, '2025-09-27 15:17:39', '2025-10-02 12:21:05', 1),
(21, 'ค่ายสิ่งแวดล้อม EP', '', '', 0, '2025-09-27 15:18:07', '2025-10-02 12:21:05', 1),
(22, 'กิจกรรมค้นหาตัวตน ม.1', 'งานแนะแนว', '', 0, '2025-09-27 15:18:24', '2025-10-02 12:21:05', 1),
(23, 'จิตอาสา ม.5', 'ระดับชั้น', '', 0, '2025-09-27 15:18:43', '2025-10-02 12:21:05', 1),
(24, 'พิธีวันคล้ายวันพระบรมราชสมภพพระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช', 'งานวันสำคัญ', '', 0, '2025-09-27 15:19:14', '2025-10-02 12:21:05', 1),
(25, 'กรีฑาสวนกุหลาบสัมพันธ์ ครั้งที่ 26', 'สนง.กก.ส่งเสริมวัฒนธรรมคุณภาพสวนกุหลาบฯ', '', 0, '2025-09-27 15:20:04', '2025-10-02 12:21:05', 1),
(26, 'โครงการโรงเรียนพี่โรงเรียนน้อง', '', '', 0, '2025-09-27 15:20:16', '2025-10-02 12:21:05', 1),
(27, 'งานวันคริสต์มาส', 'กลุ่มสาระภาษาต่างประเทศ', '', 0, '2025-09-27 15:20:45', '2025-10-02 12:21:05', 1),
(28, 'สอบกลางภาค 2/2568 นอกตาราง', 'วิชาการ', '', 0, '2025-09-27 15:21:19', '2025-10-02 12:21:05', 1),
(29, 'สอบกลางภาค 2/2568 ในตาราง', 'วิชาการ', '', 0, '2025-09-27 15:21:49', '2025-10-02 12:21:05', 1),
(30, 'ค่ายลูกเสือสวนกุหลาบสัมพันธ์ ม.2', 'งานระดับชั้น', '', 0, '2025-09-27 15:22:26', '2025-10-02 12:21:05', 1),
(31, 'ค่ายพัฒนาอัจฉริยภาพทางวิทย์ คณิต เทคโนฯ ม.1 (EP)', 'โครงการห้องเรียนพิเศษ EPLUS+ ม.ต้น', '', 0, '2025-09-27 15:22:49', '2025-10-02 12:21:05', 1),
(32, 'สอบวัดระดับภาษาอังกฤษ CEFR ม.1', 'กลุ่มสาระภาษาต่างประเทศ', '', 0, '2025-09-27 15:23:09', '2025-10-02 12:21:05', 1),
(33, 'ทัศนศึกษา ม.2 จ.ชลบุรี', 'ระดับชั้น', '', 0, '2025-09-27 15:23:33', '2025-10-02 12:21:05', 1),
(34, 'ค่ายค้นพบตัวเอง (FM.) Gate ม.3', 'โครงการห้องเรียนพิเศษ (GATE)', '', 0, '2025-09-27 15:23:53', '2025-10-02 12:21:05', 1),
(35, 'สังสรรค์ปีใหม่บุคลากร', 'งานบุคลากร', '', 0, '2025-09-27 15:25:48', '2025-10-02 12:21:05', 1),
(36, 'พิธีบูชาครูและสังสรรค์ปีใหม่', 'สนง.บุคลากร', '', 0, '2025-09-27 15:26:05', '2025-10-02 12:21:05', 1),
(37, 'สอบคัดเลือก IJSO รอบที่ 1', 'งานส่งเสริมศักยภาพนักเรียน (กุหลาบเพชร)', '', 0, '2025-09-27 15:26:26', '2025-10-02 12:21:05', 1),
(38, 'ค่ายบูรณาการวิทยาศาสตร์และสิ่งแวดล้อมGATE ม.2', 'โครงการห้องเรียนพิเศษ (GATE)', '', 0, '2025-09-27 15:26:51', '2025-10-02 12:21:05', 1),
(39, 'ทัศนศึกษา ม.6', 'งานระดับชั้น', '', 0, '2025-09-27 15:27:03', '2025-10-02 12:21:05', 1),
(40, 'ประชุมผูู้ปกครอง และประกาศผลสอบกลางภาค 2/2568', '', '', 0, '2025-09-27 15:27:19', '2025-10-02 12:21:05', 1),
(41, 'สอบประเมินศักยภาพและทดสอบความพร้อม (Pre-test)', 'วิชาการ', '', 0, '2025-09-27 15:27:39', '2025-10-02 12:21:05', 1),
(42, 'วันมอบงานกิจกรรม', 'งานกิจกรรมนักเรียน', '', 0, '2025-09-27 15:27:54', '2025-10-02 12:21:05', 1),
(43, 'SK Open House', 'โครงการห้องเรียนพิเศษ (GATE)', '', 0, '2025-09-27 15:28:06', '2025-10-02 12:21:05', 1),
(44, 'สอบปลายภาค 2/2568 นอกตาราง', 'วิชาการ', '', 0, '2025-09-27 15:28:23', '2025-10-02 12:21:05', 1),
(45, 'สอบปลายภาค 2/2568 ในตาราง', 'วิชาการ', '', 0, '2025-09-27 15:28:41', '2025-10-02 12:21:05', 1),
(46, 'งานวันจากเหย้า', 'งานระดับชั้น ม.6', '', 0, '2025-09-27 15:29:15', '2025-10-02 12:21:05', 1),
(47, 'มอบโล่และเกียรติบัตรเครือข่ายผู้ปกครอง', 'งานประสานงาน กก.เครือข่ายผู้ปกครอง', '', 0, '2025-09-27 15:29:27', '2025-10-02 12:21:05', 1),
(48, 'การประชุมเชิงปฏิบัติการคณะกรรมการนักเรียน', 'งานกิจกรรมนักเรียน', '', 0, '2025-09-27 15:29:44', '2025-10-02 12:21:05', 1),
(49, 'ครูตรวจทานคะแนน ปพ.', 'วิชาการ', '', 0, '2025-09-27 15:30:17', '2025-10-02 12:21:05', 1),
(50, 'ค่ายคณิตศาสตร์บูรณาการ ครั้งที่ 24', 'กลุ่มสาระคณิตศาสตร์', '', 0, '2025-09-27 15:30:37', '2025-10-02 12:21:05', 1),
(51, 'วันสถาปนาโรงเรียนสวนกุหลาบวิทยาลัย', 'กลุ่มบริหารทั่วไป', '', 0, '2025-09-27 15:30:51', '2025-10-02 12:21:05', 1),
(52, 'สอบคัดเลือก IJSO รอบที่ 2', 'งานส่งเสริมศักยภาพนักเรียน (กุหลาบเพชร)', '', 0, '2025-09-27 15:31:04', '2025-10-02 12:21:05', 1),
(53, 'ค่ายคนทำกิจกรรม', 'งานกิจกรรมนักเรียน', '', 0, '2025-09-27 15:31:28', '2025-10-02 12:21:05', 1),
(54, 'อบรมการเขียนงานวิจัย ครั้งที่ 2', 'งานประกันคุณภาพฯ', '', 0, '2025-09-27 15:31:52', '2025-10-02 12:21:05', 1),
(55, 'การนำเสนอ Best Practice และนิทรรศการบทความงานวิจัย', 'งานประกันคุณภาพฯ', '', 0, '2025-09-27 15:32:03', '2025-10-02 12:21:05', 1),
(56, 'วันอนุมัติจบการศึกษา ม.3, ม.6', '', '', 0, '2025-09-27 15:32:24', '2025-10-02 12:21:05', 1),
(57, 'ประกาศผลสอบปลายภาคเรียนที่ 1', '', '', 0, '2025-09-27 15:33:02', '2025-10-02 12:21:05', 1),
(58, 'อบรมพัฒนาครู', 'งานพัฒนาบุคลากร', '', 0, '2025-09-27 15:34:10', '2025-10-02 12:21:05', 1),
(59, 'งานมุทิตาจิตครูเกษียณอายุราชการ', 'งานบุคลากร', '', 0, '2025-09-27 15:34:27', '2025-10-02 12:21:05', 1),
(60, 'ค่าย Sci-Tech Camp ม.1-ม.3', 'โครงการห้องเรียนพิเศษ (GATE)', '', 0, '2025-09-27 15:35:06', '2025-10-02 12:21:05', 1),
(61, 'กิจกรรมค่ายฝึกทักษะศาสตร์และศิลป์การพูดในที่สาธารณะ EPLUS+ ม.3', '', '', 0, '2025-09-27 15:35:45', '2025-10-02 12:21:05', 1),
(62, 'ค่ายพัฒนาทักษะการเรียนรู้โดยใช้ปัญหาเป็นฐาน EPLUS+ ม.2', '', '', 0, '2025-09-27 15:35:58', '2025-10-02 12:21:05', 1),
(63, 'สอบปลายภาค 1/2568 ในตาราง', 'วิชาการ', '', 0, '2025-09-27 15:36:20', '2025-10-02 12:21:05', 1),
(64, 'ประชุมสถาบันสวนกุหลาบและมอบประกาศเกียรติคุณ', 'สำนักเลขาธิการสถาบันฯ', '', 0, '2025-09-27 15:36:45', '2025-10-02 12:21:05', 1),
(65, 'สอบปลายภาค 1/2568 นอกตาราง', 'วิชาการ', '', 0, '2025-09-27 15:37:01', '2025-10-02 12:21:05', 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_dates`
--

CREATE TABLE `event_dates` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `activity_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_dates`
--

INSERT INTO `event_dates` (`id`, `event_id`, `activity_date`) VALUES
(32, 12, '2025-10-02'),
(33, 12, '2025-10-03'),
(34, 13, '2025-10-04'),
(35, 14, '2025-10-06'),
(36, 14, '2025-10-07'),
(37, 14, '2025-10-08'),
(38, 15, '2025-10-08'),
(40, 17, '2025-10-28'),
(41, 17, '2025-10-29'),
(42, 17, '2025-10-30'),
(43, 18, '2025-11-15'),
(44, 18, '2025-11-22'),
(45, 19, '2025-11-26'),
(46, 19, '2025-11-27'),
(47, 19, '2025-11-28'),
(48, 20, '2025-11-27'),
(49, 21, '2025-12-01'),
(50, 21, '2025-12-02'),
(51, 21, '2025-12-03'),
(52, 22, '2025-12-01'),
(53, 22, '2025-12-02'),
(54, 23, '2025-12-02'),
(55, 24, '2025-12-04'),
(56, 25, '2025-12-09'),
(57, 25, '2025-12-10'),
(59, 27, '2025-12-18'),
(60, 28, '2025-12-15'),
(61, 28, '2025-12-16'),
(62, 28, '2025-12-17'),
(63, 28, '2025-12-18'),
(64, 28, '2025-12-19'),
(65, 29, '2025-12-22'),
(66, 29, '2025-12-23'),
(67, 29, '2025-12-24'),
(68, 29, '2025-12-25'),
(69, 29, '2025-12-26'),
(70, 30, '2026-01-06'),
(71, 30, '2026-01-07'),
(72, 30, '2026-01-08'),
(73, 30, '2026-01-09'),
(74, 31, '2026-01-07'),
(75, 31, '2026-01-08'),
(76, 31, '2026-01-09'),
(79, 33, '2026-01-09'),
(83, 34, '2026-01-12'),
(84, 34, '2026-01-13'),
(85, 34, '2026-01-14'),
(86, 32, '2026-01-08'),
(87, 32, '2026-01-09'),
(88, 32, '2026-01-12'),
(89, 32, '2026-01-13'),
(90, 32, '2026-01-14'),
(91, 35, '2026-01-13'),
(92, 36, '2026-01-15'),
(93, 37, '2026-01-18'),
(94, 38, '2026-01-19'),
(95, 38, '2026-01-20'),
(96, 38, '2026-01-21'),
(97, 39, '2026-01-23'),
(98, 40, '2026-01-24'),
(99, 41, '2026-01-25'),
(100, 42, '2026-01-29'),
(101, 43, '2026-02-01'),
(102, 44, '2026-02-09'),
(103, 44, '2026-02-10'),
(104, 44, '2026-02-11'),
(105, 44, '2026-02-12'),
(106, 44, '2026-02-13'),
(108, 45, '2026-02-16'),
(109, 45, '2026-02-17'),
(110, 45, '2026-02-18'),
(111, 45, '2026-02-19'),
(112, 45, '2026-02-20'),
(113, 46, '2026-02-20'),
(114, 47, '2026-02-07'),
(115, 48, '2026-02-23'),
(116, 48, '2026-02-24'),
(117, 49, '2026-02-27'),
(118, 50, '2026-03-04'),
(119, 50, '2026-03-05'),
(120, 50, '2026-03-06'),
(121, 51, '2026-03-08'),
(122, 52, '2026-03-09'),
(123, 53, '2026-03-10'),
(124, 53, '2026-03-11'),
(125, 54, '2026-03-14'),
(126, 55, '2026-03-20'),
(129, 58, '2025-09-29'),
(130, 58, '2025-10-08'),
(131, 58, '2025-10-09'),
(133, 59, '2025-09-30'),
(134, 60, '2025-09-23'),
(135, 60, '2025-09-24'),
(136, 60, '2025-09-25'),
(137, 60, '2025-09-26'),
(138, 60, '2025-09-27'),
(139, 61, '2025-09-15'),
(140, 61, '2025-09-16'),
(141, 61, '2025-09-17'),
(142, 62, '2025-09-15'),
(143, 62, '2025-09-16'),
(144, 62, '2025-09-17'),
(145, 63, '2025-09-08'),
(146, 63, '2025-09-09'),
(147, 63, '2025-09-10'),
(148, 63, '2025-09-11'),
(149, 63, '2025-09-12'),
(150, 64, '2025-09-02'),
(151, 65, '2025-09-01'),
(152, 65, '2025-09-02'),
(153, 65, '2025-09-03'),
(154, 65, '2025-09-04'),
(155, 65, '2025-09-05'),
(158, 26, '2025-12-11'),
(159, 26, '2025-12-12'),
(160, 26, '2025-12-13'),
(161, 16, '2025-10-23'),
(162, 57, '2025-09-29'),
(197, 11, '2025-10-01'),
(198, 11, '2025-10-02'),
(232, 56, '2026-03-31');

-- --------------------------------------------------------

--
-- Table structure for table `event_owners`
--

CREATE TABLE `event_owners` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_owners`
--

INSERT INTO `event_owners` (`event_id`, `user_id`) VALUES
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_owners`
--

CREATE TABLE `holiday_owners` (
  `holiday_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holiday_owners`
--

INSERT INTO `holiday_owners` (`holiday_id`, `user_id`) VALUES
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(16, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(23, 2),
(24, 2),
(25, 2),
(26, 2),
(27, 2);

-- --------------------------------------------------------

--
-- Table structure for table `problem_reports`
--

CREATE TABLE `problem_reports` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('event_date_bg_color', '#e6f7ff'),
('footer_text', '© พัฒนาเว็บไซต์โดย ครูพุทธพล  ภาคสุวรรณ์\r\nรวบรวมข้อมูลโดยกลุ่มบริหารงานพัฒนาคุณภาพการศึกษา'),
('header_bg_color', '#1a89c1'),
('header_text_color', '#ffffff'),
('highlight_today', '1'),
('holiday_bg_color', '#fffbe6'),
('nav_menu_style', 'buttons'),
('no_event_date_bg_color', '#ffffff'),
('publish_end_date', '2026-03'),
('publish_start_date', '2025-09'),
('responsible_unit_format', 'hide'),
('saturday_bg_color', '#f7e4e4'),
('show_current_date', '1'),
('show_event_ticker', '1'),
('site_bg_color', '#f2f2f2'),
('site_publication_status', 'published'),
('site_title', 'ปฏิทินกิจกรรมโรงเรียนสวนกุหลาบวิทยาลัย'),
('sunday_bg_color', '#fbbcbc'),
('table_day_name_format', 'full'),
('table_day_name_style', 'colon'),
('table_month_format', 'short'),
('table_year_format', 'be_2'),
('ticker_custom_message', ''),
('ticker_speed', '20'),
('ticker_text_color', '#0033ff'),
('truncate_event_names', '0'),
('week_start_day', 'sunday');

-- --------------------------------------------------------

--
-- Table structure for table `special_holidays`
--

CREATE TABLE `special_holidays` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  `holiday_date` date NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=visible, 1=hidden',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_holidays`
--

INSERT INTO `special_holidays` (`id`, `holiday_name`, `holiday_date`, `is_hidden`, `created_at`, `updated_at`, `created_by_user_id`) VALUES
(3, 'วันครู', '2026-01-16', 0, '2025-09-27 15:06:17', '2025-10-02 12:21:05', 1),
(4, 'วันสุดท้ายภาคเรียนที่ 1', '2025-10-10', 0, '2025-09-27 15:14:06', '2025-10-02 14:45:19', 1),
(5, 'เปิดภาคเรียนที่ 2', '2025-10-24', 0, '2025-09-27 15:14:46', '2025-10-02 12:21:05', 1),
(6, 'วันคล้ายวันพระบรมราชสมภพฯ', '2025-12-05', 0, '2025-09-27 15:19:31', '2025-10-02 12:21:05', 1),
(7, 'วันรัฐธรรมนูญ', '2025-12-10', 0, '2025-09-27 15:38:19', '2025-10-02 12:21:05', 1),
(8, 'วันสิ้นปี', '2025-12-31', 0, '2025-09-27 15:44:06', '2025-10-02 12:21:05', 1),
(9, 'วันปีใหม่', '2026-01-01', 0, '2025-09-27 15:44:27', '2025-10-02 12:21:05', 1),
(16, 'วันแรกของเดือน', '2026-04-01', 0, '2025-10-02 16:38:50', '2025-10-02 16:38:50', 2),
(17, 'วันแรกของเดือน', '2026-05-01', 0, '2025-10-02 16:38:59', '2025-10-02 16:38:59', 2),
(18, 'วันแรกของเดือน', '2026-06-01', 0, '2025-10-02 16:39:15', '2025-10-02 16:39:15', 2),
(19, 'วันแรกของเดือน', '2026-08-01', 0, '2025-10-02 16:39:23', '2025-10-02 16:39:39', 2),
(20, 'วันแรกของเดือน', '2026-07-01', 0, '2025-10-02 16:39:30', '2025-10-02 16:39:30', 2),
(21, 'วันแรกของเดือน', '2026-09-01', 0, '2025-10-02 16:39:46', '2025-10-02 16:39:46', 2),
(22, 'วันแรกของเดือน', '2026-10-01', 0, '2025-10-02 16:39:51', '2025-10-02 16:39:51', 2),
(23, 'วันแรกของเดือน', '2026-11-01', 0, '2025-10-02 16:39:57', '2025-10-02 16:39:57', 2),
(24, 'วันแรกของเดือน', '2026-12-01', 0, '2025-10-02 16:40:04', '2025-10-02 16:40:04', 2),
(25, 'วันแรกของเดือน', '2027-01-01', 0, '2025-10-02 16:40:12', '2025-10-02 16:40:12', 2),
(26, 'วันแรกของเดือน', '2027-02-01', 0, '2025-10-02 16:40:20', '2025-10-02 16:40:20', 2),
(27, 'วันแรกของเดือน', '2027-03-01', 0, '2025-10-02 16:40:28', '2025-10-02 16:40:28', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `real_name` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `permission_end_date` date DEFAULT NULL,
  `permission_start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `real_name`, `role`, `is_active`, `permission_end_date`, `permission_start_date`) VALUES
(1, 'admin', '1234', 'ผู้ดูแลระบบหลัก', 'admin', 1, NULL, NULL),
(2, 'staff1', 'abc111', 'test', 'staff', 1, '2027-03-31', '2026-01-04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `event_dates`
--
ALTER TABLE `event_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_owners`
--
ALTER TABLE `event_owners`
  ADD PRIMARY KEY (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `holiday_owners`
--
ALTER TABLE `holiday_owners`
  ADD PRIMARY KEY (`holiday_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `problem_reports`
--
ALTER TABLE `problem_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `special_holidays`
--
ALTER TABLE `special_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `event_dates`
--
ALTER TABLE `event_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT for table `problem_reports`
--
ALTER TABLE `problem_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `special_holidays`
--
ALTER TABLE `special_holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_dates`
--
ALTER TABLE `event_dates`
  ADD CONSTRAINT `event_dates_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_owners`
--
ALTER TABLE `event_owners`
  ADD CONSTRAINT `event_owners_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_owners_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_owners`
--
ALTER TABLE `holiday_owners`
  ADD CONSTRAINT `holiday_owners_ibfk_1` FOREIGN KEY (`holiday_id`) REFERENCES `special_holidays` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `holiday_owners_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `special_holidays`
--
ALTER TABLE `special_holidays`
  ADD CONSTRAINT `special_holidays_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
