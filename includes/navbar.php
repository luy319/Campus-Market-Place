<?php
$currentUserId = $_SESSION['user_id'] ?? 1;
$currentRole   = $_SESSION['role'] ?? 'student';
$currentName   = $_SESSION['full_name'] ?? 'Guest';

// Mark single notification as read if clicked
if (isset($_GET['read_notif']) && is_numeric($_GET['read_notif'])) {
    $markStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :nid AND user_id = :uid");
    $markStmt->execute([':nid' => (int)$_GET['read_notif'], ':uid' => $currentUserId]);
}

// Fetch user's notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 8");
$notifStmt->execute([':uid' => $currentUserId]);
$userNotifs = $notifStmt->fetchAll();
$unreadCount = count(array_filter($userNotifs, fn($n) => (int)$n['is_read'] === 0));

// Determine base path prefix depending on whether we are in /admin or /vendor
$inSubfolder = in_array(basename(dirname($_SERVER['SCRIPT_NAME'])), ['admin', 'vendor']);
$basePrefix = $inSubfolder ? '../' : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $basePrefix ?>events.php">Campus Marketplace</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePrefix ?>events.php">Campus Events</a>
                </li>
                <?php if ($currentRole === 'vendor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePrefix ?>vendor/events.php">Vendor Stalls</a>
                    </li>
                <?php endif; ?>
                <?php if ($currentRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePrefix ?>admin/events.php">Admin Events</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 360px; overflow-y: auto;">
                        <li class="dropdown-header fw-bold">Notifications</li>
                        <?php if (empty($userNotifs)): ?>
                            <li><span class="dropdown-item-text text-muted small">No notifications yet.</span></li>
                        <?php else: ?>
                            <?php foreach ($userNotifs as $n): ?>
                                <li>
                                    <a class="dropdown-item text-wrap <?= $n['is_read'] ? 'text-muted' : 'fw-semibold bg-light' ?>"
                                       href="?read_notif=<?= $n['notification_id'] ?>">
                                        <div class="small"><?= htmlspecialchars($n['title']) ?></div>
                                        <div class="small fw-normal text-secondary"><?= htmlspecialchars($n['message']) ?></div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Dev Role Switcher -->
                <div class="dropdown">
                    <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($currentName) ?> (<?= ucfirst($currentRole) ?>)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">Switch Test Role</li>
                        <li><a class="dropdown-item" href="?switch_role=admin">Dr. Kofi Osei (Admin)</a></li>
                        <li><a class="dropdown-item" href="?switch_role=vendor1">Kwame Boateng (Vendor 1)</a></li>
                        <li><a class="dropdown-item" href="?switch_role=vendor2">Efua Asante (Vendor 2)</a></li>
                        <li><a class="dropdown-item" href="?switch_role=student">Ama Mensah (Student)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>