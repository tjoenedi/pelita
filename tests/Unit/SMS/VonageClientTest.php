<?php

use App\Domains\SMS\Services\VonageClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\TestCase;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Client\Exception\Exception as VonageException;
use Vonage\SMS\Client as SMSClient;
use Vonage\SMS\Collection;
use Vonage\SMS\Message\SMS;
use Vonage\SMS\SentSMS;

uses(TestCase::class);

test('vonage client sends sms successfully', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.vonage.key', null)
        ->andReturn('test_key');
    Config::shouldReceive('get')
        ->with('services.vonage.secret', null)
        ->andReturn('test_secret');
    Config::shouldReceive('get')
        ->with('vonage.from', null)
        ->andReturn('test_from_number');

    // Create mocks
    $mockVonageClient = Mockery::mock(Client::class);
    $mockSMSClient = Mockery::mock(SMSClient::class);
    $mockCollection = Mockery::mock(Collection::class);
    $mockSentSMS = Mockery::mock(SentSMS::class);

    $mockSentSMS->shouldReceive('getMessageId')
        ->andReturn('test_message_id');
    $mockSentSMS->shouldReceive('getStatus')
        ->andReturn('0');

    $mockCollection->shouldReceive('current')
        ->andReturn($mockSentSMS);

    $mockSMSClient->shouldReceive('send')
        ->with(Mockery::type(SMS::class))
        ->once()
        ->andReturn($mockCollection);

    $mockVonageClient->shouldReceive('sms')
        ->andReturn($mockSMSClient);

    // Mock the Basic credentials class
    $this->mock(Basic::class, function ($mock) {
        $mock->shouldReceive('__construct')
            ->with('test_key', 'test_secret')
            ->andReturnSelf();

        return $mock;
    });

    // Mock the Vonage Client class constructor
    $this->mock(Client::class, function ($mock) use ($mockVonageClient) {
        $mock->shouldReceive('__construct')
            ->with(Mockery::type(Basic::class))
            ->andReturnSelf();
        $mock->shouldReceive('sms')
            ->andReturn($mockVonageClient->sms());

        return $mock;
    });

    $vonageClient = new VonageClient;

    // Use reflection to set the mocked client
    $reflection = new ReflectionClass($vonageClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($vonageClient, $mockVonageClient);

    $result = $vonageClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('message_id', 'test_message_id')
        ->toHaveKey('status', '0');
});

test('vonage client handles client exception', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.vonage.key', null)
        ->andReturn('test_key');
    Config::shouldReceive('get')
        ->with('services.vonage.secret', null)
        ->andReturn('test_secret');
    Config::shouldReceive('get')
        ->with('vonage.from', null)
        ->andReturn('test_from_number');

    $mockVonageClient = Mockery::mock(Client::class);
    $mockSMSClient = Mockery::mock(SMSClient::class);

    $clientException = new class extends Exception implements ClientExceptionInterface
    {
        protected $message = 'Client error';

        protected $code = 400;
    };

    $mockSMSClient->shouldReceive('send')
        ->with(Mockery::type(SMS::class))
        ->once()
        ->andThrow($clientException);

    $mockVonageClient->shouldReceive('sms')
        ->andReturn($mockSMSClient);

    Log::shouldReceive('error')
        ->with('Client error')
        ->once();

    $this->mock(Basic::class, function ($mock) {
        $mock->shouldReceive('__construct')
            ->with('test_key', 'test_secret')
            ->andReturnSelf();

        return $mock;
    });

    $this->mock(Client::class, function ($mock) use ($mockVonageClient) {
        $mock->shouldReceive('__construct')
            ->with(Mockery::type(Basic::class))
            ->andReturnSelf();
        $mock->shouldReceive('sms')
            ->andReturn($mockVonageClient->sms());

        return $mock;
    });

    $vonageClient = new VonageClient;

    $reflection = new ReflectionClass($vonageClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($vonageClient, $mockVonageClient);

    $result = $vonageClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('errorCode', 400)
        ->toHaveKey('errorMessage', 'Client error');
});

test('vonage client handles vonage exception', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.vonage.key', null)
        ->andReturn('test_key');
    Config::shouldReceive('get')
        ->with('services.vonage.secret', null)
        ->andReturn('test_secret');
    Config::shouldReceive('get')
        ->with('vonage.from', null)
        ->andReturn('test_from_number');

    $mockVonageClient = Mockery::mock(Client::class);
    $mockSMSClient = Mockery::mock(SMSClient::class);

    $vonageException = new VonageException('Vonage API error', 500);

    $mockSMSClient->shouldReceive('send')
        ->with(Mockery::type(SMS::class))
        ->once()
        ->andThrow($vonageException);

    $mockVonageClient->shouldReceive('sms')
        ->andReturn($mockSMSClient);

    Log::shouldReceive('error')
        ->with('Vonage API error')
        ->once();

    $this->mock(Basic::class, function ($mock) {
        $mock->shouldReceive('__construct')
            ->with('test_key', 'test_secret')
            ->andReturnSelf();

        return $mock;
    });

    $this->mock(Client::class, function ($mock) use ($mockVonageClient) {
        $mock->shouldReceive('__construct')
            ->with(Mockery::type(Basic::class))
            ->andReturnSelf();
        $mock->shouldReceive('sms')
            ->andReturn($mockVonageClient->sms());

        return $mock;
    });

    $vonageClient = new VonageClient;

    $reflection = new ReflectionClass($vonageClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($vonageClient, $mockVonageClient);

    $result = $vonageClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('errorCode', 500)
        ->toHaveKey('errorMessage', 'Vonage API error');
});
