<?php
require_once 'db.php';

// Handle adding physical item inventory units
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_physical_unit'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $serial     = $_POST['serial_no'];
    $location   = $_POST['location'];
    $condition  = $_POST['condition_status'];
    $status     = $_POST['current_status'];
    $today      = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO physical_inventory (catalog_id, serial_no, location, condition_status, current_status, last_checked) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $catalog_id, $serial, $location, $condition, $status, $today);
    $stmt->execute();
    $stmt->close();
    header("Location: physical_inventory.php");
    exit;
}

// Fetch Master Reference Options for Dropdown Selection list
$catalog_options = $conn->query("SELECT catalog_id, item_name FROM master_catalog ORDER BY item_name ASC");

// Filtering operations strategy layer logic 
$status_filter = $_GET['filter'] ?? 'All';
$query_string = "SELECT pi.*, mc.item_name, mc.category FROM physical_inventory pi JOIN master_catalog mc ON pi.catalog_id = mc.catalog_id";
if($status_filter != 'All') {
    $query_string .= " WHERE pi.current_status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query_string .= " ORDER BY pi.inventory_id DESC";
$inventory_units = $conn->query($query_string);

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0">Physical Inventory</h2>
        <p class="text-muted small m-0">Track individual tools and equipment by unit</p>
    </div>
    <button class="btn btn-warning text-white fw-bold px-4" style="background-color: #e45d14; border:none;" data-bs-toggle="modal" data-bs-target="#addUnitModal">+ Add Item</button>
</div>

<div class="d-flex gap-3 mb-4 border-bottom pb-2">
    <a href="physical_inventory.php?filter=All" class="text-decoration-none fw-bold <?php echo ($status_filter == 'All') ? 'text-warning border-bottom border-warning border-3 pb-2' : 'text-muted'; ?>">All</a>
    <a href="physical_inventory.php?filter=In Stock" class="text-decoration-none fw-bold <?php echo ($status_filter == 'In Stock') ? 'text-warning border-bottom border-warning border-3 pb-2' : 'text-muted'; ?>">In Stock</a>
    <a href="physical_inventory.php?filter=Pending Purchase" class="text-decoration-none fw-bold <?php echo ($status_filter == 'Pending Purchase') ? 'text-warning border-bottom border-warning border-3 pb-2' : 'text-muted'; ?>">Pending</a>
    <a href="physical_inventory.php?filter=Damaged" class="text-decoration-none fw-bold <?php echo ($status_filter == 'Damaged') ? 'text-warning border-bottom border-warning border-3 pb-2' : 'text-muted'; ?>">Damaged</a>
</div>

<div class="mb-4">
    <input type="text" class="form-control border bg-white py-2" placeholder="Search by name, serial number, or location...">
</div>

<div class="card bg-white border shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Item</th>
                    <th>Serial No.</th>
                    <th>Location</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th class="pe-4">Last Checked</th>
                </tr>
            </thead>
            <tbody>
                <?php if($inventory_units->num_rows > 0): ?>
                    <?php while($row = $inventory_units->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                <div class="text-muted small" style="font-size:0.75rem;"><?php echo $row['category']; ?></div>
                            </td>
                            <td class="font-monospace text-secondary small"><?php echo htmlspecialchars($row['serial_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td class="small text-secondary"><?php echo htmlspecialchars($row['condition_status']); ?></td>
                            <td>
                                <span class="badge <?php echo ($row['current_status'] == 'In Stock') ? 'badge-instock' : (($row['current_status'] == 'Damaged') ? 'badge-damaged' : 'badge-pending'); ?> px-2 py-1">
                                    <?php echo $row['current_status']; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-muted small"><?php echo $row['last_checked']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">No physical tracking nodes matching current filter criteria found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="physical_inventory.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Map Physical Hardware Asset Piece</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
            <div class="mb-3">
                <label class="form-label small">Select Item Model Definition Blueprint</label>
                <select name="catalog_id" class="form-select" required>
                    <option value="">-- Select Catalog Model Blueprint Reference --</option>
                    <?php while($opt = $catalog_options->fetch_assoc()): ?>
                        <option value="<?php echo $opt['catalog_id']; ?>"><?php echo htmlspecialchars($opt['item_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small">Unique Assigned Serial Number</label>
                <input type="text" name="serial_no" class="form-control" required placeholder="e.g. CS-2024-005">
            </div>
            <div class="mb-3">
                <label class="form-label small">Physical Placement Facility Location</label>
                <input type="text" name="location" class="form-control" required placeholder="e.g. Tool Room - Shelf 1">
            </div>
            <div class="mb-3">
                <label class="form-label small">Physical Structural Condition Note</label>
                <input type="text" name="condition_status" class="form-control" value="Good" required placeholder="e.g. Brand New / Good">
            </div>
            <div class="mb-2">
                <label class="form-label small">Initial Status Designation Code</label>
                <select name="current_status" class="form-select">
                    <option value="In Stock">In Stock (Active Operational Allocation Fleet)</option>
                    <option value="Damaged">Damaged (Requires Technical Repair Assessment)</option>
                    <option value="Pending Purchase">Pending Purchase</option>
                </select>
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_physical_unit" class="btn btn-warning text-white w-100 fw-bold" style="background-color: #e45d14; border:none;">Commit Tracking Node Entry</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>