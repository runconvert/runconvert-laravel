# Changelog

## 0.1.0 (2026-11-15)

First version: service provider with a configured `RunConvert\\RunConvert` singleton, `RunConvert` facade,
publishable config, and a signed webhook route that fires `WebhookReceived`, `JobFinished`, `JobFailed`, `TaskFinished`
and friends. Laravel 12 and 13, PHP 8.2+.
