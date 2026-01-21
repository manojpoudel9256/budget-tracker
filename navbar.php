<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Budget Tracker Pro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_transactions.php?type=income">Income</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_transactions.php?type=expense">Expenses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">Reports</a>
                </li>
            </ul>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    <?php if (isset($_SESSION['username']))
                        echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Alert Messages System -->
<?php if (isset($_GET['msg'])): ?>
    <div class="container">
        <?php if ($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show">Transaction updated successfully! <button type="button"
                    class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-warning alert-dismissible fade show">Transaction deleted successfully! <button type="button"
                    class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['msg'] == 'error'): ?>
            <div class="alert alert-danger alert-dismissible fade show">An error occurred. <button type="button"
                    class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    </div>
<?php endif; ?>