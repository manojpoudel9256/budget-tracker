<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Default to current month stats for specific dashboard view
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

// --- QUICK STATS ---
$stmt = $pdo->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY type");
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['income' => 1000, 'expense' => 500]

$total_income = $stats['income'] ?? 0;
$total_expense = $stats['expense'] ?? 0;
$balance = $total_income - $total_expense;

// --- RECENT TRANSACTIONS (Limit 5) ---
$recent_stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 5");
$recent_stmt->execute([$user_id]);
$recent = $recent_stmt->fetchAll();

// --- BUDGETS ---
$budget_stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ?");
$budget_stmt->execute([$user_id]);
$budgets = $budget_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate simplified budget progress (Total Spent by Category Ever vs Limit)
// Note: In a real app, you'd likely sum expenses only for the CURRENT MONTH
$cat_stmt = $pdo->prepare("SELECT category, SUM(amount) as spent FROM transactions WHERE user_id = ? AND type='expense' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE()) GROUP BY category");
$cat_stmt->execute([$user_id]);
$current_month_spending = $cat_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$budget_alerts = [];

// --- FETCH CATEGORIES FOR AUTOCOMPLETE (Merge saved + history) ---
$cat_list_stmt = $pdo->prepare("
    SELECT DISTINCT name, type FROM (
        SELECT name, type FROM categories WHERE user_id = ?
        UNION
        SELECT category as name, type FROM transactions WHERE user_id = ?
    ) as combined_categories
    ORDER BY type, name ASC
");
$cat_list_stmt->execute([$user_id, $user_id]);
$all_categories = $cat_list_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_by_type = ['income' => [], 'expense' => []];
foreach ($all_categories as $cat) {
    $type = strtolower($cat['type']);
    if (isset($categories_by_type[$type])) {
        $categories_by_type[$type][] = $cat['name'];
    }
}



?>
<?php include 'header.php'; ?>
<style>
    /* Dashboard Mobile Premium Optimizations */
    @media (max-width: 768px) {

        /* Global Mobile Reset */
        body {
            background-color: #f8fafc;
            /* Native app background */
            padding-bottom: 90px;
            /* Nav bar space */
        }



        /* Full Width Balance Card for Mobile */
        .premium-balance-card {
            border-radius: 0 !important;
            margin-left: -12px;
            /* Counteract container if needed, but container is px-0 */
            box-shadow: none !important;
            padding-bottom: 30px !important;
        }

        /* Quick Actions Horizontal Scroll */
        .quick-actions-scroll {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 20px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            margin: 0 -12px;
            /* Bleed to edge */
        }

        .quick-actions-scroll::-webkit-scrollbar {
            display: none;
        }

        .quick-action-item {
            min-width: 90px;
            scroll-snap-align: start;
            flex-shrink: 0;
        }

        /* Native List Styling */
        .transaction-card {
            border-bottom: 1px solid #f1f5f9;
            padding: 15px 0;
        }

        .glass-card {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
    }

    /* --- DESKTOP PREMIUM OPTIMIZATIONS (PC View Only) --- */
    @media (min-width: 769px) {
        body {
            background-color: #f0f2f5;
            /* Slight gray for depth on desktop */
            padding-bottom: 20px;
        }

        /* Center Content & Constrain Width */
        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px !important;
        }

        /* Desktop Card Styling */
        .glass-card {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025) !important;
        }

        /* Header Layout for Desktop */
        .d-flex.justify-content-between.align-items-end.px-3 {
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-bottom: 30px !important;
        }

        /* AMEX Card Desktop Sizing */
        .position-relative.overflow-hidden.p-4 {
            min-height: 280px !important;
            /* Taller card on desktop */
            max-width: 100%;
            margin-bottom: 40px !important;
        }

        /* Quick Actions Grid for Desktop */
        .quick-actions-scroll {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            overflow: visible;
            padding: 0;
            margin: 0 0 40px 0;
        }

        .quick-actions-scroll a {
            min-width: auto;
            height: 100%;
            padding: 25px !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease;
        }

        .quick-actions-scroll a:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
            border-color: transparent !important;
        }

        /* Headings */
        h5.fw-bold {
            font-size: 1.5rem;
            margin-bottom: 25px !important;
        }

        /* Balance Text Size */
        .display-6 {
            font-size: 3rem !important;
            /* Larger balance on PC */
        }
    }
</style>

