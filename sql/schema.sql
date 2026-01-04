CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  part_code VARCHAR(64) NOT NULL UNIQUE,
  version VARCHAR(64) NULL,
  category_id INT NULL,
  image_url VARCHAR(255) NULL,
  bricklink_url VARCHAR(255) NULL,
  years_released VARCHAR(64) NULL,
  weight DECIMAL(10,3) NULL,
  stud_dimensions VARCHAR(64) NULL,
  package_dimensions VARCHAR(64) NULL,
  no_of_parts INT NULL,
  INDEX idx_parts_name (name),
  INDEX idx_parts_code (part_code),
  INDEX idx_parts_category (category_id),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  color_name VARCHAR(128) NOT NULL UNIQUE,
  color_code VARCHAR(64) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS part_colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  color_id INT NOT NULL,
  quantity_in_inventory INT NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_part_color (part_id, color_id),
  INDEX idx_part_color_part (part_id),
  INDEX idx_part_color_color (color_id),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS part_subparts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  sub_part_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  INDEX idx_subparts_part (part_id),
  INDEX idx_subparts_sub (sub_part_id),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (sub_part_id) REFERENCES parts(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS inventory_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  color_id INT NOT NULL,
  delta INT NOT NULL,
  reason VARCHAR(255) NULL,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hist_part (part_id),
  INDEX idx_hist_color (color_id),
  INDEX idx_hist_user (user_id),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS sets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  set_name VARCHAR(255) NOT NULL,
  set_code VARCHAR(64) NOT NULL UNIQUE,
  type ENUM('official','moc','technic','custom') NOT NULL DEFAULT 'official',
  year INT NULL,
  image VARCHAR(255) NULL,
  INDEX idx_sets_name (set_name),
  INDEX idx_sets_year (year)
);
CREATE TABLE IF NOT EXISTS set_parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  set_id INT NOT NULL,
  part_id INT NOT NULL,
  color_id INT NULL,
  quantity INT NOT NULL DEFAULT 1,
  INDEX idx_set_parts_set (set_id),
  INDEX idx_set_parts_part (part_id),
  FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  set_id INT NOT NULL,
  UNIQUE KEY uniq_fav (user_id, set_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE
);
