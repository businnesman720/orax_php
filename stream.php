<?php
// stream.php - Video streaming with Range support for development
$file = $_GET['file'] ?? '';
if (!$file) exit;

$path = realpath(__DIR__ . DIRECTORY_SEPARATOR . $file);
$base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos');

// Security check: ensure path is inside uploads/videos
if (!$path || strpos($path, $base) !== 0) {
    error_log("Stream Error: Path security breach or invalid! File: $file, Path: $path, Base: $base");
    header("HTTP/1.1 403 Forbidden");
    exit;
}

if (!file_exists($path)) {
    error_log("Stream Error: File not found! Path: $path");
    header("HTTP/1.1 404 Not Found");
    exit;
}

$size = filesize($path);
$start = 0;
$end = $size - 1;

header("Content-Type: video/mp4");
header("Accept-Ranges: bytes");

if (isset($_SERVER['HTTP_RANGE'])) {
    $c_start = $start;
    $c_end = $end;

    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }

    if ($range == '-') {
        $c_start = $size - substr($range, 1);
    } else {
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
    }
    $c_end = ($c_end > $end) ? $end : $c_end;
    
    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    
    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: " . $length);
} else {
    header("Content-Length: " . $size);
}

$fp = fopen($path, 'rb');
fseek($fp, $start);
$buffer = 8192;
ob_clean();
while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
    if ($pos + $buffer > $end) {
        $buffer = $end - $pos + 1;
    }
    echo fread($fp, $buffer);
    flush();
}
fclose($fp);
clearstatcache();
exit;
?>
