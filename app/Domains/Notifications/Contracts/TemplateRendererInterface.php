<?php

namespace App\Domains\Notifications\Contracts;

use App\Domains\Notifications\DTOs\ReminderContext;

interface TemplateRendererInterface
{
    public function render(string $template, ReminderContext $context, ?string $unsubscribeUrl = null): string;
}
