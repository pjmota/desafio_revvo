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

    public function testGetRecentCourseIds(): void
    {
        $courses = new \App\Repositories\CourseRepository();
        $home = new \App\Repositories\HomepageRepository();

        // Criar dois cursos
        $this->assertTrue($courses->create('Curso Recente', 'Desc', '/assets/uploads/test.jpg'));
        $this->assertTrue($courses->create('Curso Antigo', 'Desc', '/assets/uploads/test2.jpg'));
        $pdo = db();
        $rows = $pdo->query('SELECT id FROM cursos ORDER BY id ASC')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertGreaterThanOrEqual(2, count($rows));
        $c1 = (int)$rows[count($rows)-2];
        $c2 = (int)$rows[count($rows)-1];

        // Adicionar ambos ao usuário 1
        $this->assertTrue($home->addCourse(1, $c1));
        $this->assertTrue($home->addCourse(1, $c2));

        // Tornar o c1 antigo: ajustar created_at para 2 dias atrás
        $stmt = $pdo->prepare("UPDATE user_homepage_courses SET created_at = datetime('now','-2 day') WHERE user_id = ? AND course_id = ?");
        $stmt->execute([1, $c1]);

        $recent = $home->getRecentCourseIds(1);
        $this->assertIsArray($recent);
        // Deve conter c2 e não c1
        $this->assertContains($c2, $recent);
        $this->assertNotContains($c1, $recent);
    }
}