<?php

use App\Domains\Notifications\Providers\LaravelMailProvider;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->provider = new LaravelMailProvider;
    Mail::fake();
});

it('sends email successfully', function () {
    $result = $this->provider->send(
        'test@example.com',
        'Test Subject',
        '<p>Test Body</p>'
    );

    expect($result)->toBeTrue();

    Mail::assertSent(function ($mail) {
        return true;
    });
});

it('clears last error on successful send', function () {
    $this->provider->send('test@example.com', 'Subject', 'Body');

    expect($this->provider->getLastError())->toBeNull();
});

it('returns false and captures error message on failure', function () {
    Mail::shouldReceive('html')
        ->once()
        ->andThrow(new Exception('SMTP connection failed'));

    $result = $this->provider->send(
        'test@example.com',
        'Test Subject',
        '<p>Test Body</p>'
    );

    expect($result)->toBeFalse()
        ->and($this->provider->getLastError())->toBe('SMTP connection failed');
});

it('sends email with correct parameters', function () {
    $to = 'recipient@example.com';
    $subject = 'Important Event Reminder';
    $body = '<h1>Event Reminder</h1><p>Please attend.</p>';

    $this->provider->send($to, $subject, $body);

    Mail::assertSent(function ($mail) use ($to, $subject) {
        return $mail->hasTo($to);
    });
});

it('handles html content correctly', function () {
    $htmlContent = '<html><body><h1>Title</h1><p>Content with <strong>bold</strong> text.</p></body></html>';

    $result = $this->provider->send('test@example.com', 'Subject', $htmlContent);

    expect($result)->toBeTrue();
});

it('returns null error when no error has occurred', function () {
    expect($this->provider->getLastError())->toBeNull();
});

it('preserves last error message across multiple calls', function () {
    Mail::shouldReceive('html')
        ->once()
        ->andThrow(new Exception('First error'));

    $this->provider->send('test@example.com', 'Subject', 'Body');

    expect($this->provider->getLastError())->toBe('First error');

    Mail::shouldReceive('html')
        ->once()
        ->andReturn(true);

    $this->provider->send('test@example.com', 'Subject', 'Body');

    expect($this->provider->getLastError())->toBeNull();
});
