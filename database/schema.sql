-- ============================================================================
-- Southeastern Archdeaconry, Episcopal Church of Liberia
-- Database schema
-- Engine: InnoDB, utf8mb4
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS saa_ecl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saa_ecl;

-- ----------------------------------------------------------------------------
-- roles
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    permissions_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- employees (non-clergy staff; users.employee_id may reference this)
-- ----------------------------------------------------------------------------
CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    position VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    photo VARCHAR(255) NULL,
    bio TEXT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    date_joined DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- users (dashboard/admin accounts)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NULL,
    avatar VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_users_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role_id);

-- ----------------------------------------------------------------------------
-- dioceses_bishops (Diocesan Bishop + Suffragan Bishop portfolio)
-- ----------------------------------------------------------------------------
CREATE TABLE dioceses_bishops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(150) NOT NULL,
    type ENUM('diocesan','suffragan') NOT NULL,
    photo VARCHAR(255) NULL,
    consecration_date DATE NULL,
    enthronement_date DATE NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    order_number SMALLINT UNSIGNED NULL,
    bio_short VARCHAR(500) NULL,
    bio_full TEXT NULL,
    motto VARCHAR(255) NULL,
    education TEXT NULL,
    ordination_history_json JSON NULL,
    contact_office VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_bishops_type_current ON dioceses_bishops(type, is_current);

-- ----------------------------------------------------------------------------
-- archdeacons (current + historical)
-- ----------------------------------------------------------------------------
CREATE TABLE archdeacons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    photo VARCHAR(255) NULL,
    term_start DATE NOT NULL,
    term_end DATE NULL,
    bio TEXT NULL,
    achievements TEXT NULL,
    order_number SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_archdeacons_term ON archdeacons(term_start, term_end);

-- ----------------------------------------------------------------------------
-- churches (created before clergy for FK ordering, current_priest_id added after)
-- ----------------------------------------------------------------------------
CREATE TABLE churches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    image_cover VARCHAR(255) NULL,
    gallery_json JSON NULL,
    address VARCHAR(255) NULL,
    town_district VARCHAR(150) NULL,
    founding_date DATE NULL,
    patron_saint VARCHAR(150) NULL,
    current_priest_id INT UNSIGNED NULL,
    short_history TEXT NULL,
    latest_update TEXT NULL,
    service_times VARCHAR(500) NULL,
    status ENUM('preaching_station','mission','aided_parish','parish','pro_cathedral') NOT NULL DEFAULT 'parish',
    is_pro_cathedra TINYINT(1) NOT NULL DEFAULT 0,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_churches_district ON churches(town_district);
CREATE INDEX idx_churches_pro_cathedra ON churches(is_pro_cathedra);
CREATE INDEX idx_churches_status ON churches(status);

