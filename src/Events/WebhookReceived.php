<?php

declare(strict_types=1);

namespace RunConvert\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Every verified webhook delivery. Specific events (JobFinished, JobFailed, …) are also
 * fired. $payload is the whole event: ['event' => 'job.finished', 'job' => [...]].
 */
class WebhookReceived
{
    use Dispatchable;

    public function __construct(public readonly array $payload)
    {
    }

    public function type(): string
    {
        return (string) ($this->payload['event'] ?? '');
    }

    /** The job (job.* events) or null. */
    public function job(): ?array
    {
        return $this->payload['job'] ?? null;
    }

    /** The task (task.* events) or null. */
    public function task(): ?array
    {
        return $this->payload['task'] ?? null;
    }
}
