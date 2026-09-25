<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Turns an uploaded image into a small square JPEG (spec section 35): the
 * file must decode as a real image, is center-cropped and resized, and is
 * re-encoded — which drops metadata and any embedded payload.
 */
class AvatarProcessor
{
    public const SIZE = 256;

    public function normalize(UploadedFile $file): ?string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        // Transparent PNG/WebP areas become white rather than black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::SIZE, self::SIZE, $side, $side
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $bytes = ob_get_clean();

        return $bytes !== false && $bytes !== '' ? $bytes : null;
    }
}
