-- Finished Goods (Inventory)
CREATE TABLE IF NOT EXISTS finished_goods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock INT DEFAULT 0,
    stock_in INT DEFAULT 0,
    stock_out INT DEFAULT 0,
    status VARCHAR(50) DEFAULT NULL
);

-- Transactions (Inventory Log)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    transaction_id VARCHAR(50) NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    product VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    user VARCHAR(100) NOT NULL,
    remarks TEXT
);

-- HR History
CREATE TABLE IF NOT EXISTS hr_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) NOT NULL,
    last_action DATETIME NOT NULL
);

-- Inventory History
CREATE TABLE IF NOT EXISTS inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock INT DEFAULT 0,
    stock_in INT DEFAULT 0,
    stock_out INT DEFAULT 0,
    status VARCHAR(50) DEFAULT NULL
);

-- Customer Feedback History
CREATE TABLE IF NOT EXISTS customer_feedback_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    feedback TEXT NOT NULL,
    rating INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    action VARCHAR(100) DEFAULT NULL
);
