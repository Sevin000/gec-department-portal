CREATE DATABASE IF NOT EXISTS gec_department_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gec_department_portal;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  short_name VARCHAR(20) NOT NULL UNIQUE,
  description TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE programmes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE department_programmes (
  department_id INT UNSIGNED NOT NULL,
  programme_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (department_id, programme_id),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE faculty (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  designation VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_faculty_name (name),
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE specializations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE faculty_specializations (
  faculty_id INT UNSIGNED NOT NULL,
  specialization_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (faculty_id, specialization_id),
  FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
  FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE facilities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE research_areas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO departments (id, name, short_name, description) VALUES
(1, 'Department of Computer Science and Engineering', 'CSE', 'The Department of Computer Science and Engineering supports undergraduate learning, practical computing skills, research and innovation at Government Engineering College Wayanad.');

INSERT INTO programmes (name) VALUES ('B.Tech Computer Science and Engineering');
INSERT INTO department_programmes (department_id, programme_id) VALUES (1, 1);

INSERT INTO faculty (id, department_id, name, designation) VALUES
(1, 1, 'Prof. Alice A', 'Associate Professor'),
(2, 1, 'Prof. Bob B', 'Assistant Professor'),
(3, 1, 'Prof. Charlie C', 'Assistant Professor'),
(4, 1, 'Prof. Devi D', 'Assistant Professor');

INSERT INTO specializations (name) VALUES
('Artificial Intelligence'), ('Machine Learning'), ('Computer Networks'), ('Cyber Security'), ('Web Technologies'), ('Distributed Systems'), ('Data Science');

INSERT INTO faculty_specializations (faculty_id, specialization_id) VALUES
(1,1),(1,2),(2,3),(2,4),(3,5),(3,6),(4,7),(4,2);

INSERT INTO facilities (department_id, name) VALUES
(1, 'Project Lab'), (1, 'Hardware Lab'), (1, 'Software Development Lab'), (1, 'Innovation and Entrepreneurship Development Centre');

INSERT INTO research_areas (department_id, name) VALUES
(1, 'Artificial Intelligence'), (1, 'Machine Learning'), (1, 'Cyber Security'), (1, 'Computer Networks'), (1, 'Distributed Systems'), (1, 'Web Technologies');
