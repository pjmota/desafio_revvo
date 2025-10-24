<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\CsrfService;

final class CsrfServiceTest extends TestCase
{
    public function testGetTokenNotEmpty(): void
    {
        $service = new CsrfService();
        $token = $service->getToken();
        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertSame(strlen($token), 64, 'Token esperado em hex 32 bytes (64 chars)');
    }

    public function testValidateValidToken(): void
    {
        $service = new CsrfService();
        $token = $service->getToken();
        $this->assertTrue($service->validate($token));
    }

    public function testValidateInvalidToken(): void
    {
        $service = new CsrfService();
        $this->assertFalse($service->validate('invalid'));
        $this->assertFalse($service->validate(null));
        $this->assertFalse($service->validate(''));
    }
}