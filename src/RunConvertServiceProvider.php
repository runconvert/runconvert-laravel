<?php

declare(strict_types=1);

namespace RunConvert\Laravel;

use Illuminate\Support\ServiceProvider;
use RunConvert\RunConvert;

final class RunConvertServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/runconvert.php', 'runconvert');

        $this->app->singleton(RunConvert::class, fn ($app) => new RunConvert(
            apiKey: (string) ($app['config']['runconvert.api_key'] ?? ''),
            baseUrl: (string) ($app['config']['runconvert.base_url'] ?? RunConvert::DEFAULT_BASE_URL),
            timeout: (int) ($app['config']['runconvert.timeout'] ?? 60),
        ));
        $this->app->alias(RunConvert::class, 'runconvert');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/runconvert.php' => config_path('runconvert.php')], 'runconvert-config');
        }
        if ($this->app['config']['runconvert.webhook.path']) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');
        }
    }
}
