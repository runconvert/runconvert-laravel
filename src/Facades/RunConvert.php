<?php

declare(strict_types=1);

namespace RunConvert\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RunConvert\ConvertResult convert(string|resource $file, string $to, array $opts = [])
 * @method static array me()
 * @property-read \RunConvert\Jobs $jobs
 *
 * Jobs, files and operations: RunConvert::getFacadeRoot()->jobs, or inject \RunConvert\RunConvert.
 *
 * @see \RunConvert\RunConvert
 */
final class RunConvert extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RunConvert\RunConvert::class;
    }
}
