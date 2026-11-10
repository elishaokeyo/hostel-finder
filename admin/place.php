<?php
require __DIR__ . '/auth.php';
require_login();
require_admin();

$p = ['name' => '', 'latitude' => '', 'longitude' => '', 'is_default' => 0];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $st = db()->prepare('SELECT * FROM places WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('Place not found.'); header('Location: index.php'); exit; }
    $p = array_merge($p, $row);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p['name'] = trim($_POST['name'] ?? '');
    $p['latitude'] = trim($_POST['latitude'] ?? '');
    $p['longitude'] = trim($_POST['longitude'] ?? '');
    $p['is_default'] = isset($_POST['is_default']) ? 1 : 0;

    if ($p['name'] === '' || mb_strlen($p['name']) > 120) $errors[] = 'Enter a name (up to 120 characters).';
    if (!is_numeric($p['latitude'])  || abs($p['latitude'])  > 90)  $errors[] = 'Latitude must be a number between -90 and 90. Click the map to set it.';
    if (!is_numeric($p['longitude']) || abs($p['longitude']) > 180) $errors[] = 'Longitude must be a number between -180 and 180. Click the map to set it.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        if ($p['is_default']) $pdo->exec('UPDATE places SET is_default = 0');
        if ($id) {
            $pdo->prepare('UPDATE places SET name=?, latitude=?, longitude=?, is_default=? WHERE id=?')
                ->execute([$p['name'], $p['latitude'], $p['longitude'], $p['is_default'], $id]);
        } else {
            $pdo->prepare('INSERT INTO places (name, latitude, longitude, is_default) VALUES (?,?,?,?)')
                ->execute([$p['name'], $p['latitude'], $p['longitude'], $p['is_default']]);
        }
        $pdo->commit();
        flash($id ? 'Place updated.' : 'Place added.');
        header('Location: index.php'); exit;
    }
}

$c = default_place();
$pageTitle = $id ? 'Edit place' : 'Add place';
require __DIR__ . '/_header.php';
?>
<h1><?= h($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="flash err" role="alert"><?= h($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label>Place name <input name="name" value="<?= h($p['name']) ?>" required maxlength="120" placeholder="Library, Hospital, Bus stage"></label>
  <div class="pick">
    <p>Click the map to set the place's location.</p>
    <div id="picker" data-lat="<?= h($c['latitude']) ?>" data-lng="<?= h($c['longitude']) ?>"></div>
  </div>
  <div class="grid2">
    <label>Latitude <input id="latitude" name="latitude" value="<?= h($p['latitude']) ?>" required inputmode="decimal"></label>
    <label>Longitude <input id="longitude" name="longitude" value="<?= h($p['longitude']) ?>" required inputmode="decimal"></label>
  </div>
  <label class="check"><input type="checkbox" name="is_default" <?= $p['is_default'] ? 'checked' : '' ?>> Use as the default target (students see distances to this place first)</label>
  <div class="row"><button class="btn primary">Save place</button><a class="btn" href="index.php">Cancel</a></div>
</form>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="../assets/js/picker.js"></script>
<?php require __DIR__ . '/_footer.php'; ?>
