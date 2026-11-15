<?php

declare(strict_types=1);

namespace RunConvert\Laravel\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RunConvert\Laravel\Events;
use RunConvert\Webhooks;

/**
 * Receives RunConvert webhooks: checks X-RunConvert-Signature against the raw body with the
 * configured secret, then fires WebhookReceived and the event's own class.
 */
final class WebhookController
{
    private const EVENTS = [
        'job.created' => Events\JobCreated::class,
        'job.processing' => Events\JobProcessing::class,
        'job.finished' => Events\JobFinished::class,
        'job.error' => Events\JobFailed::class,
        'task.finished' => Events\TaskFinished::class,
        'task.error' => Events\TaskFailed::class,
    ];

    public function __invoke(Request $request): Response
    {
        $secret = (string) config('runconvert.webhook.secret', '');
        if ($secret === '') {
            return new Response('RunConvert webhook secret is not set (RUNCONVERT_WEBHOOK_SECRET)', 500);
        }
        try {
            $payload = Webhooks::constructEvent($request->getContent(), $request->header('X-RunConvert-Signature'), $secret);
        } catch (\UnexpectedValueException|\JsonException) {
            return new Response('Invalid signature', 400);
        }
        event(new Events\WebhookReceived($payload));
        if ($class = self::EVENTS[$payload['event'] ?? ''] ?? null) {
            event(new $class($payload));
        }
        return new Response('', 200);
    }
}
