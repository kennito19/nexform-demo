<?php
namespace NexForm;

/**
 * FileHandler – moves uploaded files to the upload directory securely.
 */
class FileHandler
{
    /**
     * Process an uploaded file from $_FILES.
     *
     * @param  array  $file   Single entry from $_FILES
     * @return array          ['path' => '...', 'name' => '...', 'size' => N, 'mime' => '...']
     * @throws \RuntimeException on failure
     */
    public function handle(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage($file['error']));
        }

        if ($file['size'] > NF_UPLOAD_MAX_SIZE) {
            throw new \RuntimeException('File exceeds maximum allowed size of ' . round(NF_UPLOAD_MAX_SIZE / 1048576, 1) . ' MB.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, NF_UPLOAD_ALLOWED, true)) {
            throw new \RuntimeException('File type "' . $ext . '" is not allowed.');
        }

        // Validate MIME type to prevent extension spoofing
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!$this->isMimeAllowed($mime, $ext)) {
            throw new \RuntimeException('File MIME type does not match its extension.');
        }

        // Generate a unique name to prevent overwrites and path traversal
        $safeName = uniqid('nf_', true) . '.' . $ext;
        $dest     = rtrim(NF_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $safeName;

        if (!is_dir(NF_UPLOAD_DIR)) {
            mkdir(NF_UPLOAD_DIR, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save the uploaded file. Check folder permissions.');
        }

        return [
            'path'     => $dest,
            'name'     => $file['name'],
            'safeName' => $safeName,
            'size'     => $file['size'],
            'mime'     => $mime,
        ];
    }

    /** Delete a previously saved upload. */
    public function delete(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function isMimeAllowed(string $mime, string $ext): bool
    {
        $map = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
            'txt'  => ['text/plain'],
            'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        ];

        $allowed = $map[$ext] ?? null;
        if ($allowed === null) return false; // unknown extension
        return in_array($mime, $allowed, true);
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by a PHP extension.',
            default               => 'An unknown upload error occurred.',
        };
    }
}
