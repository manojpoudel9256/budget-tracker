<footer class="text-center py-4 text-muted mt-5 mb-5 d-none d-md-block">
    <small>&copy; <?php echo date('Y'); ?> Budget Tracker Pro. All rights reserved.</small>
</footer>

<!-- Bottom Navigation Bar (Mobile Only) -->
<nav class="bottom-nav d-md-none">
    <a href="index.php" class="bottom-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="view_transactions.php?type=all"
        class="bottom-nav-item <?php echo $current_page == 'view_transactions.php' ? 'active' : ''; ?>">
        <i class="fas fa-list-ul"></i>
        <span>History</span>
    </a>

    <!-- Central FAB Holder -->
    <div class="bottom-nav-fab-holder">
        <a href="add_transaction_page.php" class="bottom-nav-fab">
            <i class="fas fa-plus"></i>
        </a>
    </div>

    <a href="scan_receipt.php"
        class="bottom-nav-item <?php echo $current_page == 'scan_receipt.php' ? 'active' : ''; ?>">
        <i class="fas fa-camera"></i>
        <span>Scan</span>
    </a>
    <a href="reports.php" class="bottom-nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-pie"></i>
        <span>Report</span>
    </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div> <!-- End Main Content -->
<!-- CLOCK SCRIPT -->
<script>
    function updateClock() {
        const now = new Date();
        const options = { year: 'numeric', month: 'short', day: 'numeric' };

        // Time 12hr
        let hours = now.getHours();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const strTime = hours + ':' + minutes + ' ' + ampm;

        // Date
        const strDate = now.toLocaleDateString('en-US', options);

        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            clockEl.innerHTML = `<i class="far fa-calendar-alt me-1"></i> ${strDate} &nbsp;&bull;&nbsp; <i class="far fa-clock me-1"></i> ${strTime}`;
        }

        // Update Greeting based on time
        const hour = now.getHours();
        let greeting = 'Good Morning';
        if (hour >= 12) greeting = 'Good Afternoon';
        if (hour >= 18) greeting = 'Good Evening';
        const gElement = document.getElementById('greetingTime');
        if (gElement) gElement.textContent = greeting;
    }

    // Update every second
    setInterval(updateClock, 1000);
    // Run immediately
    updateClock();
</script>
</body>

</html>