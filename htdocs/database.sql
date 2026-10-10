-- ============================================================
--  TaskFlow — Project Management System
--  MySQL / MariaDB schema + seed data
--  Import:  mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS taskflow
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taskflow;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------- USERS -----------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('admin','manager','employee') NOT NULL DEFAULT 'employee',
  phone         VARCHAR(30)  DEFAULT NULL,
  department    VARCHAR(80)  DEFAULT NULL,
  job_title     VARCHAR(100) DEFAULT NULL,
  avatar        VARCHAR(255) DEFAULT NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  theme         ENUM('light','dark') NOT NULL DEFAULT 'light',
  bio           TEXT DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------- PROJECTS -----------------------
CREATE TABLE IF NOT EXISTS projects (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  description   TEXT,
  status        ENUM('planning','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
  priority      ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  start_date    DATE DEFAULT NULL,
  deadline      DATE DEFAULT NULL,
  budget        DECIMAL(12,2) DEFAULT 0.00,
  spent         DECIMAL(12,2) DEFAULT 0.00,
  progress      TINYINT NOT NULL DEFAULT 0,
  created_by    INT NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_proj_owner FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------- PROJECT MEMBERS -----------------------
CREATE TABLE IF NOT EXISTS project_members (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  project_id  INT NOT NULL,
  user_id     INT NOT NULL,
  role        ENUM('manager','member') NOT NULL DEFAULT 'member',
  UNIQUE KEY uniq_proj_user (project_id, user_id),
  CONSTRAINT fk_pm_proj FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------- TASKS -----------------------
CREATE TABLE IF NOT EXISTS tasks (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  project_id    INT NOT NULL,
  title         VARCHAR(200) NOT NULL,
  description   TEXT,
  status        ENUM('todo','in_progress','review','completed') NOT NULL DEFAULT 'todo',
  priority      ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  assigned_to   INT DEFAULT NULL,
  created_by    INT NOT NULL,
  deadline      DATE DEFAULT NULL,
  progress      TINYINT NOT NULL DEFAULT 0,
  estimated_hours DECIMAL(6,2) DEFAULT 0.00,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_task_proj FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_task_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_task_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------- COMMENTS -----------------------
CREATE TABLE IF NOT EXISTS comments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  task_id     INT NOT NULL,
  user_id     INT NOT NULL,
  comment     TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cmt_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_cmt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------- ATTACHMENTS -----------------------
CREATE TABLE IF NOT EXISTS attachments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  task_id     INT NOT NULL,
  file_name   VARCHAR(255) NOT NULL,
  file_path   VARCHAR(500) NOT NULL,
  file_size   INT DEFAULT 0,
  file_type   VARCHAR(100) DEFAULT NULL,
  uploaded_by INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------- NOTIFICATIONS -----------------------
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  title       VARCHAR(200) NOT NULL,
  message     VARCHAR(500) NOT NULL,
  link        VARCHAR(255) DEFAULT NULL,
  type        VARCHAR(40)  DEFAULT 'info',
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------- ACTIVITY LOGS -----------------------
CREATE TABLE IF NOT EXISTS activity_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT DEFAULT NULL,
  action       VARCHAR(100) NOT NULL,
  entity_type  VARCHAR(50)  DEFAULT NULL,
  entity_id    INT DEFAULT NULL,
  description  TEXT,
  ip_address   VARCHAR(45)  DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  SEED DATA  (passwords are bcrypt-hashed below)
--  admin@taskflow.com   /  admin123
--  manager@taskflow.com /  manager123
--  employee@taskflow.com/  employee123
-- ============================================================

INSERT INTO users (name, email, password, role, phone, department, job_title, status, theme, bio) VALUES
('System Admin', 'admin@taskflow.com', '$2y$12$aIREKEPfqXlZ7u/d9iROcOKDvz8wYY8x2t9hhm4mmBcI.knwJFz1O', 'admin', '+1-555-0100', 'Administration', 'System Administrator', 'active', 'light', 'Full system administrator with complete access.'),
('Sarah Manager', 'manager@taskflow.com', '$2y$12$9f9mLHGsnbSM7fU05yBhdO7CX5Rtq7RPP4uC67Nsp6lNZPf7wAvou', 'manager', '+1-555-0101', 'Product', 'Product Manager', 'active', 'light', 'Project manager overseeing delivery.'),
('John Employee', 'employee@taskflow.com', '$2y$12$gpynBrqAHe3mG3D1owXsxOto.bzjJftbBiQrwCORJmofJQ3OTU/p6', 'employee', '+1-555-0102', 'Engineering', 'Software Engineer', 'active', 'light', 'Frontend developer on the platform team.'),
('Emily Chen', 'emily@taskflow.com', '$2y$12$D9NQisvv9SXt0.8wkNosdOguDfLMUe0LnoPfSlFUpdW9DzYsZ.JOS', 'employee', '+1-555-0103', 'Design', 'UX Designer', 'active', 'light', 'Product designer focused on user research.'),
('Michael Brown', 'michael@taskflow.com', '$2y$12$D9NQisvv9SXt0.8wkNosdOguDfLMUe0LnoPfSlFUpdW9DzYsZ.JOS', 'employee', '+1-555-0104', 'Engineering', 'Backend Engineer', 'active', 'light', 'Backend engineer working on APIs.'),
('Lisa Garcia', 'lisa@taskflow.com', '$2y$12$9f9mLHGsnbSM7fU05yBhdO7CX5Rtq7RPP4uC67Nsp6lNZPf7wAvou', 'manager', '+1-555-0105', 'Marketing', 'Marketing Lead', 'active', 'light', 'Leads go-to-market campaigns.');

-- Projects
INSERT INTO projects (name, description, status, priority, start_date, deadline, budget, spent, progress, created_by) VALUES
('TaskFlow Platform 2.0', 'Major redesign and feature expansion of the core SaaS platform including real-time collaboration.', 'active', 'high', '2026-06-01', '2026-10-30', 150000.00, 62500.00, 55, 2),
('Mobile App Launch', 'Native iOS and Android applications with offline sync capabilities.', 'active', 'urgent', '2026-07-15', '2026-09-15', 80000.00, 34000.00, 40, 2),
('Marketing Website Refresh', 'Complete rebuild of the public marketing site with new brand identity.', 'planning', 'medium', '2026-08-20', '2026-11-30', 35000.00, 2000.00, 10, 6),
('Enterprise SSO Integration', 'Add SAML/OIDC single sign-on for enterprise customers.', 'on_hold', 'medium', '2026-05-10', '2026-08-25', 45000.00, 28000.00, 70, 1),
('Q4 Customer Survey', 'Run the annual customer satisfaction survey and analyze results.', 'planning', 'low', '2026-09-01', '2026-12-15', 12000.00, 0.00, 0, 6),
('Legacy Data Migration', 'Migrate 5 years of legacy records to the new schema.', 'completed', 'medium', '2026-03-01', '2026-06-30', 22000.00, 21500.00, 100, 1);

-- Project members
INSERT INTO project_members (project_id, user_id, role) VALUES
(1,2,'manager'),(1,3,'member'),(1,4,'member'),(1,5,'member'),
(2,2,'manager'),(2,3,'member'),(2,5,'member'),
(3,6,'manager'),(3,4,'member'),
(4,1,'manager'),(4,5,'member'),
(5,6,'manager'),(5,4,'member'),
(6,1,'manager'),(6,5,'member'),(6,3,'member');

-- Tasks
INSERT INTO tasks (project_id, title, description, status, priority, assigned_to, created_by, deadline, progress, estimated_hours) VALUES
(1,'Design new dashboard layout','Create high-fidelity mockups for the redesigned dashboard with new chart widgets.','completed','high',4,2,'2026-07-10',100,16),
(1,'Build dashboard data API','REST endpoints serving aggregated project statistics for the dashboard.','in_progress','high',5,2,'2026-08-20',60,24),
(1,'Implement dark mode toggle','Theme switching with persisted user preference and CSS variables.','completed','medium',3,2,'2026-07-25',100,8),
(1,'Setup CI/CD pipeline','Automated testing and deployment pipeline with staging environment.','review','high',5,2,'2026-08-15',85,20),
(1,'Write onboarding flow','Multi-step onboarding wizard for new signups.','todo','medium',3,2,'2026-09-05',0,12),
(2,'iOS push notifications','Implement APNs integration and notification center.','in_progress','urgent',5,2,'2026-08-30',45,18),
(2,'Offline sync engine','Conflict-free offline data sync using CRDT approach.','todo','high',5,2,'2026-09-10',0,32),
(2,'Android navigation rewrite','Migrate to Jetpack Navigation Compose.','in_progress','medium',3,2,'2026-08-25',30,14),
(3,'Brand color exploration','Mood boards and color palette proposals.','todo','medium',4,6,'2026-09-01',0,10),
(4,'SAML metadata parsing','Parse and validate IdP SAML metadata documents.','review','high',5,1,'2026-08-20',50,16),
(6,'Archive legacy database','Snapshot and archive the old production database.','completed','low',3,1,'2026-06-15',100,6),
(5,'Draft survey questions','Author the customer satisfaction questionnaire.','todo','low',4,6,'2026-09-15',0,8);

-- Fix the invalid status above (defensive) — ensure clean statuses
UPDATE tasks SET status='in_progress' WHERE status NOT IN ('todo','in_progress','review','completed');

-- Comments
INSERT INTO comments (task_id, user_id, comment) VALUES
(1,2,'The dashboard mockups look great. Can we add a quick filter bar?'),
(1,4,'Sure — I''ll add filter chips in the next revision.'),
(2,5,'API is 60% done. Need the chart aggregation queries reviewed.'),
(4,2,'CI pipeline is running. Please review the staging deployment config.'),
(6,5,'APNs certificates are configured. Testing push delivery now.');

-- Notifications
INSERT INTO notifications (user_id, title, message, link, type) VALUES
(2,'Task assigned','A new task "Write onboarding flow" was created.','task_view.php?id=5','task'),
(3,'Task assigned','You were assigned "Android navigation rewrite".','task_view.php?id=8','task'),
(5,'Task updated','"Build dashboard data API" progress updated to 60%.','task_view.php?id=2','task'),
(2,'Deadline approaching','Mobile App Launch deadline is in 35 days.','project_view.php?id=2','warning'),
(3,'Comment mention','Sarah mentioned you in a comment.','task_view.php?id=1','comment');

-- Activity logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES
(2,'created','project',1,'Created project "TaskFlow Platform 2.0"','127.0.0.1'),
(2,'created','task',1,'Created task "Design new dashboard layout"','127.0.0.1'),
(4,'commented','task',1,'Commented on "Design new dashboard layout"','127.0.0.1'),
(5,'updated','task',2,'Updated progress on "Build dashboard data API"','127.0.0.1'),
(1,'completed','project',6,'Completed project "Legacy Data Migration"','127.0.0.1'),
(2,'created','project',2,'Created project "Mobile App Launch"','127.0.0.1');

-- ============================================================
--  Helpful indexes
-- ============================================================
CREATE INDEX idx_tasks_project ON tasks(project_id);
CREATE INDEX idx_tasks_assignee ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_notif_user ON notifications(user_id, is_read);
CREATE INDEX idx_logs_user ON activity_logs(user_id);
