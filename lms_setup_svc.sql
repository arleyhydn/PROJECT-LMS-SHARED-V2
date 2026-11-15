SET NAMES utf8mb4;
DROP DATABASE IF EXISTS lms_admin_svc;
DROP DATABASE IF EXISTS lms_announcement_svc;
DROP DATABASE IF EXISTS lms_assignment_svc;
DROP DATABASE IF EXISTS lms_attendance_svc;
DROP DATABASE IF EXISTS lms_auth_svc;
DROP DATABASE IF EXISTS lms_course_svc;
DROP DATABASE IF EXISTS lms_enrollment_svc;
DROP DATABASE IF EXISTS lms_grade_svc;
DROP DATABASE IF EXISTS lms_messaging_svc;
DROP DATABASE IF EXISTS lms_planning_svc;
DROP DATABASE IF EXISTS lms_user_svc;

CREATE DATABASE IF NOT EXISTS lms_auth_svc         CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_user_svc         CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_course_svc       CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_enrollment_svc   CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_announcement_svc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_planning_svc     CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_assignment_svc   CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_grade_svc        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_attendance_svc   CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_messaging_svc    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS lms_admin_svc        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS lms_auth_svc.sessions (
  session_id CHAR(64) NOT NULL PRIMARY KEY,
  user_id    INT NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sessions_user (user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_auth_svc.login_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  email_attempted VARCHAR(255) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_user_svc.roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_user_svc.users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name  VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  program_id INT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_users_role (role_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_user_svc.onboarding_settings (
  setting_key VARCHAR(64) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_user_svc.onboarding_progress (
  user_id INT NOT NULL PRIMARY KEY,
  role VARCHAR(20) NOT NULL,
  current_step INT UNSIGNED NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_course_svc.programs (
  program_id INT AUTO_INCREMENT PRIMARY KEY,
  program_name VARCHAR(255) NOT NULL,
  program_code VARCHAR(30) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_course_svc.courses (
  course_id INT AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(30) NOT NULL,
  course_name VARCHAR(255) NOT NULL,
  program_id INT NOT NULL DEFAULT 1,
  created_by INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_course_svc.course_schedule (
  schedule_id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  day_of_week VARCHAR(10) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  room VARCHAR(50) NULL,
  KEY idx_sched_course (course_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_course_svc.course_staff (
  course_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY (course_id,user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_enrollment_svc.enrollments (
  enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  approved_by INT NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_course (student_id,course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_announcement_svc.announcements (
  announcement_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  announcement_type VARCHAR(20) NOT NULL DEFAULT 'admin',
  program_id INT NOT NULL DEFAULT 0,
  course_id INT NOT NULL DEFAULT 0,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  posted_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ann_course (course_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_announcement_svc.announcement_reactions (
  announcement_id INT NOT NULL,
  user_id INT NOT NULL,
  reaction ENUM('like','dislike') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (announcement_id,user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_announcement_svc.announcement_reads (
  announcement_id INT NOT NULL,
  user_id INT NOT NULL,
  read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (announcement_id,user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_planning_svc.todos (
  todo_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  due_date DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_todos_user (user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_planning_svc.calendar_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  event_date DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_assignment_svc.assignments (
  assignment_id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'assignment',
  due_date DATETIME NULL,
  max_score DECIMAL(8,2) NOT NULL DEFAULT 100,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  posted_by INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_assign_course (course_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_assignment_svc.submissions (
  submission_id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  student_id INT NOT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('submitted','graded','returned') NOT NULL DEFAULT 'submitted',
  file_path VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_assignment_student (assignment_id,student_id),
  KEY idx_submissions_status (status),
  KEY idx_submissions_student (student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_grade_svc.grade_components (
  component_id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  weight DECIMAL(5,2) NOT NULL DEFAULT 0,
  KEY idx_comp_course (course_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_grade_svc.grades (
  grade_id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT NOT NULL,
  assignment_id INT NOT NULL,
  component_id INT NULL,
  score DECIMAL(8,2) NOT NULL,
  max_score DECIMAL(8,2) NOT NULL,
  remarks VARCHAR(255) NOT NULL DEFAULT '',
  graded_by INT NOT NULL,
  graded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enr_assign (enrollment_id,assignment_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS lms_grade_svc.grade_history (
  history_id INT AUTO_INCREMENT PRIMARY KEY,
  grade_id INT NOT NULL,
  old_score DECIMAL(8,2) NULL,
  new_score DECIMAL(8,2) NULL,
  changed_by INT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_attendance_svc.attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT NOT NULL,
  session_date DATE NOT NULL,
  status VARCHAR(10) NOT NULL,
  recorded_by INT NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enr_date (enrollment_id,session_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_messaging_svc.messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  parent_message_id INT NOT NULL DEFAULT 0,
  subject VARCHAR(255) NOT NULL DEFAULT 'Message',
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_sender (sender_id),
  KEY idx_msg_receiver (receiver_id,is_read),
  KEY idx_msg_conversation (sender_id,receiver_id,sent_at,message_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lms_admin_svc.admin_audit_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action_type VARCHAR(30) NOT NULL,
  target_table VARCHAR(60) NOT NULL,
  target_id INT NOT NULL DEFAULT 0,
  description VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO lms_user_svc.roles(role_id,role_name) VALUES (1,'Professor'),(2,'Admin'),(3,'Student');
INSERT IGNORE INTO lms_course_svc.programs(program_id,program_name,program_code) VALUES (1,'Bachelor of Science in Information Technology','BSIT');

INSERT IGNORE INTO lms_user_svc.users(user_id,first_name,last_name,email,password_hash,role_id,program_id,status) VALUES
(2,'Hansi Denise','Abetria','hdabetria@feu.edu.ph','$2y$10$LfuUEVZAfUnEkAtE2RcliuiA4.5roYalZbdW278zfNvU8J7Bb3rcy',3,1,'active'),
(3,'Maria','Santos','msantos@feu.edu.ph','$2y$10$LfuUEVZAfUnEkAtE2RcliuiA4.5roYalZbdW278zfNvU8J7Bb3rcy',1,NULL,'active'),
(4,'System','Admin','admin@feu.edu.ph','$2y$10$LfuUEVZAfUnEkAtE2RcliuiA4.5roYalZbdW278zfNvU8J7Bb3rcy',2,NULL,'active');

INSERT IGNORE INTO lms_course_svc.courses(course_id,course_code,course_name,program_id,created_by) VALUES
(1,'IT271','Human Computer Interaction',1,3),(2,'IT261','Information Management',1,3),(3,'IT121','Fundamentals of Programming',1,3),
(4,'IT251','Networking',1,3),(5,'IT241','Research Methodologies',1,3),(6,'IT231','Approaches to Culture',1,3);
INSERT IGNORE INTO lms_course_svc.course_schedule(schedule_id,course_id,day_of_week,start_time,end_time,room) VALUES
(1,1,'Mon','13:15','15:00','Room 301'),(2,1,'Thu','13:15','15:00','Room 301'),(3,2,'Tue','09:00','10:30','Room 205'),(4,2,'Fri','09:00','10:30','Room 205'),
(5,3,'Wed','10:30','12:00','CompLab 1'),(6,3,'Sat','10:30','12:00','CompLab 1'),(7,4,'Mon','11:00','12:00','Room 204'),(8,4,'Thu','11:00','12:00','Room 204'),
(9,5,'Tue','13:00','14:00','Room 102'),(10,6,'Tue','16:00','17:00','Room 110');
INSERT IGNORE INTO lms_course_svc.course_staff(course_id,user_id) SELECT course_id,created_by FROM lms_course_svc.courses;

INSERT IGNORE INTO lms_enrollment_svc.enrollments(enrollment_id,student_id,course_id,status) VALUES
(1,2,1,'approved'),(2,2,2,'approved'),(3,2,3,'approved'),(4,2,4,'approved'),(5,2,5,'approved'),(6,2,6,'approved');

INSERT IGNORE INTO lms_announcement_svc.announcements(announcement_id,title,content,announcement_type,program_id,course_id,is_pinned,is_archived,posted_by,created_at) VALUES
(1,'Midterm Examination Schedule Released','The midterm examination schedule for all BSIT subjects has been officially released. Bring your school ID on exam day.','admin',1,0,1,0,4,NOW()),
(2,'HCI Exercise 2 Deadline Extended','The deadline for Exercise 2 - UI Design has been extended.','professor',1,1,0,0,3,NOW());

INSERT IGNORE INTO lms_planning_svc.todos(todo_id,user_id,title,status,due_date,created_at) VALUES
(1,2,'HCI Exercise 2 - UI Design Prototype','pending',NOW() + INTERVAL 3 DAY,NOW()),
(2,2,'Programming Activity 4 - Functions & Arrays','pending',NOW() + INTERVAL 4 DAY,NOW());

INSERT IGNORE INTO lms_assignment_svc.assignments(assignment_id,course_id,title,type,due_date,max_score,status,posted_by) VALUES
(1,1,'Exercise 2 - UI Design Prototype','assignment',NOW() + INTERVAL 3 DAY,100,'published',3),
(2,3,'Activity 4 - Functions & Arrays','assignment',NOW() + INTERVAL 4 DAY,50,'published',3);
INSERT IGNORE INTO lms_user_svc.users(user_id,first_name,last_name,email,password_hash,role_id,program_id,status) VALUES (1,'Juan Miguel','Lauron','pogiako123@gmail.com','$2y$10$HLD/FxHXjPVUcvwhJHZ.kuOrkKVDTVIaqQBuTVSfw3bhn2AJgmRKO',1,NULL,'active');
