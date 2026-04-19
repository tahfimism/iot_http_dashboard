<?php
include 'db_config.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();

$device_id = trim($_GET['device_id'] ?? '');

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

// Check Device Limits
$config = include 'iot_config.php';
$uStmt = $conn->prepare("SELECT user_type FROM iot_users WHERE id = ? LIMIT 1");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$uStmt->bind_result($userType);
$uStmt->fetch();
$uStmt->close();

$maxDevices = ($userType === 'premium') ? $config['devices']['max_premium'] : $config['devices']['max_free'];

$countStmt = $conn->prepare("SELECT COUNT(*) FROM device_status WHERE user_id = ?");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countStmt->bind_result($currentDeviceCount);
$countStmt->fetch();
$countStmt->close();

if ($currentDeviceCount >= $maxDevices) {
    echo json_encode(["error" => "Device limit reached for your account ($maxDevices devices). Upgrade to Premium for more."]);
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