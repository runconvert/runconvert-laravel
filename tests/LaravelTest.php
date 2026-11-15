<?php

declare(strict_types=1);

namespace RunConvert\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use RunConvert\Laravel\Events\JobFailed;
use RunConvert\Laravel\Events\JobFinished;
use RunConvert\Laravel\Events\TaskFinished;
use RunConvert\Laravel\Events\WebhookReceived;
use RunConvert\Laravel\Facades\RunConvert as RunConvertFacade;
use RunConvert\Laravel\RunConvertServiceProvider;
use RunConvert\RunConvert;

final class LaravelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [RunConvertServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['RunConvert' => RunConvertFacade::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('runconvert.api_key', 'rc_live_test');
        $app['config']->set('runconvert.webhook.secret', 'whsec_x');
    }

    private function signed(array $payload): array
    {
        $body = json_encode($payload);
        return [$body, ['X-RunConvert-Signature' => 'sha256=' . hash_hmac('sha256', $body, 'whsec_x'), 'Content-Type' => 'application/json']];
    }

    public function test_the_client_is_a_singleton_built_from_config(): void
    {
        $rc = $this->app->make(RunConvert::class);
        $this->assertInstanceOf(RunConvert::class, $rc);
        $this->assertSame($rc, $this->app->make('runconvert'));
        $this->assertSame($rc, RunConvertFacade::getFacadeRoot());
    }

    public function test_the_config_merges_defaults(): void
    {
        $this->assertSame('https://api.runconvert.com/v3', config('runconvert.base_url'));
        $this->assertSame('runconvert/webhook', config('runconvert.webhook.path'));
    }

    public function test_a_signed_job_finished_webhook_fires_events(): void
    {
        Event::fake();
        [$body, $headers] = $this->signed(['event' => 'job.finished', 'job' => ['id' => 'job_1', 'status' => 'finished']]);
        $this->call('POST', '/runconvert/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertOk();
        Event::assertDispatched(WebhookReceived::class, fn ($e) => $e->type() === 'job.finished' && $e->job()['id'] === 'job_1');
        Event::assertDispatched(JobFinished::class);
        Event::assertNotDispatched(JobFailed::class);
    }

    public function test_task_and_error_events_map_to_their_classes(): void
    {
        Event::fake();
        foreach ([['job.error', JobFailed::class], ['task.finished', TaskFinished::class]] as [$type, $class]) {
            [$body, $headers] = $this->signed(['event' => $type, 'job' => ['id' => 'job_2'], 'task' => ['name' => 'export-1']]);
            $this->call('POST', '/runconvert/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertOk();
            Event::assertDispatched($class);
        }
    }

    public function test_a_bad_signature_is_rejected(): void
    {
        Event::fake();
        $body = json_encode(['event' => 'job.finished', 'job' => ['id' => 'job_1']]);
        $headers = ['X-RunConvert-Signature' => 'sha256=' . str_repeat('0', 64), 'Content-Type' => 'application/json'];
        $this->call('POST', '/runconvert/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertStatus(400);
        $this->call('POST', '/runconvert/webhook', [], [], [], $this->transformHeadersToServerVars(['Content-Type' => 'application/json']), $body)->assertStatus(400);
        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_a_missing_secret_is_a_clear_500(): void
    {
        config(['runconvert.webhook.secret' => null]);
        [$body, $headers] = $this->signed(['event' => 'job.finished']);
        $this->call('POST', '/runconvert/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(500)->assertSee('RUNCONVERT_WEBHOOK_SECRET');
    }
}
