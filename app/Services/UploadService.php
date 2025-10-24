<?php
declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../../inc/config.php';

class UploadService
{
    // Dimensões virão do config global
    /**
     * Faz upload de imagens com validações reforçadas.
     * Retorna caminho relativo (ex.: /assets/uploads/filename.ext) ou null em caso de falha.
     */
    public function upload(array $file): ?string
    {
        global $UPLOAD_DIR, $UPLOAD_MAX_SIZE_BYTES, $UPLOAD_ALLOWED_MIME;
        global $UPLOAD_MIN_WIDTH, $UPLOAD_MIN_HEIGHT, $UPLOAD_MAX_WIDTH, $UPLOAD_MAX_HEIGHT;

        $uploadDir = $UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        // Verificação básica de erro do PHP
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            \App\Services\Logger::warning('upload_error_flag', [
                'error' => $file['error'] ?? null,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // Verificação de tamanho
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $UPLOAD_MAX_SIZE_BYTES) {
            \App\Services\Logger::warning('upload_invalid_size', [
                'size' => $size,
                'max' => $UPLOAD_MAX_SIZE_BYTES,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // Verificação de origem e arquivo temporário
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            \App\Services\Logger::warning('upload_tmp_invalid', [
                'tmp' => $tmp,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // MIME real via finfo
        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $tmp) ?: null;
                finfo_close($fi);
            }
        }
        if ($mime === null || !in_array($mime, $UPLOAD_ALLOWED_MIME, true)) {
            \App\Services\Logger::warning('upload_disallowed_mime', [
                'mime' => $mime,
                'allowed' => $UPLOAD_ALLOWED_MIME,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // Verificar se é imagem válida e checar dimensões
        $imgInfo = @getimagesize($tmp);
        if ($imgInfo === false) {
            \App\Services\Logger::warning('upload_getimagesize_failed', [
                'mime' => $mime,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }
        $width = (int)($imgInfo[0] ?? 0);
        $height = (int)($imgInfo[1] ?? 0);
        if ($width < $UPLOAD_MIN_WIDTH || $height < $UPLOAD_MIN_HEIGHT || $width > $UPLOAD_MAX_WIDTH || $height > $UPLOAD_MAX_HEIGHT) {
            \App\Services\Logger::warning('upload_dimensions_out_of_range', [
                'width' => $width,
                'height' => $height,
                'min_w' => $UPLOAD_MIN_WIDTH,
                'min_h' => $UPLOAD_MIN_HEIGHT,
                'max_w' => $UPLOAD_MAX_WIDTH,
                'max_h' => $UPLOAD_MAX_HEIGHT,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // Mapear extensão pelo MIME
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? null;
        if ($ext === null) {
            \App\Services\Logger::warning('upload_extension_map_failed', [
                'mime' => $mime,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return null;
        }

        // Nome seguro
        $safe = 'img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
        $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe;
        if (@move_uploaded_file($tmp, $dest)) {
            \App\Services\Logger::info('upload_success', [
                'dest' => $dest,
                'size' => $size,
                'mime' => $mime,
                'width' => $width,
                'height' => $height,
                'name' => (string)($file['name'] ?? ''),
            ]);
            return '/assets/uploads/' . $safe;
        }

        \App\Services\Logger::error('upload_move_failed', [
            'dest' => $dest,
            'size' => $size,
            'mime' => $mime,
            'name' => (string)($file['name'] ?? ''),
        ]);
        return null;
    }
}