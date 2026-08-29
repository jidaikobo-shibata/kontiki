<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\FileService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FileServiceTest extends TestCase
{
    private string $uploadDirectory;
    private FileService $service;

    protected function setUp(): void
    {
        $this->uploadDirectory = sys_get_temp_dir()
            . '/kontiki-file-service-' . bin2hex(random_bytes(8));
        $this->service = new FileService($this->uploadDirectory);
    }

    protected function tearDown(): void
    {
        $yearDirectory = $this->uploadDirectory . '/' . date('Y');
        if (is_dir($yearDirectory)) {
            rmdir($yearDirectory);
        }
        if (is_dir($this->uploadDirectory)) {
            rmdir($this->uploadDirectory);
        }
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function safeFileNameProvider(): iterable
    {
        yield 'jpeg ignores executable extension' => [
            'profile.php',
            'jpg',
            'profile.jpg',
        ];
        yield 'png replaces misleading extension' => [
            'diagram.pdf',
            'png',
            'diagram.png',
        ];
        yield 'empty sanitized base gets fallback' => [
            '日本語.php',
            'jpg',
            'upload.jpg',
        ];
    }

    #[DataProvider('safeFileNameProvider')]
    public function testUsesDetectedMimeExtensionInsteadOfClientExtension(
        string $clientName,
        string $safeExtension,
        string $expected
    ): void {
        self::assertSame(
            $expected,
            (new ReflectionMethod(FileService::class, 'sanitizeFileName'))
                ->invoke($this->service, $clientName, $safeExtension)
        );
    }

    public function testRejectsAllowedMimeWithoutSafeExtensionMapping(): void
    {
        $service = new FileService(
            $this->uploadDirectory,
            ['image/gif']
        );

        self::assertSame(
            ['Invalid file type.'],
            (new ReflectionMethod(FileService::class, 'validateFile'))->invoke(
                $service,
                ['tmp_name' => __FILE__, 'size' => 1],
                'image/gif'
            )
        );
    }
}
