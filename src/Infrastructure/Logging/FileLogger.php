<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Core\Logging\LoggerInterface;

final class FileLogger implements LoggerInterface
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "%s [%s] %s %s\n",
            (new \DateTimeImmutable())->format(DATE_ATOM),
            $level,
            $message,
            json_encode($context, JSON_THROW_ON_ERROR)
        );

        file_put_contents($this->filePath, $line, FILE_APPEND);
    }
}
