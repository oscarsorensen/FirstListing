CREATE DATABASE firstlisting_v1
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE firstlisting_v1;


CREATE TABLE listings (
  id INT AUTO_INCREMENT PRIMARY KEY,

  url VARCHAR(500) NOT NULL UNIQUE,
  domain VARCHAR(255) NOT NULL,
  source_type ENUM('agent','portal','other') NOT NULL,

  title VARCHAR(255),
  description TEXT,

  price INT,
  currency CHAR(3) DEFAULT 'EUR',

  area_text VARCHAR(255),
  area_normalized VARCHAR(255),

  sqm INT,
  rooms INT,

  agent_name VARCHAR(255),
  agent_phone VARCHAR(50),
  agent_email VARCHAR(255),

  first_seen_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE searches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  input_url VARCHAR(500) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE search_results (
  search_id INT NOT NULL,
  listing_id INT NOT NULL,
  similarity_score DECIMAL(5,4),
  match_type ENUM('sql','vector','mixed'),

  PRIMARY KEY (search_id, listing_id)
);

CREATE TABLE listing_clusters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE listing_cluster_items (
  cluster_id INT NOT NULL,
  listing_id INT NOT NULL,

  PRIMARY KEY (cluster_id, listing_id)
);


CREATE TABLE source_estimations (
  cluster_id INT PRIMARY KEY,
  likely_source_listing_id INT,
  confidence ENUM('low','medium','high'),
  reason TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE embedding_status (
  listing_id INT PRIMARY KEY,
  text_hash CHAR(40) NOT NULL,
  embedded_at DATETIME,
  embedding_version VARCHAR(20)
);


CREATE INDEX idx_area_norm ON listings(area_normalized);
CREATE INDEX idx_sqm ON listings(sqm);
CREATE INDEX idx_rooms ON listings(rooms);
CREATE INDEX idx_domain ON listings(domain);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,

  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(255) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,

  role ENUM('agent','admin','private') DEFAULT 'agent',

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE subscriptions (
  user_id INT PRIMARY KEY,

  plan ENUM('basic','standard','pro') NOT NULL,
  searches_per_month INT NOT NULL,

  active BOOLEAN DEFAULT TRUE,

  started_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE search_usage (
  user_id INT NOT NULL,
  month CHAR(7) NOT NULL, -- fx '2026-02'
  searches_used INT DEFAULT 0,

  PRIMARY KEY (user_id, month)
);

CREATE TABLE listing_price_history (
  listing_id INT NOT NULL,
  price INT NOT NULL,
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE crawl_sources (
  id INT AUTO_INCREMENT PRIMARY KEY,

  domain VARCHAR(255) NOT NULL,
  source_type ENUM('agent','portal') NOT NULL,

  enabled BOOLEAN DEFAULT TRUE,

  last_crawled_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);



CREATE USER 'firstlisting_admin'@'localhost'
IDENTIFIED BY 'zf4B84BHrlW9jvl4';

GRANT
  SELECT, INSERT, UPDATE, DELETE,
  CREATE, ALTER, INDEX
ON firstlisting.*
TO 'firstlisting_admin'@'localhost';

FLUSH PRIVILEGES;
