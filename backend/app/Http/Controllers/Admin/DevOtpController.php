<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * TEMPORARY: lists the latest OTP per phone so a developer can sign in to the
 * app while there is no SMS provider. Off unless DEV_OTP_INBOX=true, and never
 * available in production.
 */
class DevOtpController extends Controller
{
    public static function enabled(): bool
    {
        return (bool) config('services.dev_otp_inbox') && ! app()->environment('production');
    }

    public function index(): View
    {
        abort_unless(self::enabled(), 404);

        return view('admin.dev-otp', [
            'codes' => DB::table('dev_otp_inbox')->orderByDesc('sent_at')->limit(100)->get(),
        ]);
    }
}
