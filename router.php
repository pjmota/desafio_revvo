<?php
// Router para servidor embutido do PHP com proteção por JWT
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/vendor/autoload.php';
init_db();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = urldecode($uri);
$docRoot = __DIR__;
$publicDir = __DIR__ . '/public';
$adminDir = __DIR__ . '/admin';

// Handlers globais de erro/exception/shutdown com log e resposta apropriada
set_error_handler(function ($severity, $message, $file, $line) {
    try {
        \App\Services\Logger::error('PHP Error', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'uri' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
        ]);
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (strpos($path, '/api/') === 0) {
            \App\Services\ApiResponse::internalError('Erro interno do servidor');
        } else {
            http_response_code(500);
            echo 'Erro interno do servidor';
        }
    } catch (\Throwable $e) {
        // Silencioso para evitar loops
    }
});

set_exception_handler(function ($e) {
    try {
        \App\Services\Logger::error('Uncaught Exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uri' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
        ]);
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (strpos($path, '/api/') === 0) {
            \App\Services\ApiResponse::internalError('Erro interno do servidor');
        } else {
            http_response_code(500);
            echo 'Erro interno do servidor';
        }
    } catch (\Throwable $e2) {
        // Silencioso
    }
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        try {
            \App\Services\Logger::error('Shutdown Fatal', [
                'type' => $err['type'],
                'message' => $err['message'],
                'file' => $err['file'],
                'line' => $err['line'],
                'uri' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
            ]);
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
            if (strpos($path, '/api/') === 0) {
                \App\Services\ApiResponse::internalError('Erro interno do servidor');
            } else {
                http_response_code(500);
                echo 'Erro interno do servidor';
            }
        } catch (\Throwable $e3) {
            // Silencioso
        }
    }
});

function isAuthed(): bool {
    $u = current_user();
    return is_array($u) && !empty($u['id']);
}

// Se o arquivo solicitado existe na raiz do projeto, deixar o servidor servir diretamente
if ($uri !== '/' && file_exists($docRoot . $uri) && is_file($docRoot . $uri)) {
    return false; // serve arquivo estático
}

// Servir assets diretamente
if (strpos($uri, '/assets/') === 0 && file_exists($docRoot . $uri)) {
    return false;
}

// Rota raiz -> login se não autenticado, senão redireciona para index
if ($uri === '/') {
    if (isAuthed()) {
        header('Location: /index.php');
        exit;
    }
    require $publicDir . '/login.php';
    exit;
}

// Rota explícita para /login.php (se já autenticado, vai para index)
if ($uri === '/login.php') {
    if (isAuthed()) {
        header('Location: /index.php');
        exit;
    }
    require $publicDir . '/login.php';
    exit;
}

// Rota /index.php para a aplicação principal (requer JWT)
if ($uri === '/index.php') {
    if (!isAuthed()) {
        header('Location: /login.php');
        exit;
    }
    require $publicDir . '/index.php';
    exit;
}

// Rota explícita para /logout.php
if ($uri === '/logout.php') {
    require $publicDir . '/logout.php';
    exit;
}

// Rota /public/* para arquivos dentro de public
if (strpos($uri, '/public/') === 0) {
    $target = $publicDir . substr($uri, strlen('/public'));
    if (is_file($target)) {
        require $target;
        exit;
    }
}

// Rota admin (requer JWT)
if ($uri === '/admin' || $uri === '/admin/' || $uri === '/admin/manage.php') {
    if (!isAuthed()) {
        header('Location: /login.php');
        exit;
    }
    require $adminDir . '/manage.php';
    exit;
}

// API: health check
if ($uri === '/api/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $api = new \App\Controllers\ApiController();
    $api->getHealth();
    exit;
}

// API: obter cursos selecionados para a home do usuário
if ($uri === '/api/homepage-courses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $api = new \App\Controllers\ApiController();
    $api->getHomepageCourses();
    exit;
}

// API: adicionar curso à home do usuário
if ($uri === '/api/homepage-courses' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new \App\Controllers\ApiController();
    $api->postHomepageCourses();
    exit;
}

// API: estado do modal principal por usuário
if ($uri === '/api/user/modal-state' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $api = new \App\Controllers\ApiController();
    $api->getUserModalState();
    exit;
}

// API: marcar modal principal como fechado (não mostrar novamente)
if ($uri === '/api/user/main-modal/close' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new \App\Controllers\ApiController();
    $api->postUserMainModalClose();
    exit;
}

// Fallback: tentar mapear para public
$possible = $publicDir . $uri;
if ($uri !== '/' && file_exists($possible)) {
    if (is_file($possible)) {
        require $possible;
        exit;
    }
}

// 404 simples
http_response_code(404);
echo "404 Not Found";