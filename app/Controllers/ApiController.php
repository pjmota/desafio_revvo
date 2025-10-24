<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HomepageRepository;
use App\Repositories\UserRepository;
use App\Services\ApiResponse;

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

    private function getCurrentUserId(): ?int
    {
        $u = $this->users->getCurrentUser();
        return is_array($u) && !empty($u['id']) ? (int)$u['id'] : null;
    }

    public function getHomepageCourses(): void
    {
        if (!$this->isAuthed()) {
            ApiResponse::unauthorized();
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                ApiResponse::unauthorized();
                return;
            }

            $selectedIds = $this->homepage->getSelectedCourseIds($userId);
            $recentIds = $this->homepage->getRecentCourseIds($userId);

            ApiResponse::success([
                'course_ids' => $selectedIds,
                'recent_course_ids' => $recentIds
            ]);
        } catch (\Throwable $e) {
            ApiResponse::internalError('Erro ao buscar cursos da homepage');
        }
    }

    public function postHomepageCourses(): void
    {
        if (!$this->isAuthed()) {
            ApiResponse::unauthorized();
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                ApiResponse::unauthorized();
                return;
            }

            $rawInput = file_get_contents('php://input');
            if (!$rawInput) {
                ApiResponse::badRequest('Dados não fornecidos');
                return;
            }

            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                ApiResponse::badRequest('JSON inválido');
                return;
            }

            if (!isset($data['course_id']) || !is_numeric($data['course_id'])) {
                ApiResponse::badRequest('ID do curso é obrigatório e deve ser numérico');
                return;
            }

            $courseId = (int)$data['course_id'];
            if ($courseId <= 0) {
                ApiResponse::badRequest('ID do curso deve ser maior que zero');
                return;
            }

            $success = $this->homepage->addCourse($userId, $courseId);
            
            if ($success) {
                ApiResponse::success(['added' => true, 'course_id' => $courseId]);
            } else {
                ApiResponse::error('Falha ao adicionar curso', 422, 'ADD_COURSE_FAILED');
            }
        } catch (\Throwable $e) {
            ApiResponse::internalError('Erro ao adicionar curso à homepage');
        }
    }

    public function getUserModalState(): void
    {
        if (!$this->isAuthed()) {
            ApiResponse::unauthorized();
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                ApiResponse::unauthorized();
                return;
            }

            $showModal = $this->users->getModalState($userId);
            
            ApiResponse::success([
                'show_main_modal' => $showModal
            ]);
        } catch (\Throwable $e) {
            ApiResponse::internalError('Erro ao buscar estado do modal');
        }
    }

    public function postUserMainModalClose(): void
    {
        if (!$this->isAuthed()) {
            ApiResponse::unauthorized();
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                ApiResponse::unauthorized();
                return;
            }

            $success = $this->users->setMainModalClosedOnce($userId);
            
            if ($success) {
                ApiResponse::success(['modal_closed' => true]);
            } else {
                ApiResponse::error('Falha ao fechar modal', 422, 'CLOSE_MODAL_FAILED');
            }
        } catch (\Throwable $e) {
            ApiResponse::internalError('Erro ao fechar modal');
        }
    }
}