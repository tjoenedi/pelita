<?php

use App\Domains\SMS\Services\TwilioClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Api\V2010\Account\MessageList;
use Twilio\Rest\Client;

uses(TestCase::class);

test('twilio client sends sms successfully', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.twilio.sid', null)
        ->andReturn('test_sid');
    Config::shouldReceive('get')
        ->with('services.twilio.token', null)
        ->andReturn('test_token');
    Config::shouldReceive('get')
        ->with('twilio.from', null)
        ->andReturn('test_from_number');

    // Create mocks
    $mockTwilioClient = Mockery::mock(Client::class);
    $mockMessages = Mockery::mock(MessageList::class);
    $mockMessage = Mockery::mock(MessageInstance::class);

    $mockMessage->sid = 'test_message_sid';
    $mockMessage->status = 'queued';

    $mockTwilioClient->messages = $mockMessages;

    $mockMessages
        ->shouldReceive('create')
        ->with('+441234567890', [
            'from' => 'test_from_number',
            'body' => 'Test message',
        ])
        ->once()
        ->andReturn($mockMessage);

    // Mock the Twilio Client class constructor
    $this->mock(Client::class, function ($mock) use ($mockTwilioClient) {
        $mock->shouldReceive('__construct')
            ->with('test_sid', 'test_token')
            ->andReturnSelf();
        $mock->messages = $mockTwilioClient->messages;

        return $mock;
    });

    $twilioClient = new TwilioClient;

    // Use reflection to set the mocked client
    $reflection = new ReflectionClass($twilioClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($twilioClient, $mockTwilioClient);

    $result = $twilioClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('message_id', 'test_message_sid')
        ->toHaveKey('status', 'queued');
});

test('twilio client handles twilio exception', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.twilio.sid', null)
        ->andReturn('test_sid');
    Config::shouldReceive('get')
        ->with('services.twilio.token', null)
        ->andReturn('test_token');
    Config::shouldReceive('get')
        ->with('twilio.from', null)
        ->andReturn('test_from_number');

    $mockTwilioClient = Mockery::mock(Client::class);
    $mockMessages = Mockery::mock(MessageList::class);

    $mockTwilioClient->messages = $mockMessages;

    $twilioException = new TwilioException('Twilio API error', 400);

    $mockMessages
        ->shouldReceive('create')
        ->with('+441234567890', [
            'from' => 'test_from_number',
            'body' => 'Test message',
        ])
        ->once()
        ->andThrow($twilioException);

    Log::shouldReceive('error')
        ->with('Twilio API error')
        ->once();

    $this->mock(Client::class, function ($mock) use ($mockTwilioClient) {
        $mock->shouldReceive('__construct')
            ->with('test_sid', 'test_token')
            ->andReturnSelf();
        $mock->messages = $mockTwilioClient->messages;

        return $mock;
    });

    $twilioClient = new TwilioClient;

    $reflection = new ReflectionClass($twilioClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($twilioClient, $mockTwilioClient);

    $result = $twilioClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('errorCode', 400)
        ->toHaveKey('errorMessage', 'Twilio API error');
});

test('twilio client handles generic exception', function () {
    // Mock all config calls
    Config::shouldReceive('get')
        ->with('services.twilio.sid', null)
        ->andReturn('test_sid');
    Config::shouldReceive('get')
        ->with('services.twilio.token', null)
        ->andReturn('test_token');
    Config::shouldReceive('get')
        ->with('twilio.from', null)
        ->andReturn('test_from_number');

    $mockTwilioClient = Mockery::mock(Client::class);
    $mockMessages = Mockery::mock(MessageList::class);

    $mockTwilioClient->messages = $mockMessages;

    $genericException = new Exception('Generic error', 500);

    $mockMessages
        ->shouldReceive('create')
        ->with('+441234567890', [
            'from' => 'test_from_number',
            'body' => 'Test message',
        ])
        ->once()
        ->andThrow($genericException);

    Log::shouldReceive('error')
        ->with('Generic error')
        ->once();

    $this->mock(Client::class, function ($mock) use ($mockTwilioClient) {
        $mock->shouldReceive('__construct')
            ->with('test_sid', 'test_token')
            ->andReturnSelf();
        $mock->messages = $mockTwilioClient->messages;

        return $mock;
    });

    $twilioClient = new TwilioClient;

    $reflection = new ReflectionClass($twilioClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($twilioClient, $mockTwilioClient);

    $result = $twilioClient->sendSMS('+441234567890', 'Test message');

    expect($result)
        ->toHaveKey('errorCode', 500)
        ->toHaveKey('errorMessage', 'Generic error');
});
