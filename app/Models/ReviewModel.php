<?php
declare(strict_types=1);

class ReviewModel
{
    public static function forCamping(int $campingId): array
    {
        $stmt = db()->prepare('SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.camping_id = ? ORDER BY r.created_at DESC');
        $stmt->execute([$campingId]);

        return $stmt->fetchAll();
    }

    public static function create(int $userId, int $campingId, int $rating, string $comment, ?string $mediaType, ?string $mediaPath): void
    {
        $stmt = db()->prepare('INSERT INTO reviews (user_id, camping_id, rating, comment, media_type, media_path) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $campingId, $rating, clean($comment, 2000), $mediaType, $mediaPath]);
    }
}
