-- Back up the database first. Run this migration once in phpMyAdmin.
-- Existing project rows keep NULL client_id until an admin assigns a tag.
CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clients_slug (slug)
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

ALTER TABLE projects
    ADD COLUMN client_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_projects_client (client_id, status, year, month),
    ADD CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;
