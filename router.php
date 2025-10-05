<?php
// Router para servidor embutido do PHP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = urldecode($uri);
$docRoot = __DIR__;
$publicDir = __DIR__ . '/public';
$adminDir = __DIR__ . '/admin';

// Se o arquivo solicitado existe na raiz do projeto, deixar o servidor servir diretamente
if ($uri !== '/' && file_exists($docRoot . $uri) && is_file($docRoot . $uri)) {
    return false; // serve arquivo estático
}

// Servir assets diretamente
if (strpos($uri, '/assets/') === 0 && file_exists($docRoot . $uri)) {
    return false;
}

// Rota raiz -> public/login.php
if ($uri === '/') {
    require $publicDir . '/login.php';
    exit;
}

// Rota explícita para /login.php
if ($uri === '/login.php') {
    require $publicDir . '/login.php';
    exit;
}

// Rota /index.php para a aplicação principal
if ($uri === '/index.php') {
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

// Rota admin
if ($uri === '/admin' || $uri === '/admin/' || $uri === '/admin/manage.php') {
    require $adminDir . '/manage.php';
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