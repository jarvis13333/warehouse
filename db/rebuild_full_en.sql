-- ============================================================
-- Warehouse Management — Full database rebuild (English data)
-- ============================================================
--
-- LOCAL XAMPP:
--   Option A: Uncomment the CREATE DATABASE + USE lines below.
--   Option B: Create warehouse_db in phpMyAdmin first, then import.
--
-- CPANEL:
--   1. cPanel → MySQL® Databases → Create Database (e.g. myuser_warehouse)
--   2. Create a MySQL user and add it to the database (ALL PRIVILEGES)
--   3. phpMyAdmin → select that database → Import → choose this file
--   4. Update config.php: host, db name, user, password
--
-- Demo logins after import:
--   admin / admin123  (admin role)
--   user  / user123   (user role)
--
-- WARNING: This script DROPS all existing tables and data.
-- ============================================================

-- LOCAL XAMPP only — leave commented on cPanel (database already selected):
-- CREATE DATABASE IF NOT EXISTS `warehouse_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `warehouse_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------
-- Schema
-- ----------------------------------------------------------------

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(20) NOT NULL,
  `reorder_level` int NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `idx_items_name` (`item_name`),
  KEY `idx_items_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `movement_type` enum('IN','OUT') NOT NULL,
  `quantity` int NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mov_item` (`item_id`),
  KEY `idx_mov_type` (`movement_type`),
  KEY `idx_mov_date` (`date`),
  CONSTRAINT `fk_mov_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------
-- Users (passwords: admin123 / user123)
-- ----------------------------------------------------------------

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'admin', '$2y$10$7m.DTr5UlqbA7EzVYKCifOkRR1KiI4i2a6UjLEP565brnYOrJctN2', 'admin'),
(2, 'user',  '$2y$10$2AbgizZMIziIZFXV4yBy8ue65ja86ajQMB64NKMZ35NW2jH3ap73q', 'user');

-- ----------------------------------------------------------------
-- Items (23 English sample SKUs)
-- ----------------------------------------------------------------

INSERT INTO `items` (`id`, `item_code`, `item_name`, `category`, `location`, `quantity`, `unit`, `reorder_level`) VALUES
(1,  'ITM-001',    'Carton Box',                              'Packaging',       'A1',     50,  'box',    10),
(2,  'ITM-002',    'Packing Tape',                            'Packaging',       'A2',      8,  'roll',   10),
(3,  'ITM-003',    'Pallet',                                  'Shipping',        'B1',     20,  'pcs',     5),
(4,  'SKU-EN-001', 'Ballpoint pen blue 0.7mm',                'Office supplies', 'A-01',  480,  'pcs',   100),
(5,  'SKU-EN-002', 'A4 copy paper 80gsm (500 sheets)',        'Office supplies', 'A-02',   42,  'ream',   15),
(6,  'SKU-EN-003', 'Sticky notes 76x76mm',                    'Office supplies', 'A-03',  200,  'pad',    40),
(7,  'SKU-EN-004', 'Thermal label roll 4x6"',                  'Shipping',        'B-01',   18,  'roll',    8),
(8,  'SKU-EN-005', 'Bubble mailer size #2',                   'Packaging',       'B-02',  350,  'pcs',    80),
(9,  'SKU-EN-006', 'Kraft packing tape 48mm x 50m',           'Packaging',       'B-03',   96,  'roll',   24),
(10, 'SKU-EN-007', 'Corrugated box small (8x6x4")',          'Packaging',       'C-01',  220,  'pcs',    50),
(11, 'SKU-EN-008', 'Wood pallet 48x40 standard',              'Shipping',        'YARD-1', 45,  'pcs',    10),
(12, 'SKU-EN-009', 'Stretch wrap 18" x 1500ft',               'Packaging',       'C-02',   14,  'roll',    5),
(13, 'SKU-EN-010', 'Nitrile gloves size L (box)',             'Safety',          'D-01',   28,  'box',    10),
(14, 'SKU-EN-011', 'Safety glasses clear',                      'Safety',          'D-02',   60,  'pair',   15),
(15, 'SKU-EN-012', 'Dust mask N95 (20 pack)',                 'Safety',          'D-03',   40,  'box',    12),
(16, 'SKU-EN-013', 'Alkaline AA battery (24 pack)',           'Electronics',     'E-01',   55,  'pack',   20),
(17, 'SKU-EN-014', 'USB-C cable 1m braided',                  'Electronics',     'E-02',  120,  'pcs',    30),
(18, 'SKU-EN-015', 'Wireless barcode scanner',                'Electronics',     'E-03',    8,  'pcs',     2),
(19, 'SKU-EN-016', 'M6 hex bolt zinc 25mm (100)',             'Fasteners',       'F-01',   15,  'box',     5),
(20, 'SKU-EN-017', 'M6 nylon lock nut (100)',                 'Fasteners',       'F-01',   22,  'box',     8),
(21, 'SKU-EN-018', 'Machine oil ISO 68 (5L)',                 'Consumables',     'G-01',    9,  'can',     3),
(22, 'SKU-EN-019', 'Shop towels blue roll',                   'Consumables',     'G-02',   24,  'roll',    8),
(23, 'SKU-EN-020', 'Isopropyl alcohol 99% 500ml',             'Consumables',     'G-03',   36,  'bottle', 12);

-- ----------------------------------------------------------------
-- Stock movements (40 English demo records for SKU-EN-* items)
-- ----------------------------------------------------------------

INSERT INTO `stock_movements` (`id`, `item_id`, `movement_type`, `quantity`, `date`, `reference_note`) VALUES
(1,  4,  'IN',  500, '2025-01-08 09:15:00', 'Opening stock count'),
(2,  4,  'OUT', 120, '2025-01-12 14:20:00', 'Sales order SO-1042'),
(3,  4,  'OUT',  80, '2025-02-03 11:00:00', 'Branch transfer'),
(4,  5,  'IN',  200, '2025-01-05 08:30:00', 'PO-8821 receipt'),
(5,  5,  'OUT',  18, '2025-01-28 16:45:00', 'Office allocation'),
(6,  6,  'IN',  300, '2025-01-10 10:00:00', 'Bulk purchase'),
(7,  6,  'OUT',  50, '2025-02-01 09:30:00', 'Department request'),
(8,  7,  'IN',   40, '2025-01-15 13:10:00', 'Vendor delivery'),
(9,  7,  'OUT',  12, '2025-01-22 10:00:00', 'Packing station 2'),
(10, 8,  'IN',  400, '2025-01-06 07:45:00', 'PO-8830'),
(11, 8,  'OUT', 150, '2025-01-18 15:20:00', 'E-commerce batch'),
(12, 9,  'IN',  120, '2025-01-09 12:00:00', 'Restock'),
(13, 9,  'OUT',  36, '2025-02-05 08:50:00', 'Daily usage'),
(14, 10, 'IN',  250, '2025-01-11 09:00:00', 'Carton supplier'),
(15, 10, 'OUT',  90, '2025-01-25 14:00:00', 'Shipment wave W-12'),
(16, 11, 'IN',   50, '2025-01-07 11:30:00', 'Pallet exchange'),
(17, 11, 'OUT',   8, '2025-01-30 13:15:00', 'Outbound LTL'),
(18, 12, 'IN',   20, '2025-01-14 08:00:00', 'PO-8844'),
(19, 12, 'OUT',   6, '2025-02-02 10:40:00', 'Wrapping line'),
(20, 13, 'IN',   40, '2025-01-16 09:20:00', 'Safety restock'),
(21, 13, 'OUT',  12, '2025-01-29 07:00:00', 'Production floor'),
(22, 14, 'IN',   80, '2025-01-12 10:10:00', 'Annual PPE order'),
(23, 14, 'OUT',  15, '2025-02-04 11:25:00', 'New hire kits'),
(24, 15, 'IN',   50, '2025-01-19 14:00:00', 'Medical supplier'),
(25, 15, 'OUT',  10, '2025-02-06 09:10:00', 'Maintenance hold'),
(26, 16, 'IN',  100, '2025-01-08 08:30:00', 'Retail pack'),
(27, 16, 'OUT',  30, '2025-01-27 16:00:00', 'IT storeroom'),
(28, 17, 'IN',  200, '2025-01-13 12:45:00', 'Import shipment'),
(29, 17, 'OUT',  45, '2025-02-01 13:30:00', 'Warranty replacements'),
(30, 18, 'IN',   10, '2025-01-20 09:00:00', 'Equipment purchase'),
(31, 18, 'OUT',   2, '2025-02-07 10:15:00', 'Loaner to DC-3'),
(32, 19, 'IN',   25, '2025-01-17 08:50:00', 'Hardware bin refill'),
(33, 19, 'OUT',   8, '2025-01-31 15:00:00', 'Assembly line B'),
(34, 20, 'IN',   30, '2025-01-17 09:00:00', 'Matched with bolts'),
(35, 20, 'OUT',  10, '2025-02-03 12:20:00', 'Assembly line B'),
(36, 21, 'IN',   12, '2025-01-21 07:30:00', 'Lubrication program'),
(37, 21, 'OUT',   3, '2025-02-05 14:50:00', 'CNC maintenance'),
(38, 22, 'IN',   30, '2025-01-23 11:00:00', 'Janitorial contract'),
(39, 22, 'OUT',   8, '2025-02-06 08:40:00', 'Shop cleanup week'),
(40, 23, 'IN',   48, '2025-01-24 10:30:00', 'Chemical supplier'),
(41, 23, 'OUT',  12, '2025-02-08 09:55:00', 'Lab restock');

ALTER TABLE `users` AUTO_INCREMENT = 3;
ALTER TABLE `items` AUTO_INCREMENT = 24;
ALTER TABLE `stock_movements` AUTO_INCREMENT = 42;
