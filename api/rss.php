<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function rss_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base = preg_replace('#/api$#', '', $base);

    return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
}

function rss_date(?string $date): string
{
    $timestamp = $date ? strtotime($date) : time();

    if (!$timestamp) {
        $timestamp = time();
    }

    return date(DATE_RSS, $timestamp);
}

$db = db();

$items = [];

$campings = $db->query(
    "SELECT id, name, zone, description, created_at
     FROM campings
     ORDER BY created_at DESC
     LIMIT 10"
)->fetchAll();

foreach ($campings as $camping) {
    $items[] = [
        'title' => 'Camping nou: ' . $camping['name'],
        'description' => $camping['description'],
        'link' => site_url('index.php?page=detail&id=' . $camping['id']),
        'guid' => 'camping-' . $camping['id'],
        'date' => $camping['created_at'],
    ];
}

$reviews = $db->query(
    "SELECT r.id, r.rating, r.comment, r.created_at, c.id AS camping_id, c.name AS camping_name, u.name AS user_name
     FROM reviews r
     JOIN campings c ON c.id = r.camping_id
     JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC
     LIMIT 10"
)->fetchAll();

foreach ($reviews as $review) {
    $items[] = [
        'title' => 'Recenzie noua pentru ' . $review['camping_name'],
        'description' => $review['user_name'] . ' a oferit rating ' . $review['rating'] . '/5: ' . $review['comment'],
        'link' => site_url('index.php?page=detail&id=' . $review['camping_id']),
        'guid' => 'review-' . $review['id'],
        'date' => $review['created_at'],
    ];
}

$messages = $db->query(
    "SELECT m.id, m.content, m.created_at, c.id AS camping_id, c.name AS camping_name, u.name AS user_name
     FROM messages m
     JOIN users u ON u.id = m.user_id
     LEFT JOIN campings c ON c.id = m.camping_id
     ORDER BY m.created_at DESC
     LIMIT 10"
)->fetchAll();

foreach ($messages as $message) {
    $link = !empty($message['camping_id'])
        ? site_url('index.php?page=detail&id=' . $message['camping_id'])
        : site_url('index.php?page=community');

    $title = !empty($message['camping_name'])
        ? 'Mesaj nou despre ' . $message['camping_name']
        : 'Mesaj nou in comunitate';

    $items[] = [
        'title' => $title,
        'description' => $message['user_name'] . ': ' . $message['content'],
        'link' => $link,
        'guid' => 'message-' . $message['id'],
        'date' => $message['created_at'],
    ];
}

usort($items, function (array $a, array $b): int {
    return strtotime($b['date']) <=> strtotime($a['date']);
});

$items = array_slice($items, 0, 20);

header('Content-Type: application/rss+xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title>CaT - Camping Info Web Tool</title>
    <link><?= rss_escape(site_url('index.php?page=home')) ?></link>
    <description>Noutati despre campinguri, recenzii si mesaje din comunitatea CaT.</description>
    <language>ro-ro</language>
    <lastBuildDate><?= rss_escape(date(DATE_RSS)) ?></lastBuildDate>
<?php foreach ($items as $item): ?>
    <item>
      <title><?= rss_escape($item['title']) ?></title>
      <link><?= rss_escape($item['link']) ?></link>
      <guid isPermaLink="false"><?= rss_escape($item['guid']) ?></guid>
      <pubDate><?= rss_escape(rss_date($item['date'])) ?></pubDate>
      <description><?= rss_escape($item['description']) ?></description>
    </item>
<?php endforeach; ?>
  </channel>
</rss>