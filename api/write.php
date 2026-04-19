<?php
include 'db_config.php';
require_once 'state_helpers.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();

$device_id = $_GET['device_id'] ?? '';
$key = $_GET['key'] ?? '';
$value = $_GET['value'] ?? ($_GET['val'] ?? '');
$requestedType = $_GET['type'] ?? '';

if ($device_id === '' || $key === '') {
	echo json_encode(["error" => "Missing device_id or key"]);
	exit;
}

$selectStmt = $conn->prepare("SELECT state_json FROM device_status WHERE device_id = ? AND user_id = ?");
$selectStmt->bind_param("si", $device_id, $userId);
$selectStmt->execute();
$selectStmt->bind_result($json_raw);

$currentTypedState = [];
$deviceExistsForUser = false;
if ($selectStmt->fetch()) {
	$deviceExistsForUser = true;
	$decoded = json_decode($json_raw, true);
	$currentTypedState = normalize_typed_state($decoded);
}
$selectStmt->close();

$resolvedType = normalize_type_label($requestedType);
if ($resolvedType === '' && isset($currentTypedState[$key])) {
	$resolvedType = normalize_type_label($currentTypedState[$key]['type'] ?? '');
}
if ($resolvedType === '') {
	$resolvedType = is_numeric($value) ? 'number' : 'text';
}

$isValid = true;
$typedValue = cast_value_by_type($value, $resolvedType, $isValid);
if (!$isValid) {
	echo json_encode(["error" => "Invalid value for type " . $resolvedType]);
	exit;
}

$currentTypedState[$key] = [
	'value' => $typedValue,
	'type' => $resolvedType,
	'source' => 'manual'
];

$updatedJson = json_encode($currentTypedState);

if ($deviceExistsForUser) {
	$writeStmt = $conn->prepare("UPDATE device_status SET state_json = ?, last_seen = CURRENT_TIMESTAMP WHERE device_id = ? AND user_id = ?");
	$writeStmt->bind_param("ssi", $updatedJson, $device_id, $userId);
} else {
	$writeStmt = $conn->prepare("INSERT INTO device_status (user_id, device_id, state_json) VALUES (?, ?, ?)");
	$writeStmt->bind_param("iss", $userId, $device_id, $updatedJson);
}

if ($writeStmt->execute()) {
	echo json_encode([
		"status" => "success",
		"device" => $device_id,
		"updated_state" => flatten_typed_state($currentTypedState),
		"typed_state" => $currentTypedState
	]);
} else {
	if ($conn->errno === 1062) {
		echo json_encode(["error" => "Device ID already exists for another user. Use a unique device ID."]);
	} else {
		echo json_encode(["error" => "DB update failed"]);
	}
}

$writeStmt->close();
$conn->close();
?>