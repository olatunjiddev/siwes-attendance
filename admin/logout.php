<?php
require_once '../include/config.php';

/* ================= CONFIRMED LOGOUT ================= */
if (isset($_GET['logout']) && $_GET['logout'] === 'yes') {

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

    header("Location: login.php");
    exit;
}
?>

<!-- ================= CONFIRM DIALOG ================= -->
<script>
    if (confirm("Are you sure you want to log out?")) {
        // YES → logout
        window.location.href = "?logout=yes";
    } else {
        // NO → stay logged in
        window.location.href = document.referrer || "dashboard.php";
    }
</script>
