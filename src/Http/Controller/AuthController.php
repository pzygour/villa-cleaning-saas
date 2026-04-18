<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\AuthService;

final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function login(array $input): array
    {
        $user = $this->auth->login((string) ($input['email'] ?? ''), (string) ($input['password'] ?? ''));

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        return $user;
    }

    public function logout(): array
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return ['logged_out' => true];
    }

    public function currentUser(): ?array
    {
        $id = (string) ($_SESSION['user_id'] ?? '');
        if ($id === '') {
            return null;
        }

        return $this->auth->currentUser($id);
    }
}
