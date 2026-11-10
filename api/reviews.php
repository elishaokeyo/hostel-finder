<?php
// GET  ?hostel_id=1  -> list approved reviews
// POST JSON          -> add a review (pending approval unless REVIEWS_NEED_APPROVAL is false)
require __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $st = db()->prepare("SELECT reviewer_name, rating, comment, created_at
                             FROM reviews WHERE hostel_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 50");
        $st->execute([(int)($_GET['hostel_id'] ?? 0)]);
        out(['reviews' => $st->fetchAll()]);
    }

    if ($method === 'POST') {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!empty($in['website'])) out(['ok' => true, 'pending' => true]); // honeypot: bots fill this hidden field

        $hostelId = (int)($in['hostel_id'] ?? 0);
        $name     = trim((string)($in['name'] ?? ''));
        $rating   = (int)($in['rating'] ?? 0);
        $comment  = trim((string)($in['comment'] ?? ''));

        if ($name === '' || mb_strlen($name) > 60)  out(['error' => 'Enter your name (up to 60 characters).'], 422);
        if ($rating < 1 || $rating > 5)             out(['error' => 'Choose a rating from 1 to 5.'], 422);
        if (mb_strlen($comment) > 1000)             out(['error' => 'Keep your comment under 1000 characters.'], 422);

        $chk = db()->prepare('SELECT COUNT(*) FROM hostels WHERE id = ?');
        $chk->execute([$hostelId]);
        if (!$chk->fetchColumn()) out(['error' => 'That hostel no longer exists.'], 404);

        $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|hostel-finder');
        if (REVIEW_COOLDOWN_HOURS > 0) {
            $dup = db()->prepare('SELECT COUNT(*) FROM reviews WHERE hostel_id = ? AND ip_hash = ?
                                  AND created_at > NOW() - INTERVAL ' . (int)REVIEW_COOLDOWN_HOURS . ' HOUR');
            $dup->execute([$hostelId, $ip]);
            if ($dup->fetchColumn()) out(['error' => 'You already reviewed this hostel recently. Try again later.'], 429);
        }

        $status = REVIEWS_NEED_APPROVAL ? 'pending' : 'approved';
        db()->prepare('INSERT INTO reviews (hostel_id, reviewer_name, rating, comment, ip_hash, status) VALUES (?,?,?,?,?,?)')
            ->execute([$hostelId, $name, $rating, $comment, $ip, $status]);

        if ($status === 'pending') out(['ok' => true, 'pending' => true]);

        refresh_rating($hostelId);
        $st = db()->prepare('SELECT rating, reviews_count FROM hostels WHERE id = ?');
        $st->execute([$hostelId]);
        $h = $st->fetch();
        out(['ok' => true, 'pending' => false, 'rating' => (float)$h['rating'], 'reviews_count' => (int)$h['reviews_count']]);
    }

    out(['error' => 'Method not allowed.'], 405);
} catch (Throwable $e) {
    out(['error' => 'Could not process the request.'], 500);
}
