-- King of Songs World Cup 2026
-- Run: mysql -u root -p mundial_canciones < db.sql

CREATE DATABASE IF NOT EXISTS mundial_canciones
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mundial_canciones;

-- --------------------------------------------------------
-- Users (identified by email from presave auth service)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(255) NOT NULL,
  display_name VARCHAR(255),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  remember_token VARCHAR(64) NULL DEFAULT NULL,
  UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Songs (16 competitors)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS songs (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(255) NOT NULL,
  artist           VARCHAR(255) NOT NULL,
  cover_url        VARCHAR(500),
  spotify_track_id VARCHAR(100),
  display_order    INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Votes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS votes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  song_id       INT NOT NULL,
  rank_position TINYINT NOT NULL COMMENT '1=gold, 2=silver, 3=bronze',
  points        TINYINT NOT NULL COMMENT '5, 3, or 1',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_rank (user_id, rank_position),
  UNIQUE KEY unique_user_song (user_id, song_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed: 16 example songs
-- --------------------------------------------------------
INSERT INTO songs (title, artist, spotify_track_id, display_order) VALUES
  ('La Morocha',      'Luck Ra',                       '7aPsseax6rNFyipHn9A5CR',  1),
  ('Flamenco y Bachata',            'Daviles de Novelda',                     '6ynErSDSlxqsxg0D3LJ8sK',  2),
  ('Waka Waka',              'Shakira',                    '0W8nDs4H2cqxxAgszNMYO3',  3),
  ('Ateo',         'C.Tangana',                       '5xiAfKzE3mbxYbOkUZPR11',  4),
  ('Pedro',           'Jaxomy',                         '48lxT5qJF0yYyf2z4wB4xW',  5),
  ('Paseo',                 'Estopa',    '6pWeJvzpQR1ihiUSJklOzD',  6),
  ('Como Si Fueras A Morir Mañana',      'Leiva',                   '4aAfLSx9IthpC3Pw5pNk3E',  7),
  ('Volver a Disfrutar',     'ECDL',                     '3PuXuPcU0W4iHnd3C78FIr',  8),
  ('Como Una Ola Techno Remix',              'Marsal Ventura',  '7vZWikYcjHSFW78wJZfd1N',  9),
  ('Bizcochito',              'Rosalía',                        '4kXxEhuatrvwrTQycA7s9B', 10);
