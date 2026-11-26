<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores handwritten signatures captured in the mobile app (base64 PNG) on
 * the public disk.
 */
class SignatureImage
{
    /**
     * Decode a base64 PNG (with or without a data: URI prefix) and store it
     * under $directory. Returns the stored path, or null if the payload is
     * not a PNG.
     */
    public static function storeBase64Png(string $dataUri, string $directory): ?string
    {
        $payload = preg_replace('/^data:image\/png;base64,/i', '', trim($dataUri));
        $decoded = base64_decode((string) $payload, true);

        if ($decoded === false || ! str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        $path = self::newPath($directory);
        Storage::disk('public')->put($path, $decoded);

        return $path;
    }

    /**
     * Copy an existing stored signature into $directory, so a document keeps
     * the signature it was signed with even if the original later changes.
     */
    public static function copy(string $sourcePath, string $directory): ?string
    {
        $disk = Storage::disk('public');

        if ($sourcePath === '' || ! $disk->exists($sourcePath)) {
            return null;
        }

        $path = self::newPath($directory);
        $disk->copy($sourcePath, $path);

        return $path;
    }

    private static function newPath(string $directory): string
    {
        return trim($directory, '/') . '/' . now()->format('Y/m') . '/' . Str::uuid() . '.png';
    }
}
