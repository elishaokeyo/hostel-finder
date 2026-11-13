<?php
$file = basename($_GET['file'] ?? '');
if (!$file) { http_response_code(404); exit; }

$paths = [
    '/app/uploads/hostels/' . $file,          // Wasmer volume
    __DIR__ . '/uploads/hostels/' . $file,    // Local dev fallback
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        header('Content-Type: ' . mime_content_type($path));
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }
}
http_response_code(404);
