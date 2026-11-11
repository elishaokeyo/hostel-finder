<?php $pending = is_admin() ? (int)db()->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn() : 0; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><text y='20' font-size='20'>🏠</text></svg>">
  <title><?= h($pageTitle ?? 'Dashboard') ?> | Hostel admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
  <header class="adminbar">
    <strong><?= is_admin() ? 'Hostel admin' : 'Landlord portal' ?> <span class="who">(<?= h($_SESSION['admin'] ?? '') ?>)</span></strong>
    <nav>
      <a href="index.php">Dashboard</a>
      <?php if (is_admin()): ?>
        <a href="reviews.php">Reviews<?= $pending ? ' <span class="badge">' . $pending . '</span>' : '' ?></a>
        <a href="users.php">Landlords</a>
      <?php endif; ?>
      <a href="../index.php" target="_blank" rel="noopener">View site</a>
      <a href="login.php?logout=1">Log out</a>
    </nav>
  </header>
  <main class="admin">
    <?php if (!empty($_SESSION['flash'])): ?>
      <p class="flash" role="status"><?= h($_SESSION['flash']) ?></p>
    <?php unset($_SESSION['flash']);
    endif; ?>