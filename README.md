# RunConvert SDK for Laravel

> **Not published yet.** This SDK launches on Packagist soon, so the install command below won't work until then. Updates: https://www.runconvert.com/sdks

Convert files between 240+ formats with the [RunConvert API](https://www.runconvert.com/docs) from Laravel:
a configured client, a facade, and a signed webhook route that fires Laravel events.
Built on [runconvert-php](https://github.com/runconvert/runconvert-php). Laravel 12 and 13; PHP 8.2+.

```bash
composer require runconvert/runconvert-laravel
```

```dotenv
RUNCONVERT_API_KEY=rc_live_...
RUNCONVERT_WEBHOOK_SECRET=whsec_...   # only if you use webhooks
```

```php
use RunConvert\Laravel\Facades\RunConvert;

$result = RunConvert::convert(storage_path('app/report.docx'), 'pdf', ['out_dir' => storage_path('app/converted')]);
$result->saved; // ['.../converted/report.pdf']
```

Or inject the client anywhere:

```php
use RunConvert\RunConvert;

public function store(Request $request, RunConvert $rc)
{
    $job = $rc->jobs->create([
        'import' => ['operation' => 'import/url', 'source_url' => $request->input('url')],
        'convert' => ['operation' => 'convert', 'input' => 'import', 'input_format' => 'mp4', 'output_format' => 'mp3'],
        'export' => ['operation' => 'export/url', 'input' => 'convert'],
    ], tag: 'user-' . $request->user()->id);

    return ['job' => $job['id']]; // finish it in a webhook listener instead of waiting here
}
```

Everything from the PHP SDK is available: `jobs`, `files`, `operations`, `me()`, `convert()`.

## Configuration

```bash
php artisan vendor:publish --tag=runconvert-config   # config/runconvert.php
```

| Key | Env | Default |
|---|---|---|
| `api_key` | `RUNCONVERT_API_KEY` | – |
| `base_url` | `RUNCONVERT_BASE_URL` | `https://api.runconvert.com/v3` |
| `timeout` | `RUNCONVERT_TIMEOUT` | `60` seconds |
| `webhook.path` | `RUNCONVERT_WEBHOOK_PATH` | `runconvert/webhook` (`null` turns the route off) |
| `webhook.secret` | `RUNCONVERT_WEBHOOK_SECRET` | – |

## Webhooks

Add an endpoint in **Dashboard → Webhooks** pointing at `https://your-app.com/runconvert/webhook` and put its
secret in `RUNCONVERT_WEBHOOK_SECRET`. The route checks `X-RunConvert-Signature` on every delivery (no CSRF token
needed), then fires `WebhookReceived` plus the event's own class:

| RunConvert event | Laravel event |
|---|---|
| `job.created` / `job.processing` | `JobCreated` / `JobProcessing` |
| `job.finished` | `JobFinished` |
| `job.error` | `JobFailed` |
| `task.finished` / `task.error` | `TaskFinished` / `TaskFailed` |

```php
use RunConvert\Laravel\Events\JobFinished;
use RunConvert\Jobs;

Event::listen(function (JobFinished $event) {
    foreach (Jobs::exportedFiles(app(\RunConvert\RunConvert::class)->jobs->get($event->job()['id'])) as $file) {
        // $file['url'] is a temporary download link
    }
});
```

All events are in `RunConvert\Laravel\Events` and expose `type()`, `job()`, `task()` and the raw `payload`.

## Development

```bash
composer install
vendor/bin/phpunit
```

## License

MIT
