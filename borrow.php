<?php 
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout_tool'])) {
    $item_id = (int)$_POST['item_id'];
    $borrower_name = $_POST['borrower_name'];
    $qty = (int)$_POST['quantity_borrowed'];
    $return_date = $_POST['expected_return_date'];

    $check_stock = $conn->query("SELECT quantity_available FROM inventory_items WHERE item_id = $item_id");
    $item = $check_stock->fetch_assoc();

    if ($item && $item['quantity_available'] >= $qty) {
        $conn->begin_transaction();
        
        $stmt1 = $conn->prepare("INSERT INTO borrow_records (item_id, borrower_name, quantity_borrowed, expected_return_date, status) VALUES (?, ?, ?, ?, 'Borrowed')");
        $stmt1->bind_param("isis", $item_id, $borrower_name, $qty, $return_date);
        
        $stmt2 = $conn->prepare("UPDATE inventory_items SET quantity_available = quantity_available - ? WHERE item_id = ?");
        $stmt2->bind_param("ii", $qty, $item_id);
        
        if ($stmt1->execute() && $stmt2->execute()) {
            $conn->commit();
            $success_msg = "Allocation voucher generated successfully.";
        } else {
            $conn->rollback();
            $error_msg = "Transaction Error during resource mapping execution.";
        }
    } else {
        $error_msg = "Operational Failure: Requested quantity exceeds warehouse availability metrics.";
    }
}

$assets = $conn->query("SELECT item_id, item_name, quantity_available FROM inventory_items WHERE item_type = 'Asset' AND quantity_available > 0");
$loans = $conn->query("SELECT b.*, i.item_name FROM borrow_records b JOIN inventory_items i ON b.item_id = i.item_id WHERE b.status = 'Borrowed' ORDER BY b.borrow_id DESC");

include_once 'includes/header.php'; 
?>

<div class="mb-4">
    <h2 class="fw-bold text-slate-800 m-0">Tool Allocation & Returns</h2>
    <p class="text-muted small m-0">Log and evaluate physical training tools checked out to students and instructors.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm position-sticky" style="top: 24px;">
            <div class="card-header bg-dark text-white fw-bold border-0 py-3">Generate Allocation Voucher</div>
            <div class="card-body p-4">
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger p-2 small border-0 mb-3"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success p-2 small border-0 mb-3"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <form action="borrow.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Select Warehouse Equipment</label>
                        <select name="item_id" class="form-select py-2" required>
                            <option value="">-- Choose Profile --</option>
                            <?php while($row = $assets->fetch_assoc()): ?>
                                <option value="<?php echo $row['item_id']; ?>">
                                    <?php echo htmlspecialchars($row['item_name']); ?> (Available: <?php echo $row['quantity_available']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Recipient Full Name</label>
                        <input type="text" name="borrower_name" class="form-control py-2" placeholder="Jane Smith" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <label class="form-label small fw-medium text-muted">Quantity</label>
                            <input type="number" name="quantity_borrowed" class="form-control py-2" value="1" min="1" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-medium text-muted">Target Return Date</label>
                            <input type="date" name="expected_return_date" class="form-control py-2" required>
                        </div>
                    </div>
                    <button type="submit" name="checkout_tool" class="btn btn-primary w-100 fw-semibold py-2 shadow-sm">Authorize Checkout</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold py-3 border-0">Active External Assets Sheet</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Recipient Name</th>
                                <th>Item Description</th>
                                <th>Allocation Qty</th>
                                <th>Issued On</th>
                                <th class="pe-4">Target Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($loans->num_rows > 0): ?>
                                <?php while($loan = $loans->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?php echo htmlspecialchars($loan['borrower_name']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['item_name']); ?></td>
                                        <td><span class="badge bg-slate-100 text-slate-700 border px-2.5 py-1 fw-bold"><?php echo $loan['quantity_borrowed']; ?></span></td>
                                        <td><small class="text-muted"><?php echo date('M d, Y', strtotime($loan['borrow_date'])); ?></small></td>
                                        <td class="pe-4"><span class="text-danger fw-semibold"><i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($loan['expected_return_date'])); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">All asset classes are securely housed inside structural facility compartments.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>