<?php
require 'session_check.php';
require 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-3">Scan Receipt (AI)</h2>
        </div>

        <div class="col-md-6 offset-md-3">
            <div class="glass-card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-robot fa-4x text-primary mb-3"></i>
                        <h4>AI Receipt Scanner</h4>
                        <p class="text-muted">Upload a photo of your Japanese receipt. Our AI will translate, extract
                            details, and categorize it automatically.</p>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="process_receipt.php" method="POST" enctype="multipart/form-data" id="scanForm">
                        <div class="mb-4">
                            <label for="receiptImage" class="form-label fw-bold">Choose Image or Take Photo</label>
                            <input type="file" class="form-control" id="receiptImage" name="receipt_image"
                                accept="image/*" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="scanBtn">
                                <i class="fas fa-magic me-2"></i> Scan & Analyze
                            </button>
                        </div>
                    </form>

                    <!-- Loading State (Hidden by default) -->
                    <div id="loadingState" class="d-none mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 fw-bold">Analyzing Receipt...</p>
                        <small class="text-muted">Translating Japanese & Extracting Data (Approx. 5-10s)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('scanForm').addEventListener('submit', function () {
        document.getElementById('scanBtn').classList.add('d-none');
        document.getElementById('loadingState').classList.remove('d-none');
    });
</script>

<?php include 'footer.php'; ?>