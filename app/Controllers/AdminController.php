<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CourseRepository;
use App\Repositories\SlideRepository;
use App\Repositories\UserRepository;
use App\Services\UploadService;

class AdminController
{
    private CourseRepository $courses;
    private SlideRepository $slides;
    private UserRepository $users;
    private UploadService $uploader;

    public function __construct()
    {
        $this->courses = new CourseRepository();
        $this->slides = new SlideRepository();
        $this->users = new UserRepository();
        $this->uploader = new UploadService();
    }

    public function handlePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
        $action = $_POST['action'] ?? '';
        $tab = $_POST['current_tab'] ?? '';
        $status = 'ok';
        try {
            if ($action === 'create_course') {
                $imgPath = $this->uploader->upload($_FILES['imagem'] ?? []);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                if ($imgPath && $titulo !== '' && $descricao !== '') {
                    $this->courses->create($titulo, $descricao, $imgPath);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'update_course') {
                $imgPath = $this->uploader->upload($_FILES['imagem'] ?? []);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0 && $titulo !== '' && $descricao !== '') {
                    $this->courses->update($id, $titulo, $descricao, $imgPath ?: null);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'delete_course') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $this->courses->delete($id);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'create_slide') {
                $imgPath = $this->uploader->upload($_FILES['imagem'] ?? []);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $link = trim($_POST['link'] ?? '#');
                if ($imgPath && $titulo !== '' && $descricao !== '') {
                    $this->slides->create($imgPath, $titulo, $descricao, $link);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'update_slide') {
                $imgPath = $this->uploader->upload($_FILES['imagem'] ?? []);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $link = trim($_POST['link'] ?? '#');
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0 && $titulo !== '' && $descricao !== '') {
                    $this->slides->update($id, $titulo, $descricao, $link, $imgPath ?: null);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'delete_slide') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $this->slides->delete($id);
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'create_user') {
                $avatarPath = $this->uploader->upload($_FILES['avatar'] ?? []);
                $nome = trim($_POST['nome'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $senha = (string)($_POST['senha'] ?? '');
                $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
                if ($nome !== '' && $email !== '' && $senha !== '') {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    try {
                        $pdo = \db();
                        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, is_admin, avatar) VALUES (?, ?, ?, ?, COALESCE(?, NULL))');
                        $stmt->execute([$nome, $email, $hash, $isAdmin, $avatarPath ?: null]);
                        $status = 'ok';
                    } catch (\Throwable $e) {
                        $status = 'dup';
                    }
                } else {
                    $status = 'err';
                }
            } elseif ($action === 'update_user') {
                $avatarPath = $this->uploader->upload($_FILES['avatar'] ?? []);
                $id = (int)($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
                if ($id > 0 && $nome !== '' && $email !== '') {
                    try {
                        $pdo = \db();
                        $curStmt = $pdo->prepare('SELECT is_admin FROM usuarios WHERE id=?');
                        $curStmt->execute([$id]);
                        $curRow = $curStmt->fetch(\PDO::FETCH_ASSOC);
                        $currentIsAdmin = (int)($curRow['is_admin'] ?? 0);
                        $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE is_admin = 1')->fetchColumn();
                        if ($currentIsAdmin === 1 && $isAdmin === 0 && $adminCount === 1) {
                            $status = 'admin_guard';
                        } else {
                            $stmt = $pdo->prepare('UPDATE usuarios SET nome=?, email=?, is_admin=?, avatar=COALESCE(?, avatar) WHERE id=?');
                            $stmt->execute([$nome, $email, $isAdmin, $avatarPath ?: null, $id]);
                            $status = 'ok';
                        }
                    } catch (\Throwable $e) {
                        $status = 'dup';
                    }
                } else {
                    $status = 'err';
                }
            }
        } catch (\Throwable $e) {
            $status = 'err';
        }
        $qs = 'status=' . urlencode($status);
        if ($tab !== '') { $qs .= '&tab=' . urlencode($tab); }
        header('Location: /admin/manage.php?' . $qs);
        exit;
    }
}