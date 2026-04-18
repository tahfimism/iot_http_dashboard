<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_user_logged_in() {
    return !empty($_SESSION['user_id']);
}

function get_logged_in_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function get_logged_in_user_name() {
    return isset($_SESSION['user_name']) ? (string)$_SESSION['user_name'] : '';
}

function require_page_auth() {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_api_auth_user_id() {
    if (is_user_logged_in()) {
        return get_logged_in_user_id();
    }

    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/**
 * Maps a public-facing UID (UUID) back to the internal numeric User ID.
 */
function resolve_uid_to_user_id($conn) {
    if (is_user_logged_in()) {
        return get_logged_in_user_id();
    }

    $uid = trim($_GET['uid'] ?? '');
    if ($uid === '') {
        return 0; // Not provided
    }

    $stmt = $conn->prepare("SELECT id FROM iot_users WHERE uid = ? LIMIT 1");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $stmt->bind_result($userId);
    
    if (!$stmt->fetch()) {
        $stmt->close();
        http_response_code(401);
        echo json_encode(["error" => "Invalid uid"]);
        exit;
    }

    $stmt->close();
    return (int)$userId;
}

function resolve_read_user_id($conn = null) {
    if (is_user_logged_in()) {
        return get_logged_in_user_id();
    }

    // Prioritize UID for security
    if ($conn !== null) {
        $resolvedId = resolve_uid_to_user_id($conn);
        if ($resolvedId > 0) return $resolvedId;
    }

    // Keep legacy user_id as fallback for now
    $fallbackUserId = (int)($_GET['user_id'] ?? 0);
    if ($fallbackUserId > 0) {
        return $fallbackUserId;
    }

    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

?>