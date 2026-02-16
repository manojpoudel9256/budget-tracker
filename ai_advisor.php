<?php
require 'session_check.php';
require 'db_connect.php';

$page_title = "AI Advisor";
include 'header.php';
?>
<style>
    :root {
        --primary-glow: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.4) 0%, rgba(99, 102, 241, 0) 70%);
        --card-bg: rgba(255, 255, 255, 0.95);
        --text-primary: #1e293b;
        --text-secondary: #64748b;
    }

    body {
        background-color: #f1f5f9;
        background-image:
            radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
        background-attachment: fixed;
    }

    .advisor-container {
        padding-top: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }

    .advisor-card {
        background: var(--card-bg);
        border-radius: 32px;
        box-shadow:
            0 20px 40px -5px rgba(0, 0, 0, 0.1),
            0 10px 20px -5px rgba(99, 102, 241, 0.1),
            inset 0 0 0 1px rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        overflow: hidden;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
    }

    .advisor-header {
        position: relative;
        padding: 60px 40px 40px;
        text-align: center;
        background: radial-gradient(circle at center, rgba(238, 242, 255, 0.8) 0%, rgba(255, 255, 255, 0) 100%);
        overflow: hidden;
    }

    /* Animated background glow for header */
    .advisor-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(99, 102, 241, 0.05) 60deg, transparent 120deg);
        animation: rotateGlow 10s linear infinite;
        pointer-events: none;
    }

    @keyframes rotateGlow {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .type-icon-wrapper {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 24px;
        z-index: 2;
    }

    .type-icon {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        box-shadow: 0 15px 30px rgba(99, 102, 241, 0.4);
        position: relative;
        z-index: 2;
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Pulsing rings behind icon */
    .pulse-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid rgba(99, 102, 241, 0.3);
        animation: pulseRipple 3s infinite;
        z-index: 1;
    }

    .pulse-ring:nth-child(2) {
        animation-delay: 1s;
    }

    .pulse-ring:nth-child(3) {
        animation-delay: 2s;
    }

    @keyframes pulseRipple {
        0% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.8;
        }

        100% {
            transform: translate(-50%, -50%) scale(2.5);
            opacity: 0;
        }
    }

    .advisor-title {
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        letter-spacing: -0.02em;
        background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
        font-size: 1.75rem;
        position: relative;
        z-index: 2;
    }

    .advisor-subtitle {
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.95rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        position: relative;
        z-index: 2;
    }

    .insight-body {
        padding: 20px 40px 50px;
    }

    .insight-text {
        font-family: 'Inter', sans-serif;
        font-size: 1.25rem;
        line-height: 1.7;
        color: var(--text-primary);
        font-weight: 500;
        text-align: center;
        margin-bottom: 40px;
    }

    /* Highlight box for key stats if parsed (future proofing) */
    .stat-highlight {
        color: #6366f1;
        font-weight: 700;
        background: rgba(99, 102, 241, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
    }

    .btn-refresh {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 16px 32px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .btn-refresh:hover {
        background: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        color: #6366f1;
        border-color: #cbd5e1;
    }

    .btn-refresh i {
        transition: transform 0.5s ease;
    }

    .btn-refresh:hover i {
        transform: rotate(180deg);
    }

    .btn-refresh:active {
        transform: scale(0.98);
    }

    /* Types Styling */
    .type-warning .type-icon {
        background: linear-gradient(135deg, #ef4444, #f87171);
        box-shadow: 0 15px 30px rgba(239, 68, 68, 0.4);
    }

    .type-warning .pulse-ring {
        border-color: rgba(239, 68, 68, 0.3);
    }

    .type-praise .type-icon {
        background: linear-gradient(135deg, #10b981, #34d399);
        box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4);
    }

    .type-praise .pulse-ring {
        border-color: rgba(16, 185, 129, 0.3);
    }

    /* Loading State */
    .spinner-premium {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(99, 102, 241, 0.1);
        border-left-color: #6366f1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div class="container-fluid pb-5 px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex align-items-center px-3 pt-3 mb-4">
        <a href="index.php" class="btn btn-light btn-sm rounded-circle shadow-sm"
            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-left text-dark"></i>
        </a>
        <h4 class="fw-bold mb-0 ms-3" style="font-family: 'Inter', sans-serif; letter-spacing: -0.02em;">
            <?php echo $lang['financial_advisor']; ?></h4>
    </div>

    <div class="advisor-container">
        <!-- Main Advisor Card -->
        <div class="advisor-card fade-in-up" id="advisorCard">

            <div class="advisor-header">
                <div class="type-icon-wrapper">
                    <div class="pulse-ring"></div>
                    <div class="pulse-ring"></div>
                    <div class="type-icon" id="advisorIcon">
                        <i class="fas fa-robot"></i>
                    </div>
                </div>
                <h2 class="advisor-title" id="advisorTitle"><?php echo $lang['analyzing_finances']; ?></h2>
                <p class="advisor-subtitle"><?php echo $lang['ai_powered_insight']; ?></p>
            </div>

            <div class="insight-body">
                <div id="loadingSpinner" class="text-center py-5">
                    <div class="spinner-premium"></div>
                    <p class="text-muted fw-medium"><?php echo $lang['crunching_numbers']; ?></p>
                </div>

                <div id="insightContent" style="display: none;">
                    <div class="insight-text" id="advisorDetail">
                        <!-- Insight inserted here -->
                    </div>

                    <div class="text-center">
                        <button onclick="fetchInsight(true)" class="btn-refresh">
                            <i class="fas fa-sync-alt"></i> <?php echo $lang['generate_new_insight']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-4 opacity-50 hover-opacity-100 transition-opacity">
            <p class="small mb-0">
                <i class="fas fa-shield-alt me-1"></i> <?php echo $lang['insight_secure']; ?>
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetchInsight();
    });

    function fetchInsight(forceRefresh = false) {
        const spinner = document.getElementById('loadingSpinner');
        const content = document.getElementById('insightContent');
        const titleEl = document.getElementById('advisorTitle');
        const detailEl = document.getElementById('advisorDetail');
        const iconEl = document.getElementById('advisorIcon');

        // Localized Strings from PHP to JS
        const strAnalyzing = <?php echo json_encode($lang['analyzing_finances']); ?>;
        const strAuthError = <?php echo json_encode($lang['authentication_error']); ?>;
        const strNetworkError = <?php echo json_encode($lang['network_error']); ?>;
        const strCheckConnection = <?php echo json_encode($lang['check_connection']); ?>;

        if (forceRefresh) {
            content.style.display = 'none';
            spinner.style.display = 'block';
            titleEl.innerText = strAnalyzing;
            
            // Reset Theme
            const card = document.getElementById('advisorCard');
            card.className = 'advisor-card fade-in-up';
            iconEl.innerHTML = '<i class="fas fa-robot"></i>';
        }

        const url = forceRefresh ? 'get_ai_insights.php?refresh=true' : 'get_ai_insights.php';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                spinner.style.display = 'none';
                content.style.display = 'block';

                if (data.success) {
                    let insight;
                    try {
                        insight = JSON.parse(data.insight);
                    } catch (e) {
                        insight = typeof data.insight === 'object' ? data.insight : { summary: "Insight", detail: data.insight, type: 'tip' };
                    }

                    titleEl.innerText = insight.summary || "Financial Insight";
                    detailEl.innerHTML = insight.detail || "No details available.";

                    // Theme Logic based on Type (Class based)
                    const card = document.getElementById('advisorCard');
                    card.classList.remove('type-warning', 'type-praise');

                    if (insight.type === 'warning') {
                        card.classList.add('type-warning');
                        iconEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    } else if (insight.type === 'praise') {
                        card.classList.add('type-praise');
                        iconEl.innerHTML = '<i class="fas fa-trophy"></i>';
                    } else {
                        // Default Purple
                        iconEl.innerHTML = '<i class="fas fa-lightbulb"></i>';
                    }

                } else {
                    // Handle Errors Gracefully
                    let errTitle = strAuthError;
                    let errMsg = data.error || "Could not fetch insight.";

                    // Detect 503 / Busy
                    if (errMsg.includes('high traffic') || errMsg.includes('503')) {
                        errTitle = "AI is Busy";
                        iconEl.innerHTML = '<i class="fas fa-hourglass-half"></i>';
                        card.classList.add('type-warning'); 
                    } else if (errMsg.includes('API key') || errMsg.includes('403')) {
                        errTitle = "Configuration Error";
                        iconEl.innerHTML = '<i class="fas fa-key"></i>';
                        card.classList.add('type-warning');
                    } else {
                        iconEl.innerHTML = '<i class="fas fa-unlink"></i>';
                    }

                    titleEl.innerText = errTitle;
                    detailEl.innerText = errMsg;
                }
            })
            .catch(err => {
                console.error(err);
                spinner.style.display = 'none';
                content.style.display = 'block';
                titleEl.innerText = strNetworkError;
                detailEl.innerText = strCheckConnection;
            });
    }
</script>

<?php include 'footer.php'; ?>