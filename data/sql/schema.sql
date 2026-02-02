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
