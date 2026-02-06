
-- this database is made with the plan that the raw crawl data is stored in a single table, and then AI-extracted fields are stored in a separate table that references the raw data. This allows for flexibility in adding more AI-extracted fields in the future without altering the raw data structure. It is done because i realised that a python crawler will never be clever enough to extract all the relevant fields in one go, and it is better to have a flexible structure that allows for iterative improvements in the AI extraction process. The vector_matches table is optional and can be used later if we decide to implement vector-based similarity matching between listings.

CREATE DATABASE IF NOT EXISTS test2firstlisting
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE test2firstlisting;

-- Raw crawl storage (HTML + text + JSON-LD)
CREATE TABLE IF NOT EXISTS raw_pages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  url VARCHAR(2048) NOT NULL,
  domain VARCHAR(255) NOT NULL,
  fetched_at DATETIME NOT NULL,
  http_status INT NULL,
  content_type VARCHAR(255) NULL,
  html_raw LONGTEXT NULL,
  text_raw LONGTEXT NULL,
  jsonld_raw LONGTEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_url (url(512)),
  KEY idx_domain (domain),
  KEY idx_fetched_at (fetched_at)
);

-- Optional: AI-extracted fields (can be empty for now)
CREATE TABLE IF NOT EXISTS ai_listings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  raw_page_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(512) NULL,
  description LONGTEXT NULL,
  price INT NULL,
  currency VARCHAR(8) NULL,
  sqm INT NULL,
  rooms INT NULL,
  address VARCHAR(512) NULL,
  agent_name VARCHAR(255) NULL,
  agent_phone VARCHAR(64) NULL,
  agent_email VARCHAR(255) NULL,
  confidence_price DECIMAL(5,4) NULL,
  confidence_rooms DECIMAL(5,4) NULL,
  confidence_sqm DECIMAL(5,4) NULL,
  confidence_address DECIMAL(5,4) NULL,
  field_source JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_raw_page_id (raw_page_id),
  CONSTRAINT fk_ai_listings_raw_page
    FOREIGN KEY (raw_page_id) REFERENCES raw_pages(id)
    ON DELETE CASCADE
);

-- Optional: vector match results
CREATE TABLE IF NOT EXISTS vector_matches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id BIGINT UNSIGNED NOT NULL,
  matched_listing_id BIGINT UNSIGNED NOT NULL,
  vector_score DECIMAL(6,4) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_listing_id (listing_id),
  KEY idx_matched_listing_id (matched_listing_id)
);


-- Create user (local only)
CREATE USER IF NOT EXISTS 'firstlisting_user'@'localhost'
  IDENTIFIED BY 'girafferharlangehalse';

-- Grant access to the database
GRANT ALL PRIVILEGES ON test2firstlisting.* TO 'firstlisting_user'@'localhost';

FLUSH PRIVILEGES;
