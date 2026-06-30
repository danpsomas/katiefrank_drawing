<?php
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/functions.php';

header('Content-Type: application/json');

function respond_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function normalize_upload_display_date(string $value): ?string
{
    $value = trim($value);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date->format('Y-m-d');
}

function get_upload_image_extension(string $filename, string $path): ?string
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $imageInfo = @getimagesize($path);

    if ($imageInfo === false) {
        return null;
    }

    if (in_array($extension, $allowedExtensions, true)) {
        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];
    $mime = strtolower($imageInfo['mime'] ?? '');

    return $mimeToExtension[$mime] ?? null;
}

function get_upload_exif_datetime(string $path): ?string
{
    if (!function_exists('exif_read_data')) {
        return null;
    }

    $exif = @exif_read_data($path);
    if (!is_array($exif)) {
        return null;
    }

    $exifDate = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;
    if (!$exifDate) {
        return null;
    }

    $date = DateTime::createFromFormat('Y:m:d H:i:s', $exifDate);

    return $date ? $date->format('Y-m-d H:i:s') : null;
}

function create_upload_drawing_row(mysqli $mysqli, string $originalFilename): int
{
    $origName = substr($originalFilename, 0, 50);
    $statement = $mysqli->prepare('INSERT INTO drawing (orig_name, hidden) VALUES (?, NULL)');

    if (!$statement) {
        throw new RuntimeException('Drawing insert prepare failed: ' . $mysqli->error);
    }

    $statement->bind_param('s', $origName);

    if (!$statement->execute()) {
        $error = $statement->error;
        $statement->close();
        throw new RuntimeException('Drawing insert failed: ' . $error);
    }

    $did = (int) $mysqli->insert_id;
    $statement->close();

    return $did;
}

function update_upload_drawing_row(mysqli $mysqli, int $did, string $filename, ?string $exifDate, string $displayDate): void
{
    $statement = $mysqli->prepare('
        UPDATE drawing
        SET filename = ?,
            exif_date = ?,
            display_date = ?
        WHERE DID = ?
    ');

    if (!$statement) {
        throw new RuntimeException('Drawing update prepare failed: ' . $mysqli->error);
    }

    $statement->bind_param('sssi', $filename, $exifDate, $displayDate, $did);

    if (!$statement->execute()) {
        $error = $statement->error;
        $statement->close();
        throw new RuntimeException('Drawing update failed: ' . $error);
    }

    $statement->close();
}

function delete_upload_drawing_row(mysqli $mysqli, int $did): void
{
    $statement = $mysqli->prepare('DELETE FROM drawing WHERE DID = ?');

    if (!$statement) {
        return;
    }

    $statement->bind_param('i', $did);
    $statement->execute();
    $statement->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(405, ['success' => false, 'message' => 'Upload requests must use POST.']);
}

if ($mysqli->connect_errno) {
    respond_json(500, ['success' => false, 'message' => 'Database connection failed.']);
}

$rawDisplayDate = $_POST['display_date'] ?? '';
$displayDate = normalize_upload_display_date($rawDisplayDate);
if (!$displayDate) {
    respond_json(400, ['success' => false, 'message' => 'Please provide a valid calendar date.']);
}

$upload = $_FILES['file'] ?? null;
if (!$upload || !is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $uploadError = is_array($upload) ? ($upload['error'] ?? null) : null;
    $uploadMessage = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
        ? 'That image is larger than the server upload limit.'
        : 'Please upload a valid image file.';

    respond_json(400, ['success' => false, 'message' => $uploadMessage]);
}

$tmpPath = $upload['tmp_name'] ?? '';
if (!is_uploaded_file($tmpPath)) {
    respond_json(400, ['success' => false, 'message' => 'The uploaded file could not be verified.']);
}

$originalFilename = basename((string) ($upload['name'] ?? 'upload'));
$originalFilename = $originalFilename !== '' ? $originalFilename : 'upload';
$extension = get_upload_image_extension($originalFilename, $tmpPath);

if ($extension === null) {
    respond_json(400, ['success' => false, 'message' => 'Only JPG, PNG, and GIF images can be uploaded.']);
}

$origDir = __DIR__ . '/../drawings/orig/';
if (!is_dir($origDir) && !mkdir($origDir, 0755, true)) {
    respond_json(500, ['success' => false, 'message' => 'Could not create the original image directory.']);
}

$did = null;
$savedPath = null;

try {
    $did = create_upload_drawing_row($mysqli, $originalFilename);
    $filename = str_pad((string) $did, 4, '0', STR_PAD_LEFT) . '_' . str_makerand(6, 6, false, false, true) . '.' . $extension;
    $savedPath = $origDir . $filename;

    if (!move_uploaded_file($tmpPath, $savedPath)) {
        throw new RuntimeException('Original file save failed.');
    }

    $exifDate = get_upload_exif_datetime($savedPath);
    update_upload_drawing_row($mysqli, $did, $filename, $exifDate, $displayDate);

    respond_json(200, [
        'success' => true,
        'message' => 'Uploaded',
        'DID' => $did,
        'filename' => $filename,
        'display_date' => $displayDate,
        'exif_date' => $exifDate,
        'origName' => substr($originalFilename, 0, 50),
        'hidden' => null,
        'isHidden' => false,
        'thumbPath' => 'drawings/thumbs/' . $filename,
        'sizedPath' => 'drawings/sized/1200_1200.' . $filename,
    ]);
} catch (Throwable $e) {
    if ($savedPath && is_file($savedPath)) {
        unlink($savedPath);
    }

    if ($did) {
        delete_upload_drawing_row($mysqli, $did);
    }

    error_log('Drawing admin upload failed: ' . $e->getMessage());
    respond_json(500, ['success' => false, 'message' => 'Could not save the uploaded image.']);
}
