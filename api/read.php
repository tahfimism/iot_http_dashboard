<?php
include 'db_config.php';
require_once 'state_helpers.php';
require_once 'auth_helpers.php';
$device_id = $_GET['device_id'] ?? '';
$format = $_GET['format'] ?? 'state';
$userId = resolve_read_user_id();

if ($device_id === '') {
	echo json_encode(["error" => "Missing device_id"]);
	exit;
}

$stmt = $conn->prepare("SELECT state_json, last_seen FROM device_status WHERE device_id = ? AND user_id = ?");
$stmt->bind_param("si", $device_id, $userId);
$stmt->execute();
$stmt->bind_result($json, $last_seen);

if (!$stmt->fetch()) {
	echo json_encode(["error" => "Device not found"]);
	exit;
}

$decodedState = json_decode($json, true);
$typedState = normalize_typed_state($decodedState);
$flatState = flatten_typed_state($typedState);

if ($format === 'full') {
	echo json_encode([
		"device_id" => $device_id,
		"state" => $flatState,
		"typed_state" => $typedState,
		"last_seen" => $last_seen
	]);
} else {
	echo json_encode($flatState);
}
?>