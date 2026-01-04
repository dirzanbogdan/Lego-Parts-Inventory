CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL
);
CREATE TABLE IF NOT EXISTS parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  part_code VARCHAR(100) UNIQUE NOT NULL,
  version VARCHAR(50),
  category_id INT,
  image_url VARCHAR(500),
  bricklink_url VARCHAR(500),
  years_released VARCHAR(100),
  weight DECIMAL(10,3),
  stud_dimensions VARCHAR(100),
  package_dimensions VARCHAR(100),
  no_of_parts INT,
  FULLTEXT KEY ft_name (name),
  INDEX idx_code (part_code),
  INDEX idx_category (category_id),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  color_name VARCHAR(100) UNIQUE NOT NULL,
  color_code VARCHAR(50)
);
CREATE TABLE IF NOT EXISTS part_colors (
  part_id INT NOT NULL,
  color_id INT NOT NULL,
  quantity_in_inventory INT NOT NULL DEFAULT 0,
  PRIMARY KEY (part_id, color_id),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS inventory_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  color_id INT NOT NULL,
  delta INT NOT NULL,
  reason VARCHAR(255),
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS sets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  set_name VARCHAR(255) NOT NULL,
  set_code VARCHAR(100) UNIQUE NOT NULL,
  type ENUM('official','moc','technic','custom') NOT NULL DEFAULT 'official',
  year INT,
  image VARCHAR(500)
);
CREATE TABLE IF NOT EXISTS set_parts (
  set_id INT NOT NULL,
  part_id INT NOT NULL,
  color_id INT,
  quantity INT NOT NULL,
  PRIMARY KEY (set_id, part_id, color_id),
  FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS favorites (
  user_id INT NOT NULL,
  set_id INT NOT NULL,
  PRIMARY KEY (user_id, set_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(200) UNIQUE NOT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
