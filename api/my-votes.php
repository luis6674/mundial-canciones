<?php
/**
 * GET /api/my-votes.php
 * Requires session. Returns the current user's saved votes.
 * Response: { "votes": [ {"rank": 1, "song_id": 3}, ... ] }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../includes/session.php';

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated.', 'votes' => []]);
    exit;
}

try {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT rank_position AS `rank`, song_id FROM votes WHERE user_id = ? ORDER BY rank_position ASC'
    );
    $stmt->execute([(int)$user['id']]);
    $rows = $stmt->fetchAll();

    $votes = array_map(fn(array $r): array => [
        'rank'    => (int)$r['rank'],
        'song_id' => (int)$r['song_id'],
    ], $rows);

    echo json_encode(['votes' => $votes], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error.', 'votes' => []]);
}
