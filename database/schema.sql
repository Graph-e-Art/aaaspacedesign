CREATE TABLE contact_enquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone_whatsapp VARCHAR(40) NOT NULL,
    project_type ENUM('Residential', 'Commercial', 'Renovation') NOT NULL,
    city VARCHAR(120) NOT NULL,
    budget_range VARCHAR(120) DEFAULT NULL,
    message TEXT NOT NULL,
    preferred_call_date DATE DEFAULT NULL,
    preferred_call_time TIME DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    status ENUM('New', 'Contacted', 'Closed') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clients_slug (slug)
);

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(180) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(1000) DEFAULT NULL,
    embed_url VARCHAR(1000) DEFAULT NULL,
    status ENUM('Published', 'Archived', 'Draft') NOT NULL DEFAULT 'Published',
    featured_order TINYINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projects_archive (status, year, month),
    INDEX idx_projects_client (client_id, status, year, month),
    UNIQUE KEY uq_projects_featured_order (featured_order),
    CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT chk_projects_month CHECK (month BETWEEN 1 AND 12),
    CONSTRAINT chk_projects_year CHECK (year BETWEEN 2000 AND 2200)
);

CREATE TABLE IF NOT EXISTS project_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    media_type ENUM('image', 'drive') NOT NULL,
    url VARCHAR(1000) NOT NULL,
    label VARCHAR(180) DEFAULT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_media_project (project_id, sort_order),
    CONSTRAINT fk_project_media_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

INSERT INTO clients (name, slug) VALUES
('Move n Pick Hotel', 'move-n-pick-hotel'),
('Sanober Yasir Salon', 'sanober-yasir-salon'),
('Bank Islami', 'bank-islami'),
('Classic Ink', 'classic-ink'),
('Uniferoz', 'uniferoz'),
('Efroze Pharma', 'efroze-pharma'),
('Askari Life Insurance', 'askari-life-insurance'),
('United Gypsum', 'united-gypsum'),
('Marriott Hotels & Resorts', 'marriott-hotels-resorts'),
('Ambiace Boutique Art Hotel', 'ambiace-boutique-art-hotel'),
('Reko Diq Mining Company', 'reko-diq-mining-company'),
('Al Fauz Travel Agency', 'al-fauz-travel-agency'),
('the Owis Nest Restaurant', 'the-owis-nest-restaurant'),
('Karachi Paint House', 'karachi-paint-house'),
('Hongshao Restaurant', 'hongshao-restaurant'),
('Artibe BPO', 'artibe-bpo'),
('Raqami Islamic Digital bank', 'raqami-islamic-digital-bank'),
('Lucky Cement', 'lucky-cement'),
('Fecto Cement', 'fecto-cement'),
('Efro Tech', 'efro-tech'),
('Gerry''s Danata', 'gerrys-danata'),
('International Brand Limited', 'international-brand-limited'),
('BMS Associates', 'bms-associates'),
('300+ Residential Projects', '300-plus-residential-projects')
ON DUPLICATE KEY UPDATE name = VALUES(name);
