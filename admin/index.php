<?php
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/classes/class.drawing.php';
require_once __DIR__ . '/../include/classes/class.formfield.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_display_date(string $value): ?string
{
    $value = trim($value);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date->format('Y-m-d');
}

function save_drawing(mysqli $mysqli, int $did, string $displayDate, int $hiddenFlag): array
{
    $statement = $mysqli->prepare("
        UPDATE drawing
        SET display_date = ?,
            hidden = CASE WHEN ? = 1 THEN COALESCE(hidden, NOW()) ELSE NULL END
        WHERE DID = ?
    ");

    if (!$statement) {
        error_log('Drawing admin update prepare failed: ' . $mysqli->error);
        return ['success' => false, 'message' => 'Could not prepare the update.', 'DID' => $did];
    }

    $statement->bind_param('sii', $displayDate, $hiddenFlag, $did);

    if (!$statement->execute()) {
        error_log('Drawing admin update failed: ' . $statement->error);
        $statement->close();
        return ['success' => false, 'message' => 'Could not save the drawing.', 'DID' => $did];
    }

    $statement->close();

    $statusStatement = $mysqli->prepare("
        SELECT filename, DATE(display_date) AS display_date, hidden
        FROM drawing
        WHERE DID = ?
    ");

    if (!$statusStatement) {
        error_log('Drawing admin status prepare failed: ' . $mysqli->error);
        return ['success' => false, 'message' => 'Could not reload the drawing.', 'DID' => $did];
    }

    $filename = null;
    $savedDisplayDate = null;
    $hidden = null;
    $statusStatement->bind_param('i', $did);
    $statusStatement->execute();
    $statusStatement->bind_result($filename, $savedDisplayDate, $hidden);
    $rowFound = $statusStatement->fetch();
    $statusStatement->close();

    if (!$rowFound) {
        return ['success' => false, 'message' => 'Could not find that drawing.', 'DID' => $did];
    }

    return [
        'success' => true,
        'message' => 'Saved',
        'DID' => $did,
        'display_date' => $savedDisplayDate,
        'filename' => $filename,
        'hidden' => $hidden,
        'is_hidden' => $hidden !== null && $hidden !== '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $did = filter_input(INPUT_POST, 'DID', FILTER_VALIDATE_INT);
    $displayDate = normalize_display_date($_POST['display_date'] ?? '');
    $hiddenFlag = isset($_POST['is_hidden']) && $_POST['is_hidden'] === '1' ? 1 : 0;

    if (!$did) {
        $saveResult = ['success' => false, 'message' => 'Could not identify the drawing to update.', 'DID' => null];
    } elseif (!$displayDate) {
        $saveResult = ['success' => false, 'message' => 'Please enter the display date as YYYY-MM-DD.', 'DID' => $did];
    } else {
        $saveResult = save_drawing($mysqli, $did, $displayDate, $hiddenFlag);
    }

    header('Content-Type: application/json');
    echo json_encode($saveResult);
    exit;
}

$calendar = new drawing($_GET['month'] ?? null, null, true);
$monthTitle = $calendar->getMonthTitle();
$modalFormfield = new formfield([
    'DID' => '',
    'display_date' => '',
]);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($monthTitle); ?> Drawing Admin</title>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../common/style.css">
    <link rel="stylesheet" href="../common/elements.css">
    <link rel="stylesheet" href="../common/dropzone.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="../js/dropzone.js" defer></script>
    <script src="../js/admin-calendar.js" defer></script>
    <style>
        .admin-page {
            width: min(1200px, calc(100% - 32px));
            margin: 0 auto;
            padding: 40px 0;
        }

        .admin-thumbnail {
            position: relative;
        }

        .admin-page .thumbnail-grid {
            width: 100%;
            padding-left: 44px;
            grid-template-columns: repeat(auto-fit, 60px);
            grid-template-rows: none;
            grid-auto-rows: 60px;
            align-content: start;
            justify-content: end;
        }

        .admin-page .thumbnail-grid a {
            width: 60px !important;
            height: 60px !important;
            grid-column: auto !important;
            grid-row: auto !important;
        }

        .admin-page .date-card.dz-drag-hover,
        .admin-page .date-card--uploading {
            border-color: var(--color-accent);
            background: var(--color-date-bg);
            box-shadow: 0 12px 30px var(--color-accent-shadow);
        }

        .admin-drop-hint {
            position: absolute;
            right: 12px;
            bottom: 10px;
            z-index: 1;
            color: var(--color-text-soft);
            font-size: 0.72rem;
            font-weight: 700;
            opacity: 0;
            pointer-events: none;
            text-transform: uppercase;
            transition: opacity 0.16s ease;
        }

        .date-card:hover .admin-drop-hint,
        .date-card.dz-drag-hover .admin-drop-hint,
        .date-card--uploading .admin-drop-hint {
            opacity: 1;
        }

        .date-card.dz-drag-hover .admin-drop-hint,
        .date-card--uploading .admin-drop-hint {
            color: var(--color-link-hover);
        }

        .admin-upload-status {
            min-height: 1.4em;
            margin-top: 12px;
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }

        .admin-upload-status--error {
            color: var(--color-text);
        }

        .admin-thumbnail--hidden {
            opacity: 0.38;
        }

        .admin-thumbnail--hidden::after {
            position: absolute;
            right: 5px;
            bottom: 5px;
            padding: 2px 6px;
            border-radius: 999px;
            background: rgba(42, 42, 42, 0.76);
            color: var(--color-surface);
            content: "Hidden";
            font-size: 0.65rem;
            font-weight: 700;
        }

        .admin-edit-modal {
            position: fixed;
            inset: 0;
            z-index: 20;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--color-modal-bg);
        }

        .admin-edit-modal[hidden] {
            display: none;
        }

        .admin-edit-modal__panel {
            display: grid;
            width: min(920px, calc(100vw - 48px));
            max-height: calc(100vh - 48px);
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            overflow: auto;
            padding: 18px;
            border-radius: 20px;
            background: var(--color-surface);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        }

        .admin-edit-modal__image {
            width: 100%;
            max-height: calc(100vh - 96px);
            border-radius: 16px;
            object-fit: contain;
            background: var(--color-page-bg);
        }

        .admin-edit-modal__form {
            display: grid;
            align-content: start;
            gap: 14px;
        }

        .admin-edit-modal__title {
            margin: 0;
            color: var(--color-link);
        }

        .admin-edit-modal__close {
            justify-self: end;
            margin: 0;
        }

        .admin-edit-modal__status {
            min-height: 1.3em;
            color: var(--color-text-muted);
            font-size: 0.85rem;
        }

        .admin-edit-modal__status--error {
            color: var(--color-text);
        }

        .admin-edit-modal .nifty_wrapper {
            margin: 0;
        }

        .admin-edit-modal input[name="display_date"] {
            width: 100%;
        }

        @media (max-width: 760px) {
            .admin-edit-modal__panel {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="admin-page">
        <header class="calendar-header">
            <div>
                <p class="eyebrow"><?php echo h($monthTitle); ?></p>
                <h1>Drawing Calendar Admin</h1>
            </div>

            <nav class="calendar-nav" aria-label="Calendar months">
                <a href="?month=<?php echo h($calendar->getPreviousMonthKey()); ?>">Previous</a>
                <a href="../?month=<?php echo h($calendar->getMonthKey()); ?>" target="_blank" rel="noopener">View Calendar</a>
                <a href="?month=<?php echo h($calendar->getNextMonthKey()); ?>">Next</a>
            </nav>
        </header>

        <section class="month-grid" aria-label="<?php echo h($calendar->getCalendarLabel()); ?>">
            <?php foreach ($calendar->getWeekdays() as $weekday): ?>
                <div class="weekday" aria-hidden="true"><?php echo $weekday; ?></div>
            <?php endforeach; ?>

            <?php foreach ($calendar->getCalendarCells() as $cell): ?>
                <?php if ($cell === null): ?>
                    <div class="date-card date-card--empty" aria-hidden="true"></div>
                    <?php continue; ?>
                <?php endif; ?>

                <?php $thumbnailCount = count($cell['thumbnails']); ?>
                <article class="date-card<?php echo $cell['isToday'] ? ' date-card--today' : ''; ?><?php echo $thumbnailCount > 0 ? ' date-card--has-thumbnails date-card--thumbnail-count-' . $thumbnailCount : ''; ?>" data-date-cell="<?php echo h($cell['dateKey']); ?>">
                    <div class="date-card__header">
                        <span class="date-card__weekday"><?php echo $cell['weekday']; ?></span>
                        <time datetime="<?php echo h($cell['dateKey']); ?>"><?php echo $cell['day']; ?></time>
                    </div>
                    <span class="admin-drop-hint" aria-hidden="true">Drop image</span>

                    <div class="thumbnail-grid thumbnail-grid--count-<?php echo $thumbnailCount; ?>" aria-label="Drawings for <?php echo h($cell['fullDate']); ?>">
                        <?php foreach ($cell['thumbnails'] as $index => $thumb): ?>
                            <?php $imageAlt = 'Drawing ' . ($index + 1) . ' for ' . $cell['fullDate']; ?>
                            <a
                                class="admin-thumbnail<?php echo $thumb['isHidden'] ? ' admin-thumbnail--hidden' : ''; ?>"
                                href="../<?php echo h($thumb['sizedPath']); ?>"
                                data-admin-thumbnail
                                data-did="<?php echo (int) $thumb['DID']; ?>"
                                data-display-date="<?php echo h($thumb['displayDate']); ?>"
                                data-filename="<?php echo h($thumb['filename']); ?>"
                                data-hidden="<?php echo $thumb['isHidden'] ? '1' : '0'; ?>"
                                data-hidden-date="<?php echo h($thumb['hidden'] ?? ''); ?>"
                                data-orig-name="<?php echo h($thumb['origName'] ?? ''); ?>"
                                data-thumb-src="../<?php echo h($thumb['thumbPath']); ?>"
                                data-modal-alt="<?php echo h($imageAlt); ?>"
                            >
                                <img
                                    src="../<?php echo h($thumb['thumbPath']); ?>"
                                    alt="<?php echo h($imageAlt); ?>"
                                    loading="lazy"
                                >
                            </a>
                        <?php endforeach; ?>
                    </div>

                </article>
            <?php endforeach; ?>
        </section>

        <div class="admin-upload-status" data-admin-upload-status aria-live="polite"></div>
    </main>

    <div class="admin-edit-modal" data-admin-edit-modal role="dialog" aria-modal="true" aria-label="Edit drawing" hidden>
        <div class="admin-edit-modal__panel">
            <img class="admin-edit-modal__image" src="" alt="" data-admin-modal-image>

            <form class="admin-edit-modal__form" data-admin-edit-form autocomplete="off">
                <button class="admin-edit-modal__close" type="button" data-admin-modal-close>Close</button>
                <h2 class="admin-edit-modal__title">Edit Drawing</h2>
                <p class="drawing-meta" data-admin-modal-meta></p>

                <?php echo $modalFormfield->hidden('DID', ['id' => 'admin_DID']); ?>
                <?php
                echo $modalFormfield->input('display_date', [
                    'id' => 'admin_display_date',
                    'label' => 'Display Date',
                    'placeholder_text' => 'YYYY-MM-DD',
                    'required' => true,
                    'size' => 12,
                    'classnames' => ['datefield', 'datepicker'],
                    'allow_datepicker_entry' => true,
                    'datepicker_opts' => [
                        'dateFormat' => "'yy-mm-dd'",
                        'minDate' => 'null',
                        'onSelect' => 'function() {this.dispatchEvent(new Event(\'change\', { bubbles: true }))}',
                    ],
                    'attributes' => [
                        'inputmode' => 'numeric',
                        'pattern' => '\d{4}-\d{2}-\d{2}',
                    ],
                ]);
                ?>
                <?php
                echo $modalFormfield->checkbox('is_hidden', [
                    'id' => 'admin_is_hidden',
                    'caption' => 'Hidden',
                    'value' => '1',
                ]);
                ?>
                <span class="admin-edit-modal__status" data-admin-modal-status aria-live="polite"></span>
            </form>
        </div>
    </div>

    <script>
        $(function() {
            <?php echo $modalFormfield->js; ?>
        });
    </script>
</body>
</html>
