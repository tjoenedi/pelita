<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Contracts\TemplateRendererInterface;
use App\Domains\Notifications\DTOs\ReminderContext;

class TemplateRenderer implements TemplateRendererInterface
{
    public function render(string $template, ReminderContext $context, ?string $unsubscribeUrl = null): string
    {
        $parameters = $context->toArray();

        if ($unsubscribeUrl) {
            $parameters['unsubscribe_link'] = $unsubscribeUrl;
        }

        $rendered = $template;

        foreach ($parameters as $key => $value) {
            $rendered = str_replace('{'.$key.'}', $value ?? '', $rendered);
        }

        return $rendered;
    }
}
