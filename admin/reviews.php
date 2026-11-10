<?php
require __DIR__ . '/auth.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = db()->prepare('SELECT hostel_id FROM reviews WHERE id = ?');
    $st->execute([$id]);
    if ($hid = $st->fetchColumn()) {
        if ($action === 'approve') {
            db()->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?")->execute([$id]);
            flash('Review approved. It is now visible and counts toward the rating.');
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
            flash('Review deleted.');
        }
        refresh_rating((int)$hid);
    }
    header('Location: reviews.php'); exit;
}

function fetch_reviews(string $status): array {
    $st = db()->prepare('SELECT r.*, h.name AS hostel FROM reviews r JOIN hostels h ON h.id = r.hostel_id
                         WHERE r.status = ? ORDER BY r.id DESC LIMIT 200');
    $st->execute([$status]);
    return $st->fetchAll();
}
$pendingRows  = fetch_reviews('pending');
$approvedRows = fetch_reviews('approved');

function review_rows(array $rows, bool $pending): void {
    foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['hostel']) ?></td>
        <td><?= str_repeat('★', (int)$r['rating']) ?></td>
        <td><strong><?= h($r['reviewer_name']) ?></strong><br><?= h($r['comment']) ?></td>
        <td><?= h(substr($r['created_at'], 0, 10)) ?></td>
        <td class="actions">
          <?php if ($pending): ?>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn primary">Approve</button>
          </form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('Delete this review?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn danger"><?= $pending ? 'Reject' : 'Delete' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach;
}

$pageTitle = 'Reviews';
require __DIR__ . '/_header.php';
?>
<h1>Student reviews</h1>
<?php if (!REVIEWS_NEED_APPROVAL): ?>
  <p class="hint">Approval is switched off in config.php, so new reviews appear immediately.</p>
<?php endif; ?>

<div class="sechead"><h2>Waiting for approval (<?= count($pendingRows) ?>)</h2></div>
<div class="tablewrap"><table>
  <thead><tr><th>Hostel</th><th>Rating</th><th>Review</th><th>Date</th><th></th></tr></thead>
  <tbody>
    <?php review_rows($pendingRows, true); if (!$pendingRows): ?>
      <tr><td colspan="5" class="empty">Nothing waiting. New reviews will show up here.</td></tr>
    <?php endif; ?>
  </tbody>
</table></div>

<div class="sechead"><h2>Published</h2></div>
<div class="tablewrap"><table>
  <thead><tr><th>Hostel</th><th>Rating</th><th>Review</th><th>Date</th><th></th></tr></thead>
  <tbody>
    <?php review_rows($approvedRows, false); if (!$approvedRows): ?>
      <tr><td colspan="5" class="empty">No published reviews yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
