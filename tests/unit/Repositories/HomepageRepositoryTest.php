<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repositories\HomepageRepository;
use App\Repositories\CourseRepository;

final class HomepageRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpar tabela de cursos da homepage
        $pdo = db();
        $pdo->exec('DELETE FROM user_homepage_courses');
        $pdo->exec('DELETE FROM cursos');
    }

    public function testAddAndGetSelectedCourseIds(): void
    {
        $courses = new CourseRepository();
        $home = new HomepageRepository();

        // Criar curso real para ter um ID válido
        $ok = $courses->create('Curso Teste', 'Desc', '/assets/uploads/test.jpg');
        $this->assertTrue($ok);
        $pdo = db();
        $cid = (int)$pdo->query('SELECT id FROM cursos ORDER BY id DESC LIMIT 1')->fetchColumn();
        $this->assertGreaterThan(0, $cid);

        // Adicionar para usuário 1 (seed padrão)
        $this->assertTrue($home->addCourse(1, $cid));
        $ids = $home->getSelectedCourseIds(1);
        $this->assertContains($cid, $ids);
    }
}