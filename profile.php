<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update Currency
    if (isset($_POST['currency'])) {
        $currency = $_POST['currency'];
        $stmt = $pdo->prepare("UPDATE users SET currency = ? WHERE id = ?");
        $stmt->execute([$currency, $user_id]);
        $_SESSION['currency'] = $currency;
        $message = "Settings updated!";
    }

    // Upload Image
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "user_" . $user_id . "." . $ext;
            $destination = "uploads/" . $new_name; // Ensure 'uploads' folder exists

            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$destination, $user_id]);
                $_SESSION['profile_image'] = $destination;
                $message = "Profile picture updated!";
            } else {
                $message = "Upload failed.";
            }
        } else {
            $message = "Invalid file type.";
        }
    }
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <h2 class="mb-4">User Profile</h2>
    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <img src="<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>"
                        class="rounded-circle mb-3 border" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </h4>
                    <p class="text-muted">Member since 2026</p>

                    <form method="POST" enctype="multipart/form-data" class="mt-4 text-start">
                        <div class="mb-3">
                            <label class="form-label">Change Profile Picture</label>
                            <input type="file" name="profile_pic" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Currency Symbol</label>
                            <select name="currency" class="form-select">
                                <option value="USD" <?php echo ($_SESSION['currency'] == 'USD') ? 'selected' : ''; ?>
                                    >USD ($)</option>
                                <option value="EUR" <?php echo ($_SESSION['currency'] == 'EUR') ? 'selected' : ''; ?>
                                    >EUR (€)</option>
                                <option value="GBP" <?php echo ($_SESSION['currency'] == 'GBP') ? 'selected' : ''; ?>
                                    >GBP (£)</option>
                                <option value="JPY" <?php echo ($_SESSION['currency'] == 'JPY') ? 'selected' : ''; ?>
                                    >JPY (¥)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</body>

</html>