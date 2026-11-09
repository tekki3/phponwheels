<?php

declare(strict_types=1);

namespace PhpOnWheels\Tests\Unit\Sapi;

use PhpOnWheels\Sapi\DevelopmentCertificate;
use PHPUnit\Framework\TestCase;

final class DevelopmentCertificateTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (!extension_loaded('openssl')) {
            self::markTestSkipped('The OpenSSL extension is not available.');
        }

        $this->directory = sys_get_temp_dir() . '/phponwheels-certificate-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function test_it_generates_and_reuses_a_localhost_certificate(): void
    {
        $path = DevelopmentCertificate::ensure($this->directory);

        self::assertFileExists($path);
        self::assertSame($path, DevelopmentCertificate::ensure($this->directory));

        $pem = file_get_contents($path);
        self::assertNotFalse($pem);

        $certificate = openssl_x509_read($pem);
        self::assertNotFalse($certificate);
        $details = openssl_x509_parse($certificate);
        self::assertIsArray($details);

        if (!is_array($details['extensions'] ?? null)) {
            self::fail('The generated certificate has no extensions.');
        }

        $subjectAltName = $details['extensions']['subjectAltName'] ?? null;
        self::assertIsString($subjectAltName);
        self::assertStringContainsString('DNS:localhost', $subjectAltName);
        self::assertStringContainsString('IP Address:127.0.0.1', $subjectAltName);
    }
}
