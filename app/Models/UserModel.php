<?php
declare(strict_types=1);

class UserModel
{
    public static function current(): ?array
    {
    return AuthTokenModel::userFromToken(bearer_token());
    }

    public static function demoUserId(string $role): int
    {
        $isAdmin = $role === 'admin';
        $providerId = $isAdmin ? 'admin' : 'member';
        $name = $isAdmin ? 'Admin CaT' : 'Maria Pop';
        $email = $isAdmin ? 'admin@cat.local' : 'maria@example.com';

        $stmt = db()->prepare('INSERT OR IGNORE INTO users (provider, provider_id, name, email, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['demo-oauth', $providerId, $name, $email, $isAdmin ? 'admin' : 'member']);

        $stmt = db()->prepare('SELECT id FROM users WHERE provider = ? AND provider_id = ?');
        $stmt->execute(['demo-oauth', $providerId]);

        return (int) $stmt->fetchColumn();
    }

    public static function all(): array
    {
        return db()->query('SELECT id, provider, name, email, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    }

    public static function updateRoleAndStatus(int $id, string $role, string $status): void
    {
        $safeRole = $role === 'admin' ? 'admin' : 'member';
        $safeStatus = $status === 'blocked' ? 'blocked' : 'active';

        db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?')->execute([$safeRole, $safeStatus, $id]);
    }

    public static function exportRows(): array
    {
        return db()->query('SELECT id, name, email, role, status FROM users')->fetchAll();
    }
}
