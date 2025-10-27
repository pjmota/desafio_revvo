<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repositories\SlideRepository;

final class SlideRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = db();
        $pdo->exec('DELETE FROM slides');
    }

    public function testCreateUpdateDeleteSlide(): void
    {
        $repo = new SlideRepository();
        // Create
        $okCreate = $repo->create('/assets/uploads/test.jpg', 'Título', 'Descrição', '#');
        $this->assertTrue($okCreate);

        $pdo = db();
        $id = (int)$pdo->query('SELECT id FROM slides ORDER BY id DESC LIMIT 1')->fetchColumn();
        $this->assertGreaterThan(0, $id);

        // Update sem imagem
        $okUpdate1 = $repo->update($id, 'Titulo 2', 'Descricao 2', '/link', null);
        $this->assertTrue($okUpdate1);
        $row1 = $pdo->query('SELECT titulo, descricao, link, imagem FROM slides WHERE id = '.(int)$id)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Titulo 2', $row1['titulo'] ?? null);
        $this->assertSame('Descricao 2', $row1['descricao'] ?? null);
        $this->assertSame('/link', $row1['link'] ?? null);
        $this->assertNotEmpty($row1['imagem'] ?? '');

        // Update com imagem
        $okUpdate2 = $repo->update($id, 'Titulo 3', 'Descricao 3', '/link2', '/assets/uploads/updated.jpg');
        $this->assertTrue($okUpdate2);
        $row2 = $pdo->query('SELECT titulo, descricao, link, imagem FROM slides WHERE id = '.(int)$id)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Titulo 3', $row2['titulo'] ?? null);
        $this->assertSame('Descricao 3', $row2['descricao'] ?? null);
        $this->assertSame('/link2', $row2['link'] ?? null);
        $this->assertSame('/assets/uploads/updated.jpg', $row2['imagem'] ?? null);

        // Delete
        $okDelete = $repo->delete($id);
        $this->assertTrue($okDelete);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM slides WHERE id = '.(int)$id)->fetchColumn();
        $this->assertSame(0, $count);
    }
}