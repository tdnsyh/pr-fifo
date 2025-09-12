<?php
// auth/require_login.php
require_once __DIR__ . '/../lib/functions.php';
if (!isset($_SESSION['user'])) {
    flash('error','Silakan login terlebih dahulu.');
    redirect('auth/login.php');
}
