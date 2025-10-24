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

// Dispatcher com tabela de rotas + middlewares simples (auth, CSRF)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$api = new \App\Controllers\ApiController();

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

// Middlewares
$middlewares = [
    'guest_only' => function (): bool {
        if (isAuthed()) {
            header('Location: /index.php');
            return false;
        }
        return true;
    },
    'auth_page' => function (): bool {
        if (!isAuthed()) {
            header('Location: /login.php');
            return false;
        }
        return true;
    },
    'auth_api' => function () use ($isApiPath): bool {
        if (!isAuthed()) {
            \App\Services\ApiResponse::unauthorized();
            return false;
        }
        return true;
    },
    'csrf_api' => function (): bool {
        $csrf = new \App\Services\CsrfService();
        $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$csrf->validate($token)) {
            \App\Services\ApiResponse::error('CSRF inválido', 403, 'FORBIDDEN');
            return false;
        }
        return true;
    },
];

$runMiddlewares = function (array $names) use ($middlewares): bool {
    foreach ($names as $name) {
        if (isset($middlewares[$name])) {
            $ok = $middlewares[$name]();
            if ($ok !== true) { return false; }
        }
    }
    return true;
};

// Tabela de rotas com middlewares
$routes = [
    'GET' => [
        '/' => [
            'handler' => function() use ($publicDir) { require $publicDir . '/login.php'; },
            'middlewares' => ['guest_only']
        ],
        '/login.php' => [
            'handler' => function() use ($publicDir) { require $publicDir . '/login.php'; },
            'middlewares' => ['guest_only']
        ],
        '/index.php' => [
            'handler' => function() use ($publicDir) { require $publicDir . '/index.php'; },
            'middlewares' => ['auth_page']
        ],
        '/logout.php' => [
            'handler' => function() use ($publicDir) { require $publicDir . '/logout.php'; }
        ],
        '/admin' => [
            'handler' => function() use ($adminDir) { require $adminDir . '/manage.php'; },
            'middlewares' => ['auth_page']
        ],
        '/admin/' => [
            'handler' => function() use ($adminDir) { require $adminDir . '/manage.php'; },
            'middlewares' => ['auth_page']
        ],
        '/admin/manage.php' => [
            'handler' => function() use ($adminDir) { require $adminDir . '/manage.php'; },
            'middlewares' => ['auth_page']
        ],
        // API
        '/api/health' => [ 'handler' => [$api, 'getHealth'] ],
        '/api/homepage-courses' => [ 'handler' => [$api, 'getHomepageCourses'], 'middlewares' => ['auth_api'] ],
        '/api/user/modal-state' => [ 'handler' => [$api, 'getUserModalState'], 'middlewares' => ['auth_api'] ],
    ],
    'POST' => [
        '/api/homepage-courses' => [ 'handler' => [$api, 'postHomepageCourses'], 'middlewares' => ['auth_api', 'csrf_api'] ],
        '/api/user/main-modal/close' => [ 'handler' => [$api, 'postUserMainModalClose'], 'middlewares' => ['auth_api', 'csrf_api'] ],
    ],
];

// Mapear /public/* diretamente
if (strpos($uri, '/public/') === 0) {
    $target = $publicDir . substr($uri, strlen('/public'));
    if (is_file($target)) {
        require $target;
        exit;
    }
}

// Fallback: arquivo em public quando NÃO começar com /public/
if (strpos($uri, '/public/') !== 0) {
    $possible = $publicDir . $uri;
    if ($uri !== '/' && file_exists($possible) && is_file($possible)) {
        require $possible;
        exit;
    }
}

// Dispatcher
if (isset($routes[$method][$uri])) {
    $entry = $routes[$method][$uri];
    $mws = (array)($entry['middlewares'] ?? []);
    if (!$runMiddlewares($mws)) { exit; }

    $handler = $entry['handler'] ?? null;
    if (is_callable($handler)) {
        $handler();
    } elseif (is_array($handler) && count($handler) === 2) {
        $controller = $handler[0];
        $methodName = $handler[1];
        $controller->$methodName();
    }
    exit;
}

// Se a rota existe em outro método, responder 405
$hasPathInOtherMethod = false;
foreach ($routes as $m => $map) {
    if ($m !== $method && isset($map[$uri])) { $hasPathInOtherMethod = true; break; }
}
if ($hasPathInOtherMethod) { $respondMethodNotAllowed($uri); exit; }

// 404 padronizado
$respondNotFound($uri);