<?php
$conn = new mysqli("localhost", "root", "", "firefly_db");
if ($conn->connect_error) die(json_encode(["error" => "DB Connection failed"]));
if (!defined('SKIP_API_HEADERS')) {
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json");
}
?>