<?php
declare(strict_types=1);

namespace App\Controllers;

class ApiController
{
    private function isAuthed(): bool
    {
        require_once __DIR__ . '/../../inc/db.php';
        $u = current_user();
        return is_array($u) && !empty($u['id']);
    }

    public function getHomepageCourses(): void
    {
        require_once __DIR__ . '/../../inc/db.php';
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = current_user();
        $ids = user_homepage_get_selected_course_ids((int)$u['id']);
        $recent = user_homepage_get_recent_course_ids((int)$u['id']);
        echo json_encode(['course_ids' => $ids, 'recent_course_ids' => $recent]);
    }

    public function postHomepageCourses(): void
    {
        require_once __DIR__ . '/../../inc/db.php';
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = current_user();
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $cid = isset($data['course_id']) ? (int)$data['course_id'] : 0;
        if ($cid <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid_course_id']); return; }
        $ok = user_homepage_add_course((int)$u['id'], $cid);
        echo json_encode(['ok' => $ok]);
    }

    public function getUserModalState(): void
    {
        require_once __DIR__ . '/../../inc/db.php';
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = current_user();
        $show = user_get_modal_state((int)$u['id']);
        echo json_encode(['show_main_modal' => $show]);
    }

    public function postUserMainModalClose(): void
    {
        require_once __DIR__ . '/../../inc/db.php';
        if (!$this->isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); return; }
        header('Content-Type: application/json');
        $u = current_user();
        $ok = user_set_modal_closed_once((int)$u['id']);
        echo json_encode(['ok' => $ok]);
    }
}