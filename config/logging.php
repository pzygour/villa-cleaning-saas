<?php

declare(strict_types=1);

return [
    'file' => __DIR__ . '/../storage/logs/app.log',
    'level' => $_ENV['LOG_LEVEL'] ?? 'info',
];
