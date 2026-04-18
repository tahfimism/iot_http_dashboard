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
    if (!is_user_logged_in()) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    return get_logged_in_user_id();
}

function resolve_read_user_id() {
    if (is_user_logged_in()) {
        return get_logged_in_user_id();
    }

    $fallbackUserId = (int)($_GET['user_id'] ?? 0);
    if ($fallbackUserId > 0) {
        return $fallbackUserId;
    }

    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

?>