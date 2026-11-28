
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {

    if (!isset($_SESSION["user_id"])) {

        header("Location: ../auth/login.php");
        exit();

    }

}

function requireRole($allowedRoles) {

    requireLogin();

    if (!in_array(
        $_SESSION["role"],
        $allowedRoles,
        true
    )) {

        http_response_code(403);
        exit("Access denied.");

    }

}

?>







<?php

require_once "../includes/auth-check.php";

requireRole(["vendor"]);

require_once "../config/database.php";

?>






<?php

require_once "../includes/auth-check.php";

requireRole(["admin"]);

require_once "../config/database.php";

?>

