-- King of Songs World Cup 2026
-- Run: mysql -u root -p mundial_canciones < db.sql

CREATE DATABASE IF NOT EXISTS mundial_canciones
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mundial_canciones;

-- --------------------------------------------------------
-- Users (Spotify / Apple Music OAuth)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  provider         ENUM('spotify','apple') NOT NULL,
  provider_user_id VARCHAR(255) NOT NULL,
  display_name     VARCHAR(255),
  avatar_url       VARCHAR(500),
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_provider_user (provider, provider_user_id)
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
  ('Blinding Lights',      'The Weeknd',                       '0VjIjW4GlUZAMYd2vXMi3b',  1),
  ('As It Was',            'Harry Styles',                     '4Dvkj6JhhA12EX05fT7y2e',  2),
  ('Bad Guy',              'Billie Eilish',                    '2Fxmhks0bxGSBdJ92vM42m',  3),
  ('Shape of You',         'Ed Sheeran',                       '7qiZfU4dY1lWllzX7mPBI3',  4),
  ('Levitating',           'Dua Lipa',                         '463CkQjx2Zfoiqh9hS0cLc',  5),
  ('Stay',                 'The Kid LAROI & Justin Bieber',    '5HCyWlXZPP0y6Gqq8TgA20',  6),
  ('drivers license',      'Olivia Rodrigo',                   '4k6Uh1HXdhtusDL51XFBH7',  7),
  ('Watermelon Sugar',     'Harry Styles',                     '6UelLqGlWMcVH1E5c4H7lY',  8),
  ('Peaches',              'Justin Bieber ft. Daniel Caesar',  '4iZ4pt7kvcaH6Yo8UoZ4s2',  9),
  ('Montero',              'Lil Nas X',                        '1r9xUipOqoNwggBpENDsvJ', 10),
  ('Heat Waves',           'Glass Animals',                    '02MWAaffLxlfxAUY7c5dvx', 11),
  ('Save Your Tears',      'The Weeknd',                       '5QO79kh1waicV47BqGRL3g', 12),
  ('Good 4 U',             'Olivia Rodrigo',                   '4ZtFanR9U6ndgddUvNcjcG', 13),
  ('Kiss Me More',         'Doja Cat ft. SZA',                 '748z5sGmJmAuFRSnMBTjgN', 14),
  ('Butter',               'BTS',                              '3AIY94HKXHuFIHm7Cf4bHN', 15),
  ('Industry Baby',        'Lil Nas X ft. Jack Harlow',        '5Z9KJZvQzH6PFmb8SNkxuk', 16);
