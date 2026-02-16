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
// --- SELF-HEAL: Check for updated_at column or any other new fields if needed ---


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
        // 1. Username
        if (isset($_POST['username']) && !empty(trim($_POST['username']))) {
            $username = trim($_POST['username']);
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$username, $user_id]);
            $_SESSION['username'] = $username;
        }

        // 2. Currency
        if (isset($_POST['currency'])) {
            $currency = $_POST['currency'];
            $stmt = $pdo->prepare("UPDATE users SET currency = ? WHERE id = ?");
            $stmt->execute([$currency, $user_id]);
            $_SESSION['currency'] = $currency;
        }



        // 4. Password Change
        if (!empty($_POST['new_password'])) {
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];

            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    $message = "Password updated!";
                } else {
                    $message = "Password must be at least 6 characters.";
                    $msg_type = "danger";
                }
            } else {
                $message = "Passwords do not match.";
                $msg_type = "danger";
            }
        }

        // 5. Image
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
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: var(--text-dark);
        transition: 0.2s;
    }

    .settings-item:last-child {
        border-bottom: none;
    }

    .settings-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .bg-icon-primary {
        background: #e0e7ff;
        color: #4338ca;
    }

    .bg-icon-success {
        background: #dcfce7;
        color: #166534;
    }

    .bg-icon-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .bg-icon-warning {
        background: #ffedd5;
        color: #9a3412;
    }

    .form-control-transparent {
        background: transparent;
        border: none;
        color: white;
        font-weight: bold;
        text-align: center;
        font-size: 1.5rem;
        padding: 0;
    }

    .form-control-transparent:focus {
        background: transparent;
        box-shadow: none;
        color: white;
        border-bottom: 2px solid rgba(255, 255, 255, 0.5);
    }

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

    <?php if ($message): ?>
        <div class="px-3 mt-3 fade-in-up">
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm border-0 rounded-4 d-flex align-items-center">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="profileForm">
        <input type="hidden" name="update_profile" value="1">

        <!-- 1. IDENTITY HEADER -->
        <div class="px-3 mt-3 mb-4 fade-in-up">
            <div class="profile-header-card shadow-sm">
                <div class="profile-avatar-container">
                    <img src="<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>" class="profile-avatar"
                        id="avatarPreview">
                    <!-- Camera Button -->
                    <div class="file-input-wrapper" style="position: absolute; bottom: 0; right: -5px;">
                        <div class="bg-white text-dark rounded-circle p-2 shadow-sm"
                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-camera small"></i>
                        </div>
                        <input type="file" name="profile_pic"
                            onchange="document.getElementById('avatarPreview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('saveBtn').classList.remove('d-none');">
                    </div>
                </div>

                <!-- Editable Name -->
                <div class="mb-1">
                    <input type="text" name="username" class="form-control-transparent w-100"
                        value="<?php echo htmlspecialchars($_SESSION['username']); ?>"
                        oninput="document.getElementById('saveBtn').classList.remove('d-none');">
                </div>
                <p class="small text-white opacity-75 mb-3"><?php echo $lang['joined']; ?> <?php echo $join_date; ?></p>

                <div class="d-flex justify-content-center gap-2">
                    <div class="px-3 py-2 rounded-4 bg-white bg-opacity-25 backdrop-blur">
                        <small class="d-block opacity-75"
                            style="font-size:0.7rem;"><?php echo $lang['saved']; ?></small>
                        <span
                            class="fw-bold"><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($lifetime_saved, 0); ?></span>
                    </div>
                    <div class="px-3 py-2 rounded-4 bg-white bg-opacity-25 backdrop-blur">
                        <small class="d-block opacity-75"
                            style="font-size:0.7rem;"><?php echo $lang['transactions']; ?></small>
                        <span class="fw-bold"><?php echo $tx_count; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. APP PREFERENCES -->
        <div class="px-3 mb-4 fade-in-up delay-1">
            <h6 class="text-muted fw-bold ms-2 mb-3 small text-uppercase"><?php echo $lang['app_settings']; ?></h6>
            <div class="settings-group">
                <!-- Currency -->
                <div class="settings-item justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon bg-icon-primary"><i class="fas fa-coins"></i></div>
                        <div>
                            <span class="d-block fw-bold text-dark"><?php echo $lang['currency']; ?></span>
                        </div>
                    </div>
                    <select name="currency" class="form-select border-0 bg-light text-end fw-bold"
                        style="width: auto; max-width: 100px;"
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
        </div>

        <!-- 3. SECURITY -->
        <div class="px-3 mb-4 fade-in-up delay-2">
            <h6 class="text-muted fw-bold ms-2 mb-3 small text-uppercase"><?php echo $lang['security']; ?></h6>
            <div class="settings-group">
                <div class="settings-item" data-bs-toggle="collapse" data-bs-target="#passwordCollapse"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon bg-icon-warning"><i class="fas fa-lock"></i></div>
                        <div>
                            <span class="d-block fw-bold text-dark"><?php echo $lang['change_password']; ?></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-muted"></i>
                </div>

                <div class="collapse bg-light" id="passwordCollapse">
                    <div class="p-3">
                        <div class="mb-2">
                            <input type="password" name="new_password" class="form-control border-0 shadow-sm"
                                placeholder="<?php echo $lang['new_password']; ?>"
                                oninput="document.getElementById('saveBtn').classList.remove('d-none');">
                        </div>
                        <div>
                            <input type="password" name="confirm_password" class="form-control border-0 shadow-sm"
                                placeholder="<?php echo $lang['confirm_password']; ?>"
                                oninput="document.getElementById('saveBtn').classList.remove('d-none');">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. DATA & PRIVACY -->
        <div class="px-3 mb-5 fade-in-up delay-3">
            <h6 class="text-muted fw-bold ms-2 mb-3 small text-uppercase"><?php echo $lang['data_privacy']; ?></h6>
            <div class="settings-group">
                <a href="?action=export" class="settings-item justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon bg-icon-primary"><i class="fas fa-file-export"></i></div>
                        <div>
                            <span class="d-block fw-bold text-dark"><?php echo $lang['export_data']; ?></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>

                <div class="settings-item justify-content-between"
                    onclick="if(confirm('<?php echo $lang['reset_confirm']; ?>')) { document.getElementById('resetForm').submit(); }"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon bg-icon-danger"><i class="fas fa-trash-alt"></i></div>
                        <div>
                            <span class="d-block fw-bold text-danger"><?php echo $lang['reset_all_data']; ?></span>
                        </div>
                    </div>
                </div>

                <a href="logout.php" class="settings-item justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon bg-light text-muted"><i class="fas fa-sign-out-alt"></i></div>
                        <div>
                            <span class="d-block fw-bold text-dark"><?php echo $lang['logout']; ?></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
            </div>
        </div>

        <!-- Floating Save Button -->
        <div id="saveBtn" class="fixed-bottom p-3 d-none fade-in-up" style="z-index: 1060;">
            <div class="glass-card p-3 shadow-lg border-0"
                style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block fw-bold text-dark small"><?php echo $lang['unsaved_changes']; ?></span>
                        <span class="text-muted small"
                            style="font-size: 0.7rem;"><?php echo $lang['tap_save']; ?></span>
                    </div>
                    <button type="submit" name="update_profile" class="btn text-white rounded-pill px-4 fw-bold shadow"
                        style="background: var(--primary-gradient);">
                        <?php echo $lang['save']; ?>
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
    document.addEventListener('DOMContentLoaded', function () {
        const alertBox = document.querySelector('.alert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.parentElement.remove(), 500);
            }, 3000);
        }
    });
</script>

<?php include 'footer.php'; ?>