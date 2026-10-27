-- ============================================================
-- KaTunYu Coffee POS - Database Schema
-- ============================================================
-- Import this in phpMyAdmin (or `mysql -u root -p < schema.sql`)
-- Every column here is matched against what index.php / pages / handlers
-- actually query, so there should be no more "Unknown column" errors.
-- ============================================================

CREATE DATABASE IF NOT EXISTS katunyu_coffee
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE katunyu_coffee;

-- ------------------------------------------------------------
-- employees  (used for login + staff management)
-- ------------------------------------------------------------
CREATE TABLE employees (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(50)  NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL,          -- md5() hash, see handlers/employees_handler.php + login.php
    fullname  VARCHAR(100) NOT NULL,
    role      ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    status    ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- categories
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- products
-- ------------------------------------------------------------
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NULL,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock       INT NOT NULL DEFAULT 0,
    status      ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- customers  (orders.customer_id can point here; walk-in = id 1)
-- ------------------------------------------------------------
CREATE TABLE customers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- orders
-- ------------------------------------------------------------
CREATE TABLE orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number   VARCHAR(30)  NOT NULL UNIQUE,   -- format ORD-YYMMDD-XXX
    customer_id    INT UNSIGNED NULL,
    employee_id    INT UNSIGNED NULL,
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'transfer', 'qr') NOT NULL DEFAULT 'cash',
    status         ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
    order_date     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_orders_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_orders_order_date ON orders(order_date);

-- ------------------------------------------------------------
-- order_items
-- ------------------------------------------------------------
CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(150) NOT NULL,   -- snapshot of the name at sale time
    quantity     INT NOT NULL DEFAULT 1,
    price        DECIMAL(10,2) NOT NULL,  -- unit price at sale time
    subtotal     DECIMAL(10,2) NOT NULL,  -- price * quantity
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_order_items_product ON order_items(product_id);

-- ------------------------------------------------------------
-- settings  (key/value store used by pages/settings.php)
-- ------------------------------------------------------------
CREATE TABLE settings (
    key_name VARCHAR(50) PRIMARY KEY,
    value    TEXT NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default login accounts (password = "1234" for both, md5-hashed)
INSERT INTO employees (username, password, fullname, role, status) VALUES
('admin', MD5('1234'), 'ຜູ້ດູແລລະບົບ', 'admin', 'active'),
('staff', MD5('1234'), 'ພະນັກງານຂາຍ', 'staff', 'active');

-- Walk-in customer (id 1, matches customer_id=1 hard-coded in pages/pos.php)
INSERT INTO customers (id, name) VALUES (1, 'Walk-in');

-- Categories
INSERT INTO categories (name, description) VALUES
('ກາເຟຮ້ອນ', 'ເມນູກາເຟຮ້ອນທຸກຊະນິດ'),
('ກາເຟເຢັນ', 'ເມນູກາເຟເຢັນ ແລະ ປັ່ນ'),
('ຊາ', 'ຊາຮ້ອນ ແລະ ຊາເຢັນ'),
('ເຄື່ອງດື່ມອື່ນໆ', 'ນ້ຳໝາກໄມ້, ໂຊດາ, ອື່ນໆ'),
('ເບເກີລີ', 'ເຂົ້າໜົມ ແລະ ຂອງກິນຫຼິ້ນ');

-- Sample products
INSERT INTO products (name, category_id, price, cost, stock, status) VALUES
('ອາເມລິກາໂນຮ້ອນ', 1, 20000, 8000, 50, 'available'),
('ລາເຕັຮ້ອນ', 1, 25000, 10000, 50, 'available'),
('ຄາປູຊິໂນຮ້ອນ', 1, 25000, 10000, 50, 'available'),
('ອາເມລິກາໂນເຢັນ', 2, 22000, 8000, 50, 'available'),
('ລາເຕັເຢັນ', 2, 27000, 10000, 50, 'available'),
('ໂມກາເຢັນ', 2, 30000, 12000, 40, 'available'),
('ຊາໄທເຢັນ', 3, 20000, 7000, 40, 'available'),
('ຊາມະນາວ', 3, 18000, 6000, 40, 'available'),
('ນ້ຳໝາກກ້ຽງ', 4, 20000, 8000, 30, 'available'),
('ໂຊດາ', 4, 15000, 5000, 30, 'available'),
('ຄຣວາຊັອງ', 5, 18000, 7000, 20, 'available'),
('ເຄັກຊັອກໂກແລັດ', 5, 25000, 10000, 15, 'available');

-- Shop settings
INSERT INTO settings (key_name, value) VALUES
('shop_name', 'KaTunYu Coffee'),
('address', 'ນະຄອນຫຼວງວຽງຈັນ, ສປປ ລາວ'),
('phone', '020 1234 5678'),
('tax_id', '');
