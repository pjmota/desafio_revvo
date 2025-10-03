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
    // Tabela slides
    $pdo->exec('CREATE TABLE IF NOT EXISTS slides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        imagem TEXT NOT NULL,
        titulo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        link TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
}

function sanitize(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function upload_image(array $file): ?string {
    global $UPLOAD_DIR;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $name = basename($file['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    $safe = uniqid('img_', true) . '.' . $ext;
    $dest = $UPLOAD_DIR . '/' . $safe;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/assets/uploads/' . $safe;
    }
    return null;
}

init_db();