-- ----------------------------------------------------------------------------
-- clergy (priests / deacons / lay readers)
-- ----------------------------------------------------------------------------
CREATE TABLE clergy (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    photo VARCHAR(255) NULL,
    order_type ENUM('priest','deacon','lay_reader') NOT NULL,
    ordination_date DATE NULL,
    home_church_id INT UNSIGNED NULL,
    title VARCHAR(50) NULL,
    bio_short VARCHAR(500) NULL,
    bio_full TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    status ENUM('active','retired','deceased','transferred') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clergy_home_church FOREIGN KEY (home_church_id) REFERENCES churches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_clergy_order_status ON clergy(order_type, status);
CREATE INDEX idx_clergy_home_church ON clergy(home_church_id);

ALTER TABLE churches
    ADD CONSTRAINT fk_churches_current_priest FOREIGN KEY (current_priest_id) REFERENCES clergy(id) ON DELETE SET NULL;

-- ----------------------------------------------------------------------------
-- service_schedules (recurring services/activities for a church, e.g. the
-- Pro-Cathedral's daily Mass, Bible study, choir/acolyte/altar guild practice)
-- ----------------------------------------------------------------------------
CREATE TABLE service_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id INT UNSIGNED NOT NULL,
    activity VARCHAR(150) NOT NULL,
    day_label VARCHAR(50) NOT NULL,
    time_label VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_schedule_church ON service_schedules(church_id, sort_order);

-- ----------------------------------------------------------------------------
-- organizations
-- ----------------------------------------------------------------------------
CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    logo VARCHAR(255) NULL,
    category VARCHAR(100) NULL,
    founding_date DATE NULL,
    church_id INT UNSIGNED NULL,
    leadership_json JSON NULL,
    short_history TEXT NULL,
    latest_update TEXT NULL,
    mission_statement TEXT NULL,
    meeting_schedule VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_org_category ON organizations(category);

-- ----------------------------------------------------------------------------
-- blog_categories / blog_posts
-- ----------------------------------------------------------------------------
CREATE TABLE blog_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    cover_image VARCHAR(255) NULL,
    body LONGTEXT NULL,
    excerpt VARCHAR(500) NULL,
    author_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    tags VARCHAR(255) NULL,
    status ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_post_category FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_posts_status_published ON blog_posts(status, published_at);

-- ----------------------------------------------------------------------------
-- administrative_letters
-- ----------------------------------------------------------------------------
CREATE TABLE administrative_letters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    issued_by VARCHAR(150) NULL,
    category ENUM('pastoral_letter','circular','policy','minutes') NOT NULL,
    visibility ENUM('public','clergy_only','staff_only') NOT NULL DEFAULT 'public',
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_letters_visibility_category ON administrative_letters(visibility, category);

-- ----------------------------------------------------------------------------
-- newsletters / subscribers
-- ----------------------------------------------------------------------------
CREATE TABLE newsletters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('draft','sent','scheduled') NOT NULL DEFAULT 'draft',
    target_group VARCHAR(100) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_newsletter_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(150) NULL,
    group_name VARCHAR(100) NULL,
    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    unsubscribe_token VARCHAR(64) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE INDEX idx_subscribers_group ON subscribers(group_name);

CREATE TABLE newsletter_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    newsletter_id INT UNSIGNED NOT NULL,
    subscriber_id INT UNSIGNED NOT NULL,
    opened_at DATETIME NULL,
    clicked_at DATETIME NULL,
    CONSTRAINT fk_nr_newsletter FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE,
    CONSTRAINT fk_nr_subscriber FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE,
    UNIQUE KEY uq_newsletter_subscriber (newsletter_id, subscriber_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- events
-- ----------------------------------------------------------------------------
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NULL,
    event_type ENUM('feast','ordination','synod','confirmation','other') NOT NULL DEFAULT 'other',
    image VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_events_start ON events(start_datetime);

-- ----------------------------------------------------------------------------
-- lectionary_days
-- ----------------------------------------------------------------------------
CREATE TABLE lectionary_days (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL UNIQUE,
    season VARCHAR(100) NULL,
    color VARCHAR(30) NULL,
    readings_json JSON NULL,
    saint_of_day VARCHAR(150) NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- testimonies
-- ----------------------------------------------------------------------------
CREATE TABLE testimonies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    church_id INT UNSIGNED NULL,
    message TEXT NOT NULL,
    photo VARCHAR(255) NULL,
    approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_testimony_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_testimonies_approved ON testimonies(approved);

-- ----------------------------------------------------------------------------
-- media_gallery
-- ----------------------------------------------------------------------------
CREATE TABLE media_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NULL,
    file_path VARCHAR(255) NOT NULL,
    type ENUM('image','video') NOT NULL DEFAULT 'image',
    related_type ENUM('church','organization','event','blog_post','general') NOT NULL DEFAULT 'general',
    related_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NULL,
    alt_text VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_media_related ON media_gallery(related_type, related_id);

-- ----------------------------------------------------------------------------
-- pages_content (flexible CMS blocks)
-- ----------------------------------------------------------------------------
CREATE TABLE pages_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NULL,
    body LONGTEXT NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pages_updater FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- settings (key/value)
-- ----------------------------------------------------------------------------
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(150) NOT NULL UNIQUE,
    setting_value TEXT NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- contact_messages (submissions from the public Contact page)
-- ----------------------------------------------------------------------------
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_contact_messages_read ON contact_messages(is_read);

-- ----------------------------------------------------------------------------
-- activity_log
-- ----------------------------------------------------------------------------
CREATE TABLE activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    target_type VARCHAR(100) NULL,
    target_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_activity_created ON activity_log(created_at);

SET FOREIGN_KEY_CHECKS = 1;
