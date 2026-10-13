<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Support;

use DateTimeInterface;
use DateTimeZone;

/** Contract date formatting: ISO 8601 in UTC with Z. */
final class IsoFormat
{
    public static function dateTime(DateTimeInterface $instant): string
    {
        return $instant
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }

    public static function date(DateTimeInterface $instant): string
    {
        return $instant
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d');
    }
}
