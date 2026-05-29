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

        foreach ($rows as $row) {
            if (is_array($row) && !empty($row['name'])) {
                CampingModel::save($row);
                $count++;
            }
        }

        json_response(['ok' => true, 'imported' => $count]);
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
        $header = fgetcsv($file);
        while ($header && ($line = fgetcsv($file)) !== false) {
            $rows[] = array_combine($header, $line);
        }
        fclose($file);

        return $rows;
    }
}
