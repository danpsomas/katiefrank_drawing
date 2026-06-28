<?php
require_once __DIR__ . '/include/classes/class.drawing.php';

// Add thumbnail URLs here using dates in YYYY-MM-DD format.
$thumbnailsByDate = [
    // '2026-06-01' => [
    //     '/images/example-1.jpg',
    //     '/images/example-2.jpg',
    // ],
];

$calendar = new drawing($_GET['month'] ?? null, $thumbnailsByDate);
$monthTitle = $calendar->getMonthTitle();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?> Drawings</title>
    <link rel="stylesheet" href="common/style.css">
</head>
<body>
    <main class="calendar-page">
        <header class="calendar-header">
            <div>
                <p class="eyebrow">Drawing Calendar</p>
                <h1><?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
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

                <article class="date-card<?php echo $cell['isToday'] ? ' date-card--today' : ''; ?>">
                    <div class="date-card__header">
                        <span class="date-card__weekday"><?php echo $cell['weekday']; ?></span>
                        <time datetime="<?php echo $cell['dateKey']; ?>"><?php echo $cell['day']; ?></time>
                    </div>

                    <?php if ($cell['thumbnails']): ?>
                        <div class="thumbnail-grid" aria-label="Drawings for <?php echo htmlspecialchars($cell['fullDate'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($cell['thumbnails'] as $index => $thumb): ?>
                                <img
                                    src="<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Drawing thumbnail <?php echo $index + 1; ?> for <?php echo htmlspecialchars($cell['fullDate'], ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                >
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="date-card__empty">No drawings yet</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
