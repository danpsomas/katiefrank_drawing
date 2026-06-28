<?php

class drawing
{
    private DateTimeImmutable $today;
    private DateTimeImmutable $monthStart;
    private DateTimeImmutable $monthEnd;
    private array $thumbnailsByDate;

    public function __construct(?string $month = null, array $thumbnailsByDate = [])
    {
        $this->today = new DateTimeImmutable('today');
        $this->monthStart = $this->resolveMonthStart($month);
        $this->monthEnd = $this->monthStart->modify('last day of this month');
        $this->thumbnailsByDate = $thumbnailsByDate;
    }

    public function getMonthTitle(): string
    {
        return $this->monthStart->format('F Y');
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

        return [
            'date' => $date,
            'dateKey' => $dateKey,
            'day' => $date->format('j'),
            'weekday' => $date->format('D'),
            'fullDate' => $date->format('F j'),
            'thumbnails' => array_slice($this->thumbnailsByDate[$dateKey] ?? [], 0, 4),
            'isToday' => $dateKey === $this->today->format('Y-m-d'),
        ];
    }
}
