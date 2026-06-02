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
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="cat-' . $entity . '.json"');
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($format === 'csv') {
            self::csv($rows, $entity);
        }

        if ($format === 'svg') {
            header('Content-Type: image/svg+xml; charset=utf-8');
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
        header('Content-Type: text/csv; charset=utf-8');
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
        $stats = StatsModel::dashboard();
        $zones = $stats['zones'];
        $periods = $stats['periods'];

        $zoneBars = self::svgBars($zones, 'zone', 60, 190, '#2f6b3f');
        $periodBars = self::svgBars($periods, 'period', 60, 440, '#d9853b');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="760" height="520" viewBox="0 0 760 520">
            <rect width="760" height="520" fill="#f7faf2"/>
            <text x="40" y="45" font-size="24" font-family="Arial">CaT - statistici camping</text>
            <text x="40" y="85" font-size="16" font-family="Arial">Zone populare dupa numarul de rezervari</text>
            <line x1="40" y1="190" x2="720" y2="190" stroke="#cfd9c4"/>
            ' . $zoneBars . '
            <text x="40" y="335" font-size="16" font-family="Arial">Perioade populare dupa luna rezervarii</text>
            <line x1="40" y1="440" x2="720" y2="440" stroke="#cfd9c4"/>
            ' . $periodBars . '
        </svg>';
    }

    private static function svgBars(array $items, string $labelKey, int $startX, int $baseY, string $color): string
    {
        if (!$items) {
            return '<text x="' . $startX . '" y="' . ($baseY - 45) . '" font-size="14" font-family="Arial" fill="#65705f">Nu exista date.</text>';
        }

        $max = 1;

        foreach ($items as $item) {
            $max = max($max, (int) $item['total']);
        }

        $bars = '';
        $x = $startX;

        foreach ($items as $item) {
            $total = (int) $item['total'];
            $height = 100 * ($total / $max);
            $y = $baseY - $height;
            $label = e((string) $item[$labelKey]);

            $bars .= '<rect x="' . $x . '" y="' . $y . '" width="44" height="' . $height . '" rx="5" fill="' . $color . '"/>';
            $bars .= '<text x="' . ($x + 12) . '" y="' . ($y - 8) . '" font-size="12" font-family="Arial">' . $total . '</text>';
            $bars .= '<text x="' . ($x - 8) . '" y="' . ($baseY + 24) . '" font-size="11" font-family="Arial">' . $label . '</text>';

            $x += 92;
        }

        return $bars;
    }

    private static function pdf(): string
    {
        $stats = StatsModel::dashboard();
        $lines = [];

        $lines[] = 'CaT - Raport administrativ';
        $lines[] = 'Generat la: ' . date('Y-m-d H:i');
        $lines[] = '';
        $lines[] = 'Total campinguri: ' . $stats['totals']['campings'];
        $lines[] = 'Total utilizatori: ' . $stats['totals']['users'];
        $lines[] = 'Total rezervari: ' . $stats['totals']['reservations'];
        $lines[] = 'Total recenzii: ' . $stats['totals']['reviews'];
        $lines[] = 'Total mesaje: ' . $stats['totals']['messages'];
        $lines[] = '';
        $lines[] = 'Zone populare:';

        foreach ($stats['zones'] as $zone) {
            $lines[] = '- ' . $zone['zone'] . ': ' . $zone['total'] . ' rezervari';
        }

        $lines[] = '';
        $lines[] = 'Perioade populare:';

        foreach ($stats['periods'] as $period) {
            $lines[] = '- ' . $period['period'] . ': ' . $period['total'] . ' rezervari';
        }

        $lines[] = '';
        $lines[] = 'Top campinguri:';

        foreach ($stats['popular'] as $camping) {
            $lines[] = '- ' . $camping['name'] . ' (' . $camping['zone'] . '): ' . $camping['reservations'] . ' rezervari, rating ' . $camping['rating'];
        }

        return self::simplePdf($lines);
    }

    private static function simplePdf(array $lines): string
    {
        $escapedLines = [];
        $y = 780;

        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::ascii($line));
            $escapedLines[] = '50 ' . $y . ' Td (' . $safe . ') Tj';
            $y -= 22;
        }

        $stream = "BT /F1 12 Tf\n" . implode("\n", $escapedLines) . "\nET";
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

    private static function ascii(string $text): string
    {
        $map = [
            'ă' => 'a',
            'â' => 'a',
            'î' => 'i',
            'ș' => 's',
            'ş' => 's',
            'ț' => 't',
            'ţ' => 't',
            'Ă' => 'A',
            'Â' => 'A',
            'Î' => 'I',
            'Ș' => 'S',
            'Ş' => 'S',
            'Ț' => 'T',
            'Ţ' => 'T',
        ];

        return strtr($text, $map);
    }
}