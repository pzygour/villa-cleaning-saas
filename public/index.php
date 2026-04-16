<?php

declare(strict_types=1);

require_once dirname(__DIR__,1) . '/bootstrap.php';
use App\Core\Http\Kernel;

var_dump(class_exists(\App\Core\Http\Kernel::class));

$kernel = new Kernel();
$response = $kernel->handle($_SERVER);
$response->send();
