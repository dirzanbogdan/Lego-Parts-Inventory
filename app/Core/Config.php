<?php

namespace App\Core;

use PDO;
use PDOException;

class Config {
    private static $pdo = null;

    public static function db(): PDO {
        if (self::$pdo === null) {
            $config = [
                'host' => 'localhost',
                'db'   => 'lego_inventory',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ];

            // Load local config if exists
            $configFile = __DIR__ . '/../../db_config.php';
            if (file_exists($configFile)) {
                $localConfig = require $configFile;
                if (is_array($localConfig)) {
                    $config = array_merge($config, $localConfig);
                }
            }

            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            } catch (PDOException $e) {
                // In production, log this instead of showing it
                // For now, throw a clearer message if it's an access denied error
                if ($e->getCode() == 1045) {
                     die("Database connection failed: Access denied. Please create a 'db_config.php' file in the root directory with the correct credentials. See 'db_config.sample.php'.");
                }
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$pdo;
    }
}
