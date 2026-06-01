<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core.php';
require_once __DIR__ . '/../config/oauth.php';

$state = bin2hex(random_bytes(32));

setcookie('cat_oauth_state', $state, [
    'expires' => time() + 600,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$params = http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'read:user user:email',
    'state' => $state,
]);

header('Location: https://github.com/login/oauth/authorize?' . $params);
exit;