<?php

namespace App\Domains\Notifications\Providers;

use App\Domains\Notifications\Contracts\EmailProviderInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LaravelMailProvider implements EmailProviderInterface
{
    protected ?string $lastError = null;

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            Mail::html($body, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject);
            });

            $this->lastError = null;

            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('Email sending failed: '.$e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
            ]);

            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
