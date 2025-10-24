<?php

namespace App\Controllers;

use App\Repositories\UserRepository;

class AuthController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    /**
     * Processa login: valida credenciais, emite tokens e redireciona.
     * Retorna string de erro em caso de falha (sem redirecionar).
     */
    public function login(string $email, string $senha): ?string
    {
        try {
            $user = $this->userRepo->findByEmail($email);
            if ($user && isset($user['senha_hash']) && password_verify($senha, (string)$user['senha_hash'])) {
                // Emite tokens via função existente para manter compatibilidade com current_user()
                require_once __DIR__ . '/../../inc/db.php';
                issue_tokens(['id' => (int)$user['id'], 'nome' => (string)$user['nome'], 'email' => (string)$user['email']], 900, 3600);
                header('Location: /index.php');
                return null;
            }
            return 'Credenciais inválidas. Use email teste@teste.com e senha 123456';
        } catch (\Throwable $e) {
            return 'Erro de login: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }

    /**
     * Faz logout limpando cookies compatíveis com implementação atual e redireciona.
     */
    public function logout(): void
    {
        $opts = [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ];
        if (isset($_COOKIE['jwt'])) {
            setcookie('jwt', '', $opts);
        }
        if (isset($_COOKIE['refresh'])) {
            setcookie('refresh', '', $opts);
        }
        header('Location: /login.php');
    }
}