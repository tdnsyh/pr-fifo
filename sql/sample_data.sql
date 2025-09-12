-- sample_data_fixed.sql - seed data
USE fifo_inventory;

-- Buat user admin default: username=admin, password=admin
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$wDN19mxFQmjmYi7PzT1Q4uT4mMxvhRZEBPqlHVnQ9ce93gDTHm5aO');

-- Data contoh item
INSERT INTO items (sku, name, unit) VALUES
('OLI-001','Oli Mesin 10W-40','ltr'),
('FR-123','Filter Oli','pcs');

-- Data contoh supplier
INSERT INTO suppliers (name, phone) VALUES
('PT Sumber Jaya','0812xxxxxxx'),
('CV Makmur Abadi','0813xxxxxxx');
