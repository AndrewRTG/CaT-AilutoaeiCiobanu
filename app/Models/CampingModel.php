<?php
declare(strict_types=1);

class CampingModel
{
    public static function all(string $search = '', string $zone = 'all'): array
    {
        $search = '%' . strtolower(clean($search, 80)) . '%';
        $zone = clean($zone, 80);

        if ($zone && $zone !== 'all') {
            $stmt = db()->prepare('SELECT c.*, COUNT(r.id) AS review_count FROM campings c LEFT JOIN reviews r ON r.camping_id = c.id WHERE (LOWER(c.name) LIKE ? OR LOWER(c.description) LIKE ? OR LOWER(c.zone) LIKE ?) AND c.zone = ? GROUP BY c.id ORDER BY c.rating DESC');
            $stmt->execute([$search, $search, $search, $zone]);
        } else {
            $stmt = db()->prepare('SELECT c.*, COUNT(r.id) AS review_count FROM campings c LEFT JOIN reviews r ON r.camping_id = c.id WHERE LOWER(c.name) LIKE ? OR LOWER(c.description) LIKE ? OR LOWER(c.zone) LIKE ? GROUP BY c.id ORDER BY c.rating DESC');
            $stmt->execute([$search, $search, $search]);
        }

        return array_map([self::class, 'payload'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT c.*, COUNT(r.id) AS review_count FROM campings c LEFT JOIN reviews r ON r.camping_id = c.id WHERE c.id = ? GROUP BY c.id');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::payload($row) : null;
    }

    public static function rawFind(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM campings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function zones(): array
    {
        return db()->query('SELECT DISTINCT zone FROM campings ORDER BY zone')->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function save(array $data, ?int $id = null): int
    {
        $name = clean($data['name'] ?? '', 120);
        $facilities = $data['facilities'] ?? '';
        if (is_array($facilities)) {
            $facilities = implode(',', array_map('clean', $facilities));
        }

        $values = [
            $name,
            slug($name . '-' . ($id ?: uniqid())),
            clean($data['zone'] ?? '', 80),
            clean($data['description'] ?? '', 2000),
            (float) ($data['price_per_night'] ?? 0),
            (float) ($data['rating'] ?? 0),
            (float) ($data['latitude'] ?? 0),
            (float) ($data['longitude'] ?? 0),
            clean($data['image_url'] ?? 'https://images.unsplash.com/photo-1504851149312-7a075b496cc7?auto=format&fit=crop&w=1000&q=80', 500),
            (int) ($data['capacity'] ?? 30),
            clean($facilities, 500),
        ];

        if ($id) {
            $values[] = $id;
            db()->prepare('UPDATE campings SET name=?, slug=?, zone=?, description=?, price_per_night=?, rating=?, latitude=?, longitude=?, image_url=?, capacity=?, facilities=? WHERE id=?')->execute($values);
            return $id;
        }

        db()->prepare('INSERT INTO campings (name, slug, zone, description, price_per_night, rating, latitude, longitude, image_url, capacity, facilities) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
        return (int) db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM campings WHERE id = ?')->execute([$id]);
    }

    public static function updateRating(int $id): void
    {
        $stmt = db()->prepare('SELECT AVG(rating) FROM reviews WHERE camping_id = ?');
        $stmt->execute([$id]);
        db()->prepare('UPDATE campings SET rating = ? WHERE id = ?')->execute([round((float) $stmt->fetchColumn(), 1), $id]);
    }

    public static function exportRows(): array
    {
        return db()->query('SELECT id, name, zone, price_per_night, rating, latitude, longitude, capacity, facilities FROM campings')->fetchAll();
    }

    public static function payload(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'zone' => $row['zone'],
            'description' => $row['description'],
            'price_per_night' => (float) $row['price_per_night'],
            'rating' => (float) $row['rating'],
            'latitude' => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'image_url' => $row['image_url'],
            'capacity' => (int) $row['capacity'],
            'facilities' => array_values(array_filter(array_map('trim', explode(',', $row['facilities'] ?? '')))),
            'review_count' => (int) ($row['review_count'] ?? 0),
        ];
    }
}
