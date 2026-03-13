<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Logging\LoggerInterface;
use Throwable;

final class ExceptionHandlerMiddleware
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function handle(callable $next): mixed
    {
        try {
            return $next();
        } catch (Throwable $throwable) {
            $this->logger->error('Unhandled exception', ['exception' => $throwable->getMessage()]);
            throw $throwable;
        }
    }
}
