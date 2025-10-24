<?php
declare(strict_types=1);

namespace App\Services;

class ApiResponse
{
    /**
     * Envia resposta de sucesso padronizada
     */
    public static function success(array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Envia resposta de erro padronizada
     */
    public static function error(string $message, int $statusCode = 400, ?string $code = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code ?? self::getDefaultErrorCode($statusCode)
            ],
            'timestamp' => date('c')
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Envia resposta de não autorizado
     */
    public static function unauthorized(string $message = 'Acesso não autorizado'): void
    {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Envia resposta de dados inválidos
     */
    public static function badRequest(string $message = 'Dados inválidos'): void
    {
        self::error($message, 400, 'BAD_REQUEST');
    }

    /**
     * Envia resposta de erro interno
     */
    public static function internalError(string $message = 'Erro interno do servidor'): void
    {
        self::error($message, 500, 'INTERNAL_ERROR');
    }

    /**
     * Envia resposta de não encontrado
     */
    public static function notFound(string $message = 'Recurso não encontrado'): void
    {
        self::error($message, 404, 'NOT_FOUND');
    }

    /**
     * Obtém código de erro padrão baseado no status HTTP
     */
    private static function getDefaultErrorCode(int $statusCode): string
    {
        return match($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            500 => 'INTERNAL_ERROR',
            default => 'UNKNOWN_ERROR'
        };
    }
}