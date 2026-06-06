<?php
declare(strict_types=1);

class ExportController
{
    public static function handle(): void
    {
        require_permission('import_export');

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
        $summaryPage = [
            self::pdfText(46, 800, 'CaT - Raport administrativ', 22, true),
            self::pdfText(46, 778, 'Generat la: ' . date('Y-m-d H:i'), 10),
            self::pdfText(46, 742, 'Rezumat', 15, true),
        ];

        $totals = [
            'Campinguri' => $stats['totals']['campings'],
            'Utilizatori' => $stats['totals']['users'],
            'Rezervari' => $stats['totals']['reservations'],
            'Recenzii' => $stats['totals']['reviews'],
            'Mesaje' => $stats['totals']['messages'],
        ];

        $x = 46;
        foreach ($totals as $label => $total) {
            $summaryPage[] = '0.93 0.96 0.91 rg ' . $x . ' 680 92 46 re f';
            $summaryPage[] = self::pdfText($x + 10, 710, (string) $total, 17, true);
            $summaryPage[] = self::pdfText($x + 10, 692, $label, 9);
            $x += 99;
        }

        $summaryPage = array_merge(
            $summaryPage,
            self::pdfBarChart(
                $stats['zones'],
                'zone',
                'Zone populare dupa numarul de rezervari',
                46,
                405,
                500,
                190,
                [0.18, 0.42, 0.25]
            ),
            self::pdfBarChart(
                $stats['periods'],
                'period',
                'Perioade populare dupa luna de inceput',
                46,
                115,
                500,
                190,
                [0.85, 0.52, 0.23]
            )
        );
        $summaryPage[] = self::pdfText(46, 35, 'Pagina 1 / 2', 9);

        $detailsPage = [
            self::pdfText(46, 800, 'CaT - Detalii statistice', 22, true),
        ];
        $y = 758;

        self::pdfSection($detailsPage, $y, 'Top campinguri', array_map(
            static fn(array $camping): string =>
                $camping['name'] . ' (' . $camping['zone'] . '): '
                . $camping['reservations'] . ' rezervari, rating ' . $camping['rating'],
            $stats['popular']
        ));

        self::pdfSection($detailsPage, $y, 'Zone populare', array_map(
            static fn(array $zone): string => $zone['zone'] . ': ' . $zone['total'] . ' rezervari',
            $stats['zones']
        ));

        self::pdfSection($detailsPage, $y, 'Perioade populare', array_map(
            static fn(array $period): string => $period['period'] . ': ' . $period['total'] . ' rezervari',
            $stats['periods']
        ));

        self::pdfSection($detailsPage, $y, 'Status rezervari', array_map(
            static fn(array $status): string => ucfirst($status['status']) . ': ' . $status['total'],
            $stats['statuses']
        ));

        $detailsPage[] = self::pdfText(46, 35, 'Pagina 2 / 2', 9);

        return self::buildPdf([
            implode("\n", $summaryPage),
            implode("\n", $detailsPage),
        ]);
    }

    private static function pdfBarChart(
        array $items,
        string $labelKey,
        string $title,
        float $x,
        float $y,
        float $width,
        float $height,
        array $color
    ): array {
        $commands = [
            self::pdfText($x, $y + $height + 24, $title, 14, true),
            '0.80 0.84 0.78 RG 1 w ' . $x . ' ' . $y . ' m ' . ($x + $width) . ' ' . $y . ' l S',
        ];

        $items = array_slice($items, 0, 6);
        if (!$items) {
            $commands[] = self::pdfText($x, $y + ($height / 2), 'Nu exista date suficiente.', 11);
            return $commands;
        }

        $max = max(1, ...array_map(static fn(array $item): int => (int) $item['total'], $items));
        $slotWidth = $width / count($items);
        $barWidth = min(48, $slotWidth - 16);

        foreach ($items as $index => $item) {
            $total = (int) $item['total'];
            $barHeight = max(4, ($height - 42) * ($total / $max));
            $barX = $x + ($index * $slotWidth) + (($slotWidth - $barWidth) / 2);

            $commands[] = sprintf(
                '%.2F %.2F %.2F rg %.2F %.2F %.2F %.2F re f',
                $color[0],
                $color[1],
                $color[2],
                $barX,
                $y,
                $barWidth,
                $barHeight
            );
            $commands[] = self::pdfText(
                $barX + ($barWidth / 2) - 3,
                $y + $barHeight + 8,
                (string) $total,
                9,
                true
            );
            $commands[] = self::pdfText(
                $barX - 4,
                $y - 16,
                self::shortLabel((string) $item[$labelKey]),
                8
            );
        }

        return $commands;
    }

    private static function pdfSection(array &$commands, float &$y, string $title, array $lines): void
    {
        $commands[] = self::pdfText(46, $y, $title, 15, true);
        $y -= 24;

        if (!$lines) {
            $lines = ['Nu exista date.'];
        }

        foreach ($lines as $line) {
            $commands[] = self::pdfText(58, $y, '- ' . $line, 10);
            $y -= 18;
        }

        $y -= 18;
    }

    private static function pdfText(float $x, float $y, string $text, int $size, bool $bold = false): string
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::ascii($text));

        return sprintf(
            '0 0 0 rg BT /%s %d Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
            $font,
            $size,
            $x,
            $y,
            $safe
        );
    }

    private static function shortLabel(string $label): string
    {
        $label = self::ascii($label);

        return strlen($label) > 11 ? substr($label, 0, 10) . '.' : $label;
    }

    private static function buildPdf(array $streams): string
    {
        $pageIds = [];
        foreach ($streams as $index => $stream) {
            $pageIds[] = 5 + ($index * 2);
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [' . implode(' ', array_map(
                static fn(int $id): string => $id . ' 0 R',
                $pageIds
            )) . '] /Count ' . count($pageIds) . ' >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        foreach ($streams as $index => $stream) {
            $pageId = 5 + ($index * 2);
            $contentId = $pageId + 1;
            $objects[$pageId] =
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> '
                . '/Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] =
                '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = count($objects) + 1;
        $pdf .= "xref\n0 " . $objectCount . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $objectCount; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= 'trailer << /Size ' . $objectCount . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private static function ascii(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

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
