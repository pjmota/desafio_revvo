<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    global $DB_PATH;
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function init_db(): void {
    $pdo = db();
    // Tabela cursos
    $pdo->exec('CREATE TABLE IF NOT EXISTS cursos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    // Garantir coluna de imagem para cursos
    $cols = $pdo->query('PRAGMA table_info(cursos)')->fetchAll(PDO::FETCH_ASSOC);
    $hasImagem = false;
    foreach ($cols as $col) {
        if (($col['name'] ?? '') === 'imagem') { $hasImagem = true; break; }
    }
    if (!$hasImagem) {
        $pdo->exec('ALTER TABLE cursos ADD COLUMN imagem TEXT');
    }
    // Tabela slides
    $pdo->exec('CREATE TABLE IF NOT EXISTS slides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        imagem TEXT NOT NULL,
        titulo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        link TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    // Tabela usuarios
    $pdo->exec('CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha_hash TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    // Garantir coluna para controle de exibição do modal principal por usuário
    $colsUsuarios = $pdo->query('PRAGMA table_info(usuarios)')->fetchAll(PDO::FETCH_ASSOC);
    $hasShowMainModal = false;
    foreach ($colsUsuarios as $col) {
        if (($col['name'] ?? '') === 'show_main_modal') { $hasShowMainModal = true; break; }
    }
    if (!$hasShowMainModal) {
        // 0 = não mostrar; 1 = mostrar no primeiro acesso
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN show_main_modal INTEGER NOT NULL DEFAULT 1');
        // Setar valor inicial 1 para usuários existentes
        $pdo->exec('UPDATE usuarios SET show_main_modal = 1 WHERE show_main_modal IS NULL');
    }
    // Garantir coluna de avatar (imagem) por usuário
    $hasAvatar = false;
    foreach ($colsUsuarios as $col) {
        if (($col['name'] ?? '') === 'avatar') { $hasAvatar = true; break; }
    }
    if (!$hasAvatar) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN avatar TEXT');
    }
    // Garantir coluna is_admin (boolean/integer) por usuário
    $hasIsAdmin = false;
    foreach ($colsUsuarios as $col) {
        if (($col['name'] ?? '') === 'is_admin') { $hasIsAdmin = true; break; }
    }
    if (!$hasIsAdmin) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE usuarios SET is_admin = 0 WHERE is_admin IS NULL');
    }
    // Garantir índice único para email (proteção adicional)
    $indexes = $pdo->query("PRAGMA index_list('usuarios')")->fetchAll(PDO::FETCH_ASSOC);
    $hasEmailUniqueIdx = false;
    foreach ($indexes as $idx) {
        if (($idx['name'] ?? '') === 'idx_usuarios_email_unique') { $hasEmailUniqueIdx = true; break; }
    }
    if (!$hasEmailUniqueIdx) {
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_usuarios_email_unique ON usuarios(email)');
    }

    // Tabela de seleção de cursos por usuário (home personalizada)
    $pdo->exec('CREATE TABLE IF NOT EXISTS user_homepage_courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        position INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, course_id)
    )');

    // Seed usuário teste se tabela está vazia
    $count = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, is_admin) VALUES (?, ?, ?, 1)');
        $stmt->execute(['Teste', 'teste@teste.com', password_hash('123456', PASSWORD_DEFAULT)]);
    }
}

function sanitize(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function upload_image(array $file): ?string {
    global $UPLOAD_DIR;
    // Verificar erro e tamanho
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) { return null; }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) { return null; } // limite 5MB

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) { return null; }

    // Validação MIME real
    $mime = null;
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $mime = finfo_file($fi, $tmp) ?: null;
            finfo_close($fi);
        }
    }
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if ($mime === null || !in_array($mime, $allowed, true)) { return null; }

    // Verificação extra de imagem
    $imgInfo = @getimagesize($tmp);
    if ($imgInfo === false) { return null; }

    // Mapear extensão pelo MIME para evitar mismatch
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? null;
    if ($ext === null) { return null; }

    // Nome de arquivo seguro
    $safe = 'img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $dest = $UPLOAD_DIR . '/' . $safe;
    if (@move_uploaded_file($tmp, $dest)) {
        return '/assets/uploads/' . $safe;
    }
    return null;
}

// JWT helpers
function base64url_encode(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function base64url_decode(string $data): string { return base64_decode(strtr($data, '-_', '+/')); }

function get_jwt_secret(): string {
    global $DATA_DIR;
    $secretFile = $DATA_DIR . '/jwt_secret.txt';
    if (!is_file($secretFile)) {
        $rnd = bin2hex(random_bytes(32));
        file_put_contents($secretFile, $rnd);
        return $rnd;
    }
    $secret = trim((string)file_get_contents($secretFile));
    if ($secret === '') {
        $secret = bin2hex(random_bytes(32));
        file_put_contents($secretFile, $secret);
    }
    return $secret;
}

function jwt_encode(array $payload, int $expiresInSeconds = 3600): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresInSeconds;
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload))
    ];
    $signingInput = implode('.', $segments);
    $secret = get_jwt_secret();
    $signature = base64url_encode(hash_hmac('sha256', $signingInput, $secret, true));
    return $signingInput . '.' . $signature;
}

