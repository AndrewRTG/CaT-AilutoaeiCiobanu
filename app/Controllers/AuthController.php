<?php
declare(strict_types=1);

class AuthController
{
    public static function logout(): void
    {
        AuthTokenModel::revoke(bearer_token());
        json_response(['ok' => true]);
    }
}