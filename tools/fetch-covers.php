<?php
/**
 * tools/fetch-covers.php
 * Run once from CLI or browser (protect/delete after use) to populate
 * the cover_url column for all songs via Spotify's public oEmbed API.
 *
 * CLI: php tools/fetch-covers.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$db    = get_db();
$songs = $db->query('SELECT id, title, spotify_track_id FROM songs WHERE spotify_track_id IS NOT NULL')->fetchAll();

$updated = 0;
$failed  = [];

foreach ($songs as $song) {
    $oembed_url = 'https://open.spotify.com/oembed?url=' . urlencode('https://open.spotify.com/track/' . $song['spotify_track_id']);

    $ch = curl_init($oembed_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $http !== 200) {
        $failed[] = $song['title'] . ' (HTTP ' . $http . ')';
        continue;
    }

    try {
        $data = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        $failed[] = $song['title'] . ' (invalid JSON)';
        continue;
    }

    $thumb = $data['thumbnail_url'] ?? null;
    if (!$thumb) {
        $failed[] = $song['title'] . ' (no thumbnail_url)';
        continue;
    }

    $stmt = $db->prepare('UPDATE songs SET cover_url = ? WHERE id = ?');
    $stmt->execute([$thumb, $song['id']]);
    $updated++;

    echo "OK: {$song['title']} → $thumb\n";
    usleep(200000); // 200ms between requests to be polite
}

echo "\nDone. Updated: $updated. Failed: " . count($failed) . "\n";
foreach ($failed as $f) echo "  FAILED: $f\n";
