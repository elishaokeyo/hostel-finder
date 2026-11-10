<?php
// Returns hostels (with photos) and target places as JSON for the front end.
require __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $hostels = db()->query('SELECT * FROM hostels ORDER BY name')->fetchAll();
    $places  = db()->query('SELECT * FROM places ORDER BY is_default DESC, name')->fetchAll();

    $photos = [];
    foreach (db()->query('SELECT hostel_id, filename FROM hostel_photos ORDER BY id') as $row) {
        $photos[(int)$row['hostel_id']][] = UPLOAD_URL . '/' . $row['filename'];
    }

    foreach ($hostels as &$h) {
        $h['id']              = (int)$h['id'];
        $h['latitude']        = (float)$h['latitude'];
        $h['longitude']       = (float)$h['longitude'];
        $h['price_per_month'] = (int)$h['price_per_month'];
        $h['rating']          = (float)$h['rating'];
        $h['reviews_count']   = (int)$h['reviews_count'];
        $h['available_rooms'] = (int)$h['available_rooms'];
        $h['amenities']       = $h['amenities'] === '' ? [] : explode(',', $h['amenities']);
        $h['photos']          = $photos[$h['id']] ?? [];
    }
    foreach ($places as &$p) {
        $p['latitude']   = (float)$p['latitude'];
        $p['longitude']  = (float)$p['longitude'];
        $p['is_default'] = (bool)$p['is_default'];
    }
    echo json_encode(['hostels' => $hostels, 'places' => $places]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load data. Check config.php and database.sql.']);
}
