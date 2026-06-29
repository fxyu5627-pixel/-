-- ============================================================
-- Art Toy Shop Database Schema
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------
-- ตารางหมวดหมู่สินค้า
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- ตารางสินค้าหลัก
-- product_type: blind_single (สุ่มเดี่ยว), full_set (ยกกล่อง),
--               check_card (เช็กการ์ด/เลือกตัวได้), figure (ของเล่นทั่วไป)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    product_type ENUM('blind_single','full_set','check_card','figure') NOT NULL DEFAULT 'figure',
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    weight_gram INT DEFAULT 200,
    stock_qty INT NOT NULL DEFAULT 0,
    is_preorder TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    badge_label VARCHAR(50) DEFAULT NULL,   -- Rare!, Pre-Order, เหลือน้อย ฯลฯ (กำหนดเอง)
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- รูปภาพสินค้า (หลายมุม/หลายรูปต่อสินค้า)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- ตัวละครในกล่องสุ่ม (สำหรับ blind_single / check_card)
-- ใช้แสดง Drop Rate และผูกกับสต็อกจริงแต่ละตัว
-- is_secret: ตัวเสกรต / หายาก
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(150) NOT NULL,      -- ชื่อตัวละคร เช่น "พูห์ชุดฮันนี่"
    image_path VARCHAR(255) DEFAULT NULL,
    is_secret TINYINT(1) DEFAULT 0,
    drop_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- เปอร์เซ็นต์ที่แสดงผลให้ลูกค้าเห็น เช่น 1.50 = 1.5%
    stock_qty INT NOT NULL DEFAULT 0,        -- จำนวนตัวนี้ที่เหลือจริงในคลัง (กำหนดล่วงหน้า)
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- สมาชิก
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- ที่อยู่จัดส่งของสมาชิก (เก็บได้หลายที่อยู่)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS member_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line TEXT NOT NULL,
    subdistrict VARCHAR(100),
    district VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    is_default TINYINT(1) DEFAULT 0,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- คูปองส่วนลด
-- discount_type: percent / fixed
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    coupon_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    valid_from DATE DEFAULT NULL,
    valid_until DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- ตะกร้าสินค้า (เก็บแบบ session-based ผ่าน cart_token)
-- รองรับทั้ง guest และ member (member_id เป็น null ได้)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_token VARCHAR(64) NOT NULL,
    member_id INT DEFAULT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- คำสั่งซื้อ
-- payment_method: bank_transfer, qr, cod
-- order_status: pending, paid, processing, shipped, completed, cancelled
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    member_id INT DEFAULT NULL,
    guest_name VARCHAR(150) DEFAULT NULL,
    guest_email VARCHAR(150) DEFAULT NULL,
    guest_phone VARCHAR(20) DEFAULT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    shipping_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method ENUM('bank_transfer','qr','cod') NOT NULL,
    payment_slip VARCHAR(255) DEFAULT NULL,
    order_status ENUM('pending','paid','processing','shipped','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- รายการสินค้าในคำสั่งซื้อ
-- revealed_variant_id: ผลลัพธ์ที่เปิดเผยให้ลูกค้าเห็นกรณีเป็นกล่องสุ่ม
-- (สุ่มจาก stock จริงตอนชำระเงินสำเร็จ ไม่ใช่การพนัน เพราะลูกค้าซื้อกล่องไปแล้วแน่นอน)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    revealed_variant_id INT DEFAULT NULL,
    is_revealed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (revealed_variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------
-- รีวิวสินค้า (Review Corner)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    member_id INT DEFAULT NULL,
    display_name VARCHAR(150) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    got_secret TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ข้อมูลตัวอย่าง (Seed Data)
-- ============================================================
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('แบบสุ่มเดี่ยว (Blind Box)', 'blind-box', 'สุ่มเดี่ยว เปิดกล่องลุ้นตัวพิเศษ', '🎲', 1),
('แบบยกกล่อง (Full Set)', 'full-set', 'ยกกล่องครบเซ็ต ได้ทุกตัวแน่นอน', '📦', 2),
('แบบเช็กการ์ด', 'check-card', 'เช็กการ์ดเลือกตัวได้ ไม่แกะซอง', '🃏', 3),
('ของเล่น & ตุ๊กตาหมีพูห์', 'winnie-the-pooh', 'ของเล่นและตุ๊กตาหมีพูห์ลิขสิทธิ์แท้', '🧸', 4);

INSERT INTO coupons (code, discount_type, discount_value, min_purchase, max_uses, valid_from, valid_until, is_active) VALUES
('NEWMEMBER10', 'percent', 10.00, 300, 500, '2026-01-01', '2026-12-31', 1),
('SECRET50', 'fixed', 50.00, 500, 100, '2026-06-01', '2026-07-31', 1);
