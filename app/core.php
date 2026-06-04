<?php
declare(strict_types=1);

const DB_FILE = __DIR__ . '/../storage/cat.sqlite';
const UPLOAD_DIR = __DIR__ . '/../storage/uploads';
const UPLOAD_URL = 'storage/uploads';
$configFile = __DIR__ . '/../config/oauth.php';

spl_autoload_register(function (string $class): void {
    $paths = [
        __DIR__ . '/Models/' . $class . '.php',
        __DIR__ . '/Controllers/' . $class . '.php',
        __DIR__ . '/Core/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

if (is_file($configFile)) {
    require_once $configFile;
}

if (!defined('ADMIN_EMAILS')) {
    define('ADMIN_EMAILS', []);
}



header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

function db(): PDO
{
    return DatabaseModel::connect();
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body_json(): array
{
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function clean($value, int $max = 1000): string
{
    $text = trim(strip_tags((string) $value));
    $text = preg_replace('/\s+/u', ' ', $text) ?: $text;

    return mb_substr($text, 0, $max);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function slug(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'camping-' . time();
}



function current_user(): ?array
{
    return AuthTokenModel::userFromToken(bearer_token());
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Trebuie sa fii autentificat.'], 401);
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        json_response(['error' => 'Ai nevoie de rol admin.'], 403);
    }

    return $user;
}

function save_upload(string $field): array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['type' => null, 'path' => null];
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 20 * 1024 * 1024) {
        json_response(['error' => 'Fisier invalid sau prea mare.'], 400);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $types = [
        'jpg' => 'photo',
        'jpeg' => 'photo',
        'png' => 'photo',
        'webp' => 'photo',
        'mp3' => 'audio',
        'wav' => 'audio',
        'mp4' => 'video',
        'webm' => 'video',
    ];

    if (!isset($types[$ext])) {
        json_response(['error' => 'Sunt acceptate doar foto, audio si video.'], 400);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $name = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name);

    return ['type' => $types[$ext], 'path' => UPLOAD_URL . '/' . $name];
}


function save_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        json_response(['error' => 'Imagine invalida sau prea mare. Maxim 5 MB.'], 400);
    }

    $mime = '';

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        json_response(['error' => 'Sunt acceptate doar imagini JPG, PNG sau WEBP.'], 400);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $name = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $destination = UPLOAD_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        json_response(['error' => 'Imaginea nu a putut fi salvata.'], 500);
    }

    return UPLOAD_URL . '/' . $name;
}

function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

db();
