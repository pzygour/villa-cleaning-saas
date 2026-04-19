<?php

declare(strict_types=1);

namespace App\Http\Response;

interface ResponseInterface
{
    public function send(): void;
}
