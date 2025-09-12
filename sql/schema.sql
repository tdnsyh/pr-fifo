-- schema.sql - MySQL schema for FIFO Inventory
CREATE DATABASE IF NOT EXISTS fifo_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fifo_inventory;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) UNIQUE,
  name VARCHAR(200) NOT NULL,
  unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  phone VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  qty_initial INT NOT NULL,
  qty_remaining INT NOT NULL,
  cost_per_unit DECIMAL(12,2) NOT NULL,
  received_at DATETIME NOT NULL,
  expiry_date DATE NULL,
  purchase_id INT NULL,
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

CREATE TABLE IF NOT EXISTS purchase_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  item_id INT NOT NULL,
  batch_id INT NOT NULL,
  qty INT NOT NULL,
  cost_per_unit DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (batch_id) REFERENCES batches(id)
);

CREATE TABLE IF NOT EXISTS issues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issue_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  issue_id INT NOT NULL,
  item_id INT NOT NULL,
  batch_id INT NOT NULL,
  qty INT NOT NULL,
  cost_per_unit DECIMAL(12,2) NOT NULL,
  sell_price_per_unit DECIMAL(12,2) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (issue_id) REFERENCES issues(id),
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (batch_id) REFERENCES batches(id)
);

CREATE TABLE IF NOT EXISTS stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  movement_type ENUM('IN','OUT','ADJ') NOT NULL,
  reference_table VARCHAR(50) NOT NULL,
  reference_id INT NOT NULL,
  qty_change INT NOT NULL,
  unit_cost DECIMAL(12,2) NOT NULL,
  occurred_at DATETIME NOT NULL,
  meta JSON NULL,
  FOREIGN KEY (item_id) REFERENCES items(id)
);
