<?php

class drawing
{
    private DateTimeImmutable $today;
    private DateTimeImmutable $monthStart;
    private DateTimeImmutable $monthEnd;
    private array $thumbnailsByDate;
    private array $descriptionsByDate;
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
        $this->descriptionsByDate = [];
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

    public function getCurrentMonthKey(): string
    {
        return $this->today->format('Y-m');
    }

    public function hasVisibleDrawings(): bool
    {
        foreach ($this->getCalendarCells() as $cell) {
            if ($cell !== null && count($cell['thumbnails']) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function getEarliestMonthKey(?mysqli $mysqli, bool $includeHidden = false): ?string
    {
        if (!$mysqli || $mysqli->connect_errno) {
            return null;
        }

        $hiddenClause = $includeHidden ? '' : 'AND hidden IS NULL';
        $sql = "
            SELECT DATE_FORMAT(MIN(display_date), '%Y-%m') AS earliest_month
            FROM drawing
            WHERE display_date IS NOT NULL
                AND filename IS NOT NULL
                AND filename != ''
                {$hiddenClause}
        ";

        $result = $mysqli->query($sql);

        if (!$result) {
            error_log('Drawing earliest month query failed: ' . $mysqli->error);
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free();

        if (empty($row['earliest_month'])) {
            return null;
        }

        return (string) $row['earliest_month'];
    }

    public function getSelectedMonthValue(): string
    {
        return $this->monthStart->format('m');
    }

    public function getSelectedYearValue(): string
    {
        return $this->monthStart->format('Y');
    }

    public function formatMonthOptionValue($monthValue): string
    {
        return sprintf('%02d', (int) $monthValue);
    }

    public function isSelectedMonthOption($monthValue): bool
    {
        return $this->formatMonthOptionValue($monthValue) === $this->getSelectedMonthValue();
    }

    public function isSelectedYearOption($yearValue): bool
    {
        return (string) $yearValue === $this->getSelectedYearValue();
    }

    public function getMonthOptions(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = DateTimeImmutable::createFromFormat('!m', sprintf('%02d', $month));
            $months[$date->format('m')] = $date->format('F');
        }

        return $months;
    }

    public function getYearOptions(): array
    {
        $currentYear = (int) $this->today->format('Y');
        $selectedYear = (int) $this->getSelectedYearValue();
        $earliestYear = $this->loadEarliestDrawingYear() ?? $currentYear;
        $startYear = min($earliestYear, $currentYear, $selectedYear);
        $endYear = max($currentYear, $selectedYear);

        $years = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[(string) $year] = (string) $year;
        }

        return $years;
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

    private function loadEarliestDrawingYear(): ?int
    {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            error_log('Drawing calendar database connection is unavailable.');
            return null;
        }

        $hiddenClause = $this->includeHidden ? '' : 'AND hidden IS NULL';
        $sql = "
            SELECT MIN(YEAR(display_date)) AS earliest_year
            FROM drawing
            WHERE display_date IS NOT NULL
                AND filename IS NOT NULL
                AND filename != ''
                {$hiddenClause}
        ";

        $result = $this->mysqli->query($sql);

        if (!$result) {
            error_log('Drawing earliest year query failed: ' . $this->mysqli->error);
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free();

        if (!isset($row['earliest_year'])) {
            return null;
        }

        return (int) $row['earliest_year'];
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
            'description' => $this->descriptionsByDate[$dateKey] ?? '',
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
            SELECT DID, orig_name, DATE(display_date) AS display_date, filename, hidden, description
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

        $statement->bind_result($did, $origName, $displayDate, $filename, $hidden, $description);
        $thumbnailsByDate = [];
        $descriptionsByDate = [];

        while ($statement->fetch()) {
            if (!isset($descriptionsByDate[$displayDate])) {
                $descriptionsByDate[$displayDate] = (string) ($description ?? '');
            }

            if ($this->includeHidden) {
                $thumbnailsByDate[$displayDate][] = [
                    'DID' => (int) $did,
                    'origName' => $origName,
                    'displayDate' => $displayDate,
                    'filename' => $filename,
                    'hidden' => $hidden,
                    'isHidden' => $hidden !== null && $hidden !== '',
                    'description' => $descriptionsByDate[$displayDate],
                    'thumbPath' => 'drawings/thumbs/' . $filename,
                    'sizedPath' => 'drawings/sized/1200_1200.' . $filename,
                ];
                continue;
            }

            $thumbnailsByDate[$displayDate][] = 'drawings/thumbs/' . $filename;
        }

        $statement->close();
        $this->descriptionsByDate = $descriptionsByDate;

        return $thumbnailsByDate;
    }
}
