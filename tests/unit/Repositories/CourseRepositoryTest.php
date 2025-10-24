<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repositories\CourseRepository;

final class CourseRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = db();
        $pdo->exec('DELETE FROM cursos');
    }

    public function testCreateAndListCourse(): void
    {
        $repo = new CourseRepository();
        $ok = $repo->create('Curso PHPUnit', 'Descrição', '/assets/uploads/test.jpg');
        $this->assertTrue($ok);

        // Verificar via consulta direta
        $pdo = db();
        $row = $pdo->query("SELECT titulo FROM cursos WHERE titulo = 'Curso PHPUnit' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $this->assertIsArray($row);
        $this->assertSame('Curso PHPUnit', $row['titulo'] ?? null);

        $list = $repo->listAll();
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(1, count($list));
    }
}