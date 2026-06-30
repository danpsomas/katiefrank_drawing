<?php
require_once __DIR__ . '/include/db_connect.php';
require_once __DIR__ . '/include/classes/class.drawing.php';

$calendar = new drawing($_GET['month'] ?? null);
$monthTitle = $calendar->getMonthTitle();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?> Drawings</title>
    <link rel="stylesheet" href="common/style.css">
    <script src="js/calendar-modal.js" defer></script>
</head>
<body>
    <main class="calendar-page">
        <header class="calendar-header">
            <div>
                <p class="eyebrow"><?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></p>
                <h1>Drawing Calendar</h1>
            </div>

            <nav class="calendar-nav" aria-label="Calendar months">
                <a href="?month=<?php echo htmlspecialchars($calendar->getPreviousMonthKey(), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                <a href="?month=<?php echo htmlspecialchars($calendar->getNextMonthKey(), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
            </nav>
        </header>

        <section class="month-grid" aria-label="<?php echo htmlspecialchars($calendar->getCalendarLabel(), ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($calendar->getWeekdays() as $weekday): ?>
                <div class="weekday" aria-hidden="true"><?php echo $weekday; ?></div>
            <?php endforeach; ?>

            <?php foreach ($calendar->getCalendarCells() as $cell): ?>
                <?php if ($cell === null): ?>
                    <div class="date-card date-card--empty" aria-hidden="true"></div>
                    <?php continue; ?>
                <?php endif; ?>

                <?php $thumbnailCount = count($cell['thumbnails']); ?>
                <article class="date-card<?php echo $cell['isToday'] ? ' date-card--today' : ''; ?><?php echo $thumbnailCount > 0 ? ' date-card--has-thumbnails date-card--thumbnail-count-' . $thumbnailCount : ''; ?>">
                    <div class="date-card__header">
                        <span class="date-card__weekday"><?php echo $cell['weekday']; ?></span>
                        <time datetime="<?php echo $cell['dateKey']; ?>"><?php echo $cell['day']; ?></time>
                    </div>

                    <?php if ($cell['thumbnails']): ?>
                        <div class="thumbnail-grid thumbnail-grid--count-<?php echo $thumbnailCount; ?>" aria-label="Drawings for <?php echo htmlspecialchars($cell['fullDate'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($cell['thumbnails'] as $index => $thumb): ?>
                                <?php $imageAlt = 'Drawing ' . ($index + 1) . ' for ' . $cell['fullDate']; ?>
                                <a
                                    href="drawings/sized/1200_1200.<?php echo htmlspecialchars(basename($thumb), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-modal-image
                                    data-modal-alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <img
                                        src="<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>"
                                        loading="lazy"
                                    >
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <div class="image-modal" data-image-modal role="dialog" aria-modal="true" aria-label="Drawing preview" hidden>
        <button class="image-modal__close" type="button" data-image-modal-close aria-label="Close image preview">Close</button>
        <button class="image-modal__nav image-modal__nav--previous" type="button" data-image-modal-previous aria-label="Previous drawing">Previous</button>
        <img class="image-modal__image" src="" alt="" data-image-modal-image>
        <button class="image-modal__nav image-modal__nav--next" type="button" data-image-modal-next aria-label="Next drawing">Next</button>
    </div>
</body>
</html>
