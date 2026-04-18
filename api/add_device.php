<?php
include 'db_config.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();

$device_id = trim($_GET['device_id'] ?? '');

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

$checkStmt = $conn->prepare("SELECT 1 FROM device_status WHERE device_id = ? AND user_id = ? LIMIT 1");
$checkStmt->bind_param("si", $device_id, $userId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(["error" => "A device with that name already exists. Choose a different device ID."]);
    exit;
}

$checkStmt->close();

$insertStmt = $conn->prepare("INSERT INTO device_status (user_id, device_id, state_json) VALUES (?, ?, ?)");
$emptyState = '{}';
$insertStmt->bind_param("iss", $userId, $device_id, $emptyState);

if ($insertStmt->execute()) {
    echo json_encode([
        "status" => "created",
        "device_id" => $device_id
    ]);
} else {
    if ($conn->errno === 1062) {
        echo json_encode(["error" => "A device with that name already exists for another user. Choose a different device ID."]);
    } else {
        echo json_encode(["error" => "DB insert failed"]);
    }
}

$insertStmt->close();
$conn->close();
?>