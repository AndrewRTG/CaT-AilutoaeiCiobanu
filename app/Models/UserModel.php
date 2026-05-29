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

        public static function createLocalUser(string $name, string $email, string $password): int
    {
        $email = strtolower(trim($email));

        $stmt = db()->prepare('SELECT id FROM users WHERE provider = ? AND provider_id = ?');
        $stmt->execute(['local', $email]);

        if ($stmt->fetchColumn()) {
            json_response(['error' => 'Exista deja un cont cu acest email.'], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = db()->prepare(
            'INSERT INTO users (provider, provider_id, name, email, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(['local', $email, $name, $email, $passwordHash, 'member', 'active']);

        return (int) db()->lastInsertId();
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

        return (int) $user['id'];
    }
    
        public static function findOrCreateOAuthUser(string $provider, string $providerId, string $name, ?string $email): int
    {
        $stmt = db()->prepare('SELECT id FROM users WHERE provider = ? AND provider_id = ?');
        $stmt->execute([$provider, $providerId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                ->execute([$name, $email, $existingId]);

            return (int) $existingId;
        }

    $stmt = db()->prepare(
        'INSERT INTO users (provider, provider_id, name, email, role, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$provider, $providerId, $name, $email, 'member', 'active']);

    return (int) db()->lastInsertId();
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
