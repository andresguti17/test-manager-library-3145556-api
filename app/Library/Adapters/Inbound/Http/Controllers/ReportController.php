<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\ReportRequest;
use App\Library\Adapters\Inbound\Http\Resources\ReportResource;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Services\MostBorrowedReportService;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;

final class ReportController
{
    public function mostBorrowed(ReportRequest $request, MostBorrowedReportService $report): JsonResponse
    {
        $data = $request->validated();
        $from = $this->parseDay((string) $data['from'], false);
        $to = $this->parseDay((string) $data['to'], true);
        $result = $report->report($from, $to);

        return response()->json(ReportResource::present($result['from'], $result['to'], $result['rows']));
    }

    /**
     * Both bounds are inclusive: a plain date covers the whole day in UTC;
     * a full datetime is honored as given.
     */
    private function parseDay(string $value, bool $endOfDay): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                return new DateTimeImmutable(
                    $value.($endOfDay ? ' 23:59:59' : ' 00:00:00'),
                    $utc,
                );
            }

            return (new DateTimeImmutable($value))->setTimezone($utc);
        } catch (\Throwable) {
            throw new ValidationException('El rango de fechas no es válido.');
        }
    }
}
