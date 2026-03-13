<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Http\Kernel;

$kernel = new Kernel();
$response = $kernel->handle($_SERVER, $_GET, $_POST);
$response->send();
