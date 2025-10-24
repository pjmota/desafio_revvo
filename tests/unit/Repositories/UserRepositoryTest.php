<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repositories\UserRepository;

final class UserRepositoryTest extends TestCase
{
    public function testFindByEmailSeedUser(): void
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail('teste@teste.com');
        $this->assertIsArray($user);
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('is_admin', $user);
    }

    public function testListAllReturnsUsers(): void
    {
        $repo = new UserRepository();
        $list = $repo->listAll();
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(1, count($list));
    }

    public function testGetProfileById(): void
    {
        $repo = new UserRepository();
        $profile = $repo->getProfileById(1);
        $this->assertIsArray($profile);
        $this->assertArrayHasKey('avatar', $profile);
        $this->assertArrayHasKey('is_admin', $profile);
    }
}