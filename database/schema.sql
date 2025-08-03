-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'encoder') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- OFFICES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS offices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES offices(id)
);

-- =============================================
-- EXPENSES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fund_type ENUM('General Fund', 'Special Education Fund', 'Trust Fund') NOT NULL,
    bank VARCHAR(100),
    date DATE NOT NULL,
    check_number VARCHAR(50) NOT NULL,
    payee VARCHAR(100) NOT NULL,
    office_id INT,
    sub_office_id INT,
    expense_type ENUM(
        'Personal Services',
        'Maintenance and Other Operating Expenses',
        'Capital Outlay',
        'Cash Advance'
    ) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id),
    FOREIGN KEY (sub_office_id) REFERENCES offices(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =============================================
-- ACTIVITY LOG
-- =============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    user_name TEXT NOT NULL,
    action TEXT NOT NULL,
    table_name TEXT NOT NULL,
    record_id INTEGER NOT NULL,
    old_values TEXT,
    new_values TEXT,
    action_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =============================================
-- OFFICES DATA INSERTION (HIERARCHY)
-- =============================================

-- MAIN OFFICES (parent_id = NULL)
INSERT INTO offices (name) VALUES
('Office of the Mayor'),
('Municipal Civil Registrar'),
('MPDC'),
('Municipal Accounting Office'),
('Municipal Budget Office'),
('Municipal Treasurer''s Office'),
('Municipal Assessor'),
('Sangguniang Bayan'),
('Municipal Health Office'),
('Municipal Agriculture Office'),
('Municipal Engineering Office'),
('SPA');

-- SUB-OFFICES under Office of the Mayor (parent_id = 1)
INSERT INTO offices (name, parent_id) VALUES
('Mayor', 1),
('GSO', 1),
('BPLO', 1),
('MENRO', 1),
('Tourism', 1),
('HR', 1),
('Public Market', 1),
('PESO', 1),
('MSWD', 1);

-- SUB-OFFICES under SPA (parent_id = 12)
INSERT INTO offices (name, parent_id) VALUES
('Municipal Development Fund', 12),
('GAD', 12),
('LCPC', 12),
('PWD', 12),
('SC', 12),
('DRRMF 70%', 12),
('DRRMF 30%', 12),
('Peace and Order', 12);

-- =============================================
-- SAMPLE USER DATA
-- =============================================

-- Default Admin: password = admin123 (bcrypt hash)
-- Default Encoder: password = encoder123 (same hash)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'),
('encoder', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', 'encoder');
