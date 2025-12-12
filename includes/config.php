<?php
// 세션이 안 켜져 있으면 켜기 (안전하게)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * 나중에 MySQL 붙일 때 여기다 정보 넣을 거야
 * 예시)
 * define('DB_HOST', 'localhost');
 * define('DB_USER', 'root');
 * define('DB_PASS', '');
 * define('DB_NAME', 'dewscent');
 */
