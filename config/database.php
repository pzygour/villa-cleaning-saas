<?php

declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database' => $_ENV['DB_NAME'] ?? 'villa_cleaning',
    'username' => $_ENV['DB_USER'] ?? 'villa_cleaning_root',
    'password' => $_ENV['DB_PASSWORD'] ?? 'jR4!sA8*qV6^nK1$',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
