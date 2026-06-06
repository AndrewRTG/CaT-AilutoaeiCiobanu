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
                'messages' => (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
            ],
            'zones' => $db->query(
                "SELECT c.zone, COUNT(r.id) AS total
                 FROM campings c
                 LEFT JOIN reservations r ON r.camping_id = c.id
                 GROUP BY c.zone
                 ORDER BY total DESC, c.zone ASC
                 LIMIT 8"
            )->fetchAll(),
            'popular' => $db->query(
                "SELECT c.name, c.zone, COUNT(r.id) AS reservations, c.rating
                 FROM campings c
                 LEFT JOIN reservations r ON r.camping_id = c.id
                 GROUP BY c.id
                 ORDER BY reservations DESC, c.rating DESC
                 LIMIT 5"
            )->fetchAll(),
            'periods' => $db->query(
                "SELECT substr(start_date, 1, 7) AS period, COUNT(*) AS total
                 FROM reservations
                 GROUP BY substr(start_date, 1, 7)
                 ORDER BY total DESC, period ASC
                 LIMIT 6"
            )->fetchAll(),
            'statuses' => $db->query(
                "SELECT status, COUNT(*) AS total
                 FROM reservations
                 GROUP BY status
                 ORDER BY total DESC"
            )->fetchAll(),
        ];
    }
}