-- Skill Academy Marketplace (PHP + MySQL)
-- Create DB and tables, then seed 30 courses (15 free + 15 paid).
--
-- Usage (phpMyAdmin or mysql):
--   SOURCE C:/xampp/htdocs/skill-academy/database/schema.sql;

DROP DATABASE IF EXISTS skill_academy;
CREATE DATABASE skill_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skill_academy;

-- Users: only 'student' or 'teacher'
CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('student','teacher') NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE courses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  teacher_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(80) NOT NULL,
  level ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
  is_paid TINYINT(1) NOT NULL DEFAULT 0,
  price_inr INT UNSIGNED NOT NULL DEFAULT 0,
  youtube_playlist_url VARCHAR(500) NOT NULL,
  thumbnail_url VARCHAR(500) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courses_slug (slug),
  KEY idx_courses_teacher (teacher_id),
  KEY idx_courses_paid (is_paid),
  CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  course_id BIGINT UNSIGNED NOT NULL,
  enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enroll_student_course (student_id, course_id),
  KEY idx_enroll_course (course_id),
  KEY idx_enroll_student (student_id),
  CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_progress (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  item_key VARCHAR(80) NOT NULL, -- e.g. "playlist-1", "playlist-2"...
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_progress (enrollment_id, item_key),
  KEY idx_progress_enrollment (enrollment_id),
  CONSTRAINT fk_progress_enroll FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  certificate_code CHAR(12) NOT NULL,
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cert_enrollment (enrollment_id),
  UNIQUE KEY uq_cert_code (certificate_code),
  CONSTRAINT fk_cert_enroll FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE doubts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  course_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(160) NOT NULL,
  question TEXT NOT NULL,
  status ENUM('open','answered') NOT NULL DEFAULT 'open',
  teacher_reply TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  answered_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_doubts_course (course_id),
  KEY idx_doubts_status (status),
  CONSTRAINT fk_doubts_student FOREIGN KEY (student_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_doubts_course FOREIGN KEY (course_id) REFERENCES courses(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contacts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Seed: one default teacher account (password: Teacher@123)
INSERT INTO users (role, full_name, email, password_hash)
VALUES ('teacher', 'Default Teacher', 'teacher@skillacademy.local',
        '$2y$10$ZllOUXj48K9Me8b.vpvzP.O0NvxE6S9.e1Ggu92MWoGezpaxC6uXW');

-- Seed 30 courses (15 free, 15 paid) owned by teacher_id=1.
-- Note: playlist URLs are examples; replace with real playlists anytime.
INSERT INTO courses
  (teacher_id, title, slug, description, category, level, is_paid, price_inr, youtube_playlist_url, thumbnail_url)
VALUES
  (1,'C Programming Fundamentals','c-programming-fundamentals','Learn C from basics to pointers with practice problems (4 hour full course).','BTech Core','Beginner',0,0,'https://www.youtube.com/watch?v=87SH2Cn0s9A','https://i.ytimg.com/vi/87SH2Cn0s9A/hqdefault.jpg'),
  (1,'Data Structures in C (Playlist)','data-structures-c','Arrays, stacks, queues, linked lists, trees and graphs in C.','BTech Core','Intermediate',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqDSA_C','https://i.ytimg.com/vi/8hly31xKli0/hqdefault.jpg'),
  (1,'OOP with Java','oop-with-java','Object-oriented concepts with Java examples and mini projects.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqJAVA_OOP','https://i.ytimg.com/vi/grEKMHGYyns/hqdefault.jpg'),
  (1,'Python for Engineers','python-for-engineers','Python essentials for engineering students: scripts, files, data.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqPY_ENG','https://i.ytimg.com/vi/rfscVS0vtbw/hqdefault.jpg'),
  (1,'DBMS Fundamentals','dbms-fundamentals','ER model, normalization, transactions, indexing and SQL basics.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqDBMS','https://i.ytimg.com/vi/HXV3zeQKqGY/hqdefault.jpg'),
  (1,'Operating Systems Basics','operating-systems-basics','Processes, threads, scheduling, memory, and file systems.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqOS','https://i.ytimg.com/vi/26QPDBe-NB8/hqdefault.jpg'),
  (1,'Computer Networks Basics','computer-networks-basics','OSI/TCP-IP, routing, switching, HTTP, DNS, and security basics.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqCN','https://i.ytimg.com/vi/qiQR5rTSshw/hqdefault.jpg'),
  (1,'Web Basics: HTML + CSS','web-basics-html-css','Build responsive pages with HTML5 and CSS3 fundamentals.','Web','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqHTML_CSS','https://i.ytimg.com/vi/pQN-pnXPaVg/hqdefault.jpg'),
  (1,'JavaScript ES6 Essentials','javascript-es6-essentials','Modern JavaScript: ES6, DOM, fetch, modules, and patterns.','Web','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqJS_ES6','https://i.ytimg.com/vi/W6NZfCO5SIk/hqdefault.jpg'),
  (1,'Git & GitHub for Students','git-github-for-students','Version control workflows for projects and internships.','Tools','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqGIT','https://i.ytimg.com/vi/8JJ101D3knE/hqdefault.jpg'),
  (1,'Aptitude for Placements','aptitude-for-placements','Quantitative aptitude and reasoning for campus placements.','Placement','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqAPT','https://i.ytimg.com/vi/3Q_u1jK1bO8/hqdefault.jpg'),
  (1,'Basics of Cybersecurity','basics-of-cybersecurity','Threats, OWASP basics, passwords, and safe browsing practices.','Security','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqCYBER','https://i.ytimg.com/vi/inWWhr5tnEA/hqdefault.jpg'),
  (1,'Intro to Cloud (AWS Basics)','intro-to-cloud-aws-basics','Cloud concepts with AWS services overview for beginners.','Cloud','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqAWS','https://i.ytimg.com/vi/ulprqHHWlng/hqdefault.jpg'),
  (1,'Linux Command Line Basics','linux-command-line-basics','Linux navigation, permissions, processes, and scripting basics.','Tools','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqLINUX','https://i.ytimg.com/vi/oxuRxtrO2Ag/hqdefault.jpg'),
  (1,'Mathematics for Engineers (Quick)','math-for-engineers-quick','Key math topics: calculus, matrices, probability essentials.','BTech Core','Beginner',0,0,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqMATH','https://i.ytimg.com/vi/WUvTyaaNkzM/hqdefault.jpg'),

  (1,'DSA Mastery (Paid)','dsa-mastery-paid','Interview-ready DSA with problems, patterns and mock tests.','Placement','Intermediate',1,499,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqDSA_PAID','https://i.ytimg.com/vi/8hly31xKli0/hqdefault.jpg'),
  (1,'Full Stack Web Dev (Paid)','full-stack-web-dev-paid','HTML/CSS/JS + PHP + MySQL: build real projects end-to-end.','Web','Intermediate',1,799,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqFULLSTACK','https://i.ytimg.com/vi/Zftx68K-1D4/hqdefault.jpg'),
  (1,'Java Backend (Paid)','java-backend-paid','Servlets, JDBC, MVC basics and deployment concepts.','Backend','Intermediate',1,699,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqJAVA_BACKEND','https://i.ytimg.com/vi/eIrMbAQSU34/hqdefault.jpg'),
  (1,'Advanced DBMS + SQL (Paid)','advanced-dbms-sql-paid','Complex queries, indexing strategies, and performance tuning.','BTech Core','Intermediate',1,599,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqADV_DBMS','https://i.ytimg.com/vi/HXV3zeQKqGY/hqdefault.jpg'),
  (1,'Operating Systems Deep Dive (Paid)','operating-systems-deep-dive-paid','Deadlocks, virtual memory, synchronization and OS case studies.','BTech Core','Advanced',1,599,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqOS_ADV','https://i.ytimg.com/vi/26QPDBe-NB8/hqdefault.jpg'),
  (1,'Computer Networks Advanced (Paid)','computer-networks-advanced-paid','Subnetting, routing protocols, NAT, and network security labs.','BTech Core','Advanced',1,599,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqCN_ADV','https://i.ytimg.com/vi/qiQR5rTSshw/hqdefault.jpg'),
  (1,'System Design for Freshers (Paid)','system-design-for-freshers-paid','Learn scalable design basics: caching, DB, queues, APIs.','Placement','Intermediate',1,899,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqSYS_DESIGN','https://i.ytimg.com/vi/UzLMhqg3_Wc/hqdefault.jpg'),
  (1,'React Basics (Paid)','react-basics-paid','React fundamentals with mini projects and state patterns.','Web','Beginner',1,699,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqREACT','https://i.ytimg.com/vi/bMknfKXIFA8/hqdefault.jpg'),
  (1,'Node.js + Express (Paid)','nodejs-express-paid','Build REST APIs using Node.js, Express, and MySQL basics.','Backend','Intermediate',1,699,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqNODE','https://i.ytimg.com/vi/Oe421EPjeBE/hqdefault.jpg'),
  (1,'DevOps Starter (Paid)','devops-starter-paid','CI/CD basics, Docker overview, and deployment workflows.','DevOps','Beginner',1,799,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqDEVOPS','https://i.ytimg.com/vi/3c-iBn73dDE/hqdefault.jpg'),
  (1,'Cybersecurity Hands-on (Paid)','cybersecurity-hands-on-paid','OWASP Top 10 labs and defensive security foundations.','Security','Intermediate',1,999,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqCYBER_LABS','https://i.ytimg.com/vi/inWWhr5tnEA/hqdefault.jpg'),
  (1,'Machine Learning Basics (Paid)','machine-learning-basics-paid','ML concepts, preprocessing, models and evaluation basics.','AI/ML','Beginner',1,899,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqML','https://i.ytimg.com/vi/GwIo3gDZCVQ/hqdefault.jpg'),
  (1,'Android App Dev (Paid)','android-app-dev-paid','Android fundamentals, activities, intents, and API calls.','Mobile','Beginner',1,799,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqANDROID','https://i.ytimg.com/vi/fis26HvvDII/hqdefault.jpg'),
  (1,'Placement Bootcamp (Paid)','placement-bootcamp-paid','Resume, interview prep, DSA plan, and mock interviews.','Placement','Intermediate',1,1299,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqPLACEMENT','https://i.ytimg.com/vi/3Q_u1jK1bO8/hqdefault.jpg'),
  (1,'Final Year Project Guidance (Paid)','final-year-project-guidance-paid','Project ideas, documentation, and presentation guidance.','Academics','Beginner',1,999,'https://www.youtube.com/playlist?list=PLcQH1K1R1pKqFYP','https://i.ytimg.com/vi/9bZkp7q19f0/hqdefault.jpg');

