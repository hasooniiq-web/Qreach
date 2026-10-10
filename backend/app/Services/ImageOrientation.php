<?php

namespace App\Services;

/**
 * Phone cameras usually store a photo's pixels in the sensor's orientation and
 * add an EXIF "Orientation" flag saying how to turn it upright (and, for the
 * front camera, how to un-mirror it). GD ignores that flag, so a portrait selfie
 * would come out lying on its side. This applies it once, to the decoded pixels.
 * The output is re-encoded without EXIF, so the flag is never applied twice.
 */
final class ImageOrientation
{
    /** @param \GdImage $image */
    public static function upright($image, string $path): \GdImage
    {
        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = (int) ($exif['Orientation'] ?? 1);
        }

        $fixed = match ($orientation) {
            2 => self::flip($image, IMG_FLIP_HORIZONTAL),
            3 => imagerotate($image, 180, 0),
            4 => self::flip($image, IMG_FLIP_VERTICAL),
            5 => self::flip(imagerotate($image, -90, 0), IMG_FLIP_HORIZONTAL),
            6 => imagerotate($image, -90, 0),
            7 => self::flip(imagerotate($image, 90, 0), IMG_FLIP_HORIZONTAL),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $fixed ?: $image;
    }

    /** @param \GdImage $image */
    private static function flip($image, int $mode): \GdImage
    {
        if ($image !== false) {
            imageflip($image, $mode);
        }

        return $image;
    }
}
