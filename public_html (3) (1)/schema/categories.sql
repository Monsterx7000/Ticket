-- Admin-managed categories for tickets
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,      -- canonical value stored in tickets.category
  name_en VARCHAR(120) NOT NULL,
  name_ar VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example seeds (you can change from Admin UI afterward)
INSERT IGNORE INTO categories (slug, name_en, name_ar, is_active, sort_order) VALUES
('general','General','عام',1,10),
('technical','Technical','تقني',1,20),
('network','Network','شبكات',1,30),
('billing','Billing','فواتير',1,40);
