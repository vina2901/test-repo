<?php
require_once 'db.php';

// Handle addition of a catalog type item definition
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_catalog_type'])) {
    $name = $_POST['item_name'];
    $desc = $_POST['description'];
    $cat  = $_POST['category'];
    $qual = $_POST['qualification']; // Bago: Qualification
    $unit = $_POST['unit_type'];
    $min  = (int)$_POST['minimum_stock'];

    // Idinagdag ang qualification sa SQL query
    $stmt = $conn->prepare("INSERT INTO master_catalog (item_name, description, category, qualification, unit_type, minimum_stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $name, $desc, $cat, $qual, $unit, $min);
    $stmt->execute();
    $stmt->close();
    header("Location: master_catalog.php");
    exit;
}

// Listahan ng mga Qualifications
$qualifications = [
    'SMAW' => 'Shielded Metal Arc Welding (SMAW)',
    'EIM' => 'Electrical Installation and Maintenance (EIM)',
    'Tile_Setting' => 'Tile Setting',
    'CONSP' => 'Construction Painting (CONSP)',
    'Carpentry' => 'Carpentry'
];

// Orixinal na mga Categories
$categories = ['Power Tools', 'Hand Tools', 'PPE / Safety', 'Chemicals & Solvents'];

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0">Master Catalog</h2>
        <p class="text-muted small m-0">Item types & equipment definitions by qualification</p>
    </div>
    <button class="btn btn-warning text-white fw-bold px-4" style="background-color: #e45d14; border:none;" data-bs-toggle="modal" data-bs-target="#addTypeModal">+ Add Item Type</button>
</div>

<div class="mb-4">
    <div class="input-group bg-white border rounded">
        <span class="input-group-text bg-white border-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" class="form-control border-0 py-2" placeholder="Search catalog...">
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="qualificationTabs" role="tablist">
    <?php 
    $isFirst = true;
    foreach($qualifications as $key => $display_name): 
    ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $isFirst ? 'active fw-bold' : ''; ?>" 
                    style="<?php echo $isFirst ? 'color: #e45d14;' : 'color: #6c757d;'; ?>"
                    id="tab-<?php echo $key; ?>" 
                    data-bs-toggle="tab" 
                    data-bs-target="#content-<?php echo $key; ?>" 
                    type="button" 
                    role="tab">
                <?php echo $display_name; ?>
            </button>
        </li>
    <?php 
        $isFirst = false;
    endforeach; 
    ?>
</ul>

<div class="tab-content" id="qualificationTabsContent">
    <?php 
    $isFirst = true;
    foreach($qualifications as $key => $display_name): 
    ?>
        <div class="tab-pane fade <?php echo $isFirst ? 'show active' : ''; ?>" id="content-<?php echo $key; ?>" role="tabpanel">
            
            <?php foreach($categories as $cat_name): 
                // Filtered query gamit ang specific qualification at category
                $stmt = $conn->prepare("SELECT * FROM master_catalog WHERE qualification = ? AND category = ? ORDER BY item_name ASC");
                $stmt->bind_param("ss", $display_name, $cat_name);
                $stmt->execute();
                $items = $stmt->get_result();
            ?>
                <div class="card bg-white border shadow-sm mb-4">
                    <div class="card-header border-0 py-3 bg-light">
                        <h6 class="m-0 fw-bold text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            <?php echo $cat_name; ?>
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 25%;">Name</th>
                                    <th style="width: 45%;">Description</th>
                                    <th style="width: 15%;">Unit <span class="text-muted font-normal text-xs">(click to edit)</span></th>
                                    <th class="pe-4" style="width: 15%;">Min. Stock <span class="text-muted font-normal text-xs">(click to edit)</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($items->num_rows > 0): ?>
                                    <?php while($row = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="font-monospace text-secondary"><?php echo htmlspecialchars($row['unit_type']); ?></td>
                                            <td class="pe-4 font-monospace"><?php echo $row['minimum_stock']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3 small">No definitions added under this classification cluster yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php 
                $stmt->close();
            endforeach; 
            ?>

        </div>
    <?php 
        $isFirst = false;
    endforeach; 
    ?>
</div>

<div class="modal fade" id="addTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="master_catalog.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Register Base Item Definition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
            <div class="mb-3">
                <label class="form-label small">Item Name</label>
                <input type="text" name="item_name" class="form-control" required placeholder="e.g. Circular Saw">
            </div>
            <div class="mb-3">
                <label class="form-label small">Operational Description</label>
                <input type="text" name="description" class="form-control" required placeholder="Electric circular saw for cutting wood...">
            </div>
            
            <div class="mb-3">
                <label class="form-label small">Qualification Course</label>
                <select name="qualification" class="form-select" required>
                    <?php foreach($qualifications as $key => $display_name): ?>
                        <option value="<?php echo $display_name; ?>"><?php echo $display_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select">
                    <?php foreach($categories as $cat_name): ?>
                        <option value="<?php echo $cat_name; ?>"><?php echo $cat_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small">Unit Type</label>
                    <input type="text" name="unit_type" class="form-control" value="pcs" required>
                </div>
                <div class="col-6">
                    <label class="form-label small">Minimum Stock Metric</label>
                    <input type="number" name="minimum_stock" class="form-control" value="2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_catalog_type" class="btn btn-warning text-white w-100 fw-bold" style="background-color: #e45d14; border:none;">Commit Definition Blueprint</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>