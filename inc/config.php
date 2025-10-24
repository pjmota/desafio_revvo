<?php
declare(strict_types=1);

// Diretórios e caminhos
$DATA_DIR = __DIR__ . '/../data';
$DB_PATH = $DATA_DIR . '/revvo.sqlite';
$UPLOAD_DIR = __DIR__ . '/../assets/uploads';

// Limites de upload configuráveis
$UPLOAD_MAX_SIZE_BYTES = 5 * 1024 * 1024; // ajuste para 10 * 1024 * 1024 se desejar 10MB
$UPLOAD_ALLOWED_MIME = ['image/jpeg','image/png','image/gif','image/webp'];

// Garantir que os diretórios existem
if (!is_dir($DATA_DIR)) {
    @mkdir($DATA_DIR, 0775, true);
}
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}