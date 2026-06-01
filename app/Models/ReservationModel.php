<?php
declare(strict_types=1);

class ReservationModel
{
    public static function forUser(array $user): array
    {
        if ($user['role'] === 'admin') {
            return db()->query('SELECT r.*, u.name AS user_name, c.name AS camping_name FROM reservations r JOIN users u ON u.id = r.user_id JOIN campings c ON c.id = r.camping_id ORDER BY r.created_at DESC')->fetchAll();
        }

        $stmt = db()->prepare('SELECT r.*, u.name AS user_name, c.name AS camping_name FROM reservations r JOIN users u ON u.id = r.user_id JOIN campings c ON c.id = r.camping_id WHERE r.user_id = ? ORDER BY r.created_at DESC');
        $stmt->execute([$user['id']]);

        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): void
    {
        $stmt = db()->prepare('INSERT INTO reservations (user_id, camping_id, start_date, end_date, guests) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            (int) $data['camping_id'],
            clean($data['start_date'] ?? '', 20),
            clean($data['end_date'] ?? '', 20),
            (int) ($data['guests'] ?? 1),
        ]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        db()->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function exportRows(): array
    {
        return db()->query('SELECT r.id, u.name AS user, c.name AS camping, r.start_date, r.end_date, r.guests, r.status FROM reservations r JOIN users u ON u.id = r.user_id JOIN campings c ON c.id = r.camping_id')->fetchAll();
    }
}
