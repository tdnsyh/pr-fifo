<?php
require_once __DIR__ . '/../lib/functions.php';
$_SESSION = [];
session_destroy();
redirect('auth/login.php');
