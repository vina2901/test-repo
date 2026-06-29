<?php 
require_once 'db.php'; 

// Form Submission Action logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_code = $_POST['item_code'];
    $item_name = $_POST['item_name'];
    $item_type = $_POST['item_type'];
    $quantity = (int)$_POST['quantity'];
    $min_level = (int)$_POST['min_level'];
    $location = $_POST['location'];

    $stmt = $conn->prepare("INSERT INTO inventory_items (item_code, item_name, item_type, quantity_available, minimum_stock_level, storage_location) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdds", $item_code, $item_name, $item_type, $quantity, $min_level, $location);
    
    if ($stmt->execute()) {
        $success_msg = "Item securely provisioned into the registry database.";
    } else {
        $error_msg = "Database Error: " . $conn->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM inventory_items ORDER BY item_id DESC");
include_once 'includes/header.php'; 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-slate-800 m-0">Master Inventory Registry</h2>
        <p class="text-muted small m-0">Real-time status overview of assets and consumables at Daz Training Center.</p>
    </div>
    <button type="button" class="btn btn-primary px-4 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="fa-solid fa-plus me-2"></i> Register New Stock
    </button>
</div>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert"><?php echo $success_msg; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">SKU / Code</th>
                        <th>Item Description</th>
                        <th>Classification</th>
                        <th>Storage Location</th>
                        <th>Qty Remaining</th>
                        <th class="pe-4 text-end">Operational Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $is_low = $row['quantity_available'] <= $row['minimum_stock_level'];
                        ?>
                            <tr>
                                <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 font-monospace"><?php echo htmlspecialchars($row['item_code']); ?></span></td>
                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><span class="text-muted text-sm"><?php echo $row['item_type']; ?></span></td>
                                <td><i class="fa-solid fa-location-dot text-muted me-1 small"></i> <?php echo htmlspecialchars($row['storage_location']); ?></td>
                                <td class="fw-bold"><?php echo $row['quantity_available']; ?></td>
                                <td class="pe-4 text-end">
                                    <?php if($is_low): ?>
                                        <span class="badge bg-danger-subtle text-danger px-3 py-1.5 rounded-pill"><i class="fa-solid fa-circle-exclamation me-1"></i> Critical Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Operational</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No hardware assets logged inside the central registry database yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="inventory.php" method="POST" class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title fw-bold">Provision New Inventory Stock</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label fw-medium small text-muted">Item Unique Code (Barcode / SKU)</label>
                <input type="text" name="item_code" class="form-control py-2" placeholder="e.g. TL-CUTTER-45" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium small text-muted">Item Name Description</label>
                <input type="text" name="item_name" class="form-control py-2" placeholder="e.g. Heavy Duty Manual Tile Cutter" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium small text-muted">Inventory Classification</label>
                <select name="item_type" class="form-select py-2">
                    <option value="Asset">Asset (Lent Equipment / Reusable Tools)</option>
                    <option value="Consumable">Consumable (Classroom Materials / Supplies)</option>
                </select>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-medium small text-muted">Starting Quantity</label>
                    <input type="number" name="quantity" class="form-control py-2" value="1" min="0" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-medium small text-muted">Minimum Warning Threshold</label>
                    <input type="number" name="min_level" class="form-control py-2" value="3" min="0" required>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label fw-medium small text-muted">Physical Facility Location</label>
                <input type="text" name="location" class="form-control py-2" placeholder="e.g. Room 3 Shelf A" required>
            </div>
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_item" class="btn btn-primary px-4 fw-medium">Commit to Registry</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>