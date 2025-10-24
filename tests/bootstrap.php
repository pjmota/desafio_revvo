<?php
declare(strict_types=1);

// Autoload de classes
require_once __DIR__ . '/../vendor/autoload.php';

// Config global e DB
require_once __DIR__ . '/../inc/config.php';
// Usar banco em memória para testes
$DB_PATH = ':memory:';
require_once __DIR__ . '/../inc/db.php';

// Inicializar schema de testes
init_db();