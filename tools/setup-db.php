<?php
/**
 * tools/setup-db.php — One-time database setup script.
 *
 * Creates tables and seeds the 16 songs.
 * Safe to run multiple times: uses CREATE TABLE IF NOT EXISTS and
 * INSERT IGNORE so existing data is never overwritten.
 *
 * Usage (CLI):   php tools/setup-db.php
 * Usage (browser): visit https://yourdomain.com/mundial-canciones/tools/setup-db.php
 *                  then DELETE this file immediately afterwards.
 */

declare(strict_types=1);

// Detect CLI vs browser for output formatting
$cli = (PHP_SAPI === 'cli');
function out(string $msg, bool $ok = true) use ($cli): void {
    if ($cli) {
        echo ($ok ? '✓' : '✗') . ' ' . $msg . PHP_EOL;
    } else {
        $colour = $ok ? '#2ecc71' : '#e74c3c';
        echo '<p style="font-family:monospace;margin:4px 0;color:' . $colour . '">'
           . ($ok ? '✓' : '✗') . ' ' . htmlspecialchars($msg) . '</p>';
    }
}

if (!$cli) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<title>DB Setup</title></head><body style="background:#111;padding:2rem">';
    echo '<h2 style="color:#fff;font-family:monospace">Mundial de Canciones — DB Setup</h2>';
}

require_once __DIR__ . '/../config.php';

// ── Connect ─────────────────────────────────────────────────────────────────
try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    out('Connected to MySQL as ' . DB_USER . '@' . DB_HOST);
} catch (\Throwable $e) {
    out('Cannot connect to MySQL: ' . $e->getMessage(), false);
    if (!$cli) echo '</body></html>';
    exit(1);
}

// ── Create / select database ─────────────────────────────────────────────────
try {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    out('Database `' . DB_NAME . '` ready');
} catch (\Throwable $e) {
    out('Database error: ' . $e->getMessage(), false);
    if (!$cli) echo '</body></html>';
    exit(1);
}

// ── Create tables ─────────────────────────────────────────────────────────────
$tables = [

'users' => "CREATE TABLE IF NOT EXISTS users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(255) NOT NULL,
  display_name   VARCHAR(255),
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  remember_token VARCHAR(64) NULL DEFAULT NULL,
  UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'songs' => "CREATE TABLE IF NOT EXISTS songs (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(255) NOT NULL,
  artist           VARCHAR(255) NOT NULL,
  cover_url        VARCHAR(500),
  spotify_track_id VARCHAR(100),
  display_order    INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'votes' => "CREATE TABLE IF NOT EXISTS votes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  song_id       INT NOT NULL,
  rank_position TINYINT NOT NULL COMMENT '1=gold, 2=silver, 3=bronze',
  points        TINYINT NOT NULL COMMENT '5, 3, or 1',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_rank  (user_id, rank_position),
  UNIQUE KEY unique_user_song  (user_id, song_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

foreach ($tables as $name => $ddl) {
    try {
        $pdo->exec($ddl);
        out("Table `$name` ready");
    } catch (\Throwable $e) {
        out("Table `$name` failed: " . $e->getMessage(), false);
    }
}

// ── Seed songs ────────────────────────────────────────────────────────────────
$songs = [
    [1,  'La Morocha',                    'Luck Ra',                       '7aPsseax6rNFyipHn9A5CR'],
    [2,  'Flamenco y Bachata',            'Daviles de Novelda',            '6ynErSDSlxqsxg0D3LJ8sK'],
    [3,  'Waka Waka',                     'Shakira',                       '0W8nDs4H2cqxxAgszNMYO3'],
    [4,  'Ateo',                          'C.Tangana',                     '5xiAfKzE3mbxYbOkUZPR11'],
    [5,  'Pedro',                         'Jaxomy',                        '48lxT5qJF0yYyf2z4wB4xW'],
    [6,  'Paseo',                         'Estopa',                        '6pWeJvzpQR1ihiUSJklOzD'],
    [7,  'Como Si Fueras A Morir Mañana', 'Leiva',                         '4aAfLSx9IthpC3Pw5pNk3E'],
    [8,  'Volver a Disfrutar',            'ECDL',                          '3PuXuPcU0W4iHnd3C78FIr'],
    [9,  'Como Una Ola Techno Remix',     'Marsal Ventura',                '7vZWikYcjHSFW78wJZfd1N'],
    [10, 'Bizcochito',                    'Rosalía',                       '4kXxEhuatrvwrTQycA7s9B'],
    [11, 'Yo soy español',               'La Banda Del Capitán Canalla',   '0BV5TpoxeUXxb1QwSFEvCV'],
    [12, 'Veinticinco',                   'Dani Martín',                   '5ZmVXMkruEitcEJYYL5m0V'],
    [13, 'Agachate',                      'Danny Romero',                  '03ATKw97s9WHuek1TKmhVq'],
    [14, 'Rosas',                         'La Oreja de Van Gogh',          '4waqcUQWdj0yH26STWl2Rq'],
    [15, 'Una Lady Como Tú',             'Manuel Turizo',                  '7MHN1aCFtLXjownGhvEQlF'],
    [16, 'TOKE',                          'Chanel',                        '2H8v0g9A4anC2UmZAPKgQn'],
];

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO songs (display_order, title, artist, spotify_track_id) VALUES (?, ?, ?, ?)'
);

$inserted = 0;
foreach ($songs as [$order, $title, $artist, $track]) {
    try {
        $stmt->execute([$order, $title, $artist, $track]);
        if ($stmt->rowCount() > 0) $inserted++;
    } catch (\Throwable $e) {
        out("Song '$title' failed: " . $e->getMessage(), false);
    }
}
out("Songs seeded: $inserted new row(s) inserted (existing rows untouched)");

// ── Done ──────────────────────────────────────────────────────────────────────
if (!$cli) {
    echo '<p style="color:#aaa;font-family:monospace;margin-top:1.5rem">'
       . '⚠️  Delete this file from the server once setup is complete.</p>';
    echo '</body></html>';
}
