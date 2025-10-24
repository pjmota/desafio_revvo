<?php
declare(strict_types=1);

namespace App\Repositories;

require_once __DIR__ . '/../../inc/db.php';

class SlideRepository
{
    public function listAll(): array
    {
        $pdo = db();
        return $pdo->query('SELECT * FROM slides ORDER BY criado_em DESC')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(string $imagem, string $titulo, string $descricao, ?string $link): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO slides(imagem, titulo, descricao, link) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$imagem, $titulo, $descricao, $link ?? '#']);
    }

    public function update(int $id, string $titulo, string $descricao, ?string $link, ?string $imagem): bool
    {
        $pdo = db();
        if ($imagem) {
            $stmt = $pdo->prepare('UPDATE slides SET titulo=?, descricao=?, link=?, imagem=? WHERE id=?');
            return $stmt->execute([$titulo, $descricao, $link ?? '#', $imagem, $id]);
        }
        $stmt = $pdo->prepare('UPDATE slides SET titulo=?, descricao=?, link=? WHERE id=?');
        return $stmt->execute([$titulo, $descricao, $link ?? '#', $id]);
    }

    public function delete(int $id): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('DELETE FROM slides WHERE id=?');
        return $stmt->execute([$id]);
    }
}