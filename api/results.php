<?php
/**
 * GET /api/results.php
 * Public. Returns current vote standings sorted by points desc.
 * Response: { voting_open: bool, songs: [ {id, title, artist, cover_url, spotify_track_id, points, score_pct}, ... ] }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$now         = time();
$voting_open = ($now >= VOTING_OPEN && $now <= VOTING_CLOSE);

try {
    $db = get_db();

    $sql = "
        SELECT
            s.id,
            s.title,
            s.artist,
            s.cover_url,
            s.spotify_track_id,
            COALESCE(SUM(v.points), 0) AS points
        FROM songs s
        LEFT JOIN votes v ON v.song_id = s.id
        GROUP BY s.id, s.title, s.artist, s.cover_url, s.spotify_track_id
        ORDER BY points DESC, s.display_order ASC
    ";

    $rows = $db->query($sql)->fetchAll();

    $max_pts = (int)($rows[0]['points'] ?? 0);

    $songs = array_map(function (array $row) use ($max_pts): array {
        $pts = (int)$row['points'];
        return [
            'id'               => (int)$row['id'],
            'title'            => $row['title'],
            'artist'           => $row['artist'],
            'cover_url'        => $row['cover_url'] ?? null,
            'spotify_track_id' => $row['spotify_track_id'] ?? null,
            'points'           => $pts,
            'score_pct'        => $max_pts > 0
                                    ? round(($pts / $max_pts) * 100, 2)
                                    : 0,
        ];
    }, $rows);

    echo json_encode([
        'voting_open' => $voting_open,
        'songs'       => $songs,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'voting_open' => $voting_open, 'songs' => []]);
}
