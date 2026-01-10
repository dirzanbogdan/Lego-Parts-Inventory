-- 004_rebrickable_schema_v3.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Themes
DROP TABLE IF EXISTS themes;
CREATE TABLE themes (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    parent_id INT NULL,
    FOREIGN KEY (parent_id) REFERENCES themes(id)
);

-- Colors
DROP TABLE IF EXISTS colors;
CREATE TABLE colors (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rgb VARCHAR(6) NOT NULL,
    is_trans BOOLEAN NOT NULL
);

-- Part Categories
DROP TABLE IF EXISTS part_categories;
CREATE TABLE part_categories (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Parts
DROP TABLE IF EXISTS parts;
CREATE TABLE parts (
    part_num VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    part_cat_id INT NOT NULL,
    part_material VARCHAR(100),
    FOREIGN KEY (part_cat_id) REFERENCES part_categories(id)
);

-- Part Relationships
DROP TABLE IF EXISTS part_relationships;
CREATE TABLE part_relationships (
    rel_type CHAR(1) NOT NULL,
    child_part_num VARCHAR(50) NOT NULL,
    parent_part_num VARCHAR(50) NOT NULL,
    FOREIGN KEY (child_part_num) REFERENCES parts(part_num),
    FOREIGN KEY (parent_part_num) REFERENCES parts(part_num),
    PRIMARY KEY (rel_type, child_part_num, parent_part_num)
);

-- Elements (Mapping Part+Color to specific Element ID)
DROP TABLE IF EXISTS elements;
CREATE TABLE elements (
    element_id VARCHAR(50) PRIMARY KEY,
    part_num VARCHAR(50) NOT NULL,
    color_id INT NOT NULL,
    FOREIGN KEY (part_num) REFERENCES parts(part_num),
    FOREIGN KEY (color_id) REFERENCES colors(id)
);

-- Sets
DROP TABLE IF EXISTS sets;
CREATE TABLE sets (
    set_num VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    theme_id INT NOT NULL,
    num_parts INT NOT NULL,
    img_url VARCHAR(255),
    FOREIGN KEY (theme_id) REFERENCES themes(id)
);

-- Minifigs
DROP TABLE IF EXISTS minifigs;
CREATE TABLE minifigs (
    fig_num VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    num_parts INT NOT NULL
);

-- Inventories (The central connector)
DROP TABLE IF EXISTS inventories;
CREATE TABLE inventories (
    id INT PRIMARY KEY,
    version INT NOT NULL,
    set_num VARCHAR(50) NOT NULL,
    FOREIGN KEY (set_num) REFERENCES sets(set_num)
);

-- Inventory Parts
DROP TABLE IF EXISTS inventory_parts;
CREATE TABLE inventory_parts (
    inventory_id INT NOT NULL,
    part_num VARCHAR(50) NOT NULL,
    color_id INT NOT NULL,
    quantity INT NOT NULL,
    is_spare BOOLEAN NOT NULL DEFAULT 0,
    img_url VARCHAR(255),
    FOREIGN KEY (inventory_id) REFERENCES inventories(id),
    FOREIGN KEY (part_num) REFERENCES parts(part_num),
    FOREIGN KEY (color_id) REFERENCES colors(id),
    PRIMARY KEY (inventory_id, part_num, color_id, is_spare)
);

-- Inventory Minifigs
DROP TABLE IF EXISTS inventory_minifigs;
CREATE TABLE inventory_minifigs (
    inventory_id INT NOT NULL,
    fig_num VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (inventory_id) REFERENCES inventories(id),
    FOREIGN KEY (fig_num) REFERENCES minifigs(fig_num),
    PRIMARY KEY (inventory_id, fig_num)
);

-- Inventory Sets
DROP TABLE IF EXISTS inventory_sets;
CREATE TABLE inventory_sets (
    inventory_id INT NOT NULL,
    set_num VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (inventory_id) REFERENCES inventories(id),
    FOREIGN KEY (set_num) REFERENCES sets(set_num),
    PRIMARY KEY (inventory_id, set_num)
);

SET FOREIGN_KEY_CHECKS = 1;
