<?php
require_once 'db.php';

// Fetch Summary Metric Cards
$total_items = $conn->query("SELECT COUNT(*) as count FROM physical_inventory")->fetch_assoc()['count'];
$in_stock    = $conn->query("SELECT COUNT(*) as count FROM physical_inventory WHERE current_status = 'In Stock'")->fetch_assoc()['count'];
$pending     = $conn->query("SELECT COUNT(*) as count FROM physical_inventory WHERE current_status = 'Pending Purchase'")->fetch_assoc()['count'];
$damaged     = $conn->query("SELECT COUNT(*) as count FROM physical_inventory WHERE current_status = 'Damaged'")->fetch_assoc()['count'];

// Fetch Categories Stock Level Progress Metrics
$cat_metrics = $conn->query("SELECT category, COUNT(*) as count FROM master_catalog mc JOIN physical_inventory pi ON mc.catalog_id = pi.catalog_id GROUP BY category");

// Fetch Recent Check-Ins/Updates Matrix
$recent_activity = $conn->query("SELECT pi.*, mc.item_name FROM physical_inventory pi JOIN master_catalog mc ON pi.catalog_id = mc.catalog_id ORDER BY pi.last_checked DESC LIMIT 6");

include_once 'includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold m-0">Dashboard</h2>
    <p class="text-muted small m-0">Tools & Equipment Inventory Overview</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card-summary d-flex align-items-center gap-3">
            <div class="bg-light p-3 rounded"><i class="fa-solid fa-box text-secondary fs-4"></i></div>
            <div>
                <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Items</div>
                <div class="h3 fw-bold m-0"><?php echo $total_items; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-summary d-flex align-items-center gap-3">
            <div class="bg-success-subtle p-3 rounded"><i class="fa-solid fa-circle-check text-success fs-4"></i></div>
            <div>
                <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">In Stock</div>
                <div class="h3 fw-bold m-0"><?php echo $in_stock; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-summary d-flex align-items-center gap-3">
            <div class="bg-warning-subtle p-3 rounded"><i class="fa-solid fa-clock text-warning fs-4"></i></div>
            <div>
                <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Pending Purchase</div>
                <div class="h3 fw-bold m-0"><?php echo $pending; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-summary d-flex align-items-center gap-3">
            <div class="bg-danger-subtle p-3 rounded"><i class="fa-solid fa-triangle-exclamation text-danger fs-4"></i></div>
            <div>
                <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Damaged</div>
                <div class="h3 fw-bold m-0"><?php echo $damaged; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card p-4 h-100 bg-white border">
            <h6 class="fw-bold text-uppercase text-muted mb-4" style="font-size: 0.75rem; letter-spacing: 0.05em;">Items by Category</h6>
            <div class="d-flex flex-column gap-3">
                <?php while($cat = $cat_metrics->fetch_assoc()): ?>
                    <div>
                        <div class="d-flex justify-content-between small fw-bold mb-1">
                            <span><?php echo $cat['category']; ?></span>
                            <span class="text-muted"><?php echo $cat['count']; ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="background-color: #e45d14; width: <?php echo min(($cat['count'] / 15) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card p-4 h-100 bg-white border">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">Attention Required</h6>
            <div class="d-flex flex-column gap-2">
                <?php if($damaged > 0): ?>
                    <div class="alert alert-danger border-0 p-2 small m-0"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $damaged; ?> items marked as <strong>Damaged</strong> — review needed</div>
                <?php endif; ?>
                <?php if($pending > 0): ?>
                    <div class="alert alert-warning border-0 p-2 small m-0"><i class="fa-solid fa-clock me-2"></i> <?php echo $pending; ?> item in <strong>Pending Purchase</strong> status</div>
                <?php endif; ?>
                <div class="alert alert-primary border-0 p-2 small m-0"><i class="fa-solid fa-cart-shopping me-2"></i> Action updates logged in procurement system approval pipelines</div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-white border shadow-sm">
    <div class="card-header bg-white border-0 py-3"><h6 class="m-0 fw-bold text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">Recent Check-Ins</h6></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Item</th>
                    <th>Serial No.</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th class="pe-4">Last Checked</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $recent_activity->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td class="text-secondary font-monospace"><?php echo htmlspecialchars($row['serial_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td>
                            <span class="badge <?php echo ($row['current_status'] == 'In Stock') ? 'badge-instock' : (($row['current_status'] == 'Damaged') ? 'badge-damaged' : 'badge-pending'); ?> px-2 py-1">
                                <?php echo $row['current_status']; ?>
                            </span>
                        </td>
                        <td class="pe-4 text-muted"><?php echo $row['last_checked']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>