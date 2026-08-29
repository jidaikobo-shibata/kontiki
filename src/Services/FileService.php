<?php

namespace Jidaikobo\Kontiki\Services;

/**
 * Class for handling file uploads and deletions.
 */
class FileService
{
    protected $uploadDir;
    protected $allowedTypes;
    protected $maxSize;

    /**
     * Constructor to initialize the upload directory and settings.
     *
     * @param string $uploadDir The directory where files will be uploaded.
     * @param array $allowedTypes An array of allowed MIME types.
     * @param int $maxSize The maximum allowed file size in bytes.
     */
    public function __construct(
        string $uploadDir,
        array $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'],
        int $maxSize = 5000000
    ) {
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
     * @param array $file The file array from $_FILES.
     *
     * @return array An array with 'success' (bool), 'path' (string), 'filename' (string), and 'errors' (array).
     */
    public function upload(array $file): array
    {
        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            return $this->createErrorResponse($errors);
        }

        $sanitizedFileName = $this->sanitizeFileName($file['name']);
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
     * @param array $file The file array from $_FILES.
     * @return array An array of validation error messages.
     */
    protected function validateFile(array $file): array
    {
        $errors = [];

        // Validate MIME type
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedTypes)) {
            $errors[] = "Invalid file type: $mimeType.";
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
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
    protected function sanitizeFileName(string $fileName): string
    {
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $asciiName = $this->convertToAscii($originalName);
        return $asciiName . ($extension ? ".$extension" : '');
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
            $targetPath = $this->uploadDir . pathinfo($fileName, PATHINFO_FILENAME) . "_$suffix." . pathinfo($fileName, PATHINFO_EXTENSION);
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
        return preg_replace('/[^a-zA-Z0-9]+/', '_', $ascii);
    }

    /**
     * Create an error response.
     *
     * @param array $errors The list of error messages.
     * @return array The error response array.
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
}
