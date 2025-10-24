<?php
declare(strict_types=1);

namespace App\Services;

class UploadService
{
    /**
     * Faz upload de imagens com validação reforçada.
     * Retorna caminho relativo (ex.: /assets/uploads/filename.ext) ou null.
     */
    public function upload(array $file): ?string
    {
        // Diretório de destino (independente de config global)
        $uploadDir = __DIR__ . '/../../assets/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) { // 5MB
            return null;
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }

        // MIME real
        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $tmp) ?: null;
                finfo_close($fi);
            }
        }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if ($mime === null || !in_array($mime, $allowed, true)) {
            return null;
        }

        // Verificar se é imagem válida
        $imgInfo = @getimagesize($tmp);
        if ($imgInfo === false) {
            return null;
        }

        // Extensão pelo MIME
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? null;
        if ($ext === null) {
            return null;
        }

        // Nome seguro
        $safe = 'img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
        $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe;
        if (@move_uploaded_file($tmp, $dest)) {
            return '/assets/uploads/' . $safe;
        }
        return null;
    }
}