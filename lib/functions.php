<?php
// lib/functions.php - common helpers
session_start();

function base_url(string $path = ''): string {
    $cfg = require __DIR__ . '/../config.php';
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

function redirect(string $path) {
    header('Location: ' . base_url($path));
    exit;
}

function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

function flash(string $key, ?string $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    if ($msg) unset($_SESSION['flash'][$key]);
    return $msg;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
