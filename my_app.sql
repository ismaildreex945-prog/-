CREATE DATABASE IF NOT EXISTS my_app;
USE my_app;

-- حذف الجداول القديمة
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS queries;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;

-- جدول الطلاب
CREATE TABLE students (
    student_number VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    level VARCHAR(20)
);

-- جدول المواد
CREATE TABLE courses (
    course_code VARCHAR(20) PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    department VARCHAR(100)
);

-- جدول تسجيل المواد
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20),
    course_code VARCHAR(20),
    FOREIGN KEY (student_number) REFERENCES students(student_number),
    FOREIGN KEY (course_code) REFERENCES courses(course_code)
);

-- جدول الاستفسارات
CREATE TABLE queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20),
    question TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول النتائج
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20),
    course_name VARCHAR(100),
    grade VARCHAR(10)
);

-- بيانات الطلاب
INSERT INTO students VALUES
('2023001','محمد أحمد','Computer Science','3'),
('2023002','علي محمد','Information Systems','2'),
('2023003','سارة خالد','Software Engineering','4'),
('2023004','عمر حسن','Computer Science','1'),
('2023005','فاطمة إبراهيم','Information Technology','2');

-- المواد
INSERT INTO courses VALUES
('CS101','Programming Fundamentals','Computer Science'),
('CS201','Data Structures','Computer Science'),
('CS301','Database Systems','Computer Science'),
('SE201','Software Engineering','Software Engineering'),
('IT202','Computer Networks','Information Technology');

-- تسجيل المواد
INSERT INTO enrollments(student_number,course_code) VALUES
('2023001','CS101'),
('2023001','CS201'),
('2023001','CS301'),
('2023002','CS101'),
('2023002','IT202'),
('2023003','SE201'),
('2023004','CS101'),
('2023005','IT202');

-- النتائج
INSERT INTO results(student_number,course_name,grade) VALUES
('2023001','Programming Fundamentals','A'),
('2023001','Data Structures','B+'),
('2023001','Database Systems','A'),
('2023002','Programming Fundamentals','B'),
('2023002','Computer Networks','A'),
('2023003','Software Engineering','A+'),
('2023004','Programming Fundamentals','C+'),
('2023005','Computer Networks','B+');
