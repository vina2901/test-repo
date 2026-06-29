<?php
require_once 'db.php';

// --- 1. HANDLE ADDITION (INSERT) ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_catalog_type'])) {
    $name = $_POST['item_name'];
    $desc = $_POST['description'];
    $cat  = $_POST['category'];
    $qual = $_POST['qualification'];
    $unit = $_POST['unit_type'];
    $min  = (int)$_POST['minimum_stock'];
    
    $image_name = null;
    if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = __DIR__ . "/uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_extension = strtolower(pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if(in_array($file_extension, $allowed_extensions)) {
            $image_name = time() . '_' . uniqid() . '.' . $file_extension;
            if(!move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_dir . $image_name)) {
                $image_name = null;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO master_catalog (item_name, description, category, qualification, unit_type, minimum_stock, item_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $name, $desc, $cat, $qual, $unit, $min, $image_name);
    $stmt->execute();
    $stmt->close();
    header("Location: master_catalog.php");
    exit;
}

// --- 2. HANDLE EDIT/UPDATE LOGIC ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_catalog_type'])) {
    $catalog_id = (int)$_POST['catalog_id'];
    $name = $_POST['item_name'];
    $desc = $_POST['description'];
    $cat  = $_POST['category'];
    $qual = $_POST['qualification'];
    $unit = $_POST['unit_type'];
    $min  = (int)$_POST['minimum_stock'];
    $existing_image = $_POST['existing_image'];

    $image_name = $existing_image;

    if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = __DIR__ . "/uploads/";
        $file_extension = strtolower(pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_image = time() . '_' . uniqid() . '.' . $file_extension;
            if(move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_dir . $new_image)) {
                $image_name = $new_image;
                
                if(!empty($existing_image) && file_exists($target_dir . $existing_image)) {
                    unlink($target_dir . $existing_image);
                }
            }
        }
    }

    $stmt = $conn->prepare("UPDATE master_catalog SET item_name = ?, description = ?, category = ?, qualification = ?, unit_type = ?, minimum_stock = ?, item_image = ? WHERE catalog_id = ?");
    $stmt->bind_param("sssssisi", $name, $desc, $cat, $qual, $unit, $min, $image_name, $catalog_id);
    $stmt->execute();
    $stmt->close();
    header("Location: master_catalog.php");
    exit;
}

