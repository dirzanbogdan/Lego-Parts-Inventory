-- User Inventory Table
DROP TABLE IF EXISTS user_parts;
CREATE TABLE user_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 1, -- Placeholder for single user mode
    part_num VARCHAR(50) NOT NULL,
    color_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_num) REFERENCES parts(part_num),
    FOREIGN KEY (color_id) REFERENCES colors(id),
    UNIQUE KEY unique_part_color (user_id, part_num, color_id)
);
