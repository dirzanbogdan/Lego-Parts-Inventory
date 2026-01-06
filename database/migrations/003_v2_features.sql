ALTER TABLE part_colors ADD COLUMN condition_state ENUM('New','Used') DEFAULT 'New';
ALTER TABLE part_colors ADD COLUMN purchase_price DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE parts ADD COLUMN related_items TEXT NULL; -- JSON or text list

ALTER TABLE sets ADD COLUMN instructions_url VARCHAR(500) NULL;

CREATE TABLE IF NOT EXISTS entity_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(50) NOT NULL, -- 'part', 'set'
  entity_id INT NOT NULL,
  user_id INT,
  changes TEXT, -- JSON description of changes
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Index for search
ALTER TABLE parts ADD FULLTEXT KEY ft_desc (name, part_code);
ALTER TABLE sets ADD FULLTEXT KEY ft_set_desc (set_name, set_code);

CREATE TABLE IF NOT EXISTS part_parts (
  parent_part_id INT NOT NULL,
  child_part_id INT NOT NULL,
  quantity INT DEFAULT 1,
  PRIMARY KEY (parent_part_id, child_part_id),
  FOREIGN KEY (parent_part_id) REFERENCES parts(id) ON DELETE CASCADE,
  FOREIGN KEY (child_part_id) REFERENCES parts(id) ON DELETE CASCADE
);
