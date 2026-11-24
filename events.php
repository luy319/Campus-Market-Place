<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/dev_session.php';
require_once __DIR__ . '/includes/notification_helper.php';

// Fetch Published Events
$events = $pdo->query("SELECT * FROM events WHERE event_status = 'published' ORDER BY event_date ASC")->fetchAll();

// Prepare query to fetch ONLY approved participating vendors + their stall + sample products
$vendorStmt = $pdo->prepare("
    SELECT vp.vendor_id, vp.business_name, vp.business_description, vp.contact_information,
           ea.product_category, s.stall_number, s.stall_location
    FROM event_applications ea
    JOIN vendor_profiles vp ON ea.vendor_id = vp.vendor_id
    LEFT JOIN stalls s ON s.event_id = ea.event_id AND s.vendor_id = ea.vendor_id
    WHERE ea.event_id = :eid AND ea.application_status = 'approved' AND vp.approval_status = 'approved'
    ORDER BY s.stall_number ASC
");

$prodStmt = $pdo->prepare("
    SELECT product_name, price FROM products
    WHERE vendor_id = :vid AND availability_status = 'available'
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Campus Events | Campus Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container pb-5">
    <h3 class="fw-bold mb-1">Upcoming Campus Events</h3>
    <p class="text-muted mb-4">Discover campus markets, participating student vendors, and stall locations.</p>

    <?php foreach ($events as $ev): ?>
        <?php
        $vendorStmt->execute([':eid' => $ev['event_id']]);
        $approvedVendors = $vendorStmt->fetchAll();
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($ev['event_name']) ?></h5>
                    <small class="text-muted">📍 <?= htmlspecialchars($ev['venue']) ?> &nbsp;|&nbsp; 📅 <?= date('D, M j, Y g:i A', strtotime($ev['event_date'])) ?></small>
                </div>
                <span class="badge bg-success"><?= count($approvedVendors) ?> Confirmed Vendors</span>
            </div>
            <div class="card-body">
                <p><?= htmlspecialchars($ev['description']) ?></p>

                <h6 class="fw-bold mt-3 mb-2">Participating Vendors & Stalls</h6>
                <?php if (empty($approvedVendors)): ?>
                    <p class="text-muted small mb-0">Vendor stall allocations will be announced soon.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($approvedVendors as $v): ?>
                            <?php
                            $prodStmt->execute([':vid' => $v['vendor_id']]);
                            $sampleProducts = $prodStmt->fetchAll();
                            ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($v['business_name']) ?></h6>
                                        <span class="badge bg-dark">Stall <?= htmlspecialchars($v['stall_number'] ?? 'TBA') ?></span>
                                    </div>
                                    <div class="small text-muted mb-2"><?= htmlspecialchars($v['stall_location'] ?? '') ?> • <?= htmlspecialchars($v['product_category']) ?></div>
                                    <p class="small mb-2"><?= htmlspecialchars($v['business_description']) ?></p>

                                    <?php if (!empty($sampleProducts)): ?>
                                        <div class="small">
                                            <strong>Featured Items:</strong>
                                            <?php
                                            $items = array_map(fn($p) => htmlspecialchars($p['product_name']) . " (GHS " . number_format($p['price'], 2) . ")", $sampleProducts);
                                            echo implode(' • ', $items);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>