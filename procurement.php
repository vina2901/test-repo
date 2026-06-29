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
    $cost = (float)$_POST['estimated_cost'];
    $user = $_POST['requested_by'];
    $today = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO procurement_requests (item_name, category, priority, reason, quantity, estimated_cost, requested_by, request_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssidss", $item_name, $category, $priority, $reason, $qty, $cost, $user, $today);
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

<!-- Extra CSS overrides specifically for clean Procurement dashboard layout -->
<style>
    .procurement-card {
        border: 1px solid #e5e7eb;
        border-top: 4px solid #0b2545; /* Brand Deep Blue Top bar */
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
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0" style="color: #0b2545;">Procurement / To-Buy</h2>
        <p class="text-muted small m-0">Purchase requests and acquisition approval queue</p>
    </div>
    <!-- Updated button to match DAZ Cyan Theme -->
    <button class="btn btn-custom-primary text-white fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#reqModal">
        <i class="fa-solid fa-plus me-2"></i>New Request
    </button>
</div>

<!-- ANALYTICS SUMMARY CARDS -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm">
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1">Total Requests</div>
            <div class="display-6 fw-bold text-dark"><?php echo $totals['total_req'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm" style="border-top-color: #fccb05;"> <!-- DAZ Yellow for Pending -->
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1" style="color: #d97706 !important;">Pending Approval</div>
            <div class="display-6 fw-bold" style="color: #d97706;"><?php echo $totals['pending_count'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="procurement-card p-4 text-center shadow-sm" style="border-top-color: #00b4d8;"> <!-- DAZ Cyan for Costs -->
            <div class="text-muted small text-uppercase tracking-wider fw-bold mb-1">Est. Cost (Pending)</div>
            <div class="display-6 fw-bold" style="color: #0b2545;">₱<?php echo number_format($totals['pending_cost'] ?? 0, 2); ?></div>
        </div>
    </div>
</div>

<!-- LIST QUEUE COMPONENT -->
<h5 class="fw-bold mb-3 text-secondary" style="font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">Request Log Sheets</h5>
<div class="d-flex flex-column gap-3 mb-5">
    <?php if($requests->num_rows > 0): ?>
        <?php while($row = $requests->fetch_assoc()): ?>
            <div class="p-4 bg-white border request-row shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="fw-bold m-0 text-dark" style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                        
                        <!-- Priority Pill Switch -->
                        <?php 
                            $p_class = ($row['priority'] == 'high') ? 'bg-danger text-danger' : (($row['priority'] == 'medium') ? 'bg-warning text-warning' : 'bg-secondary text-secondary');
                        ?>
                        <span class="badge <?php echo $p_class; ?> bg-opacity-10 text-uppercase font-monospace px-2 py-1" style="font-size: 0.7rem; font-weight: 700;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $row['priority']; ?>
                        </span>
                        
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($row['category']); ?></span>
                        
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
                    
                    <p class="text-muted small mb-3" style="max-width: 700px;"><?php echo htmlspecialchars($row['reason']); ?></p>
                    
                    <div class="text-secondary font-monospace small bg-light p-2 rounded d-inline-block" style="font-size: 0.8rem;">
                        <span class="me-2 text-dark"><i class="fa-solid fa-cubes me-1 opacity-50"></i>Qty: <strong><?php echo $row['quantity']; ?></strong></span> | 
                        <span class="mx-2 text-dark"><i class="fa-solid fa-tags me-1 opacity-50"></i>Est: <strong>₱<?php echo number_format($row['estimated_cost'], 2); ?></strong></span> | 
                        <span class="ms-2"><i class="fa-solid fa-user me-1 opacity-50"></i>By: <?php echo htmlspecialchars($row['requested_by']); ?> <span class="text-muted opacity-75">(<?php echo $row['request_date']; ?>)</span></span>
                    </div>
                </div>
                <div>
                    <?php if($row['approval_status'] == 'Pending'): ?>
                        <a href="procurement.php?action=approve&id=<?php echo $row['request_id']; ?>" class="btn btn-success btn-sm px-4 fw-bold shadow-xs py-2 rounded-pill">
                            <i class="fa-solid fa-check me-1"></i>Approve
                        </a>
                    <?php else: ?>
                        <button class="btn btn-light btn-sm text-muted px-4 py-2 rounded-pill border" disabled>
                            <i class="fa-solid fa-lock me-1"></i>Locked
                        </button>
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

<!-- LOG PROCUREMENT REQUEST MODAL -->
<div class="modal fade" id="reqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="procurement.php" method="POST" class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #0b2545;">
        <h5 class="modal-title fw-bold" style="font-size: 1.1rem;"><i class="fa-solid fa-clipboard-list me-2 text-warning"></i>Log Procurement Request</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Item Name / Description</label>
                <input type="text" name="item_name" class="form-control py-2" required placeholder="Safety Harness">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Category Classification</label>
                <select name="category" class="form-select py-2">
                    <option value="Power Tools">Power Tools</option>
                    <option value="Hand Tools">Hand Tools</option>
                    <option value="PPE / Safety">PPE / Safety</option>
                    <option value="Chemicals & Solvents">Chemicals & Solvents</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Priority Urgency</label>
                <select name="priority" class="form-select py-2">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Justification / Reason</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Mandatory replacement before site inspection..."></textarea>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <label class="form-label small fw-bold text-secondary">Quantity</label>
                    <input type="number" name="quantity" class="form-control py-2" value="1" min="1" required>
                </div>
                <div class="col-8">
                    <label class="form-label small fw-bold text-secondary">Estimated Cost (Total ₱)</label>
                    <input type="number" name="estimated_cost" step="0.01" class="form-control py-2" placeholder="5600" required>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label small fw-bold text-secondary">Your Name</label>
                <input type="text" name="requested_by" class="form-control py-2" placeholder="Marco S." required>
            </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="submit" name="new_request" class="btn btn-custom-primary text-white w-100 fw-bold py-2 shadow-sm">
            Submit Procurement Ticket
        </button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>