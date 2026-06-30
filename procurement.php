<?php
require_once 'db.php';

// Handle Action item Approvals
if(isset($_GET['action']) && $_GET['action'] == 'approve') {
    $req_id = (int)$_GET['id'];
    $conn->query("UPDATE procurement_requests SET approval_status = 'Approved' WHERE request_id = $req_id");
    header("Location: procurement.php");
    exit;
}

// Handle Add New Request submissions 
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_request'])) {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $reason = $_POST['reason'];
    $qty = (int)$_POST['quantity'];
    $unit_price = (float)$_POST['unit_price']; 
    
    $cost = $qty * $unit_price; 
    $user = $_POST['requested_by'];
    
    date_default_timezone_set('Asia/Manila'); 
    $timestamp = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO procurement_requests (item_name, category, priority, reason, quantity, unit_price, estimated_cost, requested_by, request_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiddss", $item_name, $category, $priority, $reason, $qty, $unit_price, $cost, $user, $timestamp);
    $stmt->execute();
    $stmt->close();
    header("Location: procurement.php");
    exit;
}

// Analytics calculations metrics
$totals = $conn->query("SELECT COUNT(*) as total_req, SUM(CASE WHEN approval_status='Pending' THEN 1 ELSE 0 END) as pending_count, SUM(CASE WHEN approval_status='Pending' THEN estimated_cost ELSE 0 END) as pending_cost FROM procurement_requests")->fetch_assoc();

$requests = $conn->query("SELECT * FROM procurement_requests ORDER BY request_id DESC");
include_once 'includes/header.php';
?>

