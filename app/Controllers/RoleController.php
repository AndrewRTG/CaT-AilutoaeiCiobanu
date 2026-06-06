<?php
declare(strict_types=1);

class RoleController
{
    public static function handle(): void
    {
        require_permission('manage_roles');

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            json_response([
                'roles' => RoleModel::all(),
                'permissions' => self::permissions(),
            ]);
        }

        if ($method === 'POST') {
            $data = body_json();

            $role = RoleModel::create(
                $data['name'] ?? '',
                self::cleanPermissions($data['permissions'] ?? [])
            );

            json_response(['role' => $role], 201);
        }

        if ($method === 'PATCH') {
            $data = body_json();

            $role = RoleModel::update(
                $_GET['slug'] ?? '',
                $data['name'] ?? '',
                self::cleanPermissions($data['permissions'] ?? [])
            );

            json_response(['role' => $role]);
        }

        if ($method === 'DELETE') {
            RoleModel::delete($_GET['slug'] ?? '');
            json_response(['ok' => true]);
        }

        json_response(['error' => 'Metoda nepermisa.'], 405);
    }

    private static function permissions(): array
    {
        return [
            'manage_campings' => 'Gestionare campinguri',
            'manage_reservations' => 'Gestionare rezervari',
            'manage_users' => 'Gestionare utilizatori',
            'view_stats' => 'Vizualizare statistici',
            'import_export' => 'Import / export date',
            'manage_roles' => 'Gestionare roluri',
            'moderate_messages' => 'Moderare mesaje',
        ];
    }

    private static function cleanPermissions($permissions): array
    {
        $allowed = array_keys(self::permissions());

        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_intersect($allowed, array_map('strval', $permissions)));
    }
}