-- Table for Inventory Transactions (for Transaction Log, Monthly Summary, Low Stock)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    transaction_id VARCHAR(50) NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    product VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    user VARCHAR(100),
    remarks VARCHAR(255)
);

-- Table for Finished Goods (for Low Stock Report)
CREATE TABLE IF NOT EXISTS finished_goods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock INT NOT NULL,
    status VARCHAR(50)
);

-- Table for Finances (for Finance History)
CREATE TABLE IF NOT EXISTS finances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    transaction VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    account VARCHAR(100),
    remarks VARCHAR(255)
);

-- Table for HR History (for Human Resources Report)
CREATE TABLE IF NOT EXISTS hr_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100),
    status VARCHAR(50),
    last_action DATETIME
);

-- Table for Inventory History (for Inventory History Report)
CREATE TABLE IF NOT EXISTS inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock INT NOT NULL,
    stock_in INT DEFAULT 0,
    stock_out INT DEFAULT 0,
    status VARCHAR(50)
);

-- Table for Customer Feedback History (for Feedback Report)
CREATE TABLE IF NOT EXISTS customer_feedback_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    customer_name VARCHAR(255),
    email VARCHAR(255),
    feedback TEXT,
    rating INT,
    status VARCHAR(50),
    action VARCHAR(100)
);
