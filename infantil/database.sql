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

INSERT INTO kids_settings(setting_key,setting_value) VALUES
('store_name','EiFarma Kids'),('whatsapp','5565999728431'),('logo','assets/logo-eifarma.png')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO kids_products(sku,name,presentation,category,age_group,price,old_price,image,sort_order) VALUES
('CHU001','Chupeta 100% Silicone Soft Calming','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',31.99,34.99,'assets/chupeta-lillo-soft.webp',1),
('CHU002','Chupeta Lillo Brilha no Escuro','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',31.99,34.99,'assets/chupeta-lillo-brilha.webp',2),
('CHU003','Chupeta Lolly Kinddy Zoo','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',14.99,19.99,'assets/chupeta-lolly.webp',3),
('CHU004','Chupeta Kuka Premium Ortodôntica','Tamanho 2 • 6+ meses','Chupetas & Bicos','6-12',21.99,24.99,'assets/chupeta-kuka.webp',4)
ON DUPLICATE KEY UPDATE name=VALUES(name),price=VALUES(price),old_price=VALUES(old_price),image=VALUES(image);
