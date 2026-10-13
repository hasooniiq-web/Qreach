<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteMessage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * The public "Contact us" form. The message is always saved (the dashboard lists it);
 * an email to the support address is attempted on top, and a failed email never
 * loses the message or fails the visitor's request.
 */
class SiteMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $back = url('/').'?lang='.app()->getLocale().'#contact';

        // A bot filling the hidden field gets the same "sent" answer and nothing is stored.
        if ($request->filled('company_website')) {
            return redirect($back)->with('contact_sent', true);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:190', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()٠-٩۰-۹]{6,40}$/u', 'required_without:email'],
            'message' => ['required', 'string', 'min:5', 'max:1500'],
        ], [
            'name.required' => trans('site.contact.err_name'),
            'name.min' => trans('site.contact.err_name'),
            'email.email' => trans('site.contact.err_email'),
            'email.required_without' => trans('site.contact.err_reach'),
            'phone.required_without' => trans('site.contact.err_reach'),
            'phone.regex' => trans('site.contact.err_phone'),
            'message.required' => trans('site.contact.err_message'),
            'message.min' => trans('site.contact.err_message'),
            'message.max' => trans('site.contact.err_message_long'),
        ]);

        if ($validator->fails()) {
            return redirect($back)->withErrors($validator, 'contact')->withInput($request->except('company_website'));
        }

        $data = $validator->validated();

        $message = SiteMessage::create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'] ?? null,
            'phone' => isset($data['phone']) ? strip_tags($data['phone']) : null,
            'message' => strip_tags($data['message']),
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
        ]);

        AuditLogger::log('site.message_received', 'system', null, 'SiteMessage', $message->id, 'success', [], $request);

        $this->email($message);

        return redirect($back)->with('contact_sent', true);
    }

    private function email(SiteMessage $message): void
    {
        $to = config('landing.email');
        if (! $to) {
            return;
        }

        $body = "رسالة جديدة من نموذج «تواصل معنا» في الموقع\n\n"
            ."الاسم: {$message->name}\n"
            .'البريد: '.($message->email ?: '—')."\n"
            .'الهاتف: '.($message->phone ?: '—')."\n"
            ."اللغة: {$message->locale}\n\n"
            .$message->message;

        try {
            Mail::raw($body, function ($mail) use ($to, $message) {
                $mail->to($to)->subject('Qreach — رسالة تواصل جديدة من '.$message->name);
                if ($message->email) {
                    $mail->replyTo($message->email, $message->name);
                }
            });

            // With the "log" mailer nothing leaves the server; that is not "emailed".
            if (config('mail.default') !== 'log') {
                $message->update(['emailed_at' => now()]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
