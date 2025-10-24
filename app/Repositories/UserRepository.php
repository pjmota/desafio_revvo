<?php
declare(strict_types=1);

namespace App\Repositories;

require_once __DIR__ . '/../../inc/db.php';

class UserRepository
{
    public function getCurrentUser(): ?array
    {
        return current_user();
    }

    public function getModalState(int $userId): bool
    {
        return user_get_modal_state($userId);
    }

    public function setMainModalClosedOnce(int $userId): bool
    {
        return user_set_modal_closed_once($userId);
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash, avatar, is_admin FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getProfileById(int $userId): ?array
    {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT avatar, is_admin FROM usuarios WHERE id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function listAll(): array
    {
        try {
            $pdo = db();
            $stmt = $pdo->query('SELECT id, nome, email, avatar, is_admin, criado_em FROM usuarios ORDER BY criado_em DESC');
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}