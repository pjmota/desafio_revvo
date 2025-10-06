<?php
// Router para servidor embutido do PHP com proteção por JWT
require_once __DIR__ . '/inc/db.php';
init_db();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = urldecode($uri);
$docRoot = __DIR__;
$publicDir = __DIR__ . '/public';
$adminDir = __DIR__ . '/admin';

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

// API: obter cursos selecionados para a home do usuário
if ($uri === '/api/homepage-courses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
    header('Content-Type: application/json');
    $u = current_user();
    $ids = user_homepage_get_selected_course_ids((int)$u['id']);
    $recent = user_homepage_get_recent_course_ids((int)$u['id']);
    echo json_encode(['course_ids' => $ids, 'recent_course_ids' => $recent]);
    exit;
}

// API: adicionar curso à home do usuário
if ($uri === '/api/homepage-courses' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
    header('Content-Type: application/json');
    $u = current_user();
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $cid = isset($data['course_id']) ? (int)$data['course_id'] : 0;
    if ($cid <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid_course_id']); exit; }
    $ok = user_homepage_add_course((int)$u['id'], $cid);
    echo json_encode(['ok' => $ok]);
    exit;
}

// API: estado do modal principal por usuário
if ($uri === '/api/user/modal-state' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
    header('Content-Type: application/json');
    $u = current_user();
    $show = user_get_modal_state((int)$u['id']);
    echo json_encode(['show_main_modal' => $show]);
    exit;
}

// API: marcar modal principal como fechado (não mostrar novamente)
if ($uri === '/api/user/main-modal/close' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAuthed()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
    header('Content-Type: application/json');
    $u = current_user();
    $ok = user_set_modal_closed_once((int)$u['id']);
    echo json_encode(['ok' => $ok]);
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