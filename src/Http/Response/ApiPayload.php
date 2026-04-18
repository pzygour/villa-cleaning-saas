<?php

declare(strict_types=1);

namespace App\Http\Response;

final class ApiPayload
{
    public static function success($data = null, ?string $message = null): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ];
    }

    public static function error(string $message, array $errors = []): array
    {
        return [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}
