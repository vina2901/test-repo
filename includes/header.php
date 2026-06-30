<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrack | DAZ Training Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Chivo', sans-serif; background-color: #f4f6f9; color: #1f2937; overflow-x: hidden; }
        
        /* 1. TOP NAVBAR (Hamburger button trigger) */
        .top-navbar {
            background-color: #ffffff;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #0b2545;
            cursor: pointer;
            margin-right: 15px;
            padding: 0 10px;
        }
        
        /* 2. SIDEBAR (Hidden by default via left: -260px) */
        .sidebar { 
            min-height: 100vh; 
            background-color: #0b2545; 
            width: 260px; 
            position: fixed; 
            left: -260px; 
            top: 0; 
            z-index: 1050; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); 
            transition: left 0.3s ease; 
        }
        
        /* 3. ACTIVE STATE */
        .sidebar.active {
            left: 0; 
        }
        
        /* DAZ Branding Styles */
        .sidebar-brand { font-weight: 800; font-size: 1.1rem; line-height: 1.2; }
        .brand-title { color: #fccb05; text-transform: uppercase; letter-spacing: 0.03em; }
        .brand-subtitle { color: #00b4d8; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em; }
        
        .sidebar .nav-link { color: #cbd5e1; font-weight: 600; padding: 0.8rem 1.2rem; border-radius: 8px; margin-bottom: 6px; display: flex; align-items: center; transition: all 0.2s ease; }
        .sidebar .nav-link:hover { color: #ffffff; background-color: #134074; }
        .sidebar .nav-link.active { color: #ffffff; background-color: #00b4d8; box-shadow: 0 4px 12px rgba(0, 180, 216, 0.3); }
        
        /* 4. MAIN WORKSPACE */
        .main-workspace { 
            padding: 2.5rem; 
        }

        /* 5. OVERLAY */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
            display: none;
        }
        .sidebar-overlay.active {
            display: block;
        }

        .card-summary { background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1.5rem; }
        .badge-instock { background-color: #dcfce7; color: #166534; font-weight: 600; }
        .badge-damaged { background-color: #fee2e2; color: #991b1b; font-weight: 600; }
        .badge-pending { background-color: #fef3c7; color: #92400e; font-weight: 600; }
        
        .btn-custom-primary { background-color: #00b4d8; color: white; font-weight: bold; border: none; }
        .btn-custom-primary:hover { background-color: #0096b4; color: white; }
        .nav-tabs .nav-link.active { color: #00b4d8 !important; border-color: #dee2e6 #dee2e6 #fff !important; }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <button class="toggle-btn" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
    </button>
    <span class="fw-bold text-uppercase" style="color: #0b2545; letter-spacing: 1px;">ToolTrack System</span>
</div>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR MENU -->
<div class="sidebar p-3 d-flex flex-column" id="sidebarMenu">
    <!-- Close button -->
    <div class="d-flex justify-content-end">
        <button class="btn text-white fs-4 p-0 mb-2" id="sidebarClose">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="sidebar-brand px-1 py-3 mb-4 border-bottom border-dark border-opacity-25 d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-center me-2" 
             style="width: 80px; height: 80px; min-width: 80px; overflow: hidden;">
            <img src="DAZ_LOGO.png" alt="DAZ Training Center Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        
        <div>
            <div class="brand-title" style="font-size: 1rem; letter-spacing: 0.07em;">DAZ TRAINING CENTER INC.</div>
            <div class="brand-subtitle"><i class="fa-solid fa-cubes-sharing me-1"></i>ToolTrack System</div>
        </div>
    </div>
    
    <ul class="nav flex-column mb-auto">
    <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie me-3"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a href="master_catalog.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'master_catalog.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-folder-open me-3"></i> Master Catalog
        </a>
    </li>
    <li class="nav-item">
        <a href="physical_inventory.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'physical_inventory.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-boxes-stacked me-3"></i> Physical Inventory
        </a>
    </li>
    <li class="nav-item">
        <a href="borrow.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'borrow.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-hand-holding-hand me-3"></i> Borrow Tracking
        </a>
    </li>
    <li class="nav-item">
        <a href="procurement.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'procurement.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-receipt me-3"></i> Procurement
        </a>
    </li>
    <li class="nav-item">
        <a href="history.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'history.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock-rotate-left me-3"></i> Action History
        </a>
    </li>
    <li class="nav-item mt-3">
    <a href="logout.php" class="nav-link">
        <i class="fa-solid fa-right-from-bracket me-3"></i> Logout
    </a>
</li>
</ul>
    
    <div class="px-2 py-2 text-center" style="font-size: 0.65rem; color: #64748b; font-weight: 400; border-top: 1px solid rgba(255,255,255,0.05);">
        Develop Skills A-Z &copy; 2026
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const closeBtn = document.getElementById('sidebarClose');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if(toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);
    });
</script>

<div class="main-workspace">