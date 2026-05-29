<?php
declare(strict_types=1);

class AuthTokenModel
{
    private const DAYS_VALID = 7;

    public static function createForUser(int $userId): string
    {
        self::deleteExpired();

        $token = bin2hex(random_bytes(32));
        $hash = self::hash($token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::DAYS_VALID * 24 * 60 * 60);

        $stmt = db()->prepare('INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $hash, $expiresAt]);

        return $token;
    }

    public static function userFromToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        self::deleteExpired();

        $stmt = db()->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.provider, u.status
             FROM auth_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.expires_at >= CURRENT_TIMESTAMP AND u.status = ?'
        );
        $stmt->execute([self::hash($token), 'active']);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function revoke(?string $token): void
    {
        if (!$token) {
            return;
        }

        $stmt = db()->prepare('DELETE FROM auth_tokens WHERE token_hash = ?');
        $stmt->execute([self::hash($token)]);
    }

    public static function deleteExpired(): void
    {
        db()->prepare('DELETE FROM auth_tokens WHERE expires_at < CURRENT_TIMESTAMP')->execute();
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}