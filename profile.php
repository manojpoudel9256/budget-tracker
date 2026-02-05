<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = 'success'; // success or danger

// --- SELF-HEAL: Check for budget_limit column ---
try {
    $pdo->query("SELECT budget_limit FROM users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN budget_limit DECIMAL(10,2) DEFAULT 0.00");
}

// --- HANDLE ACTIONS ---
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    // Export CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="budget_tracker_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Amount', 'Description']);

    $stmt = $pdo->prepare("SELECT id, date, type, category, amount, description FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Update Profile Logic
    if (isset($_POST['update_profile'])) {
        // Currency
        if (isset($_POST['currency'])) {
            $currency = $_POST['currency'];
            $stmt = $pdo->prepare("UPDATE users SET currency = ? WHERE id = ?");
            $stmt->execute([$currency, $user_id]);
            $_SESSION['currency'] = $currency;
        }

        // Budget Limit
        if (isset($_POST['budget_limit'])) {
            $limit = (float) $_POST['budget_limit'];
            $stmt = $pdo->prepare("UPDATE users SET budget_limit = ? WHERE id = ?");
            $stmt->execute([$limit, $user_id]);
        }

        // Image
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
                if (!file_exists('uploads'))
                    mkdir('uploads', 0777, true);

                $destination = "uploads/" . $new_name;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$destination, $user_id]);
                    $_SESSION['profile_image'] = $destination;
                }
            } else {
                $message = "Invalid image format.";
                $msg_type = "danger";
            }
        }

        if (empty($message)) {
            $message = "Profile updated successfully!";
        }
    }

    // 2. Reset Data Logic
    if (isset($_POST['reset_data'])) {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $message = "All transactions deleted.";
        $msg_type = "danger";
    }
}

// --- FETCH DATA ---
// 1. User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$budget_limit = $user['budget_limit'] ?? 0;
$join_date = isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'Jan 2026'; // Mock if missing

// 2. Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$tx_count = $stmt->fetchColumn();

// 3. Total Saved (Lifetime)
$stmt = $pdo->prepare("SELECT (SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type='income') - (SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type='expense')");
$stmt->execute([$user_id, $user_id]);
$lifetime_saved = $stmt->fetchColumn() ?: 0;
?>
<?php include 'header.php'; ?>

<style>
    .profile-header-card {
        background: var(--primary-gradient);
        border-radius: 24px;
        padding: 30px 20px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .profile-header-card::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .profile-avatar-container {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 15px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .profile-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        background: #f59e0b;
        /* Gold */
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        border: 2px solid white;
    }

    .stat-pill {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(5px);
        padding: 8px 16px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        margin: 0 5px;
    }

    .settings-group {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .settings-item {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: var(--text-dark);
        transition: 0.2s;
    }

    .settings-item:last-child {
        border-bottom: none;
    }

    .settings-item:active {
        background: #f8fafc;
    }

    .settings-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1rem;
    }

    .icon-blue {
        background: #dbeafe;
        color: #3b82f6;
    }

    .icon-green {
        background: #dcfce7;
        color: #10b981;
    }

    .icon-purple {
        background: #f3e8ff;
        color: #8b5cf6;
    }

    .icon-orange {
        background: #ffedd5;
        color: #f97316;
    }

    .icon-red {
        background: #fee2e2;
        color: #ef4444;
    }

    /* Custom Input Style for File */
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        height: 100%;
        width: 100%;
    }
</style>

