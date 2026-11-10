<?php
require __DIR__ . '/auth.php';
require_login();
require_admin();
$me = (int)$_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);

  if ($action === 'create') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $dup = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
    $dup->execute([$user]);
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $user)) flash('Username must be 3 to 50 letters, numbers or underscores.');
    elseif (strlen($pass) < 8)                         flash('Password must be at least 8 characters.');
    elseif ($dup->fetchColumn())                       flash('That username is taken. Choose another.');
    else {
      db()->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, 'landlord')")
        ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
      flash("Landlord account \"$user\" created. Share the username and password with them.");
    }
  } elseif ($id === $me) {
    flash('You can\'t change your own account here.');
  } elseif ($action === 'toggle') {
    db()->prepare('UPDATE admins SET active = 1 - active WHERE id = ?')->execute([$id]);
    flash('Account updated.');
  } elseif ($action === 'reset') {
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 8) flash('New password must be at least 8 characters.');
    else {
      db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
      flash('Password changed.');
    }
  } elseif ($action === 'delete') {
    db()->prepare('UPDATE hostels SET owner_id = NULL WHERE owner_id = ?')->execute([$id]);
    db()->prepare("DELETE FROM admins WHERE id = ? AND role = 'landlord'")->execute([$id]);
    flash('Landlord deleted. Their hostels stay on the site and are now managed by admin.');
  }
  header('Location: users.php');
  exit;
}

$users = db()->query('SELECT a.*, (SELECT COUNT(*) FROM hostels WHERE owner_id = a.id) AS hostels
                      FROM admins a ORDER BY a.role, a.username')->fetchAll();
$pageTitle = 'Landlords';
require __DIR__ . '/_header.php';
?>
<h1>Landlords</h1>
<!-- <p class="hint">Landlords log in at the same page as you, but can only add and edit their own hostels and update rooms free.</p> -->

<form method="post" class="form">
  <h2>Create a landlord account</h2>
  <?= csrf_field() ?><input type="hidden" name="action" value="create">
  <div class="grid2">
    <label>Username <input name="username" required maxlength="50" autocomplete="off"></label>
    <label>Temporary password (8+ characters) <input name="password" type="text" required minlength="8" autocomplete="off"></label>
  </div>
  <div class="row"><button class="btn primary">Create account</button></div>
</form>

<div class="sechead">
  <h2>Accounts</h2>
</div>
<div class="tablewrap">
  <table>
    <thead>
      <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Hostels</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): $self = (int)$u['id'] === $me; ?>
        <tr>
          <td><?= h($u['username']) ?><?= $self ? ' <small>(you)</small>' : '' ?></td>
          <td><?= h($u['role']) ?></td>
          <td><?= (int)$u['hostels'] ?></td>
          <td><?= $u['active'] ? 'Active' : '<span class="off">Disabled</span>' ?></td>
          <td class="actions">
            <?php if (!$self): ?>
              <form method="post" class="inline">
                <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <input type="text" name="password" placeholder="New password" minlength="8" required autocomplete="off" aria-label="New password for <?= h($u['username']) ?>">
                <button class="btn">Set</button>
              </form>
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn"><?= $u['active'] ? 'Disable' : 'Enable' ?></button>
              </form>
              <?php if ($u['role'] === 'landlord'): ?>
                <form method="post" onsubmit="return confirm('Delete <?= h(addslashes($u['username'])) ?>? Their hostels will stay on the site.')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn danger">Delete</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>