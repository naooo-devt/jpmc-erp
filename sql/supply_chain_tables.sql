-- Supply Chain Management Database Tables
-- This file contains the SQL to create tables for suppliers, purchase orders, deliveries, and related entities

-- Suppliers table
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `rating` int(1) DEFAULT 3,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Orders table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date NOT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Order Items table
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `raw_material_id` (`raw_material_id`),
  CONSTRAINT `fk_poi_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_raw_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deliveries table
CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_number` varchar(50) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `received_date` date NULL,
  `status` enum('Scheduled','In Transit','Delivered','Cancelled') DEFAULT 'Scheduled',
  `carrier` varchar(100) NULL,
  `tracking_number` varchar(100) NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_number` (`delivery_number`),
  KEY `purchase_order_id` (`purchase_order_id`),
  CONSTRAINT `fk_delivery_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for suppliers
INSERT INTO `suppliers` (`name`, `contact_person`, `email`, `phone`, `address`, `rating`, `status`) VALUES
('ABC Polymers Inc.', 'John Smith', 'john.smith@abcpolymers.com', '+63 912 345 6789', '123 Polymer Street, Makati City, Metro Manila', 5, 'Active'),
('XYZ Materials Corp.', 'Maria Garcia', 'maria.garcia@xyzmaterials.com', '+63 923 456 7890', '456 Industrial Ave, Quezon City, Metro Manila', 4, 'Active'),
('PolyTech Solutions', 'Robert Johnson', 'robert.johnson@polytech.com', '+63 934 567 8901', '789 Manufacturing Blvd, Taguig City, Metro Manila', 4, 'Active'),
('Global Polymers Ltd.', 'Sarah Wilson', 'sarah.wilson@globalpolymers.com', '+63 945 678 9012', '321 Export Road, Pasig City, Metro Manila', 3, 'Active'),
('Premium Materials Co.', 'Michael Brown', 'michael.brown@premiummaterials.com', '+63 956 789 0123', '654 Quality Street, Mandaluyong City, Metro Manila', 5, 'Active');

-- Sample purchase orders
INSERT INTO `purchase_orders` (`order_number`, `supplier_id`, `order_date`, `expected_delivery`, `status`, `total_amount`, `notes`) VALUES
('PO-20241201-1001', 1, '2024-12-01', '2024-12-15', 'Approved', 150000.00, 'Regular monthly order for ABS materials'),
('PO-20241202-1002', 2, '2024-12-02', '2024-12-20', 'Pending', 85000.00, 'PP materials for new product line'),
('PO-20241203-1003', 3, '2024-12-03', '2024-12-18', 'Completed', 120000.00, 'Nylon materials for automotive parts'),
('PO-20241204-1004', 4, '2024-12-04', '2024-12-25', 'Approved', 95000.00, 'PS materials for packaging'),
('PO-20241205-1005', 5, '2024-12-05', '2024-12-30', 'Pending', 180000.00, 'HIPS materials for consumer goods');

-- Sample purchase order items
INSERT INTO `purchase_order_items` (`purchase_order_id`, `raw_material_id`, `quantity`, `unit_price`) VALUES
(1, 1, 100.00, 1500.00), -- ABS Black
(1, 2, 50.00, 1600.00),  -- ABS White
(2, 4, 75.00, 1200.00),  -- PP Natural
(2, 5, 25.00, 1300.00),  -- PP Black
(3, 7, 60.00, 2000.00),  -- Nylon 6
(3, 8, 40.00, 2100.00),  -- Nylon 66
(4, 10, 80.00, 1100.00), -- PS Crystal
(4, 11, 20.00, 1200.00), -- PS Impact
(5, 13, 90.00, 1400.00), -- HIPS Natural
(5, 14, 30.00, 1500.00); -- HIPS White

-- Sample deliveries
INSERT INTO `deliveries` (`delivery_number`, `purchase_order_id`, `delivery_date`, `status`, `carrier`, `tracking_number`, `notes`) VALUES
('DEL-20241210-2001', 3, '2024-12-10', 'Delivered', 'Local Truck', 'LT-2024-001', 'Delivered on time, all items received'),
('DEL-20241215-2002', 1, '2024-12-15', 'In Transit', 'FedEx', 'FX-123456789', 'Package in transit, expected delivery tomorrow'),
('DEL-20241220-2003', 2, '2024-12-20', 'Scheduled', 'UPS', 'UP-987654321', 'Scheduled for pickup'),
('DEL-20241225-2004', 4, '2024-12-25', 'Scheduled', 'Local Truck', 'LT-2024-002', 'Scheduled delivery'),
('DEL-20241230-2005', 5, '2024-12-30', 'Scheduled', 'DHL', 'DH-456789123', 'International shipment');

-- Update purchase orders total amounts based on items
UPDATE purchase_orders po 
SET total_amount = (
    SELECT SUM(poi.total_price) 
    FROM purchase_order_items poi 
    WHERE poi.purchase_order_id = po.id
);

-- Create indexes for better performance
CREATE INDEX idx_suppliers_status ON suppliers(status);
CREATE INDEX idx_purchase_orders_status ON purchase_orders(status);
CREATE INDEX idx_purchase_orders_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_deliveries_status ON deliveries(status);
CREATE INDEX idx_deliveries_purchase_order ON deliveries(purchase_order_id);
CREATE INDEX idx_purchase_order_items_purchase_order ON purchase_order_items(purchase_order_id);
CREATE INDEX idx_purchase_order_items_raw_material ON purchase_order_items(raw_material_id); 