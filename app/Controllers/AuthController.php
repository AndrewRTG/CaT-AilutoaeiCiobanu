<?php
declare(strict_types=1);

class AuthController
{
    public static function login(): void
    {
        $provider = $_GET['provider'] ?? 'demo_user';
        $_SESSION['user_id'] = UserModel::demoUserId($provider === 'demo_admin' ? 'admin' : 'member');
        csrf_token();

        header('Location: ../index.php?page=campings');
        exit;
    }

    public static function logout(): void
    {
        session_destroy();

        header('Location: ../index.php?page=home');
        exit;
    }
}
