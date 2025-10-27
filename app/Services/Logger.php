<?php
declare(strict_types=1);

namespace App\Services;

class Logger
{
    private static function logFilePath(): string
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }
        return $base . DIRECTORY_SEPARATOR . 'app.log';
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
        ];
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents(self::logFilePath(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}