<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --sidebar-bg: #2c3e50;
            --text-dark: #2d3436;
            --bg-light: #f4f6f9;
        }

        body {
            background-color: var(--bg-light);
            min-height: 100vh;
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Clean Minimalist Card */
        .glass-card {
            background: #ffffff;
            border: 1px solid #e1e4e8;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            /* Subtle shadow */
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        /* Subtle Animation Entry */
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(10px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            padding: 20px 0;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: var(--sidebar-bg);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            border-radius: 0 25px 25px 0;
            margin-right: 15px;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--primary-color);
            padding-left: 25px;
        }

        .sidebar .brand {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
        }

        /* Main Content */
        .main-content {
            transition: margin-left 0.3s;
            padding: 20px;
            padding-top: 80px;
        }

        /* Buttons & Interactions */
        .btn {
            border-radius: 8px;
            /* Slightly rounded, not full pill */
            font-weight: 500;
            box-shadow: none;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Desktop View */
        @media (min-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(0);
            }

            .main-content {
                margin-left: 250px;
                padding-top: 30px;
            }

            .mobile-nav {
                display: none;
            }
        }

        /* Mobile View */
        @media (max-width: 767.98px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-light">

    <!-- Mobile Header -->
    <nav class="navbar navbar-dark bg-dark fixed-top mobile-nav d-md-none shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1"><i class="fas fa-wallet me-2"></i>FinanceApp</span>
            <button class="navbar-toggler" type="button"
                onclick="document.getElementById('sidebarMenu').classList.toggle('show')">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebarMenu">
        <div class="brand d-none d-md-block"><i class="fas fa-wallet me-2"></i>FinanceApp</div>

        <!-- Mobile Close Button -->
        <div class="d-md-none text-end p-2">
            <button class="btn btn-sm btn-outline-light"
                onclick="document.getElementById('sidebarMenu').classList.remove('show')">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <nav class="nav flex-column">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home me-2" style="width:20px;"></i> Dashboard
            </a>
            <a href="view_transactions.php?type=all"
                class="nav-link <?php echo ($current_page == 'view_transactions.php' && ($_GET['type'] ?? '') == 'all') ? 'active' : ''; ?>">
                <i class="fas fa-list me-2" style="width:20px;"></i> Transactions
            </a>
            <a href="scan_receipt.php"
                class="nav-link <?php echo $current_page == 'scan_receipt.php' ? 'active' : ''; ?>">
                <i class="fas fa-camera me-2" style="width:20px;"></i> Scan Receipt
            </a>
            <a href="manage_categories.php"
                class="nav-link <?php echo $current_page == 'manage_categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags me-2" style="width:20px;"></i> Categories
            </a>
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-2" style="width:20px;"></i> Reports
            </a>
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog me-2" style="width:20px;"></i> Profile
            </a>
            <hr class="text-white">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2" style="width:20px;"></i> Logout
            </a>
        </nav>
    </div>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-content">