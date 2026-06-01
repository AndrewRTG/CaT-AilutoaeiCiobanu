<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core.php';
require_once __DIR__ . '/../config/oauth.php';

function github_request(string $url, array $headers = [], ?array $postData = null): array
{
    $ch = curl_init($url);

    $defaultHeaders = [
        'Accept: application/json',
        'User-Agent: CaT-Camping-App',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $message = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('GitHub request failed: ' . $message);
    }

    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $data = json_decode((string) $response, true);

    if ($status < 200 || $status >= 300 || !is_array($data)) {
        throw new RuntimeException('GitHub a returnat un raspuns invalid.');
    }

    return $data;
}

function redirect_with_error(string $message): void
{
    header('Location: ../index.php?page=auth&error=' . urlencode($message));
    exit;
}

try {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $savedState = $_COOKIE['cat_oauth_state'] ?? '';

    setcookie('cat_oauth_state', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (!$code || !$state || !$savedState || !hash_equals($savedState, $state)) {
        redirect_with_error('Autentificare GitHub invalida.');
    }

    $tokenPayload = github_request('https://github.com/login/oauth/access_token', [], [
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GITHUB_REDIRECT_URI,
    ]);

    $githubAccessToken = $tokenPayload['access_token'] ?? '';

    if (!$githubAccessToken) {
        redirect_with_error('Nu am putut obtine tokenul GitHub.');
    }

    $githubUser = github_request('https://api.github.com/user', [
        'Authorization: Bearer ' . $githubAccessToken,
    ]);

    $githubId = (string) ($githubUser['id'] ?? '');
    $name = $githubUser['name'] ?: ($githubUser['login'] ?? 'GitHub user');
    $email = $githubUser['email'] ?? null;

    if (!$email) {
        $emails = github_request('https://api.github.com/user/emails', [
            'Authorization: Bearer ' . $githubAccessToken,
        ]);

        foreach ($emails as $item) {
            if (!empty($item['primary']) && !empty($item['verified']) && !empty($item['email'])) {
                $email = $item['email'];
                break;
            }
        }
    }

    if (!$githubId) {
        redirect_with_error('Nu am putut identifica utilizatorul GitHub.');
    }

    $userId = UserModel::findOrCreateOAuthUser('github', $githubId, $name, $email);
    $internalToken = AuthTokenModel::createForUser($userId);

    header('Location: ../index.php?page=auth&token=' . urlencode($internalToken));
    exit;
} catch (Throwable $error) {
    redirect_with_error('Eroare GitHub OAuth: ' . $error->getMessage());
}