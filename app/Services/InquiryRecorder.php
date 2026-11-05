<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\NewInquiryNotification;
use Illuminate\Support\Facades\Mail;
use App\Mail\InquiryReceived;

class InquiryRecorder
{
    public function __construct(private SettingsRepository $settings, private ActivityLogger $activity) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, string $locale, ?string $ip, ?string $userAgent): Inquiry
    {
        $inquiry = Inquiry::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company' => $data['company'] ?? null,
            'project_type' => $data['project_type'],
            'message' => $data['message'],
            'consent' => true,
            'status' => 'new',
            'locale' => $locale,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
        ]);

        $this->activity->log('inquiry.created', 'Received a contact inquiry', $inquiry, [
            'inquiry_id' => $inquiry->id,
            'project_type' => $inquiry->project_type,
        ]);

        User::query()->permission('inquiries.view')->get()->each(
            fn (User $user) => $user->notify(new NewInquiryNotification($inquiry))
        );

        $notify = $this->settings->get('contact', [])['notify_email'] ?? null;
        $mailer = (string) config('mail.default');

        if (is_string($notify) && filter_var($notify, FILTER_VALIDATE_EMAIL) && in_array($mailer, config('racode.mailers_that_deliver'), true)) {
            Mail::to($notify)->send(new InquiryReceived($inquiry));
        }

        return $inquiry;
    }
}
