<?php
require __DIR__ . '/auth.php';

if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }

$setup = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0;
$error = '';

function sign_in(array $u): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['admin'] = $u['username'];
    $_SESSION['role'] = $u['role'];
    header('Location: index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($setup) {
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $user)) {
            $error = 'Username must be 3 to 50 letters, numbers or underscores.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            db()->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, 'admin')")
                ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
            sign_in(['id' => db()->lastInsertId(), 'username' => $user, 'role' => 'admin']);
        }
    } else {
        $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
        $st->execute([$user]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password_hash'])) {
            if (!$row['active']) $error = 'This account has been disabled. Contact the administrator.';
            else sign_in($row);
        } else {
            $error = 'Wrong username or password.';
        }
    }
}
$pageTitle = $setup ? 'Create admin account' : 'Log in';
$wa = (!$setup && preg_match('/^\d{8,15}$/', ADMIN_WHATSAPP)) ? ADMIN_WHATSAPP : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> | Hostel admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="loginpage">
<div class="loginwrap">
  <form method="post" class="loginbox">
    <h1><?= h($pageTitle) ?></h1>
    <?php if ($setup): ?>
      <p class="hint">No admin exists yet. Choose the username and password you'll use to manage the site.</p>
    <?php else: ?>
      <p class="hint">Administrators and landlords log in here.</p>
    <?php endif; ?>
    <?php if ($error): ?><p class="flash err" role="alert"><?= h($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label>Username <input name="username" required autofocus autocomplete="username"></label>
    <label>Password <input name="password" type="password" required autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"></label>
    <button class="primary"><?= $setup ? 'Create account' : 'Log in' ?></button>
  </form>

  <?php if ($wa): ?>
  <section class="reqbox">
    <details>
      <summary>
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
        <span><strong>New landlord?</strong><small>Request an account on WhatsApp</small></span>
      </summary>
      <form id="reqForm" data-wa="<?= h($wa) ?>">
        <p class="hint">Fill in your details and WhatsApp opens with your request ready to send. Never put your password in the message. The administrator will reply with a temporary one.</p>
        <label>Your full name <input name="fullname" required maxlength="80" autocomplete="name"></label>
        <label>Hostel name <input name="hostel" required maxlength="100"></label>
        <label>Hostel location or area <input name="area" required maxlength="120"></label>
        <label>Your phone number <input name="phone" type="tel" required maxlength="20" autocomplete="tel"></label>
        <label>Preferred username <input name="username" required maxlength="50" pattern="[A-Za-z0-9_]{3,50}" title="3 to 50 letters, numbers or underscores"></label>
        <div class="preview"><small>Your message</small><pre id="preview"></pre></div>
        <button class="wa">Send request on WhatsApp</button>
      </form>
    </details>
  </section>
  <?php endif; ?>
</div>

<script>
(() => {
  const f = document.getElementById('reqForm');
  if (!f) return;
  const val = (n, fallback) => f.elements[n].value.trim() || fallback;
  const message = fb => [
    'Hello, I would like a landlord account on Hostel Finder.',
    '',
    'Name: ' + val('fullname', fb),
    'Hostel name: ' + val('hostel', fb),
    'Hostel location: ' + val('area', fb),
    'Phone: ' + val('phone', fb),
    'Preferred username: ' + val('username', fb),
    '',
    'Please create my account. Thank you.'
  ].join('\n');
  const preview = document.getElementById('preview');
  const update = () => { preview.textContent = message('...'); };
  f.addEventListener('input', update);
  update();
  f.addEventListener('submit', e => {
    e.preventDefault();
    window.open('https://wa.me/' + f.dataset.wa + '?text=' + encodeURIComponent(message('')), '_blank', 'noopener');
  });
})();
</script>
</body>
</html>
