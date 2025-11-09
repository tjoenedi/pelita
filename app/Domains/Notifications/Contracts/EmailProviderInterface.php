<?php

namespace App\Domains\Notifications\Contracts;

interface EmailProviderInterface
{
    public function send(string $to, string $subject, string $body): bool;

    public function getLastError(): ?string;
}