// --- 3. HANDLE DELETE LOGIC ---
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $catalog_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT item_image FROM master_catalog WHERE catalog_id = ?");
    $stmt->bind_param("i", $catalog_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if($res && !empty($res['item_image'])) {
        $file_path = __DIR__ . "/uploads/" . $res['item_image'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM master_catalog WHERE catalog_id = ?");
    $stmt->bind_param("i", $catalog_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: master_catalog.php");
    exit;
}

// 4. HANDLE SEARCH KEYWORD EXTRACTION
$search_keyword = "";
if(isset($_GET['search'])) {
    $search_keyword = trim($_GET['search']);
}

// Listahan ng mga Qualifications
$qualifications = [
    'SMAW' => 'Shielded Metal Arc Welding (SMAW)',
    'EIM' => 'Electrical Installation and Maintenance (EIM)',
    'Tile_Setting' => 'Tile Setting',
    'CONSP' => 'Construction Painting (CONSP)',
    'Carpentry' => 'Carpentry'
];

// BAGO: SMART AUTO-TAB FOCUS DETECTION
// Kung may search keyword, hahanapin natin kung saang qualification unang may match para i-force open ang tab na 'yon
$active_tab_key = 'SMAW'; // Default fallback tab
if(!empty($search_keyword)) {
    foreach($qualifications as $key => $display_name) {
        $like_parameter = "%" . $search_keyword . "%";
        $check_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM master_catalog WHERE qualification = ? AND (item_name LIKE ? OR description LIKE ?)");
        $check_stmt->bind_param("sss", $display_name, $like_parameter, $like_parameter);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if($check_res && $check_res['cnt'] > 0) {
            $active_tab_key = $key; // Nahanap ang qualification kung saan may tumatama na tool record!
            break; // Stop loop kapag nahanap na ang unang match
        }
    }
}

// Original na mga Categories
$categories = ['Power Tools', 'Hand Tools', 'PPE / Safety', 'Chemicals & Solvents'];

include_once 'includes/header.php';
?>

<style>
    .nav-pills .nav-link {
        color: #4b5563; background-color: #fff; border: 1px solid #e5e7eb;
        font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 30px;
        transition: all 0.2s ease-in-out; font-size: 0.9rem;
    }
    .nav-pills .nav-link.active {
        background-color: #00b4d8 !important; color: #fff !important;
        box-shadow: 0 4px 12px rgba(0, 180, 216, 0.25); border-color: #00b4d8 !important;
    }
    .category-badge-header { background-color: #f8fafc; border-bottom: 1px solid #f1f5f9; }
    .tool-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; background-color: #f8fafc; display: block; cursor: pointer; transition: transform 0.2s ease; }
    .tool-thumb:hover { transform: scale(1.08); border-color: #00b4d8; }
    .tool-thumb-placeholder { width: 50px; height: 50px; border-radius: 6px; background-color: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; border: 1px dotted #cbd5e1; font-size: 1.2rem; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0" style="color: #0b2545;">Master Catalog</h2>
        <p class="text-muted small m-0">Item types & equipment definitions by qualification course clusters</p>
    </div>
    <button class="btn btn-custom-primary text-white fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="fa-solid fa-plus me-2"></i>Add Item Type
    </button>
</div>

<form action="master_catalog.php" method="GET" class="mb-4">
    <div class="input-group bg-white border rounded shadow-sm">
        <span class="input-group-text bg-white border-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" class="form-control border-0 py-2" placeholder="Type tool name or specifications and press Enter..." value="<?php echo htmlspecialchars($search_keyword); ?>">
        <?php if(!empty($search_keyword)): ?>
            <a href="master_catalog.php" class="btn btn-light bg-white border-0 text-muted d-flex align-items-center justify-content-center pe-3"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
    </div>
    <?php if(!empty($search_keyword)): ?>
        <div class="form-text text-secondary mt-1 ms-1" style="font-size: 0.8rem;">
            Active Search Filter: <span class="badge bg-secondary text-white rounded-pill px-2 py-1">"<?php echo htmlspecialchars($search_keyword); ?>"</span>
        </div>
    <?php endif; ?>
</form>

<!-- MODIFIED: GUMAGAMIT NA NG SMART ACTIVE TAB KEY -->
<ul class="nav nav-pills gap-2 mb-4 flex-nowrap overflow-auto pb-2" id="qualificationTabs" role="tablist">
    <?php 
    foreach($qualifications as $key => $display_name): 
        $isActive = ($key === $active_tab_key); // Tinitingnan kung ito ba ang dapat naka-focus na tab
    ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $isActive ? 'active' : ''; ?>" id="tab-<?php echo $key; ?>" data-bs-toggle="tab" data-bs-target="#content-<?php echo $key; ?>" type="button" role="tab">
                <?php echo $display_name; ?>
            </button>
        </li>
    <?php 
    endforeach; 
    ?>
</ul>

<!-- MODIFIED: GUMAGAMIT NA RIN NG SMART ACTIVE TAB KEY PARA SA CONTENT -->
<div class="tab-content" id="qualificationTabsContent">
    <?php 
    foreach($qualifications as $key => $display_name): 
        $isActive = ($key === $active_tab_key);
    ?>
        <div class="tab-pane fade <?php echo $isActive ? 'show active' : ''; ?>" id="content-<?php echo $key; ?>" role="tabpanel">
            
            <?php foreach($categories as $cat_name): 
                if(!empty($search_keyword)) {
                    $stmt = $conn->prepare("SELECT * FROM master_catalog WHERE qualification = ? AND category = ? AND (item_name LIKE ? OR description LIKE ?) ORDER BY item_name ASC");
                    $like_parameter = "%" . $search_keyword . "%";
                    $stmt->bind_param("ssss", $display_name, $cat_name, $like_parameter, $like_parameter);
                } else {
                    $stmt = $conn->prepare("SELECT * FROM master_catalog WHERE qualification = ? AND category = ? ORDER BY item_name ASC");
                    $stmt->bind_param("ss", $display_name, $cat_name);
                }
                
                $stmt->execute();
                $items = $stmt->get_result();
            ?>
                <div class="card bg-white border-0 shadow-sm mb-4" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-header category-badge-header py-3">
                        <div class="d-flex align-items-center">
                            <span class="p-2 rounded me-2 d-inline-flex justify-content-center align-items-center" style="width: 28px; height: 28px; background-color: #e0f2fe !important; color: #0369a1 !important;">
                                <i class="fa-solid fa-layer-group" style="font-size: 0.85rem;"></i>
                            </span>
                            <h6 class="m-0 fw-bold text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <?php echo $cat_name; ?>
                            </h6>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <thead class="table-light text-secondary" style="font-size: 0.85rem;">
                                <tr>
                                    <th class="ps-4" style="width: 12%;">Visual</th>
                                    <th style="width: 20%;">Name</th>
                                    <th style="width: 33%;">Specifications</th>
                                    <th style="width: 10%;">Unit</th>
                                    <th style="width: 10%;">Min. Stock</th>
                                    <th class="pe-4 text-center" style="width: 15%;">Actions</th> </tr>
                            </thead>
                            <tbody>
                                <?php if($items->num_rows > 0): ?>
                                    <?php while($row = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if(!empty($row['item_image']) && file_exists(__DIR__ . '/uploads/' . $row['item_image'])): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($row['item_image']); ?>" alt="Tool View" class="tool-thumb shadow-sm" data-bs-toggle="modal" data-bs-target="#viewImageModal" data-imgsrc="uploads/<?php echo htmlspecialchars($row['item_image']); ?>" data-toolname="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                <?php else: ?>
                                                    <div class="tool-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="font-monospace text-secondary" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['unit_type']); ?></td>
                                            <td class="font-monospace fw-semibold text-dark" style="font-size: 0.85rem;"><?php echo $row['minimum_stock']; ?></td>
                                            
                                            <td class="pe-4 text-center">
                                                <div class="d-inline-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-secondary px-2 rounded-pill edit-btn"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editTypeModal"
                                                            data-id="<?php echo $row['catalog_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($row['item_name']); ?>"
                                                            data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                                            data-qual="<?php echo htmlspecialchars($row['qualification']); ?>"
                                                            data-cat="<?php echo htmlspecialchars($row['category']); ?>"
                                                            data-unit="<?php echo htmlspecialchars($row['unit_type']); ?>"
                                                            data-min="<?php echo $row['minimum_stock']; ?>"
                                                            data-img="<?php echo htmlspecialchars($row['item_image']); ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    
                                                    <a href="master_catalog.php?action=delete&id=<?php echo $row['catalog_id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger px-2 rounded-pill delete-btn"
                                                       onclick="return confirm('Are you sure that you want to permanently delete \'<?php echo addslashes(htmlspecialchars($row['item_name'])); ?>\' from the Master Catalog?');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4 small"><i class="fa-solid fa-circle-info me-2 opacity-50"></i>No matching inventory definitions found in this section.</td></tr>
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
    endforeach; 
    ?>
</div>

<!-- --- MODAL 1: ADD ITEM TYPE --- -->
<div class="modal fade" id="addTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="master_catalog.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" style="font-size: 1.1rem;"><i class="fa-solid fa-square-plus me-2 text-warning"></i>Register Base Item Definition</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Item Name</label>
                <input type="text" name="item_name" class="form-control py-2" required placeholder="e.g. Circular Saw">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Specifications</label>
                <input type="text" name="description" class="form-control py-2" required placeholder="Electric circular saw for cutting wood...">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Qualification Course</label>
                <select name="qualification" class="form-select py-2" required>
                    <?php foreach($qualifications as $key => $display_name): ?>
                        <option value="<?php echo $display_name; ?>"><?php echo $display_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Category</label>
                <select name="category" class="form-select py-2">
                    <?php foreach($categories as $cat_name): ?>
                        <option value="<?php echo $cat_name; ?>"><?php echo $cat_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary"><i class="fa-solid fa-camera text-muted me-1"></i>Tool Representation Image (Optional)</label>
                <input type="file" name="item_image" class="form-control py-2" accept="image/*">
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small fw-bold text-secondary">Unit Type</label>
                    <input type="text" name="unit_type" class="form-control py-2" value="pcs" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-secondary">Minimum Stock Metric</label>
                    <input type="number" name="minimum_stock" class="form-control py-2" value="2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="submit" name="add_catalog_type" class="btn btn-custom-primary text-white w-100 fw-bold py-2 shadow-sm">Commit Definition Blueprint</button>
      </div>
    </form>
  </div>
</div>

<!-- --- MODAL 2: EDIT/UPDATE DATA POPUP --- -->
<div class="modal fade" id="editTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="master_catalog.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" style="font-size: 1.1rem;"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Modify Item Definition</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <input type="hidden" name="catalog_id" id="edit_catalog_id">
            <input type="hidden" name="existing_image" id="edit_existing_image">

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Item Name</label>
                <input type="text" name="item_name" id="edit_item_name" class="form-control py-2" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Specifications</label>
                <input type="text" name="description" id="edit_description" class="form-control py-2" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Qualification Course</label>
                <select name="qualification" id="edit_qualification" class="form-select py-2" required>
                    <?php foreach($qualifications as $key => $display_name): ?>
                        <option value="<?php echo $display_name; ?>"><?php echo $display_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Category</label>
                <select name="category" id="edit_category" class="form-select py-2">
                    <?php foreach($categories as $cat_name): ?>
                        <option value="<?php echo $cat_name; ?>"><?php echo $cat_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary"><i class="fa-solid fa-camera text-muted me-1"></i>Replace Image (Leave blank to keep current)</label>
                <input type="file" name="item_image" class="form-control py-2" accept="image/*">
                <div class="form-text text-muted small" id="edit_img_status"></div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small fw-bold text-secondary">Unit Type</label>
                    <input type="text" name="unit_type" id="edit_unit_type" class="form-control py-2" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-secondary">Minimum Stock Metric</label>
                    <input type="number" name="minimum_stock" id="edit_minimum_stock" class="form-control py-2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="submit" name="update_catalog_type" class="btn btn-success text-white w-100 fw-bold py-2 shadow-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- --- MODAL 3: BIG PREVIEW --- -->
<div class="modal fade" id="viewImageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" id="modalToolTitle" style="font-size: 1.1rem;">Tool Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-2 text-center bg-light">
          <img src="" id="modalBigImage" class="img-fluid rounded border shadow-sm" style="max-height: 450px; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

<!-- --- JAVASCRIPT LAYER INTERACTION INTERFACE --- -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    var viewImageModal = document.getElementById('viewImageModal');
    if (viewImageModal) {
        viewImageModal.addEventListener('show.bs.modal', function (event) {
            var triggerElement = event.relatedTarget;
            var imageSource = triggerElement.getAttribute('data-imgsrc');
            var toolName = triggerElement.getAttribute('data-toolname');
            
            viewImageModal.querySelector('#modalToolTitle').textContent = toolName;
            viewImageModal.querySelector('#modalBigImage').src = imageSource;
        });
    }

    var editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('edit_catalog_id').value = this.getAttribute('data-id');
            document.getElementById('edit_item_name').value = this.getAttribute('data-name');
            document.getElementById('edit_description').value = this.getAttribute('data-desc');
            document.getElementById('edit_qualification').value = this.getAttribute('data-qual');
            document.getElementById('edit_category').value = this.getAttribute('data-cat');
            document.getElementById('edit_unit_type').value = this.getAttribute('data-unit');
            document.getElementById('edit_minimum_stock').value = this.getAttribute('data-min');
            document.getElementById('edit_existing_image').value = this.getAttribute('data-img');

            var imgFile = this.getAttribute('data-img');
            var statusDiv = document.getElementById('edit_img_status');
            if(imgFile) {
                statusDiv.innerHTML = "<span class='text-success'><i class='fa-solid fa-image me-1'></i> Has current image: " + imgFile + "</span>";
            } else {
                statusDiv.innerHTML = "<span class='text-muted'><i class='fa-solid fa-image-slash me-1'></i> No image currently assigned.</span>";
            }
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>