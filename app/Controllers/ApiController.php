<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HomepageRepository;
use App\Repositories\UserRepository;

class ApiController
{
    private UserRepository $users;
    private HomepageRepository $homepage;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->homepage = new HomepageRepository();
    }

    private function isAuthed(): bool
    {
        $u = $this->users->getCurrentUser();
        return is_array($u) && !empty($u['id']);
    }

    public function getHomepageCourses(): void
    {
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = $this->users->getCurrentUser();
        $ids = $this->homepage->getSelectedCourseIds((int)$u['id']);
        $recent = $this->homepage->getRecentCourseIds((int)$u['id']);
        echo json_encode(['course_ids' => $ids, 'recent_course_ids' => $recent]);
    }

    public function postHomepageCourses(): void
    {
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = $this->users->getCurrentUser();
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $cid = isset($data['course_id']) ? (int)$data['course_id'] : 0;
        if ($cid <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid_course_id']); return; }
        $ok = $this->homepage->addCourse((int)$u['id'], $cid);
        echo json_encode(['ok' => $ok]);
    }

    public function getUserModalState(): void
    {
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = $this->users->getCurrentUser();
        $show = $this->users->getModalState((int)$u['id']);
        echo json_encode(['show_main_modal' => $show]);
    }

    public function postUserMainModalClose(): void
    {
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = $this->users->getCurrentUser();
        $ok = $this->users->setMainModalClosedOnce((int)$u['id']);
        echo json_encode(['ok' => $ok]);
    }
}