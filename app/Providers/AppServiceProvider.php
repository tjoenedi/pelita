<?php

namespace App\Providers;

use App\Domains\Notifications\Contracts\EmailProviderInterface;
use App\Domains\Notifications\Contracts\TemplateRendererInterface;
use App\Domains\Notifications\Providers\LaravelMailProvider;
use App\Domains\Notifications\Services\TemplateRenderer;
use App\Domains\SMS\Contracts\SMSClientInterface;
use App\Domains\SMS\Services\SMSService;
use App\Domains\SMS\Services\TwilioClient;
use App\Models\Event;
use App\Observers\EventObserver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // SMS Service bindings
        $this->app->bind(SMSClientInterface::class, TwilioClient::class);
        $this->app->bind(SMSService::class, function (Application $app) {
            return new SMSService($app->make(SMSClientInterface::class));
        });

        // Notification Service bindings
        $this->app->bind(EmailProviderInterface::class, LaravelMailProvider::class);
        $this->app->singleton(TemplateRendererInterface::class, TemplateRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Event Observer for auto-scheduling reminders
        Event::observe(EventObserver::class);
    }
}
