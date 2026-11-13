<?php
require __DIR__ . '/auth.php';
require_login();

const MAX_PHOTOS = 8;
const MAX_PHOTO_BYTES = 3 * 1024 * 1024;

$types = ['Single', 'Double', 'Bedsitter', 'Shared'];
$h = ['name' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'price_per_month' => '',
      'room_type' => 'Single', 'rating' => '0', 'reviews_count' => '0', 'available_rooms' => '1',
      'amenities' => '', 'phone' => '', 'owner_id' => ''];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// When editing, the hostel must exist and belong to this user (admins may edit any).
$existing = null;
if ($id) {
    $st = db()->prepare('SELECT * FROM hostels WHERE id = ?');
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) { flash('Hostel not found.'); header('Location: index.php'); exit; }
    if (!can_edit_hostel($existing)) { flash('You can only edit your own hostels.'); header('Location: index.php'); exit; }
}

// Validate and store uploaded photos. Returns a list of problems (empty when all is well).
function save_photos(int $hostelId): array {
    $errs = [];
    if (empty($_FILES['photos']['name'][0])) return $errs;
     if (PHOTO_BASE) return ['Photo uploads are turned off on this address. Please upload photos on the main site.'];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $count = db()->prepare('SELECT COUNT(*) FROM hostel_photos WHERE hostel_id = ?');
    $count->execute([$hostelId]);
    $have = (int)$count->fetchColumn();

    foreach ($_FILES['photos']['name'] as $i => $orig) {
        $code = $_FILES['photos']['error'][$i];
        if ($code === UPLOAD_ERR_NO_FILE) continue;
        $orig = basename($orig);
        if ($have >= MAX_PHOTOS) { $errs[] = 'Only ' . MAX_PHOTOS . ' photos are allowed per hostel.'; break; }
        if ($code !== UPLOAD_ERR_OK) { $errs[] = "$orig is too large or failed to upload."; continue; }

        $tmp = $_FILES['photos']['tmp_name'][$i];
        if ($_FILES['photos']['size'][$i] > MAX_PHOTO_BYTES) { $errs[] = "$orig is over 3 MB."; continue; }
        $info = @getimagesize($tmp);
        $mime = $info['mime'] ?? '';
        if (!isset($allowed[$mime])) { $errs[] = "$orig is not a JPG, PNG or WebP image."; continue; }

        $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($tmp, UPLOAD_DIR . '/' . $name)) { $errs[] = "$orig could not be saved."; continue; }
        db()->prepare('INSERT INTO hostel_photos (hostel_id, filename) VALUES (?, ?)')->execute([$hostelId, $name]);
        $have++;
    }
    return $errs;
}

// Remove a single photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_photo' && $id) {
    $pid = (int)($_POST['photo_id'] ?? 0);
    $st = db()->prepare('SELECT filename FROM hostel_photos WHERE id = ? AND hostel_id = ?');
    $st->execute([$pid, $id]);
    if ($file = $st->fetchColumn()) {
        db()->prepare('DELETE FROM hostel_photos WHERE id = ?')->execute([$pid]);
        @unlink(UPLOAD_DIR . '/' . basename($file));
        flash('Photo removed.');
    }
    header('Location: hostel.php?id=' . $id); exit;
}

if ($existing && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $h = array_merge($h, $existing);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($h as $k => $_) { $h[$k] = trim((string)($_POST[$k] ?? '')); }

    if ($h['name'] === '' || mb_strlen($h['name']) > 120)       $errors[] = 'Enter a name (up to 120 characters).';
    if ($h['address'] === '' || mb_strlen($h['address']) > 200) $errors[] = 'Enter an address (up to 200 characters).';
    if (!is_numeric($h['latitude'])  || abs($h['latitude'])  > 90)  $errors[] = 'Latitude must be a number between -90 and 90. Click the map to set it.';
    if (!is_numeric($h['longitude']) || abs($h['longitude']) > 180) $errors[] = 'Longitude must be a number between -180 and 180. Click the map to set it.';
    if (!ctype_digit($h['price_per_month']))   $errors[] = 'Price must be a whole number, like 6500.';
    if (!in_array($h['room_type'], $types, true)) $errors[] = 'Choose a room type.';
    if (!ctype_digit($h['available_rooms']))   $errors[] = 'Free rooms must be a whole number.';
    if (!preg_match('/^[0-9+\s-]{0,30}$/', $h['phone'])) $errors[] = 'Phone can only contain digits, spaces, + and -.';

    $ownerId = null;
    if (is_admin()) {
        if (!is_numeric($h['rating']) || $h['rating'] < 0 || $h['rating'] > 5) $errors[] = 'Rating must be between 0 and 5.';
        if (!ctype_digit($h['reviews_count'])) $errors[] = 'Reviews must be a whole number.';
        if ($h['owner_id'] !== '') {
            $chk = db()->prepare('SELECT COUNT(*) FROM admins WHERE id = ?');
            $chk->execute([(int)$h['owner_id']]);
            if (!ctype_digit($h['owner_id']) || !$chk->fetchColumn()) $errors[] = 'Choose a valid owner.';
            else $ownerId = (int)$h['owner_id'];
        }
    }

    $h['amenities'] = implode(',', array_filter(array_map('trim', explode(',', $h['amenities']))));
    if (mb_strlen($h['amenities']) > 255) $errors[] = 'Amenities list is too long.';

    if (!$errors) {
        $cols = [
            'name' => $h['name'], 'address' => $h['address'],
            'latitude' => $h['latitude'], 'longitude' => $h['longitude'],
            'price_per_month' => (int)$h['price_per_month'], 'room_type' => $h['room_type'],
            'available_rooms' => (int)$h['available_rooms'], 'amenities' => $h['amenities'], 'phone' => $h['phone'],
        ];
        if (is_admin()) {
            $cols['rating'] = round((float)$h['rating'], 1);
            $cols['reviews_count'] = (int)$h['reviews_count'];
            $cols['owner_id'] = $ownerId;
        } elseif (!$id) {
            $cols['owner_id'] = (int)$_SESSION['uid'];
        }

        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cols)));
            db()->prepare("UPDATE hostels SET $set WHERE id = ?")->execute([...array_values($cols), $id]);
            $msg = 'Hostel updated.';
        } else {
            $names = implode(', ', array_keys($cols));
            $marks = implode(', ', array_fill(0, count($cols), '?'));
            db()->prepare("INSERT INTO hostels ($names) VALUES ($marks)")->execute(array_values($cols));
            $id = (int)db()->lastInsertId();
            $msg = 'Hostel added.';
        }
        $photoErrors = save_photos($id);
        if ($photoErrors) {
            flash($msg . ' But some photos were skipped: ' . implode(' ', $photoErrors));
            header('Location: hostel.php?id=' . $id); exit;
        }
        flash($msg);
        header('Location: index.php'); exit;
    }
}

