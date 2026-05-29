<?php
declare(strict_types=1);

const DB_FILE = __DIR__ . '/../storage/cat.sqlite';
const UPLOAD_DIR = __DIR__ . '/../storage/uploads';
const UPLOAD_URL = 'storage/uploads';



header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

require_once __DIR__ . '/Models/UserModel.php';

require_once __DIR__ . '/Models/AuthTokenModel.php';
require_once __DIR__ . '/Models/CampingModel.php';
require_once __DIR__ . '/Models/ReviewModel.php';
require_once __DIR__ . '/Models/MessageModel.php';
require_once __DIR__ . '/Models/ReservationModel.php';
require_once __DIR__ . '/Models/StatsModel.php';
require_once __DIR__ . '/Models/DatabaseModel.php';

require_once __DIR__ . '/Controllers/SessionController.php';
require_once __DIR__ . '/Controllers/CampingController.php';
require_once __DIR__ . '/Controllers/ReviewController.php';
require_once __DIR__ . '/Controllers/MessageController.php';
require_once __DIR__ . '/Controllers/ReservationController.php';
require_once __DIR__ . '/Controllers/AdminUserController.php';
require_once __DIR__ . '/Controllers/StatsController.php';
require_once __DIR__ . '/Controllers/ImportController.php';
require_once __DIR__ . '/Controllers/ExportController.php';
require_once __DIR__ . '/Controllers/AuthController.php';

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

function csrf_token(): string
{
    return '';
}

function check_csrf(): void
{
    return;
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
