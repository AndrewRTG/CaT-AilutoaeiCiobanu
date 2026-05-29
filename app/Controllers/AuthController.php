<?php
declare(strict_types=1);

class AuthController
{
    public static function login(): void
    {
        $provider = $_GET['provider'] ?? 'demo_user';
        $userId = UserModel::demoUserId($provider === 'demo_admin' ? 'admin' : 'member');
        $token = AuthTokenModel::createForUser($userId);

        header('Location: ../index.php?page=auth&token=' . urlencode($token));
        exit;
    }

    public static function logout(): void
    {
        AuthTokenModel::revoke(bearer_token());
        json_response(['ok' => true]);
    }
}