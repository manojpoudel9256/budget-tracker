<?php
require 'session_check.php';
?>
<?php include 'header.php'; ?>

<div class="container-fluid px-0 px-md-3">
    <div class="row justify-content-center mt-0">
        <div class="col-12 col-md-8 col-lg-6">

            <!-- AI MASCOT SECTION -->
            <div class="text-center position-relative mb-4 mt-4 fade-in-up">
                <!-- Glowing Background Effect -->
                <div class="position-absolute top-50 start-50 translate-middle"
                    style="width: 150px; height: 150px; background: radial-gradient(circle, rgba(99,102,241,0.4) 0%, rgba(99,102,241,0) 70%); filter: blur(20px); z-index: -1; animation: pulseGlow 3s infinite;">
                </div>

                <!-- Floating Robot Image -->
                <img src="img/Robot.png" alt="AI Robot" class="img-fluid"
                    style="max-height: 180px; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1)); animation: floatRobot 4s ease-in-out infinite;">

                <h3 class="fw-bold mt-3 mb-1"
                    style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    AI Receipt Scanner</h3>
                <p class="text-muted small px-4">Upload a receipt and let our AI extract the details.</p>
            </div>

            <!-- SCANNER CARD -->
            <div class="glass-card mb-4 p-4 fade-in-up delay-1">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center rounded-3 border-0 shadow-sm mb-3"
                        role="alert">
                        <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                        <div><?php echo htmlspecialchars($_GET['error']); ?></div>
                    </div>
                <?php endif; ?>

                <form action="process_receipt.php" method="POST" enctype="multipart/form-data" id="scanForm">
                    <div class="mb-4">
                        <label for="receiptImage" class="d-block w-100 position-relative cursor-pointer group">
                            <!-- Custom Upload Area -->
                            <div class="upload-zone text-center p-5 rounded-4 border-2 border-dashed position-relative overflow-hidden"
                                style="border-color: #cbd5e1; background: #f8fafc; transition: all 0.3s ease;">

                                <div id="uploadPlaceholder" class="position-relative z-1">
                                    <div class="mb-3 d-inline-block p-3 rounded-circle bg-white shadow-sm text-primary">
                                        <i class="fas fa-camera fa-2x"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Tap to Upload</h6>
                                    <p class="text-muted small mb-0">or drag and drop</p>
                                </div>

                                <!-- Image Preview -->
                                <div id="imagePreview"
                                    class="d-none position-absolute top-0 start-0 w-100 h-100 bg-white d-flex align-items-center justify-content-center p-2">
                                    <img src="" alt="Preview" class="img-fluid rounded-3 shadow-sm h-100"
                                        style="object-fit: contain;">
                                    <div
                                        class="position-absolute bottom-0 start-0 w-100 p-2 bg-white bg-opacity-75 backdrop-blur">
                                        <p class="text-primary small fw-bold mb-0"><i class="fas fa-sync-alt me-1"></i>
                                            Change Image</p>
                                    </div>
                                </div>
                            </div>
                            <input type="file" class="d-none" id="receiptImage" name="receipt_image"
                                accept="image/*,image/heic,image/heif" required onchange="previewImage(this)">
                        </label>
                    </div>

                    <button type="submit"
                        class="btn btn-lg w-100 text-white rounded-4 fw-bold shadow-lg position-relative overflow-hidden btn-scan"
                        id="scanBtn" style="background: var(--primary-gradient); padding: 16px;">
                        <span class="position-relative z-1"><i class="fas fa-bolt me-2"></i> Scan Now</span>
                    </button>
                </form>

                <!-- Loading State Overlay -->
                <!-- Loading State Overlay -->
                <div id="loadingState" class="d-none text-center py-4">
                    <div class="mb-3 position-relative mx-auto" style="width: 80%;">
                        <div class="progress"
                            style="height: 10px; border-radius: 10px; background: rgba(0,0,0,0.05); overflow: hidden;">
                            <div id="scanProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%; background: var(--primary-gradient);"></div>
                        </div>
                    </div>
                    <h5 class="fw-bold" id="scanText">Initializing...</h5>
                    <p class="text-muted small mb-0">Our AI robot is analyzing your receipt</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Animations */
    @keyframes floatRobot {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    @keyframes pulseGlow {

        0%,
        100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.4;
        }

        50% {
            transform: translate(-50%, -50%) scale(1.2);
            opacity: 0.6;
        }
    }

    .upload-zone:hover {
        border-color: var(--primary-color) !important;
        background: #eff6ff !important;
    }

    .btn-scan:active {
        transform: scale(0.98);
    }

    /* Premium Progress Bar Stripes */
    .progress-bar-striped {
        background-image: linear-gradient(45deg, rgba(255, 255, 255, .15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .15) 50%, rgba(255, 255, 255, .15) 75%, transparent 75%, transparent);
        background-size: 1rem 1rem;
    }

    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite;
    }

    @keyframes progress-bar-stripes {
        0% {
            background-position: 1rem 0;
        }

        100% {
            background-position: 0 0;
        }
    }
</style>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.querySelector('#uploadPlaceholder').classList.add('d-none');
                document.querySelector('#imagePreview').classList.remove('d-none');
                document.querySelector('#imagePreview img').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('scanForm').addEventListener('submit', function () {
        document.getElementById('scanForm').classList.add('d-none');
        document.getElementById('loadingState').classList.remove('d-none');

        // Progress Bar Animation
        let progressBar = document.getElementById('scanProgressBar');
        let scanText = document.getElementById('scanText');
        let width = 0;
        let messages = ["Scanning...", "Identifying Text...", "Extracting Dates...", "Analyzing Amounts...", "Finalizing..."];
        let msgIndex = 0;

        let interval = setInterval(function () {
            width += Math.random() * 5;
            if (width > 95) width = 95; // Wait for server
            progressBar.style.width = width + '%';

            // Cycle texts
            if (width > (msgIndex + 1) * 20 && msgIndex < messages.length - 1) {
                msgIndex++;
                scanText.innerText = messages[msgIndex];
            }
        }, 200);
    });
</script>

<?php include 'footer.php'; ?>