<div class="container-fluid px-0 px-md-3 pb-5">

    <!-- Title -->
    <div class="px-3 mt-2 mb-3 fade-in-up">
        <h2 class="fw-bold mb-1"
            style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            My Profile</h2>
    </div>

    <?php if ($message): ?>
        <div class="px-3 mb-3 fade-in-up">
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm border-0 rounded-4 d-flex align-items-center">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="profileForm">
        <input type="hidden" name="update_profile" value="1">

        <!-- 1. HEADER CARD -->
        <div class="px-3 mb-4 fade-in-up delay-1">
            <div class="profile-header-card shadow-sm">
                <div class="profile-avatar-container">
                    <img src="<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>" class="profile-avatar"
                        id="avatarPreview">
                    <div class="profile-badge"><i class="fas fa-star"></i></div>

                    <!-- Hidden File Input Trigger -->
                    <div class="file-input-wrapper" style="position: absolute; bottom: 0; right: -10px;">
                        <div class="bg-white text-dark rounded-circle p-2 shadow-sm"
                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-camera small"></i>
                        </div>
                        <input type="file" name="profile_pic"
                            onchange="document.getElementById('avatarPreview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('saveBtn').classList.remove('d-none');">
                    </div>
                </div>

                <h4 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p class="small text-white opacity-75 mb-3">Premium Member</p>

                <div class="d-flex justify-content-center">
                    <div class="stat-pill">
                        <small class="opacity-75 me-2">Transactions</small>
                        <span class="fw-bold"><?php echo $tx_count; ?></span>
                    </div>
                    <div class="stat-pill">
                        <small class="opacity-75 me-2">Saved</small>
                        <span
                            class="fw-bold"><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($lifetime_saved, 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. SETTINGS LIST -->
        <div class="px-3 mb-4 fade-in-up delay-2">
            <h6 class="text-muted fw-bold ms-2 mb-3 small text-uppercase">Preferences</h6>
            <div class="settings-group">
                <!-- Currency -->
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon icon-blue"><i class="fas fa-globe"></i></div>
                        <div>
                            <span class="d-block fw-bold small text-dark">Currency</span>
                            <span class="text-muted small">Selected: <?php echo $_SESSION['currency']; ?></span>
                        </div>
                    </div>
                    <div>
                        <select name="currency" class="form-select form-select-sm border-0 bg-light"
                            style="width: auto;"
                            onchange="document.getElementById('saveBtn').classList.remove('d-none');">
                            <option value="USD" <?php echo $_SESSION['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)
                            </option>
                            <option value="JPY" <?php echo $_SESSION['currency'] == 'JPY' ? 'selected' : ''; ?>>JPY (¥)
                            </option>
                            <option value="EUR" <?php echo $_SESSION['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)
                            </option>
                            <option value="GBP" <?php echo $_SESSION['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)
                            </option>
                            <option value="INR" <?php echo $_SESSION['currency'] == 'INR' ? 'selected' : ''; ?>>INR (₹)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Monthly Budget -->
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon icon-green"><i class="fas fa-wallet"></i></div>
                        <div>
                            <span class="d-block fw-bold small text-dark">Monthly Budget</span>
                            <span class="text-muted small">Set your spending limit</span>
                        </div>
                    </div>
                    <div style="width: 100px;">
                        <input type="number" name="budget_limit"
                            class="form-control form-control-sm text-end bg-light border-0 fw-bold"
                            value="<?php echo (int) $budget_limit; ?>" placeholder="0"
                            oninput="document.getElementById('saveBtn').classList.remove('d-none');">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. DATA MANAGEMENT -->
        <div class="px-3 mb-5 fade-in-up delay-3">
            <h6 class="text-muted fw-bold ms-2 mb-3 small text-uppercase">Data & Privacy</h6>
            <div class="settings-group">
                <!-- Export -->
                <a href="?action=export" class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon icon-purple"><i class="fas fa-file-csv"></i></div>
                        <div>
                            <span class="d-block fw-bold small text-dark">Export Data</span>
                            <span class="text-muted small">Download CSV of all transactions</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>

                <!-- Reset (Danger) -->
                <div class="settings-item"
                    onclick="if(confirm('Are you sure? This will delete ALL transactions permanently.')) { document.getElementById('resetForm').submit(); }"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon icon-red"><i class="fas fa-trash"></i></div>
                        <div>
                            <span class="d-block fw-bold small text-danger">Reset Data</span>
                            <span class="text-muted small">Clear all transactions</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </div>

                <!-- Logout -->
                <a href="logout.php" class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon" style="background: #f1f5f9; color: #64748b;"><i
                                class="fas fa-sign-out-alt"></i></div>
                        <div>
                            <span class="d-block fw-bold small text-dark">Log Out</span>
                            <span class="text-muted small">Sign out of your account</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
            </div>
        </div>

        <!-- Floating Action Bar (Glassmorphism) -->
        <div id="saveBtn" class="fixed-bottom p-3 d-none fade-in-up" style="z-index: 1060;">
            <div class="glass-card p-3 shadow-lg border-0"
                style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block fw-bold text-dark small">Unsaved Changes</span>
                        <span class="text-muted small" style="font-size: 0.7rem;">Tap save to apply</span>
                    </div>
                    <button type="submit" name="update_profile" class="btn text-white rounded-pill px-4 fw-bold shadow"
                        style="background: var(--primary-gradient);">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden Reset Form -->
    <form method="POST" id="resetForm">
        <input type="hidden" name="reset_data" value="1">
    </form>

</div>

<script>
    // Auto-hide alert after 3 seconds
    document.addEventListener('DOMContentLoaded', function () {
        const alertBox = document.querySelector('.alert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.parentElement.remove(), 500); // Remove the wrapper div
            }, 3000);
        }
    });
</script>

<?php include 'footer.php'; ?>