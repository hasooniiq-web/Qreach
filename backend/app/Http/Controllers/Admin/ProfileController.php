<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed-in admin's own profile: display name, photo, and password.
 * Every action targets auth('admin')->user() — no route takes an admin id,
 * so one admin can never read or change another's profile from here.
 *
 * Photo upload is hardened (spec section 35): the file must decode as a real
 * image, is re-encoded to a small square JPEG (dropping metadata and any
 * embedded payload), and is stored on the private disk under a server-chosen
 * name — never publicly addressable, never named by the client.
 */
class ProfileController extends Controller
{
    private const AVATAR_SIZE = 256;

    public function edit(): View
    {
        $admin = auth('admin')->user()->load('roles');

        return view('admin.profile.edit', compact('admin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ], [], ['name' => 'الاسم']);

        $admin = auth('admin')->user();
        $admin->update(['name' => trim($data['name'])]);

        AuditLogger::log('admin.profile_updated', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        return back()->with('status', 'تم حفظ الاسم.');
    }

    public function avatar(): Response
    {
        $path = auth('admin')->user()->avatar_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function storeAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
        ], [], ['avatar' => 'الصورة']);

        $admin = auth('admin')->user();
        $jpeg = $this->normalize($request->file('avatar'));

        if ($jpeg === null) {
            return back()->withErrors(['avatar' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']);
        }

        $path = "admin-avatars/{$admin->uuid}.jpg";
        Storage::disk('local')->put($path, $jpeg);
        $admin->update(['avatar_path' => $path]);

        AuditLogger::log('admin.avatar_updated', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        return back()->with('status', 'تم تحديث الصورة.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $admin = auth('admin')->user();

        if ($admin->avatar_path) {
            Storage::disk('local')->delete($admin->avatar_path);
            $admin->update(['avatar_path' => null]);
            AuditLogger::log('admin.avatar_removed', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);
        }

        return back()->with('status', 'تمت إزالة الصورة.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', 'min:12', 'different:current_password'],
        ], [], ['password' => 'كلمة المرور الجديدة', 'current_password' => 'كلمة المرور الحالية']);

        $admin = auth('admin')->user();
        $admin->forceFill([
            'password' => Hash::make($request->string('password')),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        AuditLogger::log('admin.password_changed', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        return back()->with('status', 'تم تغيير كلمة المرور.');
    }

    /** Decode -> center-crop to a square -> resize -> re-encode as JPEG. */
    private function normalize(UploadedFile $file): ?string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        // Transparent PNG/WebP areas become white rather than black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::AVATAR_SIZE, self::AVATAR_SIZE, $side, $side
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $bytes = ob_get_clean();

        return $bytes !== false && $bytes !== '' ? $bytes : null;
    }
}
