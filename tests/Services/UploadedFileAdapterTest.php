<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\UploadedFileAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFileAdapterTest extends TestCase
{
    public function testReturnsStorageInputWithoutClientMime(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getMetadata')
            ->with('uri')->willReturn('/tmp/php-upload');
        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn(UPLOAD_ERR_OK);
        $file->method('getClientFilename')->willReturn('photo.php');
        $file->method('getClientMediaType')->willReturn('image/png');
        $file->method('getSize')->willReturn(123);
        $file->method('getStream')->willReturn($stream);

        $result = (new UploadedFileAdapter())->fromRequest(
            $this->requestWith(['attachment' => $file])
        );

        self::assertSame([
            'name' => 'photo.php',
            'tmp_name' => '/tmp/php-upload',
            'size' => 123,
        ], $result);
        self::assertArrayNotHasKey('type', $result);
    }

    /** @return iterable<string, array{int, ?string, ?int, mixed}> */
    public static function invalidUploadProvider(): iterable
    {
        yield 'upload error' => [UPLOAD_ERR_PARTIAL, 'file.png', 1, '/tmp/file'];
        yield 'missing name' => [UPLOAD_ERR_OK, null, 1, '/tmp/file'];
        yield 'empty name' => [UPLOAD_ERR_OK, '', 1, '/tmp/file'];
        yield 'missing size' => [UPLOAD_ERR_OK, 'file.png', null, '/tmp/file'];
        yield 'missing temporary path' => [UPLOAD_ERR_OK, 'file.png', 1, null];
        yield 'empty temporary path' => [UPLOAD_ERR_OK, 'file.png', 1, ''];
    }

    #[DataProvider('invalidUploadProvider')]
    public function testRejectsIncompleteUpload(
        int $error,
        ?string $name,
        ?int $size,
        mixed $temporaryPath
    ): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->willReturn($temporaryPath);
        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn($error);
        $file->method('getClientFilename')->willReturn($name);
        $file->method('getSize')->willReturn($size);
        $file->method('getStream')->willReturn($stream);

        self::assertNull(
            (new UploadedFileAdapter())->fromRequest(
                $this->requestWith(['attachment' => $file])
            )
        );
    }

    public function testRejectsMissingField(): void
    {
        self::assertNull(
            (new UploadedFileAdapter())->fromRequest($this->requestWith([]))
        );
    }

    /** @param array<string, UploadedFileInterface> $files */
    private function requestWith(array $files): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn($files);

        return $request;
    }
}
