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

CREATE TABLE IF NOT EXISTS project_clients (
    project_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, client_id),
    INDEX idx_project_clients_client (client_id, project_id),
    CONSTRAINT fk_project_clients_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_clients_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS work_tags (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_key VARCHAR(64) NOT NULL,
    category_label VARCHAR(180) NOT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_work_tags_slug (slug),
    INDEX idx_work_tags_category (category_key, sort_order)
);

CREATE TABLE IF NOT EXISTS project_work_tags (
    project_id INT UNSIGNED NOT NULL,
    work_tag_id SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, work_tag_id),
    INDEX idx_project_work_tags_tag (work_tag_id, project_id),
    CONSTRAINT fk_project_work_tags_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_work_tags_tag FOREIGN KEY (work_tag_id) REFERENCES work_tags(id) ON DELETE CASCADE
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

INSERT INTO work_tags (category_key, category_label, name, slug, sort_order) VALUES
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'IT infrastructure planning and installation', 'it-infrastructure-planning-installation', 1),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Voice and data cabling', 'voice-data-cabling', 2),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Network infrastructure', 'network-infrastructure', 3),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Power and data cable management', 'power-data-cable-management', 4),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Server-room or equipment-room coordination', 'server-equipment-room-coordination', 5),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Office technology infrastructure', 'office-technology-infrastructure', 6),
('it-infrastructure', 'IT INFRASTRUCTURE & STRUCTURED CABLING', 'Structured cabling solutions', 'structured-cabling-solutions', 7),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Electrical installation and coordination', 'electrical-installation-coordination', 8),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Lighting and power solutions', 'lighting-power-solutions', 9),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Plumbing systems', 'plumbing-systems', 10),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Water supply and drainage coordination', 'water-supply-drainage-coordination', 11),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Air conditioning systems', 'air-conditioning-systems', 12),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'HVAC installation and coordination', 'hvac-installation-coordination', 13),
('mep-services', 'MEP SERVICES: ELECTRICAL, PLUMBING & HVAC', 'Maintenance access and service planning', 'maintenance-access-service-planning', 14),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Interior design and space planning', 'interior-design-space-planning', 15),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Office partitioning systems', 'office-partitioning-systems', 16),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Carpentry and woodwork', 'carpentry-woodwork', 17),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Custom furniture and joinery', 'custom-furniture-joinery', 18),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Glass installation', 'glass-installation', 19),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Ceiling and wall finishing', 'ceiling-wall-finishing', 20),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Commercial interior works', 'commercial-interior-works', 21),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Renovation and décor solutions', 'renovation-decor-solutions', 22),
('interior-fit-out', 'INTERIOR FIT-OUT, CARPENTRY & FURNITURE WORKS', 'Interior execution and coordination', 'interior-execution-coordination', 23)
ON DUPLICATE KEY UPDATE category_label = VALUES(category_label), name = VALUES(name), sort_order = VALUES(sort_order);
