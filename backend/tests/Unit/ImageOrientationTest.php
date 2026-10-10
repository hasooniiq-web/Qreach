<?php

namespace Tests\Unit;

use App\Services\AvatarProcessor;
use App\Services\MessageImageProcessor;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

/** A phone selfie is stored sideways with an EXIF flag; the processors must stand it up. */
class ImageOrientationTest extends TestCase
{
    /** A 60x20 JPEG, red on the left half and blue on the right, flagged with the given EXIF orientation. */
    private function sidewaysJpeg(int $orientation): UploadedFile
    {
        $im = imagecreatetruecolor(60, 20);
        imagefilledrectangle($im, 0, 0, 29, 19, imagecolorallocate($im, 255, 0, 0));
        imagefilledrectangle($im, 30, 0, 59, 19, imagecolorallocate($im, 0, 0, 255));
        ob_start();
        imagejpeg($im, null, 95);
        $jpeg = ob_get_clean();

        // Minimal big-endian EXIF APP1 segment carrying only the Orientation tag.
        $tiff = "MM\x00\x2A\x00\x00\x00\x08".pack('n', 1).pack('nnN', 0x0112, 3, 1).pack('nn', $orientation, 0)."\x00\x00\x00\x00";
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;
        $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
        file_put_contents($path, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));

        return new UploadedFile($path, 'selfie.jpg', 'image/jpeg', null, true);
    }

    private function pixel(string $jpeg, int $x, int $y): array
    {
        $im = imagecreatefromstring($jpeg);
        $c = imagecolorat($im, $x, $y);

        return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
    }

    public function test_the_avatar_of_a_sideways_selfie_comes_out_upright(): void
    {
        // Orientation 6 = rotate 90° clockwise: the left (red) side ends up on top.
        $jpeg = (new AvatarProcessor)->normalize($this->sidewaysJpeg(6));

        [$rTop, , $bTop] = $this->pixel($jpeg, 128, 20);
        [$rBottom, , $bBottom] = $this->pixel($jpeg, 128, 236);
        $this->assertGreaterThan(180, $rTop);
        $this->assertLessThan(80, $bTop);
        $this->assertGreaterThan(180, $bBottom);
        $this->assertLessThan(80, $rBottom);
    }

    public function test_a_normal_photo_is_left_alone(): void
    {
        $jpeg = (new AvatarProcessor)->normalize($this->sidewaysJpeg(1));

        // Orientation 1: still red on the left, blue on the right.
        [$rLeft] = $this->pixel($jpeg, 20, 128);
        [, , $bRight] = $this->pixel($jpeg, 236, 128);
        $this->assertGreaterThan(180, $rLeft);
        $this->assertGreaterThan(180, $bRight);
    }

    public function test_message_photos_are_stood_up_too(): void
    {
        $jpeg = (new MessageImageProcessor)->normalize($this->sidewaysJpeg(6));
        $im = imagecreatefromstring($jpeg);

        $this->assertSame(20, imagesx($im));
        $this->assertSame(60, imagesy($im));
    }

    public function test_a_mirrored_front_camera_flag_is_undone(): void
    {
        // Orientation 2 = mirrored horizontally: red/blue swap sides.
        $jpeg = (new AvatarProcessor)->normalize($this->sidewaysJpeg(2));

        [$rLeft] = $this->pixel($jpeg, 20, 128);
        [, , $bLeft] = $this->pixel($jpeg, 20, 128);
        $this->assertGreaterThan(180, $bLeft);
        $this->assertLessThan(80, $rLeft);
    }
}
