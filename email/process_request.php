<?php
require_once'../vendor/autoload.php';
use PhpMimeMailParser\Parser;

require_once __DIR__ . '/../include/db_ini.php';
require_once __DIR__ . '/../include/functions.php';

ob_start();
$logFile = '/var/www/drawing.katiefrank.com/public_html/email/email_log.txt'; // Fixed: uppercase F
$origDir = __DIR__ . '/../drawings/orig/';

function write_email_log($message) {
    global $logFile;
    file_put_contents($logFile, sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message), FILE_APPEND);
}

function get_image_extension($filename, $contentType, $content) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $imageInfo = @getimagesizefromstring($content);

    if ($imageInfo === false) {
        return null;
    }

    if (in_array($extension, $allowedExtensions, true)) {
        return $extension;
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    $mime = strtolower($imageInfo['mime'] ?? $contentType);
    return $mimeToExtension[$mime] ?? null;
}

function get_exif_datetime($path) {
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

function get_display_date($exifDate, $timestamp) {
    if ($exifDate) {
        return substr($exifDate, 0, 10);
    }

    if ($timestamp) {
        $date = date_create($timestamp);
        if ($date) {
            return $date->format('Y-m-d');
        }
    }

    return date('Y-m-d');
}

function create_drawing_row($mysqli, $originalFilename) {
    $origName = $mysqli->real_escape_string(substr($originalFilename, 0, 50));
    $sql = "INSERT INTO drawing (orig_name, hidden) VALUES ('{$origName}', NULL)";
    if (!$mysqli->query($sql)) {
        throw new RuntimeException('Drawing insert failed: ' . $mysqli->error);
    }

    return $mysqli->insert_id;
}

function update_drawing_row($mysqli, $DID, $filename, $exifDate, $displayDate) {
    $DID = (int)$DID;
    $filename = $mysqli->real_escape_string($filename);
    $displayDate = $mysqli->real_escape_string($displayDate);
    $exifDateValue = $exifDate ? "'" . $mysqli->real_escape_string($exifDate) . "'" : 'NULL';
    $sql = "
        UPDATE drawing
        SET filename = '{$filename}',
            exif_date = {$exifDateValue},
            display_date = '{$displayDate}'
        WHERE DID = {$DID}
    ";

    if (!$mysqli->query($sql)) {
        throw new RuntimeException('Drawing update failed: ' . $mysqli->error);
    }
}

function delete_drawing_row($mysqli, $DID) {
    $DID = (int)$DID;
    $mysqli->query("DELETE FROM drawing WHERE DID = {$DID}");
}


/*
// Security check
$expectedToken = "zp-email-upload";
$receivedToken = $_SERVER['HTTP_X_EMAIL_TOKEN'] ?? '';
if ($receivedToken !== $expectedToken) {
    http_response_code(403);
    die("Unauthorized");
}
*/

// Get posted data
$json = file_get_contents('php://input');
$jsonBytes = is_string($json) ? strlen($json) : 0;
$data = json_decode($json ?: '', true);
$jsonError = json_last_error();


$from = $data['from'] ?? '';
$to = $data['to'] ?? '';
$subject = $data['subject'] ?? '';
$rawEmail = $data['rawEmail'] ?? '';
$timestamp = $data['timestamp'] ?? '';

if ($jsonError !== JSON_ERROR_NONE) {
    write_email_log(sprintf(
        "JSON decode error: %s, JSON bytes: %d",
        json_last_error_msg(),
        $jsonBytes
    ));
}

write_email_log(sprintf(
    "Request received. From: %s, Subject: %s, JSON bytes: %d, Raw email bytes: %d, Timestamp: %s",
    $from,
    $subject,
    $jsonBytes,
    is_string($rawEmail) ? strlen($rawEmail) : 0,
    $timestamp
));

// Parse the email
$parser = new Parser();
$parser->setText($rawEmail);



// Get attachments -- we really only use the attachments part, but the others are here if we ever want them
$attachments = $parser->getAttachments();
$textBody = $parser->getMessageBody('text');
$htmlBody = $parser->getMessageBody('html');

$mysqli = new mysqli($server_info['db_host'], $server_info['db_user'], $server_info['db_pass'], $server_info['db_name']);
if ($mysqli->connect_errno) {
    write_email_log("Database connection failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    http_response_code(500);
    exit;
}

// Save attachments
$savedAttachments = [];
$attachmentIndex = 0;
foreach ($attachments as $attachment) {
    $attachmentIndex++;
    $originalFilename = $attachment->getFilename();
    if ($originalFilename === null || $originalFilename === '') {
        $originalFilename = 'attachment_' . $attachmentIndex;
    }

    $content = $attachment->getContent();

    $extension = get_image_extension($originalFilename, $attachment->getContentType(), $content);
    if ($extension === null) {
        write_email_log(sprintf(
            "Attachment %d skipped. Original filename: %s, Content type: %s, Reason: not a supported image",
            $attachmentIndex,
            $originalFilename,
            $attachment->getContentType()
        ));
        continue;
    }

    if (!is_dir($origDir) && !mkdir($origDir, 0755, true)) {
        write_email_log(sprintf("Attachment %d failed. Could not create original image directory: %s", $attachmentIndex, $origDir));
        continue;
    }

    $DID = null;
    $savedPath = null;

    try {
        $DID = create_drawing_row($mysqli, $originalFilename);
        $filename = str_pad($DID, 4, '0', STR_PAD_LEFT) . '_' . str_makerand(6, 6, FALSE, FALSE, TRUE) . '.' . $extension;
        $savedPath = $origDir . $filename;
        $bytesWritten = file_put_contents($savedPath, $content);

        if ($bytesWritten === false) {
            throw new RuntimeException('Original file save failed');
        }

        $savedSize = file_exists($savedPath) ? filesize($savedPath) : 0;
        $exifDate = get_exif_datetime($savedPath);
        $displayDate = get_display_date($exifDate, $timestamp);
        update_drawing_row($mysqli, $DID, $filename, $exifDate, $displayDate);

        $savedAttachments[] = [
            'index' => $attachmentIndex,
            'DID' => $DID,
            'originalFilename' => $originalFilename,
            'filename' => $filename,
            'path' => $savedPath,
            'contentType' => $attachment->getContentType(),
            'size' => $savedSize,
            'bytesWritten' => $bytesWritten,
            'exifDate' => $exifDate,
            'displayDate' => $displayDate
        ];

        write_email_log(sprintf(
            "Attachment %d saved. DID: %d, Original filename: %s, Saved filename: %s, Content type: %s, MIME bytes: %d, Bytes written: %s, Saved size: %d, EXIF date: %s, Display date: %s",
            $attachmentIndex,
            $DID,
            $originalFilename,
            $filename,
            $attachment->getContentType(),
            is_string($content) ? strlen($content) : 0,
            $bytesWritten,
            $savedSize,
            $exifDate ?? 'none',
            $displayDate
        ));
    } catch (Throwable $e) {
        if ($savedPath && is_file($savedPath)) {
            unlink($savedPath);
        }

        if ($DID) {
            delete_drawing_row($mysqli, $DID);
        }

        write_email_log(sprintf(
            "Attachment %d failed. Original filename: %s, Error: %s",
            $attachmentIndex,
            $originalFilename,
            $e->getMessage()
        ));
    }
}







// Log
write_email_log(sprintf(
    "Request complete. From: %s, Subject: %s, Attachments parsed: %d, Attachments saved: %d",
    $from,
    $subject,
    count($attachments),
    count($savedAttachments)
));

$capturedOutput = trim(ob_get_contents());
if ($capturedOutput !== '') {
    write_email_log("Captured output: " . $capturedOutput);
}



// Return success
http_response_code(200);

?>