$c = default_place();
$owners = is_admin() ? db()->query("SELECT id, username FROM admins WHERE role = 'landlord' ORDER BY username")->fetchAll() : [];
$pageTitle = $id ? 'Edit hostel' : 'Add hostel';
require __DIR__ . '/_header.php';
?>
<h1><?= h($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="flash err" role="alert"><?= h($e) ?></p><?php endforeach; ?>

<form method="post" class="form" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label>Name <input name="name" value="<?= h($h['name']) ?>" required maxlength="120"></label>
  <label>Address or landmark <input name="address" value="<?= h($h['address']) ?>" required maxlength="200"></label>

  <div class="pick">
    <p>Click the map to place the hostel. The coordinates fill in automatically.</p>
    <div id="picker" data-lat="<?= h($c['latitude']) ?>" data-lng="<?= h($c['longitude']) ?>"></div>
  </div>
  <div class="grid2">
    <label>Latitude <input id="latitude" name="latitude" value="<?= h($h['latitude']) ?>" required inputmode="decimal"></label>
    <label>Longitude <input id="longitude" name="longitude" value="<?= h($h['longitude']) ?>" required inputmode="decimal"></label>
  </div>

  <div class="grid2">
    <label>Price per month (KSh) <input name="price_per_month" value="<?= h($h['price_per_month']) ?>" required inputmode="numeric"></label>
    <label>Room type
      <select name="room_type"><?php foreach ($types as $t): ?>
        <option <?= $h['room_type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Rooms free <input name="available_rooms" value="<?= h($h['available_rooms']) ?>" inputmode="numeric"></label>
    <label>Contact phone <input name="phone" value="<?= h($h['phone']) ?>" maxlength="30"></label>
    <?php if (is_admin()): ?>
      <label>Rating (0 to 5) <input name="rating" value="<?= h($h['rating']) ?>" inputmode="decimal"></label>
      <label>Number of reviews <input name="reviews_count" value="<?= h($h['reviews_count']) ?>" inputmode="numeric"></label>
      <label>Owner
        <select name="owner_id">
          <option value="">No landlord (managed by admin)</option>
          <?php foreach ($owners as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (string)$h['owner_id'] === (string)$o['id'] ? 'selected' : '' ?>><?= h($o['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
  </div>
  <?php if (is_admin()): ?><p class="hint">Rating and review count update automatically when approved reviews change.</p><?php endif; ?>
  <label>Amenities, separated by commas <input name="amenities" value="<?= h($h['amenities']) ?>" placeholder="WiFi, Water, Security"></label>

  <label>Add photos (JPG, PNG or WebP, up to 3 MB each, 8 per hostel)
    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
  </label>

  <div class="row"><button class="btn primary">Save hostel</button><a class="btn" href="index.php">Cancel</a></div>
</form>

<?php if ($id):
  $ph = db()->prepare('SELECT id, filename FROM hostel_photos WHERE hostel_id = ? ORDER BY id');
  $ph->execute([$id]);
  $photos = $ph->fetchAll();
  if ($photos): ?>
<section>
  <div class="sechead"><h2>Photos</h2></div>
  <div class="photogrid">
    <?php foreach ($photos as $p): ?>
    <figure>
      <img src="<?= h((PHOTO_BASE ? PHOTO_BASE . '/' : '../') . UPLOAD_URL . '/' . $p['filename']) ?>" alt="Hostel photo">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete_photo">
        <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
        <button class="btn danger">Remove</button>
      </form>
    </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="../assets/js/picker.js"></script>
<?php require __DIR__ . '/_footer.php'; ?>
