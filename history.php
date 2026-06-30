<?php 
require_once 'db.php';
include_once 'includes/header.php'; 

$active_sub_tab = isset($_GET['view']) ? $_GET['view'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base Query
$query = "SELECT * FROM system_logs WHERE 1=1";
$params = [];
$types = "";

// Filter by search keyword if present
if (!empty($search)) {
    $query .= " AND (details LIKE ? OR user_name LIKE ? OR category LIKE ?)";
    $search_param = "%" . $search . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

// Filter by Sub-tab View Selection
if ($active_sub_tab === 'catalog') {
    $query .= " AND category IN ('Power Tools', 'Hand Tools', 'PPE / Safety', 'Chemicals & Solvents')";
} elseif ($active_sub_tab === 'borrow') {
    $query .= " AND category = 'Borrow'";
}

$query .= " ORDER BY log_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$log_results = $stmt->get_result();
?>

<style>
    .page-header-title { color: #0b2545; font-weight: 800; letter-spacing: -0.02em; }
    .search-box-group .form-control { border: 1px solid #e5e7eb; padding: 0.6rem 1rem; font-size: 0.9rem; border-radius: 8px; }
    .search-box-group .form-control:focus { border-color: #00b4d8; box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15); }

    .history-tabs .nav-link {
        color: #4b5563; background-color: #fff; border: 1px solid #e5e7eb;
        font-weight: 600; padding: 0.6rem 1.4rem; border-radius: 30px; font-size: 0.85rem;
    }
    .history-tabs .nav-link.active {
        background-color: #0b2545 !important; color: #fff !important;
        box-shadow: 0 4px 12px rgba(11, 37, 69, 0.2); border-color: #0b2545 !important;
    }

    .history-table-card { background: #ffffff; border: none; border-radius: 8px; box-shadow: 0 4px 20px rgba(11, 37, 69, 0.05); overflow: hidden; }
    .table thead th { background-color: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border-bottom: 1px solid #edf2f7; }
    .table tbody td { padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

    /* Dynamic Badges Map */
    .badge-cat { font-size: 0.75rem; font-weight: 700; padding: 0.4rem 0.8rem; border-radius: 6px; text-transform: uppercase; display: inline-flex; align-items: center; }
    .cat-power-tools { background-color: #0b2545; color: #ffffff; }
    .cat-hand-tools { background-color: #00b4d8; color: #ffffff; }
    .cat-ppe-safety { background-color: #fccb05; color: #1e293b; }
    .cat-chemicals-solvents { background-color: #64748b; color: #ffffff; }
    .cat-default { background-color: #e2e8f0; color: #334155; }

    .badge-action { font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 4px; text-transform: uppercase; }
    .action-created { background-color: #dcfce7; color: #166534; }
    .action-modified { background-color: #e0f2fe; color: #0369a1; }
    .action-deleted { background-color: #fee2e2; color: #991b1b; }
    
    .user-avatar { width: 35px; height: 35px; background-color: #134074; color: #ffffff; font-weight: 700; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
</style>

<div class="container-fluid px-1 py-2">
    
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="page-header-title mb-1">System Audit Logs</h2>
            <p class="text-muted small mb-0">Track structural updates, equipment modifications, and inventory transactions across ToolTrack.</p>
        </div>
    </div>

    <div class="card card-summary border-0 shadow-sm mb-4 p-3" style="border-radius: 8px;">
        <form action="history.php" method="GET" class="row g-3 search-box-group">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($active_sub_tab); ?>">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control py-2" placeholder="Search logs dynamically by item names, handlers, actions..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-custom-primary text-white fw-bold w-100 py-2" style="border-radius: 8px;">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Search Logs
                </button>
            </div>
        </form>
    </div>

    <ul class="nav history-tabs gap-2 mb-4 flex-nowrap overflow-auto pb-2">
        <li class="nav-item">
            <a href="history.php?view=all" class="nav-link <?php echo ($active_sub_tab === 'all') ? 'active' : ''; ?>">All Activities</a>
        </li>
        <li class="nav-item">
            <a href="history.php?view=catalog" class="nav-link <?php echo ($active_sub_tab === 'catalog') ? 'active' : ''; ?>">Master Catalog Changes</a>
        </li>
        <li class="nav-item">
            <a href="history.php?view=borrow" class="nav-link <?php echo ($active_sub_tab === 'borrow') ? 'active' : ''; ?>">Borrowing Logs</a>
        </li>
    </ul>

    <div class="history-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 18%;">Source Category</th>
                        <th style="width: 20%;">Authorized User</th>
                        <th style="width: 12%;">Action Taken</th>
                        <th>Modification Transaction Details</th>
                        <th class="pe-4" style="width: 15%;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($log_results->num_rows > 0): ?>
                        <?php while($row = $log_results->fetch_assoc()): 
                            // Determine Category style class
                            $cat_slug = strtolower(str_replace([' ', '/'], ['-', ''], $row['category']));
                            $cat_class = (in_array($row['category'], ['Power Tools', 'Hand Tools', 'PPE / Safety', 'Chemicals & Solvents'])) ? 'cat-' . $cat_slug : 'cat-default';
                            
                            // Determine Action style class
                            $action_class = 'action-' . strtolower($row['action_type']);
                            $avatar_initials = strtoupper(substr($row['user_name'], 0, 2));
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge-cat <?php echo $cat_class; ?>"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar"><?php echo $avatar_initials; ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size:0.85rem;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($row['user_role']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-action <?php echo $action_class; ?>"><?php echo htmlspecialchars($row['action_type']); ?></span></td>
                                <td class="text-dark"><?php echo htmlspecialchars($row['details']); ?></td>
                                <td class="pe-4 text-secondary small">
                                    <?php echo date("M d, Y", strtotime($row['log_time'])); ?><br>
                                    <span class="text-muted" style="font-size:0.75rem;"><?php echo date("h:i A", strtotime($row['log_time'])); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="fa-solid fa-circle-info me-2"></i>No system modification logs tracked yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
<?php 
$stmt->close();
include_once 'includes/footer.php'; 
?>