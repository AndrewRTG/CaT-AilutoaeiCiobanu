<?php
declare(strict_types=1);

class ImportController
{
    public static function handle(): void
    {
        require_admin();

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'Alege un fisier CSV sau JSON.'], 400);
        }

        $rows = self::readRows($_FILES['file']['tmp_name'], strtolower($_FILES['file']['name']));
        $count = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $data = self::normalizeCampingRow($row);

            if ($data['name'] === '') {
                continue;
            }

            if ($data['description'] === '') {
                $errors[] = 'Randul ' . ($index + 1) . ': descriere lipsa.';
                continue;
            }

            if (self::campingExists($data['name'], $data['zone'])) {
                $errors[] = 'Randul ' . ($index + 1) . ': camping deja existent.';
                continue;
            }


            try {
                CampingModel::save($data);
                $count++;
            } catch (Throwable $error) {
                $errors[] = 'Randul ' . ($index + 1) . ': ' . $error->getMessage();
            }
        }

        json_response([
            'ok' => true,
            'imported' => $count,
            'errors' => $errors,
        ]);
    }

    private static function readRows(string $path, string $name): array
    {
        if (substr($name, -5) === '.json') {
            $data = json_decode((string) file_get_contents($path), true);

            return is_array($data) ? ($data['campings'] ?? $data) : [];
        }

        if (substr($name, -4) !== '.csv') {
            return [];
        }

        $rows = [];
        $file = fopen($path, 'r');

        if (!$file) {
            return [];
        }

        $header = fgetcsv($file);

        if (!$header) {
            fclose($file);
            return [];
        }

        $header = array_map([self::class, 'normalizeKey'], $header);

        while (($line = fgetcsv($file)) !== false) {
            if (count($line) < count($header)) {
                $line = array_pad($line, count($header), '');
            }

            if (count($line) > count($header)) {
                $line = array_slice($line, 0, count($header));
            }

            $rows[] = array_combine($header, $line);
        }

        fclose($file);

        return $rows;
    }

    private static function normalizeCampingRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[self::normalizeKey((string) $key)] = $value;
        }

        $name = self::first($normalized, ['name', 'nume', 'titlu']);
        $zone = self::first($normalized, ['zone', 'zona', 'region', 'regiune']);
        $description = self::first($normalized, ['description', 'descriere', 'desc']);
        $price = self::first($normalized, ['price_per_night', 'price', 'pret', 'pret_noapte']);
        $latitude = self::first($normalized, ['latitude', 'latitudine', 'lat']);
        $longitude = self::first($normalized, ['longitude', 'longitudine', 'lng', 'lon']);
        $capacity = self::first($normalized, ['capacity', 'capacitate']);
        $facilities = self::first($normalized, ['facilities', 'facilitati']);
        $imageUrl = self::first($normalized, ['image_url', 'imagine', 'poza', 'image']);

        return [
            'name' => $name,
            'zone' => $zone !== '' ? $zone : 'Necunoscuta',
            'description' => $description,
            'price_per_night' => $price !== '' ? $price : 1,
            'latitude' => $latitude !== '' ? $latitude : 45.9432,
            'longitude' => $longitude !== '' ? $longitude : 24.9668,
            'capacity' => $capacity !== '' ? $capacity : 30,
            'facilities' => $facilities,
            'image_url' => $imageUrl,
        ];
    }

    private static function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = strtr($key, [
            'ă' => 'a',
            'â' => 'a',
            'î' => 'i',
            'ș' => 's',
            'ş' => 's',
            'ț' => 't',
            'ţ' => 't',
        ]);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?: $key;

        return trim($key, '_');
    }

    private static function first(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private static function campingExists(string $name, string $zone): bool
        {
            $stmt = db()->prepare(
                'SELECT COUNT(*)
                FROM campings
                WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
                AND LOWER(TRIM(zone)) = LOWER(TRIM(?))'
            );

            $stmt->execute([$name, $zone]);

            return (int) $stmt->fetchColumn() > 0;
        }
}