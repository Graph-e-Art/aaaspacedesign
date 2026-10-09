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
