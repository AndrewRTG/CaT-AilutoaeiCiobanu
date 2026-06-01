<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core.php';

$allowedPages = ['home', 'campings', 'detail', 'map', 'compare', 'community', 'admin', 'auth'];

$page = $_GET['page'] ?? 'home';
if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$campingId = isset($_GET['id']) ? (int) $_GET['id'] : null;

$user = null;
$oauthError = $_GET['error'] ?? '';

require __DIR__ . '/app/Views/templates/header.php';
require __DIR__ . '/app/Views/pages/' . $page . '.php';
require __DIR__ . '/app/Views/templates/footer.php';