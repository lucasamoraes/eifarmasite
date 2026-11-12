CREATE TABLE IF NOT EXISTS kids_products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  presentation VARCHAR(120) NOT NULL DEFAULT '',
  category VARCHAR(100) NOT NULL,
  age_group ENUM('all','0-6','6-12','1-3','3+') NOT NULL DEFAULT 'all',
  price DECIMAL(10,2) NOT NULL,
  old_price DECIMAL(10,2) NULL,
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kids_catalog (active,category,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kids_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kids_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kids_presentations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO kids_settings(setting_key,setting_value) VALUES
('store_name','EiFarma Kids'),('whatsapp','5565999728431'),('logo','assets/logo-eifarma.png')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO kids_categories(name,sort_order) VALUES
('Chupetas & Bicos',1),('Alimentadores',2),('Brinquedos & Mordedores',3),('Higiene & Cuidados',4),
('Higiene & Banho',5),('Pele & Assaduras',6),('Primeiros Socorros',7),('Vitaminas',8)
ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order);

INSERT INTO kids_presentations(name,sort_order) VALUES
('Tamanho 1 • 0-6 meses',1),('Tamanho 2 • 6+ meses',2),('Para bebês • 6+ meses',3),
('Para bebês',4),('4+ meses',5),('200 ml',6),('Unidade',7)
ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order);

INSERT INTO kids_products(sku,name,presentation,category,age_group,price,old_price,image,sort_order) VALUES
('CHU001','Chupeta 100% Silicone Soft Calming','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',31.99,34.99,'assets/chupeta-lillo-soft.webp',1),
('CHU002','Chupeta Lillo Brilha no Escuro','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',31.99,34.99,'assets/chupeta-lillo-brilha.webp',2),
('CHU003','Chupeta Lolly Kinddy Zoo','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',14.99,19.99,'assets/chupeta-lolly.webp',3),
('CHU004','Chupeta Kuka Premium Ortodôntica','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',21.99,24.99,'assets/chupeta-kuka.webp',4)
ON DUPLICATE KEY UPDATE name=VALUES(name),price=VALUES(price),old_price=VALUES(old_price),image=VALUES(image);

UPDATE kids_products SET category='Brinquedos & Mordedores' WHERE category IN ('Brinquedo','Brinquedos');
UPDATE kids_products SET category='Higiene & Cuidados' WHERE category='Cuidados';
UPDATE kids_products SET presentation='Tamanho 1 • 0-6 meses' WHERE presentation LIKE 'Tamanho 1%0-6 meses';
UPDATE kids_products SET presentation='Tamanho 2 • 6+ meses' WHERE presentation LIKE 'Tamanho 2%6+ meses';
UPDATE kids_products SET presentation='Para bebês • 6+ meses' WHERE LOWER(presentation) LIKE 'para bebês%6+ meses';
UPDATE kids_products SET presentation='Para bebês' WHERE LOWER(TRIM(presentation))='para bebês';
UPDATE kids_products SET presentation='4+ meses' WHERE presentation='4 meses';
DELETE FROM kids_categories WHERE name IN ('Brinquedo','Brinquedos','Cuidados');
DELETE FROM kids_presentations WHERE name IN ('Tamanho 1 •  0-6 meses','para bebês • 6+ meses','para bebês ','4 meses');

INSERT IGNORE INTO kids_categories(name,sort_order)
SELECT DISTINCT category,99 FROM kids_products WHERE category<>'';
INSERT IGNORE INTO kids_presentations(name,sort_order)
SELECT DISTINCT presentation,99 FROM kids_products WHERE presentation<>'';

UPDATE kids_products SET age_group='0-6' WHERE presentation LIKE '%0-6%' OR presentation='4 meses';
UPDATE kids_products SET age_group='6-12' WHERE presentation LIKE '%6+%';
