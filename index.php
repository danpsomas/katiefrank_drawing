<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/include/db_connect.php';
require_once __DIR__ . '/include/classes/class.drawing.php';
require_once __DIR__ . '/include/calendar_render.php';

$calendar = new drawing($_GET['month'] ?? null);
$monthTitle = $calendar->getMonthTitle();
$earliestMonth = drawing::getEarliestMonthKey($mysqli);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo calendar_h($monthTitle); ?> Drawings</title>
    <link rel="stylesheet" href="common/style.css">
    <script src="js/calendar-modal.js" defer></script>
    <script src="js/calendar-infinite.js" defer></script>
</head>
<body>
    <main class="calendar-page">
        <header class="calendar-header">
            <div class="calendar-header__inner">
                <form class="eyebrow calendar-date-picker" method="get" autocomplete="off" data-calendar-date-picker>
                    <input type="hidden" name="month" value="<?php echo calendar_h($calendar->getMonthKey()); ?>">
                    <label class="visually-hidden" for="calendar_month">Month</label>
                    <select id="calendar_month" class="calendar-date-picker__select" name="calendar_month" autocomplete="off">
                        <?php foreach ($calendar->getMonthOptions() as $monthValue => $monthLabel): ?>
                            <option value="<?php echo calendar_h($calendar->formatMonthOptionValue($monthValue)); ?>"<?php echo $calendar->isSelectedMonthOption($monthValue) ? ' selected' : ''; ?>>
                                <?php echo calendar_h($monthLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="visually-hidden" for="calendar_year">Year</label>
                    <select id="calendar_year" class="calendar-date-picker__select calendar-date-picker__select--year" name="calendar_year" autocomplete="off">
                        <?php foreach ($calendar->getYearOptions() as $yearValue => $yearLabel): ?>
                            <option value="<?php echo calendar_h((string) $yearValue); ?>"<?php echo $calendar->isSelectedYearOption($yearValue) ? ' selected' : ''; ?>>
                                <?php echo calendar_h($yearLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <h1>Drawing Calendar</h1>
            </div>
        </header>

        <div
            class="calendar-feed"
            data-calendar-feed
            data-initial-month="<?php echo calendar_h($calendar->getMonthKey()); ?>"
            data-current-month="<?php echo calendar_h($calendar->getCurrentMonthKey()); ?>"
            data-earliest-month="<?php echo calendar_h($earliestMonth ?? $calendar->getMonthKey()); ?>"
        >
            <p class="calendar-feed-status calendar-feed-status--top" data-calendar-feed-status-top aria-live="polite" hidden></p>
            <div class="calendar-scroll-sentinel calendar-scroll-sentinel--next" data-calendar-sentinel="next" aria-hidden="true"></div>
            <?php render_public_calendar_month($calendar); ?>
            <div class="calendar-scroll-sentinel calendar-scroll-sentinel--previous" data-calendar-sentinel="previous" aria-hidden="true"></div>
        </div>

        <p class="calendar-feed-status" data-calendar-feed-status aria-live="polite"></p>
    </main>

    <div class="image-modal" data-image-modal role="dialog" aria-modal="true" aria-label="Drawing preview" hidden>
        <button class="image-modal__close" type="button" data-image-modal-close aria-label="Close image preview">Close</button>
        <button class="image-modal__nav image-modal__nav--previous" type="button" data-image-modal-previous aria-label="Previous drawing">Previous</button>
        <img class="image-modal__image" src="" alt="" data-image-modal-image>
        <button class="image-modal__nav image-modal__nav--next" type="button" data-image-modal-next aria-label="Next drawing">Next</button>
    </div>
</body>
</html>
