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
    <!-- Premium Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* Premium Core Palette */
            --primary-color: #6366f1;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            /* Updated for vibrancy */
            --secondary-color: #3b82f6;
            --secondary-gradient: linear-gradient(135deg, #3b82f6 0%, #2dd4bf 100%);
            /* Added for vibrancy */
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;

            /* Transaction Colors */
            --expense-gradient: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
            --income-gradient: linear-gradient(135deg, #10b981 0%, #34d399 100%);

            /* Backgrounds */
            --bg-body: #f8fafc;
            --bg-glass: rgba(255, 255, 255, 0.85);
            --bg-glass-strong: rgba(255, 255, 255, 0.95);

            /* Text */
            --text-main: #1e293b;
            --text-muted: #64748b;

            /* Shadows & Borders */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-premium: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

            /* iPhone Safe Area */
            --safe-area-top: env(safe-area-inset-top);
            --safe-area-bottom: env(safe-area-inset-bottom);
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-bottom: calc(80px + var(--safe-area-bottom));
            /* Space for bottom nav */
        }

        /* Glassmorphism Utilities */
        .glass-card {
            background: var(--bg-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
        }

        .glass-card-premium {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: var(--shadow-lg);
        }

        /* ==========================================
           BOTTOM NAVIGATION BAR (Mobile Only)
           ========================================== */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding-bottom: var(--safe-area-bottom);
            z-index: 1050;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.03);
            height: calc(65px + var(--safe-area-bottom));
        }

        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 500;
            flex: 1;
            height: 100%;
            transition: all 0.2s ease;
        }

        .bottom-nav-item i {
            font-size: 1.4rem;
            margin-bottom: 4px;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .bottom-nav-item.active {
            color: var(--primary-color);
        }

        .bottom-nav-item.active i {
            transform: translateY(-2px);
        }

        /* Floating Center Button Adjustment */
        .bottom-nav-fab-holder {
            position: relative;
            width: 60px;
            height: 60px;
            margin-top: -30px;
            /* Pull up */
        }

        .bottom-nav-fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
            border: 4px solid var(--bg-body);
            /* Fake transparency cutout */
            transition: transform 0.2s ease;
        }

        .bottom-nav-fab:active {
            transform: scale(0.95);
        }

        /* Input Styles */
        .form-control,
        .form-select {
            border-color: #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* Hide FAB from desktop if undesired, or keep as desired */
        .fab-btn {
            display: none !important;
        }

        /* Replaced by Bottom Nav FAB */
        /* ==========================================
           SIDEBAR & MAIN LAYOUT
           ========================================== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 1040;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding-top: 1rem;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            margin-left: 260px;
            /* Width of sidebar */
            padding: 2rem 1.5rem 6rem;
            /* Bottom padding for nav */
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .sidebar .brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            padding: 0 1.5rem 2rem;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
        }

        .sidebar .nav-link:hover {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }

        .sidebar .nav-link.active {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.08);
            border-left-color: var(--primary-color);
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Mobile Adjustments */
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                padding-top: 80px;
                /* Space for mobile header */
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding-top: 80px;
                /* Space for mobile header */
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .mobile-nav {
                z-index: 1045;
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .navbar-brand {
                color: var(--primary-color) !important;
                font-weight: 800;
            }

            .navbar-toggler {
                border: none;
                padding: 0;
                color: var(--text-main);
            }

            .navbar-toggler:focus {
                box-shadow: none;
            }
        }

        /* --- GLOBAL PREMIUM STYLES --- */
        /* Fixed Global Header */
        .mobile-sticky-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            background: rgba(248, 250, 252, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 10px 24px 5px 24px;
            padding-top: calc(15px + env(safe-area-inset-top));
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Spacer */
        .header-spacer {
            height: 0px;
            width: 100%;
            display: none;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        /* Premium List Item Style */
        .premium-list-item {
            background: white;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid rgba(0, 0, 0, 0.03);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            margin-bottom: 12px;
            transition: transform 0.2s;
        }

        .premium-list-item:active {
            transform: scale(0.98);
        }
    </style>
</head>

<body class="bg-light">

    <!-- Global Fixed Mobile Header -->
    <div class="mobile-sticky-header d-md-none">
        <!-- Brand -->
        <div class="d-flex align-items-center text-primary">
            <i class="fas fa-wallet fa-lg me-2"></i>
            <span class="fw-bold h5 mb-0" style="font-family: 'Inter', sans-serif;">FinanceApp</span>
        </div>

        <!-- Profile -->
        <a href="profile.php" class="rounded-circle bg-white p-1 shadow-sm border d-block"
            style="width: 40px; height: 40px;">
            <?php
            $h_profile_img = $_SESSION['profile_image'] ?? '';
            $h_avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'User') . '&background=6366f1&color=fff';

            if (!empty($h_profile_img) && $h_profile_img !== 'default.png') {
                $h_check_path = (strpos($h_profile_img, 'uploads/') === 0) ? $h_profile_img : 'uploads/' . $h_profile_img;
                if (file_exists($h_check_path)) {
                    $h_avatar_url = $h_check_path;
                }
            }
            ?>
            <img src="<?php echo $h_avatar_url; ?>" class="rounded-circle w-100 h-100" style="object-fit: cover;"
                alt="Profile">
        </a>
    </div>

    <!-- Spacer -->
    <div class="header-spacer d-md-none"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebarMenu">
        <div class="brand d-none d-md-block"><i class="fas fa-wallet me-2"></i>FinanceApp</div>



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

    <div class="main-content">
        <!-- Content injected by other pages -->

        <!-- Page Transition Overlay -->
        <div class="page-transition-overlay" id="pageTransition">
            <div class="d-flex align-items-center justify-content-center h-100">
                <div class="loading-spinner"></div>
            </div>
        </div>

        <script>
            // ==========================================
            // PREMIUM ANIMATION SYSTEM
            // ==========================================

            // Auto-close sidebar on mobile when a link is clicked
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        document.getElementById('sidebarMenu').classList.remove('show');
                    }
                });
            });

            // Smooth Page Transitions
            document.querySelectorAll('a:not([href^="#"]):not([href^="javascript"]):not([target="_blank"]):not([onclick])').forEach(link => {
                link.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');

                    // Skip if it's a hash link, javascript, or has onclick
                    if (!href || href.startsWith('#') || href.startsWith('javascript') || this.hasAttribute('onclick')) {
                        return;
                    }

                    // Skip if it's an external link
                    if (href.startsWith('http') && !href.includes(window.location.hostname)) {
                        return;
                    }

                    e.preventDefault();
                    const overlay = document.getElementById('pageTransition');
                    if (overlay) {
                        overlay.classList.add('active');
                    }

                    setTimeout(() => {
                        window.location.href = href;
                    }, 200);
                });
            });

            // Intersection Observer for Scroll Animations
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        entry.target.style.opacity = '1';
                        animationObserver.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observe elements with animation classes
            document.querySelectorAll('.fade-in-up, .scale-in, .slide-in-left').forEach(el => {
                animationObserver.observe(el);
            });

            // Add ripple effect to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                if (!btn.classList.contains('ripple-effect')) {
                    btn.classList.add('ripple-effect');
                }
            });

            // Function to clear page transition overlay
            function clearPageTransition() {
                const overlay = document.getElementById('pageTransition');
                if (overlay) {
                    overlay.classList.remove('active');
                    overlay.style.opacity = '0';
                    overlay.style.pointerEvents = 'none';
                }
            }

            // Clear overlay immediately when DOM is ready
            document.addEventListener('DOMContentLoaded', clearPageTransition);

            // Clear overlay when page fully loads
            window.addEventListener('load', clearPageTransition);

            // Handle back/forward navigation (always clear, not just for persisted)
            window.addEventListener('pageshow', clearPageTransition);

            // Also clear on visibility change (when tab becomes visible again)
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    clearPageTransition();
                }
            });

            // Failsafe: clear overlay after a short delay
            setTimeout(clearPageTransition, 500);
        </script>