<?php
declare(strict_types=1);

namespace App\Repositories;

require_once __DIR__ . '/../../inc/db.php';

class HomepageRepository
{
    public function getSelectedCourseIds(int $userId): array
    {
        return user_homepage_get_selected_course_ids($userId);
    }

    public function getRecentCourseIds(int $userId): array
    {
        return user_homepage_get_recent_course_ids($userId);
    }

    public function addCourse(int $userId, int $courseId): bool
    {
        return user_homepage_add_course($userId, $courseId);
    }
}