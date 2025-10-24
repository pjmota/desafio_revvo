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

// Dispatcher padronizado com tabela de rotas e respostas consistentes
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$api = new \App\Controllers\ApiController();

// Tabela de rotas
$routes = [
    'GET' => [
        // Páginas públicas/admin
        '/' => function() use ($publicDir) {
            if (isAuthed()) {
                header('Location: /index.php');
                return;
            }
            require $publicDir . '/login.php';
        },
        '/login.php' => function() use ($publicDir) {
            if (isAuthed()) {
                header('Location: /index.php');
                return;
            }
            require $publicDir . '/login.php';
        },
        '/index.php' => function() use ($publicDir) {
            if (!isAuthed()) {
                header('Location: /login.php');
                return;
            }
            require $publicDir . '/index.php';
        },
        '/logout.php' => function() use ($publicDir) {
            require $publicDir . '/logout.php';
        },
        '/admin' => function() use ($adminDir) {
            if (!isAuthed()) {
                header('Location: /login.php');
                return;
            }
            require $adminDir . '/manage.php';
        },
        '/admin/' => function() use ($adminDir) {
            if (!isAuthed()) {
                header('Location: /login.php');
                return;
            }
            require $adminDir . '/manage.php';
        },
        '/admin/manage.php' => function() use ($adminDir) {
            if (!isAuthed()) {
                header('Location: /login.php');
                return;
            }
            require $adminDir . '/manage.php';
        },
        // API
        '/api/health' => [$api, 'getHealth'],
        '/api/homepage-courses' => [$api, 'getHomepageCourses'],
        '/api/user/modal-state' => [$api, 'getUserModalState'],
    ],
    'POST' => [
        '/api/homepage-courses' => [$api, 'postHomepageCourses'],
        '/api/user/main-modal/close' => [$api, 'postUserMainModalClose'],
    ],
];

// Helper para detectar se é rota de API
$isApiPath = function(string $path): bool {
    return strncmp($path, '/api/', 5) === 0;
};

$respondNotFound = function(string $path) use ($isApiPath) {
    if ($isApiPath($path)) {
        \App\Services\ApiResponse::notFound();
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
};

$respondMethodNotAllowed = function(string $path) use ($isApiPath) {
    if ($isApiPath($path)) {
        \App\Services\ApiResponse::error('Método não permitido', 405, 'METHOD_NOT_ALLOWED');
    } else {
        http_response_code(405);
        echo '405 Method Not Allowed';
    }
};

// Mapear /public/* diretamente
if (strpos($uri, '/public/') === 0) {
    $target = $publicDir . substr($uri, strlen('/public'));
    if (is_file($target)) {
        require $target;
        exit;
    }
}

// Fallback: tentar mapear para arquivo dentro de public quando NÃO começar com /public/
if (strpos($uri, '/public/') !== 0) {
    $possible = $publicDir . $uri;
    if ($uri !== '/' && file_exists($possible) && is_file($possible)) {
        require $possible;
        exit;
    }
}

// Dispatcher
if (isset($routes[$method][$uri])) {
    $handler = $routes[$method][$uri];
    if (is_callable($handler)) {
        $handler();
    } elseif (is_array($handler) && count($handler) === 2) {
        $controller = $handler[0];
        $methodName = $handler[1];
        $controller->$methodName();
    }
    exit;
}

// Se rota existe em outro método, responder 405
$hasPathInOtherMethod = false;
foreach ($routes as $m => $map) {
    if ($m !== $method && isset($map[$uri])) {
        $hasPathInOtherMethod = true;
        break;
    }
}
if ($hasPathInOtherMethod) {
    $respondMethodNotAllowed($uri);
    exit;
}

// Sem correspondência: 404 padronizado
$respondNotFound($uri);