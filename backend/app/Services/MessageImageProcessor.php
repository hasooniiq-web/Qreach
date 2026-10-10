<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Normalizes a photo attached to a contact-request message (e.g. "your car
 * is blocking the road", with a picture of it): the file must decode as a
 * real image, is rotated upright using its EXIF orientation (phone cameras
 * store landscape/portrait as a flag, not by rotating the pixels), downscaled
 * to a sane maximum, and re-encoded — which, same discipline as avatars
 * (section 35), drops all metadata (including GPS) and any embedded payload.
 */
class MessageImageProcessor
{
    /** Longest side after resizing; large enough to stay legible, small enough to stay light. */
    public const MAX_DIMENSION = 1600;

    public function normalize(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        $source = @imagecreatefromstring((string) file_get_contents($path));

        if (! $source) {
            return null;
        }

        $source = ImageOrientation::upright($source, $path);

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        // Transparent PNG/WebP areas become white rather than black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 80);
        $bytes = ob_get_clean();

        return $bytes !== false && $bytes !== '' ? $bytes : null;
    }
}
