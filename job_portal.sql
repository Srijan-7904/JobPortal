-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2025 at 01:05 PM
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
-- Database: `job_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `resume` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `job_id`, `resume`) VALUES
(1, 2, 1, 'resume_1742562153_1708.pdf'),
(2, 1, 1, 'resume_1742800762_5444.pdf'),
(3, 4, 1, 'resume_1742823974_7768.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `bookmarked_jobs`
--

CREATE TABLE `bookmarked_jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookmarked_jobs`
--

INSERT INTO `bookmarked_jobs` (`id`, `user_id`, `job_id`, `created_at`) VALUES
(32, 2, 1, '2025-03-24 09:35:00'),
(33, 4, 1, '2025-03-25 09:58:28');

-- --------------------------------------------------------

--
-- Table structure for table `hackathons`
--

CREATE TABLE `hackathons` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `organizer` varchar(255) NOT NULL,
  `registration_deadline` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `employer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hackathons`
--

INSERT INTO `hackathons` (`id`, `title`, `description`, `date`, `location`, `organizer`, `registration_deadline`, `created_at`, `is_active`, `employer_id`) VALUES
(1, 'CodeStorm 2025', 'Join us for CodeStorm 2025, a 24-hour coding marathon where developers, designers, and innovators come together to solve real-world problems! Build cutting-edge solutions, network with industry professionals, and compete for exciting prizes. Whether you&#39;re a seasoned coder or a beginner, this hackathon is the perfect opportunity to showcase your skills and collaborate with like-minded individuals. Themes will be announced at the event, and mentors will be available to guide teams throughout the competition.', '2025-03-06 21:44:00', 'echHub Innovation Center, 1234 Silicon Avenue, San Francisco, CA 94105', 'echHub Innovation Center, 1234 Silicon Avenue, San Francisco, CA 94105', '2025-03-23 21:45:00', '2025-03-23 16:15:20', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `hackathon_registrations`
--

CREATE TABLE `hackathon_registrations` (
  `id` int(11) NOT NULL,
  `hackathon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hackathon_registrations`
--

INSERT INTO `hackathon_registrations` (`id`, `hackathon_id`, `user_id`, `registered_at`) VALUES
(1, 1, 2, '2025-03-23 16:16:39'),
(2, 1, 4, '2025-03-25 09:45:41');

-- --------------------------------------------------------

--
-- Table structure for table `interview_sessions`
--

CREATE TABLE `interview_sessions` (
  `id` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `user1_id` int(11) DEFAULT NULL,
  `user2_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_sessions`
--

INSERT INTO `interview_sessions` (`id`, `room_id`, `user1_id`, `user2_id`, `created_at`) VALUES
(1, 'test123', NULL, NULL, '2025-03-23 13:43:57'),
(59, '8f59e2c24ab81c4a', NULL, NULL, '2025-03-23 14:56:50'),
(60, '0c74c87d0af015fd', NULL, NULL, '2025-03-23 14:57:01'),
(61, 'fe157ccfe2eff0e0', NULL, NULL, '2025-03-23 14:59:44'),
(62, '9fb2f10bb3a9e4bd', NULL, NULL, '2025-03-23 14:59:54'),
(63, '4193b5437a73a22d', NULL, NULL, '2025-03-23 15:04:01'),
(64, 'fe9c75fc2abbcf3b', NULL, NULL, '2025-03-23 15:12:30'),
(65, '02ba4db9d75af31a', NULL, NULL, '2025-03-23 15:12:38'),
(66, 'fa69d3db5d830e57', NULL, NULL, '2025-03-23 15:12:43'),
(67, 'f4d29a5ae7719897', NULL, NULL, '2025-03-23 15:12:44'),
(68, '342a2fc843d17622', NULL, NULL, '2025-03-23 15:15:43'),
(69, 'b7e7038623f46dbf', NULL, NULL, '2025-03-23 15:15:48'),
(70, '620698b4496c7120', NULL, NULL, '2025-03-23 15:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','closed','draft') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `employer_id`, `title`, `description`, `salary`, `created_at`, `updated_at`, `status`) VALUES
(1, 1, 'Software Developoer', 'kuch bhi kar lo', 2000000.00, '2025-03-21 06:22:48', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resume_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`resume_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resumes`
--

INSERT INTO `resumes` (`id`, `user_id`, `resume_data`, `created_at`, `updated_at`) VALUES
(1, 2, '{\"contact_info\":{\"name\":\"Srijan Jaiswal\",\"email\":\"jaiswalsrijan91@gmail.com\",\"phone\":\"09628180970\",\"location\":\"Varanasi UttarPradesh\"},\"summary\":\"Srijan Jaiswal is a B.Tech Computer Science and Engineering student at LPU with strong problem-solving skills in C, C++, and Python. He has hands-on experience in data structures, system programming, and file handling, working on projects like banking systems, linked lists, and encryption programs. Actively preparing for company placements, he has solved recruitment questions from Wipro, Capgemini, and Mettl. Alongside his technical expertise, he has contributed as an instructor at Kranti Foundation Trust and EducationForAll, showcasing leadership and communication skills.\",\"experience\":[{\"job_title\":\"Software Developer Intern\",\"company\":\"TechNova Solutions Pvt. Ltd.\",\"dates\":\"June 2023 \\u2013 September 2023\",\"responsibilities\":\"Developed and optimized C++ programs for data processing and file handling.\\r\\n\\r\\nDesigned and implemented a banking system using object-oriented programming.\\r\\n\\r\\nWorked on linked list and binary tree algorithms for efficient data management.\\r\\n\\r\\nAssisted in debugging and optimizing system-level applications.\\r\\n\\r\\nCollaborated with a team to solve real-world coding challenges and improve algorithm efficiency.\\r\\n\\r\\nDocumented code and provided technical reports for project analysis.\"}],\"education\":[{\"degree\":\"B.Tech in Computer Science and Engineering\",\"institution\":\"Lovely Professional University (LPU)\",\"graduation_year\":\"2027\"}],\"skills\":[\"Programming Languages: C, C++, Python  Data Structures and Algorithms  Object-Oriented Programming (OOP)  File Handling and System Calls  Networking and Routing Protocols  Operating Systems Concepts  Problem-Solving and Competitive Coding\"],\"custom_sections\":[]}', '2025-03-24 09:40:45', '2025-03-24 09:40:45'),
(2, 4, '{\"contact_info\":{\"name\":\"Srijan Jaiswal\",\"email\":\"jaiswalsrijan91@gmail.com\",\"phone\":\"09628180970\",\"location\":\"Varanasi UttarPradesh\"},\"summary\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book\",\"experience\":[{\"job_title\":\"Software Developer Intern\",\"company\":\"Civo\",\"dates\":\"June 2023 \\u2013 September 2023\",\"responsibilities\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit.\\r\\nNam fermentum velit sed mauris mattis accumsan.\\r\\nPhasellus eget elit semper, sollicitudin mauris ut, convallis eros.\\r\\nMorbi convallis arcu a quam egestas, sit amet commodo nunc scelerisque.\\r\\nQuisque laoreet nunc nec leo faucibus, vel lobortis magna sagittis.\"}],\"education\":[{\"degree\":\"B.Tech in Computer Science and Engineering\",\"institution\":\"Lovely Professional University (LPU)\",\"graduation_year\":\"2027\"}],\"skills\":[\"Programming Languages: C, C++, Python  Data Structures and Algorithms  Object-Oriented Programming (OOP)  File Handling and System Calls  Networking and Routing Protocols  Operating Systems Concepts  Problem-Solving and Competitive Coding\"],\"custom_sections\":[]}', '2025-03-25 09:04:50', '2025-03-25 09:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `user_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `user_name`) VALUES
(1, 'Srijan jaiswal', 'jaiswalsrijan1@gmail.com', '$2y$10$bCoHL7CAhLufe.U.JaeizOYSufmAlG1.mpw8kwdRc1YLKKYEtyRoG', 'employer', NULL),
(2, 'KRITI', 'jaiswalsrijan2@gmail.com', '$2y$10$u3ZxSc5bsV6bFswWQx.rguAqJgQZS.YrQv4Na0kfUJ/g1LEAuIt7C', 'jobseeker', NULL),
(3, 'Manav Singh', 'jaiswalsrijan4@gmail.com', '$2y$10$Y6CGAnziLGSH9Q/6MoySSOGjTPscKC7bBGBr3Myd4QEHCMT8C/JCK', 'jobseeker', NULL),
(4, 'Abhay Tomar', 'ab@gmail.com', '$2y$10$oBbsgP73f6nADvADR4rdf.H0KB.1xSpkzNB43r86Vc4qUPqyCJmda', 'jobseeker', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `bookmarked_jobs`
--
ALTER TABLE `bookmarked_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`user_id`,`job_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `hackathons`
--
ALTER TABLE `hackathons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `hackathon_registrations`
--
ALTER TABLE `hackathon_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hackathon_id` (`hackathon_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `interview_sessions`
--
ALTER TABLE `interview_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_id` (`room_id`),
  ADD KEY `user1_id` (`user1_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookmarked_jobs`
--
ALTER TABLE `bookmarked_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `hackathons`
--
ALTER TABLE `hackathons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hackathon_registrations`
--
ALTER TABLE `hackathon_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interview_sessions`
--
ALTER TABLE `interview_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookmarked_jobs`
--
ALTER TABLE `bookmarked_jobs`
  ADD CONSTRAINT `bookmarked_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarked_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hackathons`
--
ALTER TABLE `hackathons`
  ADD CONSTRAINT `hackathons_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `hackathon_registrations`
--
ALTER TABLE `hackathon_registrations`
  ADD CONSTRAINT `hackathon_registrations_ibfk_1` FOREIGN KEY (`hackathon_id`) REFERENCES `hackathons` (`id`),
  ADD CONSTRAINT `hackathon_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `interview_sessions`
--
ALTER TABLE `interview_sessions`
  ADD CONSTRAINT `interview_sessions_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `interview_sessions_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `resumes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
