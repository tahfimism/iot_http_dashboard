<?php
include 'db_config.php';
require_once 'state_helpers.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();

$device_id = $_GET['device_id'] ?? '';
$key = $_GET['key'] ?? '';

if ($device_id === '' || $key === '') {
    echo json_encode(["error" => "Missing device_id or key"]);
    exit;
}

$selectStmt = $conn->prepare("SELECT state_json FROM device_status WHERE device_id = ? AND user_id = ?");
$selectStmt->bind_param("si", $device_id, $userId);
$selectStmt->execute();
$selectStmt->bind_result($json_raw);

if (!$selectStmt->fetch()) {
    $selectStmt->close();
    echo json_encode(["error" => "Device not found"]);
    exit;
}
$selectStmt->close();

$decodedState = json_decode($json_raw, true);
$currentTypedState = normalize_typed_state($decodedState);

if (!array_key_exists($key, $currentTypedState)) {
    echo json_encode(["error" => "Key not found"]);
    exit;
}

unset($currentTypedState[$key]);
$updatedJson = json_encode($currentTypedState);

$deleteStmt = $conn->prepare("UPDATE device_status SET state_json = ?, last_seen = CURRENT_TIMESTAMP WHERE device_id = ? AND user_id = ?");
$deleteStmt->bind_param("ssi", $updatedJson, $device_id, $userId);

if ($deleteStmt->execute()) {
    echo json_encode(["status" => "deleted", "key" => $key]);
} else {
    echo json_encode(["error" => "DB delete failed"]);
}

$deleteStmt->close();
$conn->close();
?>