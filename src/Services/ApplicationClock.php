<?php

namespace Jidaikobo\Kontiki\Services;

use Carbon\Carbon;
use DateTimeZone;

class ApplicationClock
{
    private DateTimeZone $timezone;

    public function __construct(string $timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function now(): Carbon
    {
        return Carbon::now('UTC')->setTimezone($this->timezone);
    }

    public function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    public function parseLocal(string $value): Carbon
    {
        return Carbon::parse($value, $this->timezone);
    }

    public function localToUtc(string $value): Carbon
    {
        return $this->parseLocal($value)->setTimezone('UTC');
    }

    public function utcToLocal(string $value): Carbon
    {
        return Carbon::parse($value, 'UTC')->setTimezone($this->timezone);
    }
}
