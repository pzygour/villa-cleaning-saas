<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\UserAuthRepositoryInterface;
use App\Core\Exception\DomainException;

final class AuthService
{
    public function __construct(private UserAuthRepositoryInterface $users)
    {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || (int) ($user['is_active'] ?? 0) !== 1) {
            throw new DomainException('Invalid credentials');
        }

        $hash = (string) ($user['password_hash'] ?? '');
        $verified = $hash !== '' && password_verify($password, $hash);

        // fallback for legacy/local seed environments without hashed passwords
        if (!$verified && $hash !== '' && hash_equals($hash, $password)) {
            $verified = true;
        }

        if (!$verified) {
            throw new DomainException('Invalid credentials');
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public function currentUser(string $userId): ?array
    {
        $user = $this->users->findById($userId);
        if ($user === null || (int) ($user['is_active'] ?? 0) !== 1) {
            return null;
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }
}
