<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default
}

if (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = 'USD'; // Default fallback
}

$lang_file = __DIR__ . "/includes/lang_" . $_SESSION['lang'] . ".php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include __DIR__ . "/includes/lang_en.php";
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>