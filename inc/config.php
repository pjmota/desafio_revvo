<?php
declare(strict_types=1);

// Diretórios e caminhos
$DATA_DIR = __DIR__ . '/../data';
$DB_PATH = $DATA_DIR . '/revvo.sqlite';
$UPLOAD_DIR = __DIR__ . '/../assets/uploads';

// Garantir que os diretórios existem
if (!is_dir($DATA_DIR)) {
    @mkdir($DATA_DIR, 0775, true);
}
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}