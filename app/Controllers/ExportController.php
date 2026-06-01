<?php
declare(strict_types=1);

class ExportController
{
    public static function handle(): void
    {
        require_admin();

        $format = $_GET['format'] ?? 'json';
        $entity = $_GET['entity'] ?? 'campings';
        $rows = self::rows($entity);

        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="cat-' . $entity . '.json"');
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($format === 'csv') {
            self::csv($rows, $entity);
        }

        if ($format === 'svg') {
            header('Content-Type: image/svg+xml');
            header('Content-Disposition: attachment; filename="cat-statistici.svg"');
            echo self::svg();
            exit;
        }

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="cat-raport.pdf"');
            echo self::pdf();
            exit;
        }

        json_response(['error' => 'Format invalid.'], 400);
    }

    private static function rows(string $entity): array
    {
        if ($entity === 'users') {
            return UserModel::exportRows();
        }
        if ($entity === 'reservations') {
            return ReservationModel::exportRows();
        }

        return CampingModel::exportRows();
    }

    private static function csv(array $rows, string $entity): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cat-' . $entity . '.csv"');

        $out = fopen('php://output', 'w');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        exit;
    }

    private static function svg(): string
    {
        $zones = StatsModel::dashboard()['zones'];
        $max = 1;
        foreach ($zones as $zone) {
            $max = max($max, (int) $zone['total']);
        }

        $bars = '';
        $x = 60;
        foreach ($zones as $zone) {
            $height = 130 * ((int) $zone['total'] / $max);
            $bars .= '<rect x="' . $x . '" y="' . (180 - $height) . '" width="46" height="' . $height . '" fill="#2f6b3f"/>';
            $bars .= '<text x="' . ($x - 6) . '" y="215" font-size="12">' . e($zone['zone']) . '</text>';
            $x += 100;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="620" height="250"><rect width="620" height="250" fill="#f7faf2"/><text x="30" y="35" font-size="22">Zone populare CaT</text>' . $bars . '</svg>';
    }

    private static function pdf(): string
    {
        $text = 'CaT raport - campinguri: ' . StatsModel::dashboard()['totals']['campings'];
        $stream = "BT /F1 18 Tf 50 760 Td (" . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text) . ") Tj ET";
        $pdf = "%PDF-1.4\n";
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            '5 0 obj << /Length ' . strlen($stream) . " >> stream\n" . $stream . "\nendstream endobj",
        ];
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }
}
