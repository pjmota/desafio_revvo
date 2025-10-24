<?php
declare(strict_types=1);

namespace App\Repositories;

require_once __DIR__ . '/../../inc/db.php';

class CourseRepository
{
    public function listAll(): array
    {
        $pdo = db();
        return $pdo->query('SELECT * FROM cursos ORDER BY criado_em DESC')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(string $titulo, string $descricao, string $imagem): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO cursos(titulo, descricao, imagem) VALUES (?, ?, ?)');
        return $stmt->execute([$titulo, $descricao, $imagem]);
    }

    public function update(int $id, string $titulo, string $descricao, ?string $imagem): bool
    {
        $pdo = db();
        if ($imagem) {
            $stmt = $pdo->prepare('UPDATE cursos SET titulo=?, descricao=?, imagem=? WHERE id=?');
            return $stmt->execute([$titulo, $descricao, $imagem, $id]);
        }
        $stmt = $pdo->prepare('UPDATE cursos SET titulo=?, descricao=? WHERE id=?');
        return $stmt->execute([$titulo, $descricao, $id]);
    }

    public function delete(int $id): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('DELETE FROM cursos WHERE id=?');
        return $stmt->execute([$id]);
    }
}