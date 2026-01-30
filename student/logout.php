<?php
require_once '../include/config.php';

/* If user confirmed logout */
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {

    $_SESSION = array();

    if (ini_get("session_use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: ../index.php");
    exit;
}
?>

<script>
    if (confirm("Are you sure you want to log out?")) {
        // YES → reload same file with confirmation
        window.location.href = "?confirm=yes";
    } else {
        // NO → stay logged in
        window.history.back();
    }
</script>
