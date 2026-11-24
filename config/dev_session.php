<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default dev user (Admin: Dr. Kofi Osei) if not yet set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4;
    $_SESSION['full_name'] = 'Dr. Kofi Osei';
    $_SESSION['role'] = 'admin';
    $_SESSION['vendor_id'] = null;
}

// Handle role switching from the top navbar (?switch_role=student|vendor1|vendor2|admin)
if (isset($_GET['switch_role'])) {
    switch ($_GET['switch_role']) {
        case 'student':
            $_SESSION['user_id'] = 1;
            $_SESSION['full_name'] = 'Ama Mensah';
            $_SESSION['role'] = 'student';
            $_SESSION['vendor_id'] = null;
            break;
        case 'vendor1':
            $_SESSION['user_id'] = 2;
            $_SESSION['full_name'] = 'Kwame Boateng';
            $_SESSION['role'] = 'vendor';
            $_SESSION['vendor_id'] = 1;
            break;
        case 'vendor2':
            $_SESSION['user_id'] = 3;
            $_SESSION['full_name'] = 'Efua Asante';
            $_SESSION['role'] = 'vendor';
            $_SESSION['vendor_id'] = 2;
            break;
        case 'admin':
            $_SESSION['user_id'] = 4;
            $_SESSION['full_name'] = 'Dr. Kofi Osei';
            $_SESSION['role'] = 'admin';
            $_SESSION['vendor_id'] = null;
            break;
    }

    // Redirect cleanly without the query string
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $cleanUrl);
    exit;
}