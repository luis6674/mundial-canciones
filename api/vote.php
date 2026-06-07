<?php
/**
 * POST /api/vote.php
 * Requires active session. Accepts JSON body:
 *   { "votes": [{"song_id": 3, "rank": 1}, {"song_id": 7, "rank": 2}, {"song_id": 1, "rank": 3}], "csrf": "TOKEN" }
 * Returns { "success": true } or { "error": "..." }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function json_error(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// --- Auth ---
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    json_error('Not authenticated.', 401);
}

// --- Voting window ---
$now = time();
if ($now < VOTING_OPEN || $now > VOTING_CLOSE) {
    json_error('Voting is not currently open.');
}

// --- Parse body ---
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    json_error('Empty request body.');
}

try {
    $body = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (\JsonException $e) {
    json_error('Invalid JSON.');
}

// --- CSRF ---
$csrf_from_body    = $body['csrf'] ?? '';
$csrf_from_session = $_SESSION['csrf_token'] ?? '';
if (!$csrf_from_session || !hash_equals($csrf_from_session, (string)$csrf_from_body)) {
    json_error('Invalid CSRF token.', 403);
}

// --- Validate votes array ---
$votes_input = $body['votes'] ?? null;
if (!is_array($votes_input) || count($votes_input) !== 3) {
    json_error('You must submit exactly 3 votes.');
}

$POINTS_MAP = [1 => 5, 2 => 3, 3 => 1];

$votes   = [];
$ranks   = [];
$song_ids = [];

foreach ($votes_input as $v) {
    $rank    = isset($v['rank'])    ? (int)$v['rank']    : 0;
    $song_id = isset($v['song_id']) ? (int)$v['song_id'] : 0;

    if (!array_key_exists($rank, $POINTS_MAP)) {
        json_error("Invalid rank: $rank. Must be 1, 2, or 3.");
    }
    if ($song_id <= 0) {
        json_error('Invalid song_id.');
    }
    if (in_array($rank, $ranks, true)) {
        json_error('Duplicate rank in submission.');
    }
    if (in_array($song_id, $song_ids, true)) {
        json_error('Duplicate song in submission.');
    }

    $ranks[]    = $rank;
    $song_ids[] = $song_id;
    $votes[]    = ['rank' => $rank, 'song_id' => $song_id, 'points' => $POINTS_MAP[$rank]];
}

sort($ranks);
if ($ranks !== [1, 2, 3]) {
    json_error('Ranks must be exactly 1, 2, and 3.');
}

// --- Verify all song_ids exist ---
try {
    $db = get_db();

    $placeholders = implode(',', array_fill(0, count($song_ids), '?'));
    $stmt = $db->prepare("SELECT id FROM songs WHERE id IN ($placeholders)");
    $stmt->execute($song_ids);
    $found = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if (count($found) !== 3) {
        json_error('One or more song_ids are invalid.');
    }

    // --- Transaction: delete existing votes, insert new ---
    $user_id = (int)$user['id'];

    $db->beginTransaction();
    try {
        $del = $db->prepare('DELETE FROM votes WHERE user_id = ?');
        $del->execute([$user_id]);

        $ins = $db->prepare(
            'INSERT INTO votes (user_id, song_id, rank_position, points) VALUES (?, ?, ?, ?)'
        );
        foreach ($votes as $v) {
            $ins->execute([$user_id, $v['song_id'], $v['rank'], $v['points']]);
        }

        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
}
