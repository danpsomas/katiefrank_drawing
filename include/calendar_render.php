<?php

function calendar_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_public_calendar_month(drawing $calendar): void
{
    $monthKey = $calendar->getMonthKey();
    $hasDrawings = false;

    foreach ($calendar->getCalendarCells() as $cell) {
        if ($cell !== null && count($cell['thumbnails']) > 0) {
            $hasDrawings = true;
            break;
        }
    }

    if (!$hasDrawings) {
        return;
    }
    ?>
    <section
        class="calendar-month"
        data-calendar-month="<?php echo calendar_h($monthKey); ?>"
        aria-label="<?php echo calendar_h($calendar->getCalendarLabel()); ?>"
    >
        <h2 class="calendar-month__title"><?php echo calendar_h($calendar->getMonthTitle()); ?></h2>
        <div class="month-grid">
            <?php foreach ($calendar->getWeekdays() as $weekday): ?>
                <div class="weekday" aria-hidden="true"><?php echo $weekday; ?></div>
            <?php endforeach; ?>

            <?php foreach ($calendar->getCalendarCells() as $cell): ?>
                <?php if ($cell === null): ?>
                    <div class="date-card date-card--empty" aria-hidden="true"></div>
                    <?php continue; ?>
                <?php endif; ?>

                <?php $thumbnailCount = count($cell['thumbnails']); ?>
                <?php if ($thumbnailCount === 0): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <article class="date-card<?php echo $cell['isToday'] ? ' date-card--today' : ''; ?><?php echo $thumbnailCount > 0 ? ' date-card--has-thumbnails date-card--thumbnail-count-' . $thumbnailCount : ''; ?>">
                    <div class="date-card__header">
                        <span class="date-card__weekday"><?php echo $cell['weekday']; ?></span>
                        <time datetime="<?php echo calendar_h($cell['dateKey']); ?>"><?php echo $cell['day']; ?></time>
                    </div>

                    <?php if ($cell['description'] !== ''): ?>
                        <p class="date-card__description"><?php echo calendar_h($cell['description']); ?></p>
                    <?php endif; ?>

                    <?php if ($cell['thumbnails']): ?>
                        <div class="thumbnail-grid thumbnail-grid--count-<?php echo $thumbnailCount; ?>" aria-label="Drawings for <?php echo calendar_h($cell['fullDate']); ?>">
                            <?php foreach ($cell['thumbnails'] as $index => $thumb): ?>
                                <?php $imageAlt = 'Drawing ' . ($index + 1) . ' for ' . $cell['fullDate']; ?>
                                <a
                                    href="drawings/sized/1200_1200.<?php echo calendar_h(basename($thumb)); ?>"
                                    data-modal-image
                                    data-modal-alt="<?php echo calendar_h($imageAlt); ?>"
                                >
                                    <img
                                        src="<?php echo calendar_h($thumb); ?>"
                                        alt="<?php echo calendar_h($imageAlt); ?>"
                                        loading="lazy"
                                    >
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function render_public_calendar_month_html(drawing $calendar): string
{
    ob_start();
    render_public_calendar_month($calendar);
    return (string) ob_get_clean();
}
