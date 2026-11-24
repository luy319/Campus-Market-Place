<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/dev_session.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Switch to Staff Admin role in the top bar.");
}

$flash = '';

// Handle Event Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $stmt = $pdo->prepare("
        INSERT INTO events (event_name, description, event_date, venue, registration_deadline, event_status)
        VALUES (:name, :desc, :edate, :venue, :deadline, 'published')
    ");
    $stmt->execute([
        ':name'     => trim($_POST['event_name']),
        ':desc'     => trim($_POST['description']),
        ':edate'    => $_POST['event_date'],
        ':venue'    => trim($_POST['venue']),
        ':deadline' => $_POST['registration_deadline']
    ]);
    $flash = "Event published successfully!";
}

// Handle Stall Application Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review_application') {
    $appId       = (int) $_POST['application_id'];
    $decision    = $_POST['decision']; // 'approved' or 'rejected'
    $stallNumber = trim($_POST['stall_number'] ?? '');
    $stallLoc    = trim($_POST['stall_location'] ?? '');

    // Fetch application + vendor user_id + event_name for notification
    $infoStmt = $pdo->prepare("
        SELECT ea.event_id, ea.vendor_id, vp.user_id, vp.business_name, e.event_name
        FROM event_applications ea
        JOIN vendor_profiles vp ON ea.vendor_id = vp.vendor_id
        JOIN events e ON ea.event_id = e.event_id
        WHERE ea.application_id = :app_id
    ");
    $infoStmt->execute([':app_id' => $appId]);
    $app = $infoStmt->fetch();

    if ($app) {
        $pdo->beginTransaction();
        try {
            // Update application status
            $upd = $pdo->prepare("UPDATE event_applications SET application_status = :status WHERE application_id = :id");
            $upd->execute([':status' => $decision, ':id' => $appId]);

            if ($decision === 'approved') {
                // Allocate stall
                $stallStmt = $pdo->prepare("
                    INSERT INTO stalls (event_id, vendor_id, stall_number, stall_location, allocation_status)
                    VALUES (:eid, :vid, :snum, :sloc, 'allocated')
                ");
                $stallStmt->execute([
                    ':eid'  => $app['event_id'],
                    ':vid'  => $app['vendor_id'],
                    ':snum' => $stallNumber ?: 'TBD',
                    ':sloc' => $stallLoc ?: 'Main Grounds'
                ]);

                createNotification(
                    $pdo,
                    (int) $app['user_id'],
                    "Stall Application Approved!",
                    "Your stall application for {$app['event_name']} is approved. Stall: {$stallNumber} ({$stallLoc}).",
                    'application',
                    $appId
                );
                $flash = "Approved {$app['business_name']} and allocated stall {$stallNumber}.";
            } else {
                createNotification(
                    $pdo,
                    (int) $app['user_id'],
                    "Stall Application Update",
                    "Your stall application for {$app['event_name']} was not approved at this time.",
                    'application',
                    $appId
                );
                $flash = "Application from {$app['business_name']} rejected.";
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $flash = "Error processing application: " . $e->getMessage();
        }
    }
}

// Fetch All Events
$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();

// Fetch All Stall Applications
$applications = $pdo->query("
    SELECT ea.*, e.event_name, vp.business_name, s.stall_number, s.stall_location
    FROM event_applications ea
    JOIN events e ON ea.event_id = e.event_id
    JOIN vendor_profiles vp ON ea.vendor_id = vp.vendor_id
    LEFT JOIN stalls s ON s.event_id = ea.event_id AND s.vendor_id = ea.vendor_id
    ORDER BY ea.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Event Admin | Campus Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container pb-5">
    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Create Event Form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">Create Campus Event</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_event">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Event Name</label>
                            <input type="text" name="event_name" class="form-control" required placeholder="e.g., Ashesi Entrepreneurship Fair">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Venue</label>
                            <input type="text" name="venue" class="form-control" required placeholder="e.g., Archer Cornfield Courtyard">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Event Date & Time</label>
                            <input type="datetime-local" name="event_date" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Vendor Registration Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Publish Event</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Review Stall Applications -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white fw-bold">Vendor Stall Applications</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Vendor</th>
                                <th>Category / Notes</th>
                                <th>Status</th>
                                <th>Action / Stall Allocation</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($app['event_name']) ?></td>
                                <td><?= htmlspecialchars($app['business_name']) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($app['product_category'] ?? 'General') ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($app['notes'] ?? '') ?></div>
                                </td>
                                <td>
                                    <?php if ($app['application_status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved (<?= htmlspecialchars($app['stall_number']) ?>)</span>
                                    <?php elseif ($app['application_status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($app['application_status'] === 'pending'): ?>
                                        <form method="POST" class="d-flex flex-column gap-1">
                                            <input type="hidden" name="action" value="review_application">
                                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                            <input type="text" name="stall_number" class="form-control form-control-sm" placeholder="Stall # (e.g. B-04)" required>
                                            <input type="text" name="stall_location" class="form-control form-control-sm" placeholder="Zone (e.g. Craft Row)" required>
                                            <div class="d-flex gap-1 mt-1">
                                                <button type="submit" name="decision" value="approved" class="btn btn-success btn-sm flex-fill">Approve</button>
                                                <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger btn-sm">Reject</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted"><?= htmlspecialchars($app['stall_location'] ?? 'Processed') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Published Events List -->
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Published Campus Events</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($events as $ev): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($ev['event_name']) ?></div>
                                <div class="small text-muted">Venue: <?= htmlspecialchars($ev['venue']) ?> | Date: <?= $ev['event_date'] ?></div>
                            </div>
                            <span class="badge bg-info text-dark"><?= strtoupper($ev['event_status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>