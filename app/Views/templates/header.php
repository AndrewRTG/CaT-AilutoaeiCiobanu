<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title>CaT - Camping Info Web Tool</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <script type="application/json" id="bootstrap-data"><?= json_encode(['user' => $user, 'page' => $page, 'camping_id' => $campingId ?? null, 'oauth_error' => $oauthError], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body data-page="<?= e($page) ?>">
  <header class="topbar">
    <div class="shell nav">
      <button class="mobile-menu" type="button" id="mobileMenu" aria-label="Meniu">☰</button>
      <a class="brand" href="index.php?page=home">
        <span class="brand-mark">CaT</span>
        <span class="brand-text">
          <strong>Camping Info</strong>
          <span>rezervari, harta, recenzii</span>
        </span>
      </a>

      <nav class="nav-links" aria-label="Navigatie principala">
        <a class="nav-btn <?= $page === 'home' ? 'active' : '' ?>" href="index.php?page=home">Acasa</a>
        <a class="nav-btn <?= $page === 'campings' || $page === 'detail' ? 'active' : '' ?>" href="index.php?page=campings">Campinguri</a>
        <a class="nav-btn <?= $page === 'map' ? 'active' : '' ?>" href="index.php?page=map">Harta</a>
        <a class="nav-btn <?= $page === 'community' ? 'active' : '' ?>" href="index.php?page=community">Comunitate</a>
        <a class="nav-btn <?= $page === 'admin' ? 'active' : '' ?>" href="index.php?page=admin">Admin</a>
      </nav>

      <div class="actions">
        <?php if ($user): ?>
          <span class="user-pill"><?= e($user['name']) ?> · <?= e($user['role']) ?></span>
          <a class="btn btn-ghost" href="auth/logout.php">Logout</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="index.php?page=auth">Intra in cont</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="shell mobile-panel" id="mobilePanel">
      <nav class="nav-links" aria-label="Navigatie mobila">
        <a class="nav-btn" href="index.php?page=home">Acasa</a>
        <a class="nav-btn" href="index.php?page=campings">Campinguri</a>
        <a class="nav-btn" href="index.php?page=map">Harta</a>
        <a class="nav-btn" href="index.php?page=community">Comunitate</a>
        <a class="nav-btn" href="index.php?page=admin">Admin</a>
        <a class="nav-btn" href="index.php?page=auth">Intra in cont</a>
      </nav>
    </div>
  </header>

  <main>
