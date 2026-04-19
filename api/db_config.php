<?php
// --- CONFIGURATION ---
$is_local = true; // Set to true for XAMPP, false for production server

if ($is_local) {
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "firefly_db";
} else {
    $db_host = "localhost";
    $db_user = "forihbdx_admin";
    $db_pass = "t*wUd(hdCiW[XuHD";
    $db_name = "forihbdx_card";
}

// --- DEBUGGING START ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// --- DEBUGGING END ---

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
} catch (Exception $e) {
    die("<h1>Database Connection Failed</h1><p>" . $e->getMessage() . "</p>");
}

if (!defined('SKIP_API_HEADERS')) {
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json");
}
?>