<?php

require_once __DIR__ . '/include/db_connect.php';
require_once __DIR__ . '/include/classes/class.drawing.php';
require_once __DIR__ . '/include/calendar_render.php';

header('Content-Type: application/json; charset=utf-8');

$month = $_GET['month'] ?? '';

if (!is_string($month) || preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid month as YYYY-MM.',
    ]);
    exit;
}

$calendar = new drawing($month);
$earliestMonth = drawing::getEarliestMonthKey($mysqli);

echo json_encode([
    'success' => true,
    'month' => $calendar->getMonthKey(),
    'previousMonth' => $calendar->getPreviousMonthKey(),
    'nextMonth' => $calendar->getNextMonthKey(),
    'currentMonth' => $calendar->getCurrentMonthKey(),
    'earliestMonth' => $earliestMonth,
    'hasDrawings' => $calendar->hasVisibleDrawings(),
    'html' => render_public_calendar_month_html($calendar),
]);
