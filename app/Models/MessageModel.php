<?php
declare(strict_types=1);

class MessageModel
{
    public static function latest(): array
    {
        return db()->query('SELECT m.*, u.name AS user_name, c.name AS camping_name FROM messages m JOIN users u ON u.id = m.user_id LEFT JOIN campings c ON c.id = m.camping_id ORDER BY m.created_at DESC LIMIT 30')->fetchAll();
    }

    public static function forCamping(int $campingId): array
    {
        $stmt = db()->prepare('SELECT m.*, u.name AS user_name, c.name AS camping_name FROM messages m JOIN users u ON u.id = m.user_id LEFT JOIN campings c ON c.id = m.camping_id WHERE m.camping_id = ? ORDER BY m.created_at DESC');
        $stmt->execute([$campingId]);

        return $stmt->fetchAll();
    }

    public static function create(int $userId, ?int $campingId, string $content, ?string $mediaType, ?string $mediaPath): void
    {
        $stmt = db()->prepare('INSERT INTO messages (user_id, camping_id, content, media_type, media_path) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $campingId, clean($content, 2000), $mediaType, $mediaPath]);
    }
}