function jwt_decode(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    [$h64, $p64, $s64] = $parts;
    $secret = get_jwt_secret();
    $expected = base64url_encode(hash_hmac('sha256', "$h64.$p64", $secret, true));
    if (!hash_equals($expected, $s64)) return null;
    $payload = json_decode(base64url_decode($p64), true);
    if (!is_array($payload)) return null;
    if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
    return $payload;
}

// Emite cookies de access token (curto) e refresh token (1h por padrão)
function issue_tokens(array $user, int $accessTtl = 900, int $refreshTtl = 3600): void {
    $accessPayload = [
        'sub' => (int)$user['id'],
        'name' => (string)($user['nome'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'type' => 'access'
    ];
    $refreshPayload = [
        'sub' => (int)$user['id'],
        'type' => 'refresh'
    ];
    $access = jwt_encode($accessPayload, $accessTtl);
    $refresh = jwt_encode($refreshPayload, $refreshTtl);
    $params = [
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ];
    $accessParams = $params; $accessParams['expires'] = time() + $accessTtl;
    $refreshParams = $params; $refreshParams['expires'] = time() + $refreshTtl;
    setcookie('jwt', $access, $accessParams);
    setcookie('refresh', $refresh, $refreshParams);
}

function current_user(): ?array {
    $jwt = $_COOKIE['jwt'] ?? '';
    if ($jwt) {
        $payload = jwt_decode($jwt);
        if (is_array($payload) && ($payload['type'] ?? 'access') === 'access') {
            return [
                'id' => isset($payload['sub']) ? (int)$payload['sub'] : null,
                'nome' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
            ];
        }
    }
    // Tentar refresh automático quando access token inválido/expirado
    $refresh = $_COOKIE['refresh'] ?? '';
    if ($refresh) {
        $rp = jwt_decode($refresh);
        if (is_array($rp) && ($rp['type'] ?? 'refresh') === 'refresh' && isset($rp['sub'])) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE id = ?');
                $stmt->execute([(int)$rp['sub']]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    issue_tokens($u); // 15min access, 1h refresh por padrão
                    $newPayload = isset($_COOKIE['jwt']) ? jwt_decode($_COOKIE['jwt']) : null;
                    if (is_array($newPayload)) {
                        return [
                            'id' => isset($newPayload['sub']) ? (int)$newPayload['sub'] : null,
                            'nome' => $newPayload['name'] ?? null,
                            'email' => $newPayload['email'] ?? null,
                        ];
                    }
                }
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    return null;
}
function user_homepage_get_selected_course_ids(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT course_id FROM user_homepage_courses WHERE user_id = ? AND is_active = 1 ORDER BY position ASC, created_at ASC');
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function user_homepage_get_recent_course_ids(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT course_id FROM user_homepage_courses WHERE user_id = ? AND is_active = 1 AND datetime(created_at) >= datetime('now','-1 day') ORDER BY position ASC, created_at ASC");
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function user_homepage_add_course(int $userId, int $courseId): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM user_homepage_courses WHERE user_id = ?');
    $stmt->execute([$userId]);
    $pos = (int)$stmt->fetchColumn();
    $stmt2 = $pdo->prepare('INSERT OR IGNORE INTO user_homepage_courses (user_id, course_id, position, is_active) VALUES (?, ?, ?, 1)');
    return $stmt2->execute([$userId, $courseId, $pos]);
}

function user_homepage_remove_course(int $userId, int $courseId): bool {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM user_homepage_courses WHERE user_id = ? AND course_id = ?');
    return $stmt->execute([$userId, $courseId]);
}

function user_homepage_set_positions(int $userId, array $courseIdsInOrder): bool {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE user_homepage_courses SET position = ? WHERE user_id = ? AND course_id = ?');
        $pos = 0;
        foreach ($courseIdsInOrder as $cid) {
            $stmt->execute([$pos, $userId, (int)$cid]);
            $pos++;
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

function user_get_modal_state(int $userId): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT show_main_modal FROM usuarios WHERE id = ?');
    $stmt->execute([$userId]);
    $val = $stmt->fetchColumn();
    return ((int)$val) === 1;
}

function user_set_modal_closed_once(int $userId): bool {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE usuarios SET show_main_modal = 0 WHERE id = ?');
    return $stmt->execute([$userId]);
}