<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Storage;

use App\Library\Application\Ports\CoverStorage;

/**
 * Cover binaries on local disk under COVERS_DIR (Article IX: from env).
 * Keys are opaque (uuid + extension); the directory layout never leaks.
 */
final class DiskCoverStorage implements CoverStorage
{
    public function __construct(private readonly string $directory) {}

    public function store(string $key, string $bytes, string $mimeType): void
    {
        $path = $this->path($key);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, $bytes);
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function exists(string $key): bool
    {
        return is_file($this->path($key));
    }

    /**
     * Reads a stored cover with its content-sniffed MIME type.
     *
     * @return array{bytes: string, mime: string}|null when the key is missing
     */
    public function content(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($bytes) ?: 'application/octet-stream';

        return ['bytes' => $bytes, 'mime' => $mime];
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, '/\\').DIRECTORY_SEPARATOR.basename($key);
    }
}
