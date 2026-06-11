<?php
/**
 * Central session bootstrap.
 * Include instead of calling session_start() directly.
 * Configures Memcached storage when SESSION_MEMCACHED_HOST is set,
 * allowing sessions to be shared across multiple server instances.
 */
if (session_status() === PHP_SESSION_NONE) {
    if (defined('SESSION_MEMCACHED_HOST') && SESSION_MEMCACHED_HOST !== '') {
        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', SESSION_MEMCACHED_HOST . ':' . SESSION_MEMCACHED_PORT);
    }
    session_start();
}
