<?php
require __DIR__ . '/auth.php';
require_login();
$uid = (int)$_SESSION['uid'];

function owned_hostel(int $id): ?array
{
  $st = db()->prepare('SELECT * FROM hostels WHERE id = ?');
  $st->execute([$id]);
  $r = $st->fetch();
  return ($r && can_edit_hostel($r)) ? $r : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($action === 'delete_hostel') {
    if (owned_hostel($id)) {
      $ph = db()->prepare('SELECT filename FROM hostel_photos WHERE hostel_id = ?');
      $ph->execute([$id]);
      foreach ($ph->fetchAll(PDO::FETCH_COLUMN) as $f) {
        @unlink(UPLOAD_DIR . '/' . basename($f));
      }
      db()->prepare('DELETE FROM hostels WHERE id = ?')->execute([$id]);
      flash('Hostel deleted.');
    } else flash('You can only change your own hostels.');
  } elseif ($action === 'set_rooms') {
    if (owned_hostel($id)) {
      db()->prepare('UPDATE hostels SET available_rooms = ? WHERE id = ?')
        ->execute([max(0, (int)($_POST['rooms'] ?? 0)), $id]);
      flash('Rooms free updated.');
    } else flash('You can only change your own hostels.');
  } elseif ($action === 'delete_place') {
    if (!is_admin()) {
      flash('Only administrators can change places.');
    } else {
      $st = db()->prepare('SELECT is_default FROM places WHERE id = ?');
      $st->execute([$id]);
      if ($st->fetchColumn()) {
        flash('You can\'t delete the default place. Make another place the default first.');
      } else {
        db()->prepare('DELETE FROM places WHERE id = ?')->execute([$id]);
        flash('Place deleted.');
      }
    }
  }
  header('Location: index.php');
  exit;
}

$sql = 'SELECT h.*, a.username AS owner FROM hostels h LEFT JOIN admins a ON a.id = h.owner_id';
$places = [];
$pending = 0;
if (is_admin()) {
  $hostels = db()->query($sql . ' ORDER BY h.name')->fetchAll();
} else {
  $st = db()->prepare($sql . ' WHERE h.owner_id = ? ORDER BY h.name');
  $st->execute([$uid]);
  $hostels = $st->fetchAll();
}
$free = array_sum(array_column($hostels, 'available_rooms'));
if (is_admin()) {
  $places  = db()->query('SELECT * FROM places ORDER BY is_default DESC, name')->fetchAll();
  $pending = (int)db()->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
}
$pageTitle = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<h1><?= is_admin() ? 'Dashboard' : 'Your hostels' ?></h1>
<div class="stats">
  <div><b><?= count($hostels) ?></b> hostels</div>
  <div><b><?= $free ?></b> rooms free</div>
  <?php if (is_admin()): ?>
    <div><b><?= count($places) ?></b> target places</div>
    <div><b><?= $pending ?></b> reviews waiting</div>
  <?php endif; ?>
</div>

<section>
  <div class="sechead">
    <h2>Hostels</h2><a class="btn primary" href="hostel.php">Add hostel</a>
  </div>
  <div class="tablewrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Price</th>
          <th>Rating</th>
          <th>Rooms free</th><?= is_admin() ? '<th>Owner</th>' : '' ?><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($hostels as $r): ?>
          <tr>
            <td><?= h($r['name']) ?><br><small><?= h($r['address']) ?></small></td>
            <td><?= h($r['room_type']) ?></td>
            <td>KSh <?= number_format($r['price_per_month']) ?></td>
            <td><?= h($r['rating']) ?> <small>(<?= (int)$r['reviews_count'] ?>)</small></td>
            <td>
              <form method="post" class="inline">
                <?= csrf_field() ?><input type="hidden" name="action" value="set_rooms"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="number" name="rooms" min="0" value="<?= (int)$r['available_rooms'] ?>" aria-label="Rooms free in <?= h($r['name']) ?>">
                <button class="btn">Update</button>
              </form>
            </td>
            <?php if (is_admin()): ?><td><?= $r['owner'] ? h($r['owner']) : '<small>admin</small>' ?></td><?php endif; ?>
            <td class="actions">
              <a class="btn" href="hostel.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete <?= h(addslashes($r['name'])) ?>?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete_hostel"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach;
        if (!$hostels): ?>
          <tr>
            <td colspan="7" class="empty"><?= is_admin() ? 'No hostels yet. Add your first one.' : 'You haven\'t added a hostel yet. Use "Add hostel" to list your first one.' ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php if (is_admin()): ?>
  <section>
    <div class="sechead">
      <h2>Target places</h2><a class="btn primary" href="place.php">Add place</a>
    </div>
    <div class="tablewrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($places as $r): ?>
            <tr>
              <td><?= h($r['name']) ?> <?= $r['is_default'] ? '<span class="badge">default</span>' : '' ?></td>
              <td><?= h($r['latitude']) ?></td>
              <td><?= h($r['longitude']) ?></td>
              <td class="actions">
                <a class="btn" href="place.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete <?= h(addslashes($r['name'])) ?>?')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete_place"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>