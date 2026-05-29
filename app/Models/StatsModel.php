<?php
declare(strict_types=1);

class StatsModel
{
    public static function dashboard(): array
    {
        $db = db();

        return [
            'totals' => [
                'campings' => (int) $db->query('SELECT COUNT(*) FROM campings')->fetchColumn(),
                'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'reservations' => (int) $db->query('SELECT COUNT(*) FROM reservations')->fetchColumn(),
                'reviews' => (int) $db->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
            ],
            'zones' => $db->query('SELECT c.zone, COUNT(r.id) AS total FROM campings c LEFT JOIN reservations r ON r.camping_id = c.id GROUP BY c.zone ORDER BY total DESC')->fetchAll(),
            'popular' => $db->query('SELECT c.name, c.zone, COUNT(r.id) AS reservations, c.rating FROM campings c LEFT JOIN reservations r ON r.camping_id = c.id GROUP BY c.id ORDER BY reservations DESC, c.rating DESC LIMIT 5')->fetchAll(),
        ];
    }
}
