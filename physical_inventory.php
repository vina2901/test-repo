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

<style>
    .nav-pills .nav-link {
        color: #4b5563;
        background-color: #fff;
        border: 1px solid #e5e7eb;
        font-weight: 600;
        padding: 0.5rem 1.2rem;
        border-radius: 30px;
        transition: all 0.2s ease-in-out;
        font-size: 0.9rem;
    }
    .nav-pills .nav-link:hover {
        background-color: #f3f4f6;
        color: #1f2937;
        border-color: #cbd5e1;
    }
    .nav-pills .nav-link.active {
        background-color: #00b4d8 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(0, 180, 216, 0.25);
        border-color: #00b4d8 !important;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0" style="color: #0b2545;">Physical Inventory</h2>
        <p class="text-muted small m-0">Track individual tools and equipment units across facilities</p>
    </div>
    <button class="btn btn-custom-primary text-white fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUnitModal">
        <i class="fa-solid fa-plus me-2"></i>Add Item Unit
    </button>
</div>

<div class="mb-4">
    <ul class="nav nav-pills gap-2" id="statusFilterTabs">
        <li class="nav-item">
            <a href="physical_inventory.php?filter=All" class="nav-link <?php echo ($status_filter == 'All') ? 'active' : ''; ?>">
                <i class="fa-solid fa-list me-1"></i> All Units
            </a>
        </li>
        <li class="nav-item">
            <a href="physical_inventory.php?filter=In Stock" class="nav-link <?php echo ($status_filter == 'In Stock') ? 'active' : ''; ?>">
                <i class="fa-solid fa-circle-check me-1"></i> In Stock
            </a>
        </li>
        <li class="nav-item">
            <a href="physical_inventory.php?filter=Pending Purchase" class="nav-link <?php echo ($status_filter == 'Pending Purchase') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock me-1"></i> Pending
            </a>
        </li>
        <li class="nav-item">
            <a href="physical_inventory.php?filter=Damaged" class="nav-link <?php echo ($status_filter == 'Damaged') ? 'active' : ''; ?>">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Damaged
            </a>
        </li>
    </ul>
</div>

<div class="mb-4">
    <div class="input-group bg-white border rounded shadow-sm">
        <span class="input-group-text bg-white border-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" class="form-control border-0 py-2" placeholder="Search by name, serial number, or facility placement location...">
    </div>
</div>

<div class="card bg-white border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary" style="font-size: 0.85rem;">
                <tr>
                    <th class="ps-4" style="width: 25%;">Item Name / Category</th>
                    <th style="width: 20%;">Serial No.</th>
                    <th style="width: 20%;">Placement Location</th>
                    <th style="width: 15%;">Condition Note</th>
                    <th style="width: 10%;">Status</th>
                    <th class="pe-4" style="width: 10%;">Last Checked</th>
                </tr>
            </thead>
            <tbody>
                <?php if($inventory_units->num_rows > 0): ?>
                    <?php while($row = $inventory_units->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                <div class="text-muted small" style="font-size:0.75rem;"><?php echo htmlspecialchars($row['category']); ?></div>
                            </td>
                            <td class="font-monospace text-secondary" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['serial_no']); ?></td>
                            <td class="text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['location']); ?></td>
                            <td class="small text-secondary">
                                <span class="bg-light px-2 py-1 rounded border border-opacity-10"><i class="fa-solid fa-wrench opacity-50 me-1"></i><?php echo htmlspecialchars($row['condition_status']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo ($row['current_status'] == 'In Stock') ? 'badge-instock' : (($row['current_status'] == 'Damaged') ? 'badge-damaged' : 'badge-pending'); ?> px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;">
                                    <?php echo $row['current_status']; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-muted small" style="font-size: 0.8rem;"><i class="fa-regular fa-calendar me-1"></i><?php echo $row['last_checked']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="fa-solid fa-folder-open display-6 d-block mb-2 opacity-25"></i>No active tracking nodes found matching this filter criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="physical_inventory.php" method="POST" class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" style="font-size: 1.1rem;"><i class="fa-solid fa-cube me-2 text-warning"></i>Map Physical Hardware Asset Piece</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Select Item Model Definition Blueprint</label>
                <select name="catalog_id" class="form-select py-2" required>
    <option value="">-- Select Catalog Model Blueprint Reference --</option>
    <?php while($opt = $catalog_options->fetch_assoc()): ?>
        <option value="<?php echo $opt['catalog_id']; ?>"><?php echo htmlspecialchars($opt['item_name']); ?></option>
    <?php endwhile; ?> </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Unique Assigned Serial Number</label>
                <input type="text" name="serial_no" class="form-control py-2" required placeholder="e.g. CS-2024-005">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Physical Placement Facility Location</label>
                <input type="text" name="location" class="form-control py-2" required placeholder="e.g. Tool Room - Shelf 1">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Physical Structural Condition Note</label>
                <input type="text" name="condition_status" class="form-control py-2" value="Good" required placeholder="e.g. Brand New / Good">
            </div>
            <div class="mb-2">
                <label class="form-label small fw-bold text-secondary">Initial Status Designation Code</label>
                <select name="current_status" class="form-select py-2">
                    <option value="In Stock">In Stock (Active Operational Allocation Fleet)</option>
                    <option value="Damaged">Damaged (Requires Technical Repair Assessment)</option>
                    <option value="Pending Purchase">Pending Purchase</option>
                </select>
            </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="submit" name="add_physical_unit" class="btn btn-custom-primary text-white w-100 fw-bold py-2 shadow-sm">
            Commit Tracking Node Entry
        </button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>