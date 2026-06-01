<?php
declare(strict_types=1);

class SessionController
{
    public static function handle(): void
    {
        json_response([
            'user' => current_user(),
        ]);
    }
}