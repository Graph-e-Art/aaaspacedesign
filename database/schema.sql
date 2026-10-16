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

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(1000) DEFAULT NULL,
    embed_url VARCHAR(1000) DEFAULT NULL,
    status ENUM('Published', 'Archived', 'Draft') NOT NULL DEFAULT 'Published',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projects_archive (status, year, month),
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
