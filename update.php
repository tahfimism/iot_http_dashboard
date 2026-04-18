<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

if ($action === 'write') {
    require_once __DIR__ . '/api/write.php';
    exit;
}

if ($action === 'delete') {
    require_once __DIR__ . '/api/delete.php';
    exit;
}

if ($action === 'read') {
    require_once __DIR__ . '/api/read.php';
    exit;
}

echo json_encode(["error" => "Invalid action"]);
?>