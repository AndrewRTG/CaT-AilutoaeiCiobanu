<?php
declare(strict_types=1);

class UserModel
{
    public static function current(): ?array
    {
    return AuthTokenModel::userFromToken(bearer_token());
    }
        private static function roleForEmail(?string $email): string
    {
        $normalizedEmail = strtolower(trim((string) $email));

        foreach (ADMIN_EMAILS as $adminEmail) {
            if ($normalizedEmail === strtolower(trim((string) $adminEmail))) {
                return 'admin';
            }
        }

        return 'member';
    }



    public static function authenticateLocalUser(string $email, string $password): int
    {
        $email = strtolower(trim($email));

        $stmt = db()->prepare(
            'SELECT id, password_hash, status
            FROM users
            WHERE provider = ? AND provider_id = ?'
        );
        $stmt->execute(['local', $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            json_response(['error' => 'Email sau parola incorecta.'], 401);
        }

        if ($user['status'] !== 'active') {
            json_response(['error' => 'Contul este blocat.'], 403);
        }

        $role = self::roleForEmail($email);

        if ($role === 'admin') {
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['admin', $user['id']]);
        }

        return (int) $user['id'];
    }
    
        public static function findOrCreateOAuthUser(string $provider, string $providerId, string $name, ?string $email): int
    {
        $stmt = db()->prepare('SELECT id FROM users WHERE provider = ? AND provider_id = ?');
        $stmt->execute([$provider, $providerId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
           $role = self::roleForEmail($email);

            db()->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?')
                ->execute([$name, $email, $role, $existingId]);

            return (int) $existingId;
        }

    $stmt = db()->prepare(
        'INSERT INTO users (provider, provider_id, name, email, role, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $role = self::roleForEmail($email);
    $stmt->execute([$provider, $providerId, $name, $email, $role, 'active']);

    return (int) db()->lastInsertId();
}

    public static function all(): array
    {
        return db()->query('SELECT id, provider, name, email, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    }

    public static function updateRoleAndStatus(int $id, string $role, string $status): void
    {
        $safeRole = RoleModel::exists($role) ? $role : 'member';
        $safeStatus = $status === 'blocked' ? 'blocked' : 'active';

        db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?')
            ->execute([$safeRole, $safeStatus, $id]);
    }

    public static function exportRows(): array
    {
        return db()->query('SELECT id, name, email, role, status FROM users')->fetchAll();
    }

    
}
