<?php
declare(strict_types=1);

class AdminUserController
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_permission('manage_users');
            json_response(['users' => UserModel::all()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            require_permission('manage_users');
            $data = body_json();
            UserModel::updateRoleAndStatus((int) ($_GET['id'] ?? 0), $data['role'] ?? 'member', $data['status'] ?? 'active');
            json_response(['ok' => true]);
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }
}
