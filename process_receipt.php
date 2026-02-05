<?php
session_start();
require 'db_connect.php';

// --- CONFIGURATION ---
$API_KEY = GEMINI_API_KEY; // Loaded from db_connect.php -> config.php
$API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $API_KEY;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['receipt_image'])) {
    header("Location: scan_receipt.php?error=No file uploaded");
    exit;
}

$file = $_FILES['receipt_image'];
$userId = $_SESSION['user_id'];

// 1. Validation

// Check for PHP upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => "File is too large (server limit). Please try a smaller image.",
        UPLOAD_ERR_FORM_SIZE => "File is too large (form limit).",
        UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
    ];
    $msg = $errorMessages[$file['error']] ?? "Unknown upload error occurred.";
    header("Location: scan_receipt.php?error=" . urlencode($msg));
    exit;
}

// Check file size (custom limit, e.g., 5MB)
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxSize) {
    header("Location: scan_receipt.php?error=File is too large (Max 5MB). Please resize.");
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
// Allow application/octet-stream if extension is valid (common issue on some mobile variants)
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file['type'], $allowedTypes)) {
    // Soft check for generic binary types if extension matches
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
    if (!in_array($fileExt, $allowedExts)) {
        header("Location: scan_receipt.php?error=Invalid file type. JPG, PNG, WEBP, HEIC only.");
        exit;
    }
}

// 2. Upload Handling
$uploadDir = 'uploads/receipts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('receipt_', true) . '.' . $extension;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    header("Location: scan_receipt.php?error=Failed to save image.");
    exit;
}

// 3. Prepare Image for API (Base64)
$imageData = base64_encode(file_get_contents($filepath));
$mimeType = $file['type'];

// 4. Construct AI Prompt
$promptText = <<<EOT
You are an expert receipt analyzer. Look at this receipt image (likely in Japanese).
Extract the following information and output ONLY a valid JSON object:
{
  "store_name": "Translate store name to English (e.g., 'Seven Eleven')",
  "date": "YYYY-MM-DD (Use today's date if not found)",
  "amount": 0.00 (Total numerical amount),
  "category": "Best guess from: Groceries, Transport, Dining Out, Entertainment, Rent, Utilities, Shopping, Salary, Other",
  "description": "Short summary of items (translated to English)"
}
Do not include markdown formatting (like ```json), just the raw JSON string.
EOT;

// 5. Call Gemini API
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $promptText],
                [
                    "inline_data" => [
                        "mime_type" => $mimeType,
                        "data" => $imageData
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// Disable SSL verification for local development environments to prevent "SSL certificate problem"
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $curlError) {
    // Show detailed error for debugging
    echo "<h1>AI Analysis Failed</h1>";
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    if ($curlError) {
        echo "<p><strong>Curl Error:</strong> $curlError</p>";
    }
    echo "<p><strong>API Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";
    echo "<p><a href='scan_receipt.php'>Go Back</a></p>";
    exit;
}

// 6. Parse JSON Response
$result = json_decode($response, true);
// Gemini 1.5 structure: candidates[0].content.parts[0].text
$aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Clean up markdown code blocks if present
$aiText = preg_replace('/^```json\s*|\s*```$/', '', trim($aiText));

$data = json_decode($aiText, true);

if (!$data) {
    header("Location: scan_receipt.php?error=Could not parse receipt data.");
    exit;
}

// 7. Map Category Name to ID
$categoryName = $data['category'] ?? 'Other';
$stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
$stmt->execute([$userId, $categoryName]);
$categoryId = $stmt->fetchColumn();

// If category doesn't exist by name, try to find 'Other'
if (!$categoryId) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Other'");
    $stmt->execute([$userId]);
    $categoryId = $stmt->fetchColumn();
}

// If still no category, CREATE 'Other' category
if (!$categoryId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Other', 'expense')");
        $stmt->execute([$userId]);
        $categoryId = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Fallback: If creation fails (race condition?), try one last time to get ANY category
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $categoryId = $stmt->fetchColumn();

        if (!$categoryId) {
            header("Location: scan_receipt.php?error=Error: No categories found and could not create default.");
            exit;
        }
    }
}

// 8. Insert into Database
try {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, category, type, amount, date, description) VALUES (?, ?, ?, 'expense', ?, ?, ?)");
    $stmt->execute([
        $userId,
        $categoryId,
        $categoryName, // Store inferred name for display/legacy
        $data['amount'],
        $data['date'],
        $data['store_name'] . ' - ' . $data['description']
    ]);

    // Success!
    header("Location: index.php?success=Receipt Added: " . urlencode($data['store_name']));
    exit;

} catch (PDOException $e) {
    header("Location: scan_receipt.php?error=Database Error: " . $e->getMessage());
    exit;
}
?>