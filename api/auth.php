<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metoda nepermisa.'], 405);
}

$data = body_json();
$action = $data['action'] ?? '';

if ($action === 'register') {
    $name = clean($data['name'] ?? '', 120);
    $email = strtolower(clean($data['email'] ?? '', 180));
    $password = (string) ($data['password'] ?? '');
    $confirmPassword = (string) ($data['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        json_response(['error' => 'Completeaza toate campurile.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Email invalid.'], 400);
    }

    if (strlen($password) < 8) {
        json_response(['error' => 'Parola trebuie sa aiba minim 8 caractere.'], 400);
    }

    if ($password !== $confirmPassword) {
        json_response(['error' => 'Parolele nu coincid.'], 400);
    }

    $userId = UserModel::createLocalUser($name, $email, $password);
    $token = AuthTokenModel::createForUser($userId);
    $user = AuthTokenModel::userFromToken($token);

    if ($user) {
        $user['permissions'] = RoleModel::permissionsForRole($user['role'] ?? 'member');
    }

    json_response([
        'token' => $token,
        'user' => $user,
    ]);
}

if ($action === 'login') {
    $email = strtolower(clean($data['email'] ?? '', 180));
    $password = (string) ($data['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(['error' => 'Completeaza emailul si parola.'], 400);
    }

    $userId = UserModel::authenticateLocalUser($email, $password);
    $token = AuthTokenModel::createForUser($userId);
    $user = AuthTokenModel::userFromToken($token);
    if ($user) {
        $user['permissions'] = RoleModel::permissionsForRole($user['role'] ?? 'member');
    }
    
    json_response([
        'token' => $token,
        'user' => $user,
    ]);
}

json_response(['error' => 'Actiune invalida.'], 400);