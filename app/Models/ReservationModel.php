<?php
declare(strict_types=1);

class ReservationModel
{
    public static function forUser(array $user): array
    {
        if (user_can($user, 'manage_reservations')) {
            return db()->query(
                'SELECT r.*, u.name AS user_name, c.name AS camping_name
                 FROM reservations r
                 JOIN users u ON u.id = r.user_id
                 JOIN campings c ON c.id = r.camping_id
                 ORDER BY r.created_at DESC'
            )->fetchAll();
        }

        $stmt = db()->prepare(
            'SELECT r.*, u.name AS user_name, c.name AS camping_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN campings c ON c.id = r.camping_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$user['id']]);

        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): void
    {
        $campingId = (int) ($data['camping_id'] ?? 0);
        $startDate = clean($data['start_date'] ?? '', 20);
        $endDate = clean($data['end_date'] ?? '', 20);
        $guests = (int) ($data['guests'] ?? 1);

        self::validateReservationData($campingId, $startDate, $endDate, $guests);

        $stmt = db()->prepare(
            'INSERT INTO reservations (user_id, camping_id, start_date, end_date, guests)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $campingId, $startDate, $endDate, $guests]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = db()->prepare('SELECT id FROM reservations WHERE id = ?');
        $stmt->execute([$id]);

        if (!$stmt->fetchColumn()) {
            json_response(['error' => 'Rezervarea nu exista.'], 404);
        }

        db()->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function exportRows(): array
    {
        return db()->query(
            'SELECT r.id, u.name AS user, c.name AS camping, r.start_date, r.end_date, r.guests, r.status
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN campings c ON c.id = r.camping_id'
        )->fetchAll();
    }

    private static function validateReservationData(int $campingId, string $startDate, string $endDate, int $guests): void
    {
        if ($campingId <= 0) {
            json_response(['error' => 'Camping invalid.'], 422);
        }

        if (!self::isValidDate($startDate) || !self::isValidDate($endDate)) {
            json_response(['error' => 'Datele rezervarii sunt invalide.'], 422);
        }

        $today = new DateTimeImmutable('today');
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        if ($start < $today) {
            json_response(['error' => 'Nu poti face rezervari in trecut.'], 422);
        }

        if ($end <= $start) {
            json_response(['error' => 'Data de final trebuie sa fie dupa data de inceput.'], 422);
        }

        if ($guests < 1) {
            json_response(['error' => 'Numarul de persoane trebuie sa fie cel putin 1.'], 422);
        }

        $camping = self::findCamping($campingId);

        if (!$camping) {
            json_response(['error' => 'Campingul selectat nu exista.'], 404);
        }

        if ($guests > (int) $camping['capacity']) {
            json_response(['error' => 'Numarul de persoane depaseste capacitatea campingului.'], 422);
        }

        if (self::hasOverlap($campingId, $startDate, $endDate)) {
            json_response(['error' => 'Exista deja o rezervare activa in aceasta perioada.'], 409);
        }
    }

    private static function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private static function findCamping(int $campingId): ?array
    {
        $stmt = db()->prepare('SELECT id, capacity FROM campings WHERE id = ?');
        $stmt->execute([$campingId]);
        $camping = $stmt->fetch();

        return $camping ?: null;
    }

    private static function hasOverlap(int $campingId, string $startDate, string $endDate): bool
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM reservations
             WHERE camping_id = ?
               AND status IN (?, ?)
               AND start_date < ?
               AND end_date > ?'
        );
        $stmt->execute([$campingId, 'pending', 'confirmed', $endDate, $startDate]);

        return (int) $stmt->fetchColumn() > 0;
    }
}