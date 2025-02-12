-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_title VARCHAR(100),
    profile_description TEXT,
    profile_image VARCHAR(255),
    theme_color VARCHAR(20) DEFAULT '#000000',
    theme_bg VARCHAR(20) DEFAULT '#f8f9fa',
    theme_text VARCHAR(20) DEFAULT '#212529',
    theme_card_bg VARCHAR(20) DEFAULT '#ffffff',
    theme_style ENUM('light', 'dark', 'auto') DEFAULT 'auto',
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    theme_id INT DEFAULT NULL,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sosyal medya profilleri tablosu
CREATE TABLE IF NOT EXISTS social_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform VARCHAR(50) NOT NULL,
    username VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    icon VARCHAR(50),
    order_number INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Linkler tablosu
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    icon VARCHAR(50),
    order_number INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ziyaret istatistikleri tablosu
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    link_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tema şablonları tablosu
CREATE TABLE IF NOT EXISTS themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    theme_color VARCHAR(20) DEFAULT '#000000',
    theme_bg VARCHAR(20) DEFAULT '#f8f9fa',
    theme_text VARCHAR(20) DEFAULT '#212529',
    theme_card_bg VARCHAR(20) DEFAULT '#ffffff',
    theme_style ENUM('light', 'dark', 'auto') DEFAULT 'auto',
    css_code TEXT,
    is_premium TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan tema şablonları
INSERT INTO themes (name, description, theme_color, theme_bg, theme_text, theme_card_bg, theme_style, is_premium) VALUES 
('Klasik Açık', 'Sade ve şık açık tema', '#007bff', '#f8f9fa', '#212529', '#ffffff', 'light', 0),
('Klasik Koyu', 'Modern koyu tema', '#007bff', '#212529', '#f8f9fa', '#343a40', 'dark', 0),
('Minimalist', 'Sade ve minimalist tasarım', '#000000', '#ffffff', '#000000', '#f8f9fa', 'light', 0),
('Neon', 'Canlı ve dikkat çekici neon tema', '#00ff00', '#000000', '#ffffff', '#0a0a0a', 'dark', 0),
('Pastel', 'Yumuşak pastel renkler', '#ff9999', '#fff5f5', '#4a4a4a', '#ffffff', 'light', 0),
('Okyanus', 'Ferah mavi tonları', '#0066cc', '#e6f3ff', '#003366', '#ffffff', 'light', 0),
('Gece Modu', 'Göz yormayan koyu tema', '#9966ff', '#1a1a1a', '#e6e6e6', '#2d2d2d', 'dark', 0),
('Sonbahar', 'Sıcak sonbahar renkleri', '#ff6600', '#fff9f2', '#663300', '#ffffff', 'light', 0);

-- Admin kullanıcısı oluştur (şifre: admin123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@linkspot.com', '$2y$10$8KzQ8IzAF9tXBQxnwO7yZejhwD.qxcIo8HGXQKjwNnXeGf9VuqQEi', 1); 