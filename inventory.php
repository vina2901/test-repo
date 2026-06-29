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
        <h2 class="fw-bold m-0" style="color: #0b2545;">Master Inventory Registry</h2>
        <p class="text-muted small m-0">Real-time status overview of hardware assets and consumables at DAZ Training Center.</p>
    </div>
    <!-- Updated button using DAZ Branding scheme -->
    <button type="button" class="btn btn-custom-primary text-white fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="fa-solid fa-plus me-2"></i>Register New Stock
    </button>
</div>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4 bg-success bg-opacity-10 text-success d-flex align-items-center" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>
        <div><?php echo $success_msg; ?></div>
    </div>
<?php endif; ?>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4 bg-danger bg-opacity-10 text-danger d-flex align-items-center" role="alert">
        <i class="fa-solid fa-circle-xmark me-2"></i>
        <div><?php echo $error_msg; ?></div>
    </div>
<?php endif; ?>

<!-- MODERN HARDWARE REGISTRY TABLE CARD -->
<div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary" style="font-size: 0.85rem;">
                    <tr>
                        <th class="ps-4" style="width: 15%;">SKU / Code</th>
                        <th style="width: 30%;">Item Description</th>
                        <th style="width: 15%;">Classification</th>
                        <th style="width: 15%;">Storage Location</th>
                        <th style="width: 10%;">Qty Remaining</th>
                        <th class="pe-4 text-end" style="width: 15%;">Operational Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $is_low = $row['quantity_available'] <= $row['minimum_stock_level'];
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-secondary border px-2 py-1 font-monospace" style="font-size: 0.8rem;">
                                        <?php echo htmlspecialchars($row['item_code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                </td>
                                <td>
                                    <?php if($row['item_type'] == 'Asset'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-screwdriver-wrench me-1"></i>Asset</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-box-open me-1"></i>Consumable</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary" style="font-size: 0.9rem;">
                                    <i class="fa-solid fa-location-dot text-muted me-1 small"></i> <?php echo htmlspecialchars($row['storage_location']); ?>
                                </td>
                                <td class="fw-bold" style="font-size: 0.95rem;">
                                    <span class="<?php echo $is_low ? 'text-danger fw-black' : 'text-dark'; ?>">
                                        <?php echo $row['quantity_available']; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if($is_low): ?>
                                        <span class="badge badge-damaged px-3 py-1.5 rounded-pill" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-exclamation me-1"></i> Critical Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-instock px-3 py-1.5 rounded-pill" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check me-1"></i> Operational</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-boxes-stacked display-6 d-block mb-2 opacity-25"></i>
                                No hardware assets logged inside the central registry database yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PROVISION STOCK MODAL UI -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="inventory.php" method="POST" class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" style="font-size: 1.1rem;"><i class="fa-solid fa-boxes-packing me-2 text-warning"></i>Provision New Inventory Stock</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">Item Unique Code (Barcode / SKU)</label>
                <input type="text" name="item_code" class="form-control py-2" placeholder="e.g. TL-CUTTER-45" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">Item Name / Description</label>
                <input type="text" name="item_name" class="form-control py-2" placeholder="e.g. Heavy Duty Manual Tile Cutter" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">Inventory Classification</label>
                <select name="item_type" class="form-select py-2">
                    <option value="Asset">Asset (Lent Equipment / Reusable Tools)</option>
                    <option value="Consumable">Consumable (Classroom Materials / Supplies)</option>
                </select>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-bold small text-secondary">Starting Quantity</label>
                    <input type="number" name="quantity" class="form-control py-2" value="1" min="0" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold small text-secondary">Minimum Warning Threshold</label>
                    <input type="number" name="min_level" class="form-control py-2" value="3" min="0" required>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label fw-bold small text-secondary">Physical Facility Location</label>
                <input type="text" name="location" class="form-control py-2" placeholder="e.g. Room 3 Shelf A" required>
            </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="button" class="btn btn-light px-4 fw-bold py-2 rounded" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_item" class="btn btn-custom-primary text-white px-4 fw-bold py-2 shadow-sm">Commit to Registry</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>