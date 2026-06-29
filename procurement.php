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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0 text-dark">Procurement / To-Buy</h2>
        <p class="text-muted small m-0">Purchase requests and approval queue</p>
    </div>
    <button class="btn btn-warning text-white fw-bold px-4" style="background-color: #e45d14; border:none;" data-bs-toggle="modal" data-bs-target="#reqModal">+ New Request</button>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card-summary text-center">
            <div class="text-muted small text-uppercase tracking-wider fw-bold">Total Requests</div>
            <div class="display-6 fw-bold"><?php echo $totals['total_req'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-summary text-center">
            <div class="text-muted small text-uppercase tracking-wider fw-bold text-warning">Pending Approval</div>
            <div class="display-6 fw-bold text-warning"><?php echo $totals['pending_count'] ?? 0; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-summary text-center">
            <div class="text-muted small text-uppercase tracking-wider fw-bold">Est. Cost (Pending)</div>
            <div class="display-6 fw-bold">₱<?php echo number_format($totals['pending_cost'] ?? 0, 2); ?></div>
        </div>
    </div>
</div>

<div class="d-flex flex-column gap-3">
    <?php while($row = $requests->fetch_assoc()): ?>
        <div class="p-4 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h5 class="fw-bold m-0"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                    <span class="badge bg-danger-subtle text-danger text-uppercase font-monospace text-xs"><?php echo $row['priority']; ?></span>
                    <span class="badge bg-secondary-subtle text-secondary"><?php echo $row['category']; ?></span>
                    <?php if($row['approval_status'] == 'Approved'): ?>
                        <span class="badge bg-success-subtle text-success">Approved</span>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars($row['reason']); ?></p>
                <div class="text-muted font-monospace small">
                    Qty: <?php echo $row['quantity']; ?> | Est: ₱<?php echo number_format($row['estimated_cost'], 2); ?> | By: <?php echo htmlspecialchars($row['requested_by']); ?> (<?php echo $row['request_date']; ?>)
                </div>
            </div>
            <div>
                <?php if($row['approval_status'] == 'Pending'): ?>
                    <a href="procurement.php?action=approve&id=<?php echo $row['request_id']; ?>" class="btn btn-success btn-sm px-4 fw-bold">Approve</a>
                <?php else: ?>
                    <button class="btn btn-light btn-sm text-muted" disabled>Revoke</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<div class="modal fade" id="reqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="procurement.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Log Procurement Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
            <div class="mb-3">
                <label class="form-label small">Item Description</label>
                <input type="text" name="item_name" class="form-control" required placeholder="Safety Harness">
            </div>
            <div class="mb-3">
                <label class="form-label small">Category Classification</label>
                <select name="category" class="form-select">
                    <option value="Power Tools">Power Tools</option>
                    <option value="Hand Tools">Hand Tools</option>
                    <option value="Classroom Supplies">PPE / Safety</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small">Priority Urgency</label>
                <select name="priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small">Justification / Reason</label>
                <textarea name="reason" class="form-control" rows="2" placeholder="Mandatory replacement before site inspection"></textarea>
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <label class="form-label small">Quantity</label>
                    <input type="number" name="quantity" class="form-control" value="1" min="1">
                </div>
                <div class="col-8">
                    <label class="form-label small">Estimated Cost (Total ₱)</label>
                    <input type="number" name="estimated_cost" step="0.01" class="form-control" placeholder="5600">
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label small">Your Name</label>
                <input type="text" name="requested_by" class="form-control" placeholder="Marco S." required>
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="new_request" class="btn btn-warning text-white w-100 fw-bold" style="background-color: #e45d14; border:none;">Submit Ticket</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>