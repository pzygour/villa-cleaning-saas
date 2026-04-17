<?php

declare(strict_types=1);

namespace App\Http\Request;

final class Request
{
    public function __construct(
        public array $query,
        public array $body,
        public array $server
    ) {
    }
}
