<?php

class drawing
{
    private DateTimeImmutable $today;
    private DateTimeImmutable $monthStart;
    private DateTimeImmutable $monthEnd;
    private array $thumbnailsByDate;
    private ?mysqli $mysqli;
    private bool $includeHidden;

    public function __construct(?string $month = null, ?array $thumbnailsByDate = null, bool $includeHidden = false)
    {
        global $mysqli;

        $this->mysqli = $mysqli instanceof mysqli ? $mysqli : null;
        $this->includeHidden = $includeHidden;
        $this->today = new DateTimeImmutable('today');
        $this->monthStart = $this->resolveMonthStart($month);
        $this->monthEnd = $this->monthStart->modify('last day of this month');
        $this->thumbnailsByDate = $thumbnailsByDate ?? $this->loadThumbnailsByDate();
    }

    public function getMonthTitle(): string
    {
        return $this->monthStart->format('F Y');
    }

    public function getMonthKey(): string
    {
        return $this->monthStart->format('Y-m');
    }

    public function getCalendarLabel(): string
    {
        return $this->getMonthTitle() . ' calendar';
    }

    public function getWeekdays(): array
    {
        return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    }

    public function getPreviousMonthKey(): string
    {
        return $this->monthStart->modify('-1 month')->format('Y-m');
    }

    public function getNextMonthKey(): string
    {
        return $this->monthStart->modify('+1 month')->format('Y-m');
    }

    public function getCalendarCells(): array
    {
        $leadingEmptyDays = (int) $this->monthStart->format('N') - 1;
        $totalDays = (int) $this->monthEnd->format('j');
        $calendarCells = [];

        for ($i = 0; $i < $leadingEmptyDays; $i++) {
            $calendarCells[] = null;
        }

        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $this->monthStart->setDate(
                (int) $this->monthStart->format('Y'),
                (int) $this->monthStart->format('m'),
                $day
            );

            $calendarCells[] = $this->buildDateCell($date);
        }

        $trailingEmptyDays = (7 - (count($calendarCells) % 7)) % 7;

        for ($i = 0; $i < $trailingEmptyDays; $i++) {
            $calendarCells[] = null;
        }

        return $calendarCells;
    }

    private function resolveMonthStart(?string $month): DateTimeImmutable
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return new DateTimeImmutable($month . '-01');
        }

        return $this->today->modify('first day of this month');
    }

    private function buildDateCell(DateTimeImmutable $date): array
    {
        $dateKey = $date->format('Y-m-d');
        $thumbnails = $this->thumbnailsByDate[$dateKey] ?? [];

        if (!$this->includeHidden) {
            $thumbnails = array_slice($thumbnails, 0, 4);
        }

        return [
            'date' => $date,
            'dateKey' => $dateKey,
            'day' => $date->format('j'),
            'weekday' => $date->format('D'),
            'fullDate' => $date->format('F j'),
            'thumbnails' => $thumbnails,
            'isToday' => $dateKey === $this->today->format('Y-m-d'),
        ];
    }

    private function loadThumbnailsByDate(): array
    {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            error_log('Drawing calendar database connection is unavailable.');
            return [];
        }

        $startDate = $this->monthStart->format('Y-m-d');
        $nextMonthDate = $this->monthStart->modify('+1 month')->format('Y-m-d');
        $hiddenClause = $this->includeHidden ? '' : 'AND hidden IS NULL';
        $sql = "
            SELECT DID, orig_name, DATE(display_date) AS display_date, filename, hidden
            FROM drawing
            WHERE display_date >= ?
                AND display_date < ?
                AND filename IS NOT NULL
                AND filename != ''
                {$hiddenClause}
            ORDER BY display_date ASC, DID ASC
        ";

        $statement = $this->mysqli->prepare($sql);
        if (!$statement) {
            error_log('Drawing calendar query prepare failed: ' . $this->mysqli->error);
            return [];
        }

        $statement->bind_param('ss', $startDate, $nextMonthDate);
        if (!$statement->execute()) {
            error_log('Drawing calendar query failed: ' . $statement->error);
            $statement->close();
            return [];
        }

        $statement->bind_result($did, $origName, $displayDate, $filename, $hidden);
        $thumbnailsByDate = [];

        while ($statement->fetch()) {
            if ($this->includeHidden) {
                $thumbnailsByDate[$displayDate][] = [
                    'DID' => (int) $did,
                    'origName' => $origName,
                    'displayDate' => $displayDate,
                    'filename' => $filename,
                    'hidden' => $hidden,
                    'isHidden' => $hidden !== null && $hidden !== '',
                    'thumbPath' => 'drawings/thumbs/' . $filename,
                    'sizedPath' => 'drawings/sized/1200_1200.' . $filename,
                ];
                continue;
            }

            $thumbnailsByDate[$displayDate][] = 'drawings/thumbs/' . $filename;
        }

        $statement->close();

        return $thumbnailsByDate;
    }
}
