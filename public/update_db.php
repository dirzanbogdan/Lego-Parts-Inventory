<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;
use App\Core\Security;

header('Content-Type: application/json');

// Authorization: allow via UPDATE_SECRET or admin user
$secret = getenv('UPDATE_SECRET') ?: '';
if ($secret) {
    $provided = $_GET['secret'] ?? '';
    if (!hash_equals($secret, $provided)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
} else {
    $user = $_SESSION['user'] ?? null;
    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
}

$result = ['ok' => true, 'steps' => []];

try {
    $pdo = Config::db();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Helper: run SQL and record
    $run = function(string $desc, string $sql) use ($pdo, &$result) {
        try {
            $pdo->exec($sql);
            $result['steps'][] = "$desc: ok";
        } catch (Throwable $e) {
            $result['steps'][] = "$desc: " . $e->getMessage();
        }
    };
    $ensureColumn = function(string $table, string $column, string $definition) use ($pdo, &$result) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
                $result['steps'][] = "ALTER TABLE $table ADD $column: ok";
            } else {
                $result['steps'][] = "ALTER TABLE $table ADD $column: exists";
            }
        } catch (Throwable $e) {
            $result['steps'][] = "ALTER TABLE $table ADD $column: " . $e->getMessage();
        }
    };

    // Core Rebrickable schema (safe creates)
    $run('Create themes', "
        CREATE TABLE IF NOT EXISTS themes (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            parent_id INT NULL,
            FOREIGN KEY (parent_id) REFERENCES themes(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create colors', "
        CREATE TABLE IF NOT EXISTS colors (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            rgb VARCHAR(6) NOT NULL,
            is_trans BOOLEAN NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create part_categories', "
        CREATE TABLE IF NOT EXISTS part_categories (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create parts', "
        CREATE TABLE IF NOT EXISTS parts (
            part_num VARCHAR(50) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            part_cat_id INT NOT NULL,
            part_material VARCHAR(100),
            img_url VARCHAR(255),
            FOREIGN KEY (part_cat_id) REFERENCES part_categories(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create sets', "
        CREATE TABLE IF NOT EXISTS sets (
            set_num VARCHAR(50) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            year INT NOT NULL,
            theme_id INT NOT NULL,
            num_parts INT NOT NULL,
            img_url VARCHAR(255),
            FOREIGN KEY (theme_id) REFERENCES themes(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create inventories', "
        CREATE TABLE IF NOT EXISTS inventories (
            id INT PRIMARY KEY,
            version INT NOT NULL,
            set_num VARCHAR(50) NOT NULL,
            FOREIGN KEY (set_num) REFERENCES sets(set_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create inventory_parts', "
        CREATE TABLE IF NOT EXISTS inventory_parts (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create minifigs', "
        CREATE TABLE IF NOT EXISTS minifigs (
            fig_num VARCHAR(50) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            num_parts INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create inventory_minifigs', "
        CREATE TABLE IF NOT EXISTS inventory_minifigs (
            inventory_id INT NOT NULL,
            fig_num VARCHAR(50) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (inventory_id) REFERENCES inventories(id),
            FOREIGN KEY (fig_num) REFERENCES minifigs(fig_num),
            PRIMARY KEY (inventory_id, fig_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create inventory_sets', "
        CREATE TABLE IF NOT EXISTS inventory_sets (
            inventory_id INT NOT NULL,
            set_num VARCHAR(50) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (inventory_id) REFERENCES inventories(id),
            FOREIGN KEY (set_num) REFERENCES sets(set_num),
            PRIMARY KEY (inventory_id, set_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create elements', "
        CREATE TABLE IF NOT EXISTS elements (
            element_id VARCHAR(50) PRIMARY KEY,
            part_num VARCHAR(50) NOT NULL,
            color_id INT NOT NULL,
            FOREIGN KEY (part_num) REFERENCES parts(part_num),
            FOREIGN KEY (color_id) REFERENCES colors(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create part_relationships', "
        CREATE TABLE IF NOT EXISTS part_relationships (
            rel_type CHAR(1) NOT NULL,
            child_part_num VARCHAR(50) NOT NULL,
            parent_part_num VARCHAR(50) NOT NULL,
            FOREIGN KEY (child_part_num) REFERENCES parts(part_num),
            FOREIGN KEY (parent_part_num) REFERENCES parts(part_num),
            PRIMARY KEY (rel_type, child_part_num, parent_part_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // App-specific tables
    $run('Create users', "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create user_parts', "
        CREATE TABLE IF NOT EXISTS user_parts (
            user_id INT NOT NULL,
            part_num VARCHAR(50) NOT NULL,
            color_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            PRIMARY KEY (user_id, part_num, color_id),
            FOREIGN KEY (part_num) REFERENCES parts(part_num),
            FOREIGN KEY (color_id) REFERENCES colors(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $run('Create user_sets', "
        CREATE TABLE IF NOT EXISTS user_sets (
            user_id INT NOT NULL,
            set_num VARCHAR(50) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (user_id, set_num),
            FOREIGN KEY (set_num) REFERENCES sets(set_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Ensure columns used by code exist
    $ensureColumn('parts', 'img_url', 'img_url VARCHAR(255)');
    $ensureColumn('sets', 'img_url', 'img_url VARCHAR(255)');
    $ensureColumn('inventory_parts', 'img_url', 'img_url VARCHAR(255)');

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'steps' => $result['steps'] ?? []]);
}
