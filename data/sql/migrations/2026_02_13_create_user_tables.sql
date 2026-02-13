-- Create auth/user tables for test2firstlisting

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(255) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('agent','admin','private') DEFAULT 'agent',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
  user_id INT PRIMARY KEY,
  plan ENUM('basic','standard','pro') NOT NULL,
  searches_per_month INT NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS search_usage (
  user_id INT NOT NULL,
  month CHAR(7) NOT NULL,
  searches_used INT DEFAULT 0,
  PRIMARY KEY (user_id, month),
  CONSTRAINT fk_search_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
