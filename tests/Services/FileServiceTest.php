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
            foreach (glob($yearDirectory . '/*') ?: [] as $filePath) {
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
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
            [FileService::ERROR_INVALID_TYPE],
            (new ReflectionMethod(FileService::class, 'validateFile'))->invoke(
                $service,
                ['tmp_name' => __FILE__, 'size' => 1],
                'image/gif'
            )
        );
    }

    public function testReturnsStableErrorCodeForOversizedFile(): void
    {
        $service = new FileService(
            $this->uploadDirectory,
            ['image/png'],
            100
        );

        self::assertSame(
            [FileService::ERROR_TOO_LARGE],
            (new ReflectionMethod(FileService::class, 'validateFile'))->invoke(
                $service,
                ['tmp_name' => __FILE__, 'size' => 101],
                'image/png'
            )
        );
    }

    public function testRemovesUploadedFileOnlyInsideUploadRoot(): void
    {
        $filePath = $this->createUploadFile('orphan.png');

        self::assertTrue($this->service->removeUploadedFile($filePath));
        self::assertFileDoesNotExist($filePath);
        self::assertFalse($this->service->removeUploadedFile(__FILE__));
        self::assertFileExists(__FILE__);
    }

    public function testRestoresStagedFileWhenDatabaseDeletionFails(): void
    {
        $filePath = $this->createUploadFile('restore.png');

        $stagedPath = $this->service->stageDeletion($filePath);
        self::assertIsString($stagedPath);
        self::assertNotSame('', $stagedPath);
        self::assertFileDoesNotExist($filePath);
        self::assertFileExists($stagedPath);

        self::assertTrue($this->service->restoreDeletion($stagedPath, $filePath));
        self::assertFileExists($filePath);
        self::assertFileDoesNotExist($stagedPath);
    }

    public function testFinalizesStagedFileAfterDatabaseDeletion(): void
    {
        $filePath = $this->createUploadFile('delete.png');
        $stagedPath = $this->service->stageDeletion($filePath);
        self::assertIsString($stagedPath);
        self::assertNotSame('', $stagedPath);

        self::assertTrue($this->service->finalizeDeletion($stagedPath));
        self::assertFileDoesNotExist($filePath);
        self::assertFileDoesNotExist($stagedPath);
    }

    public function testTreatsMissingSafeFileAsAlreadyDeleted(): void
    {
        $missingPath = $this->uploadDirectory . '/' . date('Y') . '/missing.png';

        self::assertSame('', $this->service->stageDeletion($missingPath));
        self::assertTrue($this->service->finalizeDeletion(''));
        self::assertTrue($this->service->restoreDeletion('', $missingPath));
    }

    private function createUploadFile(string $fileName): string
    {
        $filePath = $this->uploadDirectory . '/' . date('Y') . '/' . $fileName;
        file_put_contents($filePath, 'test');

        return $filePath;
    }
}
