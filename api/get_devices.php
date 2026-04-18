<?php
include 'db_config.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();
$stmt = $conn->prepare("SELECT device_id FROM device_status WHERE user_id = ? ORDER BY device_id ASC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$devices = [];
while($row = $result->fetch_assoc()) { $devices[] = $row['device_id']; }
$stmt->close();
echo json_encode($devices);
?>