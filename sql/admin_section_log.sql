-- Create the database
CREATE DATABASE IF NOT EXISTS hr_management_system;
USE hr_management_system;

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    date_hired DATE NOT NULL,
    employment_type ENUM('Regular', 'Probationary', 'Contract', 'Part-time') NOT NULL,
    status ENUM('Active', 'Inactive', 'On Leave', 'Terminated') NOT NULL DEFAULT 'Active',
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Applicants table
CREATE TABLE IF NOT EXISTS applicants (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position_applied VARCHAR(100) NOT NULL,
    date_applied DATE NOT NULL,
    status ENUM('Screening', 'Interview', 'Hired', 'Rejected') NOT NULL DEFAULT 'Screening',
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    resume VARCHAR(255) NOT NULL,
    assigned_hr VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- HR Functions table
CREATE TABLE IF NOT EXISTS hr_functions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    leave_type ENUM('Sick Leave', 'Vacation Leave', 'Maternity Leave', 'Paternity Leave', 
                   'Bereavement Leave', 'Emergency Leave', 'Personal Leave', 'Unpaid Leave') NOT NULL,
    date_filed DATE NOT NULL,
    leave_duration VARCHAR(100) NOT NULL,
    status ENUM('Approved', 'Pending', 'Denied') NOT NULL DEFAULT 'Pending',
    benefit_type VARCHAR(100) NOT NULL,
    benefit_start DATE NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Activity Log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NOT NULL,
    ip_address VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table (for authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'HR', 'Manager', 'Employee') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample employees
INSERT INTO employees (id, name, position, department, date_hired, employment_type, status, contact, email) VALUES
('JPMC-HRD-025', 'ROBIN NOMBRANDO', 'PROCESS ENGINEER', 'Engineering', '2022-03-01', 'Regular', 'Active', '09171234567', 'robin.nombrando@company.com'),
('JPMC-HRD-026', 'JONATHAN RAY Y. ANTIONIO', 'QA SUPERVISOR', 'Quality Assurance', '2021-06-15', 'Regular', 'Active', '09181234567', 'jonathan.antonio@company.com'),
('JPMC-HRD-027', 'ALBERT B. ALACAPA', 'WAREHOUSEMAN', 'Warehouse', '2023-01-10', 'Probationary', 'Active', '09191234567', 'albert.alacapa@company.com'),
('JPMC-HRD-028', 'MARDY AGUILAR', 'MOLD FABRICATOR', 'Production', '2022-11-05', 'Regular', 'Active', '09201234567', 'mardy.aguilar@company.com'),
('JPMC-HRD-029', 'JOHN BRYAN FERRER', 'IT SUPERVISOR', 'IT Department', '2020-02-20', 'Regular', 'Active', '09211234567', 'john.ferrer@company.com'),
('JPMC-HRD-030', 'ANABEL E. PANUNCIAR', 'MACHINE OPERATOR', 'Production', '2023-03-15', 'Probationary', 'Active', '09221234567', 'anabel.panunciar@company.com'),
('JPMC-HRD-031', 'RICKY V. TONGOL', 'QUALITY CONTROL', 'Quality Assurance', '2021-08-25', 'Regular', 'Active', '09231234567', 'ricky.tongol@company.com');

-- Insert sample applicants
INSERT INTO applicants (id, name, position_applied, date_applied, status, contact, email, resume, assigned_hr) VALUES
('APP-001', 'MARIA SANTOS', 'QA SUPERVISOR', '2024-06-01', 'Screening', '09181234567', 'maria.santos@gmail.com', 'resumes/maria_santos.pdf', 'JONATHAN RAY Y. ANTIONIO'),
('APP-002', 'JUAN DELA CRUZ', 'PROCESS ENGINEER', '2024-06-05', 'Interview', '09191234567', 'juan.delacruz@gmail.com', 'resumes/juan_delacruz.pdf', 'ROBIN NOMBRANDO');

-- Insert sample HR functions
INSERT INTO hr_functions (employee_id, name, leave_type, date_filed, leave_duration, status, benefit_type, benefit_start, remarks) VALUES
('JPMC-HRD-025', 'ROBIN NOMBRANDO', 'Sick Leave', '2024-05-20', '2024-05-21 to 2024-05-23', 'Approved', 'Health', '2024-06-01', 'Medical certificate provided'),
('JPMC-HRD-026', 'JONATHAN RAY Y. ANTIONIO', 'Vacation Leave', '2024-05-15', '2024-05-16 to 2024-05-18', 'Pending', 'Paid Time Off', '2024-06-10', 'N/A');

-- Insert sample activity logs
INSERT INTO activity_log (user_id, username, action, details, ip_address) VALUES
(1, 'admin', 'Login', 'User logged in', '192.168.1.10'),
(1, 'admin', 'View', 'Viewed employee list', '192.168.1.10'),
(2, 'hr_user', 'Login', 'User logged in', '192.168.1.15'),
(2, 'hr_user', 'Update', 'Updated employee JPMC-HRD-025', '192.168.1.15');

-- Insert sample users
INSERT INTO users (username, password, role, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin@company.com'),
('hr_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR', 'hr@company.com'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'manager@company.com');
