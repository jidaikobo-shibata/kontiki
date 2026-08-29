<?php

namespace Jidaikobo\Kontiki\Services;

/**
 * Class for handling file uploads and deletions.
 */
class FileService
{
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    protected string $baseUploadDir;
    protected string $uploadDir;

    /** @var list<string> */
    protected array $allowedTypes;

    protected int $maxSize;

    /**
     * Constructor to initialize the upload directory and settings.
     *
     * @param string $uploadDir The directory where files will be uploaded.
     * @param list<string> $allowedTypes An array of allowed MIME types.
     * @param int $maxSize The maximum allowed file size in bytes.
     */
    public function __construct(
        string $uploadDir,
        array $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'],
        int $maxSize = 5000000
    ) {
        $this->baseUploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR);
        $this->uploadDir = $this->initializeUploadDir($uploadDir);
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
    }

    /**
     * Initialize the upload directory with year-based subdirectory.
     *
     * @param string $baseDir The base upload directory.
     *
     * @return string The initialized upload directory path.
     */
    protected function initializeUploadDir(string $baseDir): string
    {
        $uploadDir = rtrim($baseDir, '/') . '/' . date('Y') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        return $uploadDir;
    }

   /**
     * Handle the file upload.
     *
     * @param array<string, mixed> $file The file array from $_FILES.
     *
     * @return array{success: bool, path: string, filename: string, errors: list<string>}
     */
    public function upload(array $file): array
    {
        $mimeType = $this->detectMimeType($file['tmp_name'] ?? '');
        $errors = $this->validateFile($file, $mimeType);
        if (!empty($errors)) {
            return $this->createErrorResponse($errors);
        }

        $sanitizedFileName = $this->sanitizeFileName(
            $file['name'],
            self::MIME_EXTENSIONS[$mimeType]
        );
        $targetPath = $this->getUniqueFilePath($sanitizedFileName);

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => true,
                'path' => $targetPath,
                'filename' => basename($targetPath),
                'errors' => [],
            ];
        }

        return $this->createErrorResponse(['Failed to move uploaded file.']);
    }

    /**
     * Validate the uploaded file.
     *
     * @param array<string, mixed> $file The file array from $_FILES.
     * @return list<string> An array of validation error messages.
     */
    protected function validateFile(array $file, ?string $mimeType = null): array
    {
        $errors = [];

        $mimeType ??= $this->detectMimeType($file['tmp_name'] ?? '');
        if (
            $mimeType === null
            || !in_array($mimeType, $this->allowedTypes, true)
            || !isset(self::MIME_EXTENSIONS[$mimeType])
        ) {
            $errors[] = 'Invalid file type.';
        }

        // Validate file size
        if (($file['size'] ?? 0) > $this->maxSize) {
            $errors[] = "File exceeds maximum size of " . ($this->maxSize / 1000000) . " MB.";
        }

        return $errors;
    }

    /**
     * Sanitize the file name.
     *
     * @param string $fileName The original file name.
     * @return string The sanitized file name.
     */
    protected function sanitizeFileName(string $fileName, string $extension): string
    {
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $asciiName = $this->convertToAscii($originalName);
        $asciiName = trim($asciiName, '_');

        return ($asciiName !== '' ? $asciiName : 'upload') . ".{$extension}";
    }

    protected function detectMimeType(string $filePath): ?string
    {
        if ($filePath === '' || !is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        $mimeType = mime_content_type($filePath);

        return is_string($mimeType) ? $mimeType : null;
    }

    /**
     * Get a unique file path by appending a numeric suffix if necessary.
     *
     * @param string $fileName The sanitized file name.
     * @return string The unique file path.
     */
    protected function getUniqueFilePath(string $fileName): string
    {
        $targetPath = $this->uploadDir . $fileName;
        $suffix = 1;

        while (file_exists($targetPath)) {
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $targetPath = $this->uploadDir . "{$baseName}_{$suffix}.{$extension}";
            $suffix++;
        }

        return $targetPath;
    }

    /**
     * Convert a string to ASCII, replacing non-ASCII characters with underscores.
     *
     * @param string $string The input string.
     * @return string The ASCII converted string.
     */
    protected function convertToAscii(string $string): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        if ($ascii === false) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9]+/', '_', $ascii) ?? '';
    }

    /**
     * Create an error response.
     *
     * @param list<string> $errors The list of error messages.
     * @return array{success: false, path: '', filename: '', errors: list<string>}
     */
    protected function createErrorResponse(array $errors): array
    {
        return [
            'success' => false,
            'path' => '',
            'filename' => '',
            'errors' => $errors,
        ];
    }

    /**
     * Delete a file from the upload directory.
     *
     * @param string $filePath The relative path of the file to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $filePath): bool
    {
        $fullPath = $this->uploadDir . basename($filePath);
        return file_exists($fullPath) && unlink($fullPath);
    }

    /**
     * Remove a newly uploaded file when its database record could not be created.
     */
    public function removeUploadedFile(string $filePath): bool
    {
        if (!$this->isPathInsideUploadRoot($filePath)) {
            return false;
        }

        return !file_exists($filePath) || @unlink($filePath);
    }

    /**
     * Move a file aside before deleting its database record.
     *
     * An empty string means that the file was already absent. False means that
     * the path was unsafe or the file could not be staged.
     */
    public function stageDeletion(string $filePath): string|false
    {
        if (!$this->isPathInsideUploadRoot($filePath)) {
            return false;
        }

        if (!file_exists($filePath)) {
            return '';
        }

        try {
            $stagedPath = $filePath . '.kontiki-delete-' . bin2hex(random_bytes(8));
        } catch (\Exception) {
            return false;
        }

        return @rename($filePath, $stagedPath) ? $stagedPath : false;
    }

    public function restoreDeletion(string $stagedPath, string $originalPath): bool
    {
        if ($stagedPath === '') {
            return true;
        }

        if (
            !$this->isPathInsideUploadRoot($stagedPath)
            || !$this->isPathInsideUploadRoot($originalPath)
            || !file_exists($stagedPath)
            || file_exists($originalPath)
        ) {
            return false;
        }

        return @rename($stagedPath, $originalPath);
    }

    public function finalizeDeletion(string $stagedPath): bool
    {
        if ($stagedPath === '') {
            return true;
        }

        if (!$this->isPathInsideUploadRoot($stagedPath)) {
            return false;
        }

        return !file_exists($stagedPath) || @unlink($stagedPath);
    }

    private function isPathInsideUploadRoot(string $filePath): bool
    {
        if ($filePath === '' || str_contains($filePath, "\0")) {
            return false;
        }

        $root = realpath($this->baseUploadDir);
        $parent = realpath(dirname($filePath));
        if ($root === false || $parent === false) {
            return false;
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($parent !== $root && !str_starts_with($parent . DIRECTORY_SEPARATOR, $rootPrefix)) {
            return false;
        }

        if (file_exists($filePath)) {
            $resolvedPath = realpath($filePath);
            return $resolvedPath !== false && str_starts_with($resolvedPath, $rootPrefix);
        }

        return true;
    }
}
