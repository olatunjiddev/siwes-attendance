<?php
require_once '../include/config.php';

$_SESSION = array();

if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
// Redirect to login page
header("Location: ../index.php");
exit;
?>
