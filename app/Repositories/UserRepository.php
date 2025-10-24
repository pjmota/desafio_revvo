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
}