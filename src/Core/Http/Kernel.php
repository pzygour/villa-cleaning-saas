<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Http\Response\HtmlResponse;

final class Kernel
{
    public function handle(array $server, array $query, array $request): HtmlResponse
    {
        $body = '<h1>Villa Cleaning Operations Foundation</h1><p>Architecture bootstrap is ready.</p>';

        return new HtmlResponse($body, 200);
    }
}
