<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core.php';

$router = new Router(
    ['home', 'campings', 'detail', 'map', 'compare', 'community', 'admin', 'auth'],
    __DIR__ . '/app/Views'
);

$router->dispatch($_GET);
