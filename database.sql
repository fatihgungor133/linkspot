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
    email_notifications TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    theme_id INT DEFAULT NULL,
    language_code VARCHAR(5) DEFAULT 'en',
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
    image VARCHAR(255),
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

-- Diller tablosu
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dil metinleri tablosu
CREATE TABLE IF NOT EXISTS language_strings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    string_key VARCHAR(100) NOT NULL,
    string_value TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_translation (language_id, string_key)
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

-- Varsayılan dilleri ekle
INSERT INTO languages (code, name, is_active, is_default) VALUES 
('en', 'English', 1, 1),
('tr', 'Türkçe', 1, 0);

-- İngilizce metinler
INSERT INTO language_strings (language_id, string_key, string_value) VALUES
((SELECT id FROM languages WHERE code = 'en'), 'login', 'Login'),
((SELECT id FROM languages WHERE code = 'en'), 'register', 'Register'),
((SELECT id FROM languages WHERE code = 'en'), 'profile', 'Profile'),
((SELECT id FROM languages WHERE code = 'en'), 'settings', 'Settings'),
((SELECT id FROM languages WHERE code = 'en'), 'logout', 'Logout'),
((SELECT id FROM languages WHERE code = 'en'), 'dashboard', 'Dashboard'),
((SELECT id FROM languages WHERE code = 'en'), 'add_link', 'Add Link'),
((SELECT id FROM languages WHERE code = 'en'), 'edit_link', 'Edit Link'),
((SELECT id FROM languages WHERE code = 'en'), 'delete_link', 'Delete Link'),
((SELECT id FROM languages WHERE code = 'en'), 'title', 'Title'),
((SELECT id FROM languages WHERE code = 'en'), 'url', 'URL'),
((SELECT id FROM languages WHERE code = 'en'), 'image', 'Image'),
((SELECT id FROM languages WHERE code = 'en'), 'active', 'Active'),
((SELECT id FROM languages WHERE code = 'en'), 'save', 'Save'),
((SELECT id FROM languages WHERE code = 'en'), 'cancel', 'Cancel'),
((SELECT id FROM languages WHERE code = 'en'), 'delete', 'Delete'),
((SELECT id FROM languages WHERE code = 'en'), 'confirm_delete', 'Are you sure you want to delete?'),
((SELECT id FROM languages WHERE code = 'en'), 'success', 'Success'),
((SELECT id FROM languages WHERE code = 'en'), 'error', 'Error'),
((SELECT id FROM languages WHERE code = 'en'), 'welcome', 'Welcome'),
((SELECT id FROM languages WHERE code = 'en'), 'email', 'Email'),
((SELECT id FROM languages WHERE code = 'en'), 'password', 'Password'),
((SELECT id FROM languages WHERE code = 'en'), 'confirm_password', 'Confirm Password'),
((SELECT id FROM languages WHERE code = 'en'), 'remember_me', 'Remember Me'),
((SELECT id FROM languages WHERE code = 'en'), 'forgot_password', 'Forgot Password?'),
((SELECT id FROM languages WHERE code = 'en'), 'no_account', 'Don\'t have an account?'),
((SELECT id FROM languages WHERE code = 'en'), 'have_account', 'Already have an account?'),
((SELECT id FROM languages WHERE code = 'en'), 'register_now', 'Register Now'),
((SELECT id FROM languages WHERE code = 'en'), 'login_now', 'Login Now'),
((SELECT id FROM languages WHERE code = 'en'), 'welcome_text', 'Collect all your social media accounts and important links on a single page.'),
((SELECT id FROM languages WHERE code = 'en'), 'quick_stats', 'Quick Stats'),
((SELECT id FROM languages WHERE code = 'en'), 'total_links', 'Total Links'),
((SELECT id FROM languages WHERE code = 'en'), 'active_links', 'Active Links'),
((SELECT id FROM languages WHERE code = 'en'), 'last_7_days_visits', 'Last 7 Days Visits'),
((SELECT id FROM languages WHERE code = 'en'), 'my_links', 'My Links'),
((SELECT id FROM languages WHERE code = 'en'), 'visit_stats', 'Visit Statistics'),
((SELECT id FROM languages WHERE code = 'en'), 'edit_profile', 'Edit Profile'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_image', 'Profile Image'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_title', 'Profile Title'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_description', 'Profile Description'),
((SELECT id FROM languages WHERE code = 'en'), 'theme_settings', 'Theme Settings'),
((SELECT id FROM languages WHERE code = 'en'), 'main_color', 'Main Color'),
((SELECT id FROM languages WHERE code = 'en'), 'background_color', 'Background Color'),
((SELECT id FROM languages WHERE code = 'en'), 'text_color', 'Text Color'),
((SELECT id FROM languages WHERE code = 'en'), 'card_background', 'Card Background'),
((SELECT id FROM languages WHERE code = 'en'), 'theme_style', 'Theme Style'),
((SELECT id FROM languages WHERE code = 'en'), 'auto_system', 'Auto (System)'),
((SELECT id FROM languages WHERE code = 'en'), 'light_theme', 'Light Theme'),
((SELECT id FROM languages WHERE code = 'en'), 'dark_theme', 'Dark Theme'),
((SELECT id FROM languages WHERE code = 'en'), 'social_profiles', 'Social Profiles'),
((SELECT id FROM languages WHERE code = 'en'), 'add_social', 'Add Social Profile'),
((SELECT id FROM languages WHERE code = 'en'), 'platform', 'Platform'),
((SELECT id FROM languages WHERE code = 'en'), 'username', 'Username'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_url', 'Profile URL'),
((SELECT id FROM languages WHERE code = 'en'), 'recommended_size', 'Recommended size'),
((SELECT id FROM languages WHERE code = 'en'), 'visit_profile', 'Visit Profile'),
((SELECT id FROM languages WHERE code = 'en'), 'powered_by', 'Powered by');

-- Türkçe metinler
INSERT INTO language_strings (language_id, string_key, string_value) VALUES
((SELECT id FROM languages WHERE code = 'tr'), 'login', 'Giriş Yap'),
((SELECT id FROM languages WHERE code = 'tr'), 'register', 'Kayıt Ol'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile', 'Profil'),
((SELECT id FROM languages WHERE code = 'tr'), 'settings', 'Ayarlar'),
((SELECT id FROM languages WHERE code = 'tr'), 'logout', 'Çıkış'),
((SELECT id FROM languages WHERE code = 'tr'), 'dashboard', 'Panel'),
((SELECT id FROM languages WHERE code = 'tr'), 'add_link', 'Link Ekle'),
((SELECT id FROM languages WHERE code = 'tr'), 'edit_link', 'Link Düzenle'),
((SELECT id FROM languages WHERE code = 'tr'), 'delete_link', 'Link Sil'),
((SELECT id FROM languages WHERE code = 'tr'), 'title', 'Başlık'),
((SELECT id FROM languages WHERE code = 'tr'), 'url', 'URL'),
((SELECT id FROM languages WHERE code = 'tr'), 'image', 'Görsel'),
((SELECT id FROM languages WHERE code = 'tr'), 'active', 'Aktif'),
((SELECT id FROM languages WHERE code = 'tr'), 'save', 'Kaydet'),
((SELECT id FROM languages WHERE code = 'tr'), 'cancel', 'İptal'),
((SELECT id FROM languages WHERE code = 'tr'), 'delete', 'Sil'),
((SELECT id FROM languages WHERE code = 'tr'), 'confirm_delete', 'Silmek istediğinizden emin misiniz?'),
((SELECT id FROM languages WHERE code = 'tr'), 'success', 'Başarılı'),
((SELECT id FROM languages WHERE code = 'tr'), 'error', 'Hata'),
((SELECT id FROM languages WHERE code = 'tr'), 'welcome', 'Hoş Geldiniz'),
((SELECT id FROM languages WHERE code = 'tr'), 'email', 'E-posta'),
((SELECT id FROM languages WHERE code = 'tr'), 'password', 'Şifre'),
((SELECT id FROM languages WHERE code = 'tr'), 'confirm_password', 'Şifre Tekrar'),
((SELECT id FROM languages WHERE code = 'tr'), 'remember_me', 'Beni Hatırla'),
((SELECT id FROM languages WHERE code = 'tr'), 'forgot_password', 'Şifremi Unuttum'),
((SELECT id FROM languages WHERE code = 'tr'), 'no_account', 'Hesabınız yok mu?'),
((SELECT id FROM languages WHERE code = 'tr'), 'have_account', 'Zaten hesabınız var mı?'),
((SELECT id FROM languages WHERE code = 'tr'), 'register_now', 'Hemen Kayıt Ol'),
((SELECT id FROM languages WHERE code = 'tr'), 'login_now', 'Hemen Giriş Yap'),
((SELECT id FROM languages WHERE code = 'tr'), 'welcome_text', 'Tüm sosyal medya hesaplarınızı ve önemli linklerinizi tek bir sayfada toplayın.'),
((SELECT id FROM languages WHERE code = 'tr'), 'quick_stats', 'Hızlı İstatistikler'),
((SELECT id FROM languages WHERE code = 'tr'), 'total_links', 'Toplam Link'),
((SELECT id FROM languages WHERE code = 'tr'), 'active_links', 'Aktif Link'),
((SELECT id FROM languages WHERE code = 'tr'), 'last_7_days_visits', 'Son 7 Gün Ziyaret'),
((SELECT id FROM languages WHERE code = 'tr'), 'my_links', 'Linklerim'),
((SELECT id FROM languages WHERE code = 'tr'), 'visit_stats', 'Ziyaret İstatistikleri'),
((SELECT id FROM languages WHERE code = 'tr'), 'edit_profile', 'Profili Düzenle'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_image', 'Profil Resmi'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_title', 'Profil Başlığı'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_description', 'Profil Açıklaması'),
((SELECT id FROM languages WHERE code = 'tr'), 'theme_settings', 'Tema Ayarları'),
((SELECT id FROM languages WHERE code = 'tr'), 'main_color', 'Ana Renk'),
((SELECT id FROM languages WHERE code = 'tr'), 'background_color', 'Arkaplan Rengi'),
((SELECT id FROM languages WHERE code = 'tr'), 'text_color', 'Metin Rengi'),
((SELECT id FROM languages WHERE code = 'tr'), 'card_background', 'Kart Arkaplanı'),
((SELECT id FROM languages WHERE code = 'tr'), 'theme_style', 'Tema Stili'),
((SELECT id FROM languages WHERE code = 'tr'), 'auto_system', 'Otomatik (Sistem)'),
((SELECT id FROM languages WHERE code = 'tr'), 'light_theme', 'Açık Tema'),
((SELECT id FROM languages WHERE code = 'tr'), 'dark_theme', 'Koyu Tema'),
((SELECT id FROM languages WHERE code = 'tr'), 'social_profiles', 'Sosyal Medya Profilleri'),
((SELECT id FROM languages WHERE code = 'tr'), 'add_social', 'Sosyal Medya Ekle'),
((SELECT id FROM languages WHERE code = 'tr'), 'platform', 'Platform'),
((SELECT id FROM languages WHERE code = 'tr'), 'username', 'Kullanıcı Adı'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_url', 'Profil URL'),
((SELECT id FROM languages WHERE code = 'tr'), 'recommended_size', 'Önerilen boyut'),
((SELECT id FROM languages WHERE code = 'tr'), 'visit_profile', 'Profili Ziyaret Et'),
((SELECT id FROM languages WHERE code = 'tr'), 'powered_by', 'Altyapı');

-- Admin kullanıcısı oluştur (şifre: admin123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@linkspot.com', '$2y$10$8KzQ8IzAF9tXBQxnwO7yZejhwD.qxcIo8HGXQKjwNnXeGf9VuqQEi', 1); 