<style>
    .procurement-card {
        border: 1px solid #e5e7eb;
        border-top: 4px solid #0b2545; 
        border-radius: 8px;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .procurement-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }
    .request-row {
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .request-row:hover {
        border-color: #cbd5e1 !important;
        background-color: #f8fafc !important;
    }
    /* Dagdag na styling para sa Form Inputs ng Modal */
    .modal-input-custom:focus {
        border-color: #0b2545 !important;
        box-shadow: 0 0 0 0.25rem rgba(11, 37, 69, 0.15) !important;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0" style="color: #0b2545;">Procurement / To-Buy</h2>
        <p class="text-muted small m-0">Purchase requests and acquisition approval queue</p>
    </div>
    <button class="btn btn-custom-primary text-white fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#reqModal">
        <i class="fa-solid fa-plus me-2"></i>New Request
    </button>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm">
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1">Total Requests</div>
            <div class="display-6 fw-bold text-dark"><?php echo $totals['total_req'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm" style="border-top-color: #fccb05;">
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1" style="color: #d97706 !important;">Pending Approval</div>
            <div class="display-6 fw-bold" style="color: #d97706;"><?php echo $totals['pending_count'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm" style="border-top-color: #00b4d8;">
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1">Est. Cost (Pending)</div>
            <div class="display-6 fw-bold" style="color: #0b2545;">₱<?php echo number_format($totals['pending_cost'] ?? 0, 2); ?></div>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3 text-secondary" style="font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">Request Log Sheets</h5>
<div class="d-flex flex-column gap-3 mb-5">
    <?php if($requests->num_rows > 0): ?>
        <?php while($row = $requests->fetch_assoc()): ?>
            <div class="p-4 bg-white border request-row shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-3" style="border-left: 5px solid <?php echo ($row['priority'] == 'high') ? '#dc3545' : (($row['priority'] == 'medium') ? '#ffc107' : '#6c757d'); ?> !important;">
                
                <div style="flex: 1; min-width: 280px;">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="fw-bold m-0 text-dark" style="font-size: 1.2rem; color: #0b2545 !important;"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                        
                        <?php 
                            $p_class = ($row['priority'] == 'high') ? 'bg-danger text-danger' : (($row['priority'] == 'medium') ? 'bg-warning text-warning' : 'bg-secondary text-secondary');
                        ?>
                        <span class="badge <?php echo $p_class; ?> bg-opacity-10 text-uppercase font-monospace px-2 py-1" style="font-size: 0.7rem; font-weight: 700;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $row['priority']; ?>
                        </span>
                        
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-folder me-1 opacity-50"></i><?php echo htmlspecialchars($row['category']); ?></span>
                        
                        <?php if($row['approval_status'] == 'Approved'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1" style="font-size: 0.75rem; font-weight: 600;">
                                <i class="fa-solid fa-circle-check me-1"></i>Approved
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1" style="font-size: 0.75rem; font-weight: 600;">
                                <i class="fa-solid fa-clock me-1"></i>Pending
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted small mb-3" style="max-width: 700px; border-left: 2px solid #e2e8f0; padding-left: 10px; font-style: italic;">
                        "<?php echo htmlspecialchars($row['reason']); ?>"
                    </p>
                    
                    <div class="text-muted small">
                        <i class="fa-solid fa-user me-1 opacity-50"></i> Requested by: <strong class="text-dark"><?php echo htmlspecialchars($row['requested_by']); ?></strong> 
                        <span class="mx-2 text-silver">|</span> 
                        <i class="fa-regular fa-clock me-1 opacity-50"></i> <?php echo date_format(date_create($row['request_date']), "M d, Y - h:i A"); ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap" style="background: #f8fafc; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div class="text-center px-2">
                        <span class="d-block text-uppercase text-muted font-monospace" style="font-size: 0.65rem; letter-spacing: 0.05em;">Quantity</span>
                        <span class="fw-bold text-dark" style="font-size: 1.05rem;"><i class="fa-solid fa-boxes-stacked me-1 opacity-50" style="font-size: 0.85rem;"></i><?php echo $row['quantity']; ?></span>
                    </div>
                    
                    <div style="width: 1px; height: 30px; background: #cbd5e1;" class="mx-2 d-none d-sm-block"></div>
                    
                    <div class="text-center px-2">
                        <span class="d-block text-uppercase text-muted font-monospace" style="font-size: 0.65rem; letter-spacing: 0.05em;">Price Each</span>
                        <span class="fw-bold text-secondary" style="font-size: 1.05rem;">₱<?php echo number_format($row['unit_price'] ?? 0, 2); ?></span>
                    </div>
                    
                    <div style="width: 1px; height: 30px; background: #cbd5e1;" class="mx-2 d-none d-sm-block"></div>
                    
                    <div class="text-center px-3">
                        <span class="d-block text-uppercase text-primary font-monospace fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Total Cost</span>
                        <span class="fw-bold text-primary" style="font-size: 1.15rem;">₱<?php echo number_format($row['estimated_cost'], 2); ?></span>
                    </div>
                </div>

                <div class="text-end" style="min-width: 120px;">
                    <?php if($row['approval_status'] == 'Pending'): ?>
                        <a href="procurement.php?action=approve&id=<?php echo $row['request_id']; ?>" class="btn btn-success btn-sm w-100 fw-bold shadow-sm py-2 px-3 rounded">
                            <i class="fa-solid fa-check me-1"></i>Approve
                        </a>
                    <?php else: ?>
                        <span class="text-muted small d-block text-center border rounded py-2 bg-light font-monospace" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-lock me-1 text-secondary"></i>Processed
                        </span>
                    <?php endif; ?>
                </div>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="p-5 bg-white border text-center text-muted rounded shadow-sm">
            <i class="fa-solid fa-receipt display-6 d-block mb-2 opacity-25"></i> No procurement records logged yet.
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="reqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <form action="procurement.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      
      <div class="modal-header text-white p-3 border-0" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold d-flex align-items-center" style="font-size: 1.1rem;">
            <i class="fa-solid fa-square-plus me-2 text-warning" style="font-size: 1.3rem;"></i>Create Procurement Ticket
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-4 bg-white">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark d-flex align-items-center mb-1">
                    <i class="fa-solid fa-screwdriver-wrench me-1 text-secondary opacity-75"></i> Item Name / Description
                </label>
                <input type="text" name="item_name" class="form-control py-2 modal-input-custom" required placeholder="e.g. Heavy Duty Safety Harness" style="border-radius: 6px;">
            </div>
            
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small fw-bold text-dark d-flex align-items-center mb-1">
                        <i class="fa-solid fa-folder-open me-1 text-secondary opacity-75"></i> Category
                    </label>
                    <select name="category" class="form-select py-2 modal-input-custom" style="border-radius: 6px;">
                        <option value="Power Tools">Power Tools</option>
                        <option value="Hand Tools">Hand Tools</option>
                        <option value="PPE / Safety">PPE / Safety</option>
                        <option value="Chemicals & Solvents">Chemicals & Solvents</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-bold text-dark d-flex align-items-center mb-1">
                        <i class="fa-solid fa-circle-exclamation me-1 text-secondary opacity-75"></i> Priority Level
                    </label>
                    <select name="priority" class="form-select py-2 modal-input-custom" style="border-radius: 6px;">
                        <option value="low">🟢 Low Urgency</option>
                        <option value="medium" selected>🟡 Medium Priority</option>
                        <option value="high">🔴 High Urgency</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark d-flex align-items-center mb-1">
                    <i class="fa-solid fa-comment-dots me-1 text-secondary opacity-75"></i> Justification / Reason
                </label>
                <textarea name="reason" class="form-control modal-input-custom" rows="3" required placeholder="State the main reason (e.g., Mandatory replacement before site inspection...)" style="border-radius: 6px; resize: none;"></textarea>
            </div>
            
            <hr class="text-muted opacity-25 my-3">
            
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <label class="form-label small fw-bold text-dark mb-1">Quantity</label>
                    <div class="input-group">
                        <input type="number" name="quantity" id="input_qty" class="form-control py-2 modal-input-custom text-center fw-bold" value="1" min="1" required style="border-radius: 6px;">
                    </div>
                </div>
                <div class="col-8">
                    <label class="form-label small fw-bold text-dark mb-1">Unit Price (₱ Price per piece)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted" style="border-top-left-radius: 6px; border-bottom-left-radius: 6px;">₱</span>
                        <input type="number" name="unit_price" id="input_price" step="0.01" class="form-control py-2 modal-input-custom border-start-0" placeholder="0.00" required style="border-top-right-radius: 6px; border-bottom-right-radius: 6px;">
                    </div>
                </div>
            </div>
            
            <div class="p-3 mb-3 d-flex justify-content-between align-items-center" style="background-color: #f0f4f8; border-radius: 8px; border-left: 4px solid #0b2545;">
                <span class="small fw-bold text-secondary d-flex align-items-center">
                    <i class="fa-solid fa-calculator me-2 text-primary" style="font-size: 1rem;"></i> Estimated Total Cost:
                </span>
                <span class="fw-bold" style="font-size: 1.25rem; color: #0b2545;">₱<span id="live_total_preview">0.00</span></span>
            </div>

            <div class="mt-3">
                <label class="form-label small fw-bold text-dark d-flex align-items-center mb-1">
                    <i class="fa-solid fa-user-pen me-1 text-secondary opacity-75"></i> Requested By (Your Name)
                </label>
                <input type="text" name="requested_by" class="form-control py-2 modal-input-custom" placeholder="e.g. Marco S." required style="border-radius: 6px;">
            </div>
      </div>
      
      <div class="modal-footer border-0 p-3" style="background-color: #f8fafc;">
        <button type="submit" name="new_request" class="btn text-white w-100 fw-bold py-2 shadow-sm" style="background-color: #0b2545; border-radius: 6px; transition: background 0.2s;">
            <i class="fa-solid fa-paper-plane me-2"></i>Submit Procurement Ticket
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var inputQty = document.getElementById('input_qty');
    var inputPrice = document.getElementById('input_price');
    var totalPreview = document.getElementById('live_total_preview');

    function calculateTotal() {
        var qty = parseInt(inputQty.value) || 0;
        var price = parseFloat(inputPrice.value) || 0;
        var total = qty * price;
        totalPreview.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    inputQty.addEventListener('input', calculateTotal);
    inputPrice.addEventListener('input', calculateTotal);
});
</script>

<?php include_once 'includes/footer.php'; ?>