<div class="container-fluid px-0 px-md-3">
    <!-- FIXED HEADER: Brand + Greeting -->
    <!-- Greeting & Clock -->
    <div class="d-flex justify-content-between align-items-end px-3 mb-0 pt-0">
        <div>
            <h6 class="text-muted fw-bold mb-0 text-uppercase small ls-1" id="greetingTime">Good Morning</h6>
            <h2 class="fw-bold mb-0 text-dark display-6" style="font-size: 1.5rem;">
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
            </h2>
        </div>
        <p id="liveClock" class="small text-muted mb-0 fw-medium bg-light px-2 py-1 rounded-pill border">
            <i class="far fa-clock me-1"></i> --:--
        </p>
    </div>

    <!-- Spacer for Mobile -->
    <div style="height: 20px;"></div>

    <!-- AMEX CENTURION STYLE (BLACK CARD) -->
    <div class="px-3 mb-4 mt-2">
        <div class="position-relative overflow-hidden p-4"
            style="background: radial-gradient(circle at 10% 20%, #4a4a4a 0%, #1a1a1a 90%); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); min-height: 230px; border: 1px solid rgba(255,255,255,0.1); font-family: 'Inter', sans-serif;">

            <!-- Texture Overlay -->
            <div class="position-absolute w-100 h-100 top-0 start-0"
                style="background: repeating-linear-gradient(45deg, rgba(255,255,255,0.02) 0px, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 10px); pointer-events: none;">
            </div>

            <!-- Centurion Watermark -->
            <div class="position-absolute top-50 start-50 translate-middle opacity-10"
                style="pointer-events: none; opacity: 0.05;">
                <i class="fas fa-user-circle fa-10x text-white"></i>
            </div>

            <!-- Decorative Border Lines -->
            <div class="position-absolute w-100 start-0"
                style="top: 20px; height: 1px; background: linear-gradient(90deg, transparent 5%, rgba(255,255,255,0.15) 20%, rgba(255,255,255,0.15) 80%, transparent 95%);">
            </div>
            <div class="position-absolute w-100 start-0"
                style="bottom: 20px; height: 1px; background: linear-gradient(90deg, transparent 5%, rgba(255,255,255,0.15) 20%, rgba(255,255,255,0.15) 80%, transparent 95%);">
            </div>

            <!-- Top Row: Branding -->
            <div class="d-flex justify-content-between align-items-center mb-4 position-relative z-1 pt-1">
                <div class="text-uppercase fw-bold tracking-widest text-white small"
                    style="letter-spacing: 2px; font-size: 0.7rem; text-shadow: 0 1px 2px black;">Budget Tracker</div>
                <div class="text-end">
                    <span class="d-block text-white fw-bold fst-italic"
                        style="font-family: 'Times New Roman', serif; font-size: 0.9rem;">Centurion</span>
                </div>
            </div>

            <!-- Middle: Chip & Balance -->
            <div class="d-flex justify-content-between align-items-center mb-4 position-relative z-1">
                <!-- EMV Chip -->
                <div
                    style="width: 45px; height: 35px; background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #b38728 100%); border-radius: 5px; position: relative; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.4);">
                    <div class="position-absolute top-50 start-0 w-100"
                        style="height: 1px; background: rgba(0,0,0,0.2);"></div>
                    <div class="position-absolute start-50 top-0 h-100"
                        style="width: 1px; background: rgba(0,0,0,0.2); left: 35%;"></div>
                    <div class="position-absolute start-50 top-0 h-100"
                        style="width: 1px; background: rgba(0,0,0,0.2); left: 65%;"></div>
                </div>

                <div class="text-end">
                    <!-- Balance -->
                    <h2 class="display-6 fw-bold text-white mb-0"
                        style="text-shadow: 0 2px 4px rgba(0,0,0,0.8); font-family: 'Courier New', monospace; letter-spacing: -1px;">
                        <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                        <?php echo number_format($balance, 0); ?>
                    </h2>
                </div>
            </div>

            <!-- Bottom Info -->
            <div class="d-flex justify-content-between align-items-end mt-auto position-relative z-1 pb-1">
                <!-- Name -->
                <div>
                    <div class="small text-white opacity-50 mb-0" style="font-size: 0.45rem; letter-spacing: 1px;">
                        MEMBER SINCE <?php echo date('y'); ?></div>
                    <div class="text-white fw-bold tracking-wide"
                        style="text-shadow: 0 1px 2px black; font-family: 'Courier New', monospace; letter-spacing: 1px; font-size: 0.9rem;">
                        <?php echo strtoupper($_SESSION['username'] ?? 'USER'); ?>
                    </div>
                </div>

                <!-- Income/Expense Summary (Subtle) -->
                <div class="d-flex gap-3 text-end">
                    <div>
                        <div class="text-success small fw-bold"
                            style="font-size: 0.8rem; text-shadow: 0 1px 1px black;">+
                            <?php echo number_format($total_income, 0); ?>
                        </div>
                        <div class="text-white opacity-50 small" style="font-size: 0.5rem; letter-spacing: 1px;">CREDIT
                        </div>
                    </div>
                    <div>
                        <div class="text-danger small fw-bold" style="font-size: 0.8rem; text-shadow: 0 1px 1px black;">
                            - <?php echo number_format($total_expense, 0); ?></div>
                        <div class="text-white opacity-50 small" style="font-size: 0.5rem; letter-spacing: 1px;">DEBIT
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="mb-4">
        <h6 class="text-muted fw-bold mb-3 small text-uppercase px-3">Quick Actions</h6>
        <div class="quick-actions-scroll">
            <a href="add_transaction_page.php"
                class="d-flex flex-column align-items-center text-decoration-none text-dark bg-white p-3 rounded-4 shadow-sm border border-light"
                style="min-width: 100px;">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary mb-2 d-flex align-items-center justify-content-center"
                    style="width: 50px; height: 50px;">
                    <i class="fas fa-plus fa-lg"></i>
                </div>
                <span class="small fw-bold">Add New</span>
            </a>
            <a href="scan_receipt.php"
                class="d-flex flex-column align-items-center text-decoration-none text-dark bg-white p-3 rounded-4 shadow-sm border border-light"
                style="min-width: 100px;">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning mb-2 d-flex align-items-center justify-content-center"
                    style="width: 50px; height: 50px;">
                    <i class="fas fa-camera fa-lg"></i>
                </div>
                <span class="small fw-bold">Scan</span>
            </a>
            <a href="#" data-bs-toggle="modal" data-bs-target="#setBudgetModal"
                class="d-flex flex-column align-items-center text-decoration-none text-dark bg-white p-3 rounded-4 shadow-sm border border-light"
                style="min-width: 100px;">
                <div class="rounded-circle bg-success bg-opacity-10 text-success mb-2 d-flex align-items-center justify-content-center"
                    style="width: 50px; height: 50px;">
                    <i class="fas fa-bullseye fa-lg"></i>
                </div>
                <span class="small fw-bold">Budget</span>
            </a>
            <a href="reports.php"
                class="d-flex flex-column align-items-center text-decoration-none text-dark bg-white p-3 rounded-4 shadow-sm border border-light"
                style="min-width: 100px;">
                <div class="rounded-circle bg-info bg-opacity-10 text-info mb-2 d-flex align-items-center justify-content-center"
                    style="width: 50px; height: 50px;">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <span class="small fw-bold">Stats</span>
            </a>
        </div>
    </div>



    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-lg-7">
            <div class="glass-card mb-4 p-4" style="border-radius: 24px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold text-dark">Recent Activity</h5>
                    <a href="view_transactions.php?type=all" class="text-decoration-none small fw-bold text-primary">See
                        All <i class="fas fa-chevron-right ms-1" style="font-size: 0.75rem;"></i></a>
                </div>

                <?php if (empty($recent)): ?>
                    <div class="text-center py-5 text-muted opacity-75">
                        <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No recent transactions</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recent as $t):
                            // Icon Logic
                            $cat_lower = strtolower($t['category']);
                            $icon = 'fa-tag';
                            $bg_color = 'bg-light';
                            $text_color = 'text-muted';

                            if (strpos($cat_lower, 'food') !== false || strpos($cat_lower, 'dining') !== false) {
                                $icon = 'fa-utensils';
                                $bg_color = 'bg-warning bg-opacity-10';
                                $text_color = 'text-warning';
                            } elseif (strpos($cat_lower, 'transport') !== false || strpos($cat_lower, 'uber') !== false) {
                                $icon = 'fa-car';
                                $bg_color = 'bg-info bg-opacity-10';
                                $text_color = 'text-info';
                            } elseif (strpos($cat_lower, 'shopping') !== false || strpos($cat_lower, 'market') !== false) {
                                $icon = 'fa-shopping-bag';
                                $bg_color = 'bg-danger bg-opacity-10';
                                $text_color = 'text-danger';
                            } elseif (strpos($cat_lower, 'home') !== false || strpos($cat_lower, 'rent') !== false) {
                                $icon = 'fa-home';
                                $bg_color = 'bg-primary bg-opacity-10';
                                $text_color = 'text-primary';
                            } elseif (strpos($cat_lower, 'salary') !== false || strpos($cat_lower, 'income') !== false) {
                                $icon = 'fa-wallet';
                                $bg_color = 'bg-success bg-opacity-10';
                                $text_color = 'text-success';
                            }
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-4 hover-glass-effect"
                                style="transition: all 0.2s;">
                                <!-- Icon & Text -->
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle <?php echo $bg_color; ?> <?php echo $text_color; ?> d-flex align-items-center justify-content-center me-3"
                                        style="width: 50px; height: 50px;">
                                        <i class="fas <?php echo $icon; ?> fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($t['category']); ?></h6>
                                        <div class="small text-muted mt-1">
                                            <span class="opacity-75"><?php echo date('M d', strtotime($t['date'])); ?></span>
                                            <?php if (!empty($t['description'])): ?>
                                                <span class="mx-1">&bull;</span> <span
                                                    class="fst-italic"><?php echo htmlspecialchars($t['description']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Amount -->
                                <div class="text-end">
                                    <h6
                                        class="fw-bold mb-0 <?php echo $t['type'] == 'income' ? 'text-success' : 'text-dark'; ?>">
                                        <?php echo $t['type'] == 'income' ? '+' : '-'; ?>
                                        <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                                        <?php echo number_format($t['amount'], 0); ?>
                                    </h6>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <style>
                    .hover-glass-effect:hover {
                        background: rgba(0, 0, 0, 0.02);
                        transform: translateX(5px);
                        cursor: default;
                    }
                </style>
            </div>
        </div>

        <!-- Monthly Budgets -->
        <div class="col-lg-5">
            <div class="glass-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold">Monthly Budgets</h5>
                    <a href="set_budget_page.php" class="btn btn-outline-primary btn-sm rounded-pill">
                        <i class="fas fa-plus me-1"></i>Set Goal
                    </a>
                </div>
                <div>
                    <?php if (empty($budgets)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3 text-muted"><i class="fas fa-bullseye fa-3x opacity-25"></i></div>
                            <p class="text-muted">No budget goals set yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($budgets as $b):
                            $cat = $b['category'];
                            $limit = $b['amount'];
                            $spent = $current_month_spending[$cat] ?? 0;
                            $percent = ($limit > 0) ? ($spent / $limit) * 100 : 0;
                            $color = $percent < 75 ? 'bg-success' : ($percent < 90 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat); ?></span>
                                    <small class="text-muted fw-bold">
                                        <?php echo number_format($spent, 0); ?> / <?php echo number_format($limit, 0); ?>
                                    </small>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 10px; background-color: #f1f3f5;">
                                    <div class="progress-bar <?php echo $color; ?>" role="progressbar"
                                        style="width: <?php echo min($percent, 100); ?>%; border-radius: 10px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="add_transaction_page.php" class="fab-btn d-md-none">
        <i class="fas fa-plus"></i>
    </a>
</div>

<!-- Add Transaction Modal (Premium) -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
            <!-- Modal Header with Gradient -->
            <div class="modal-header border-0 text-white py-4"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="w-100 text-center">
                    <div class="mb-2">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25"
                            style="width: 48px; height: 48px;">
                            <i class="fas fa-plus-circle"></i>
                        </span>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">New Transaction</h5>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="add_transaction.php" method="POST">
                <div class="modal-body p-4">
                    <!-- Type Toggle -->
                    <div class="d-flex gap-2 mb-4">
                        <button type="button" class="btn flex-fill py-3 rounded-3 type-btn active" data-type="expense"
                            onclick="selectType('expense', this)">
                            <i class="fas fa-arrow-up me-2"></i> Expense
                        </button>
                        <button type="button" class="btn flex-fill py-3 rounded-3 type-btn" data-type="income"
                            onclick="selectType('income', this)">
                            <i class="fas fa-arrow-down me-2"></i> Income
                        </button>
                    </div>
                    <input type="hidden" name="type" id="transactionType" value="expense">

                    <!-- Amount -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Amount</label>
                        <div class="input-group input-group-lg">
                            <span
                                class="input-group-text bg-light border-end-0 rounded-start-3"><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?></span>
                            <input type="number" step="1" name="amount"
                                class="form-control form-control-lg border-start-0 rounded-end-3 fw-bold"
                                placeholder="0" required style="font-size: 1.25rem;">
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                        <input type="text" name="category" class="form-control form-control-lg rounded-3"
                            list="categoryList" placeholder="e.g. Food, Transport" required autocomplete="off">
                        <datalist id="categoryList">
                            <?php foreach ($category_options as $cat_name): ?>
                                <option value="<?php echo htmlspecialchars($cat_name); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Date</label>
                        <input type="date" name="date" class="form-control form-control-lg rounded-3"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Notes (Optional)</label>
                        <textarea name="description" class="form-control rounded-3" rows="2"
                            placeholder="Add a note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-check me-2"></i> Add Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Set Budget Modal (Premium) -->
<div class="modal fade" id="setBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
            <!-- Modal Header with Gradient -->
            <div class="modal-header border-0 text-white py-4"
                style="background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);">
                <div class="w-100 text-center">
                    <div class="mb-2">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25"
                            style="width: 48px; height: 48px;">
                            <i class="fas fa-bullseye"></i>
                        </span>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">Set Budget Goal</h5>
                    <p class="mb-0 opacity-75 small mt-1">Control your monthly spending</p>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="set_budget.php" method="POST">
                <div class="modal-body p-4">
                    <!-- Category -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-tag me-1"></i> Category
                        </label>
                        <input type="text" name="category" class="form-control form-control-lg rounded-3"
                            list="budgetCategoryList" placeholder="e.g. Food, Entertainment" required autocomplete="off"
                            style="border: 2px solid #e2e8f0;">
                        <datalist id="budgetCategoryList">
                            <?php foreach ($categories_by_type['expense'] as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Monthly Limit -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-coins me-1"></i> Monthly Limit
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light rounded-start-3"
                                style="border: 2px solid #e2e8f0; border-right: 0;">
                                <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                            </span>
                            <input type="number" step="1" name="amount"
                                class="form-control form-control-lg rounded-end-3 fw-bold" placeholder="10000" required
                                style="border: 2px solid #e2e8f0; border-left: 0; font-size: 1.5rem;">
                        </div>
                        <small class="text-muted mt-2 d-block">Set a spending limit for this category</small>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn w-100 py-3 rounded-3 fw-bold text-white"
                        style="background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%); border: none;">
                        <i class="fas fa-check me-2"></i> Set Budget
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const categoriesByType = <?php echo json_encode($categories_by_type); ?>;

    // Type Toggle for Add Transaction Modal
    function selectType(type, btn) {
        document.getElementById('transactionType').value = type;

        // Update button styles
        document.querySelectorAll('.type-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'white';
            b.style.color = '#64748b';
            b.style.border = '2px solid #e2e8f0';
            b.style.boxShadow = 'none';
        });

        btn.classList.add('active');
        if (type === 'expense') {
            btn.style.background = 'linear-gradient(135deg, #ff5858 0%, #f09819 100%)';
            btn.style.color = 'white';
            btn.style.border = '2px solid transparent';
            btn.style.boxShadow = '0 10px 20px rgba(255, 88, 88, 0.25)';
        } else {
            btn.style.background = 'linear-gradient(135deg, #0ba360 0%, #3cba92 100%)';
            btn.style.color = 'white';
            btn.style.border = '2px solid transparent';
            btn.style.boxShadow = '0 10px 20px rgba(11, 163, 96, 0.25)';
        }

        // Update category list
        updateCategoryOptions();
    }

    function updateCategoryOptions() {
        const typeInput = document.getElementById('transactionType');
        const list = document.getElementById('categoryList');
        const selectedType = typeInput.value;

        list.innerHTML = ''; // Clear existing options

        if (categoriesByType[selectedType]) {
            categoriesByType[selectedType].forEach(catName => {
                const option = document.createElement('option');
                option.value = catName;
                list.appendChild(option);
            });
        }
    }

    // Initialize on page load and modal open
    document.addEventListener('DOMContentLoaded', function () {
        updateCategoryOptions();

        // Initialize type buttons styling
        const activeBtn = document.querySelector('.type-btn.active');
        if (activeBtn) {
            selectType('expense', activeBtn);
        }
    });

    // Reset modal state when opened
    const modal = document.getElementById('addTransactionModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function () {
            const expenseBtn = document.querySelector('.type-btn[data-type="expense"]');
            if (expenseBtn) {
                selectType('expense', expenseBtn);
            }
        });
    }
</script>
<?php include 'footer.php'; ?>