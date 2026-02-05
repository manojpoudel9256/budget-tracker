<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get categories by type for datalist (Merge saved categories + history)
$cat_stmt = $pdo->prepare("
    SELECT DISTINCT name, type FROM (
        SELECT name, type FROM categories WHERE user_id = ?
        UNION
        SELECT category as name, type FROM transactions WHERE user_id = ?
    ) as combined_categories
    ORDER BY type, name
");
$cat_stmt->execute([$user_id, $user_id]);
$all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_by_type = ['income' => [], 'expense' => []];
foreach ($all_categories as $cat) {
    if (empty($cat['name']))
        continue;
    $type = strtolower($cat['type']);
    if (isset($categories_by_type[$type])) {
        $categories_by_type[$type][] = $cat['name'];
    }
}

// Get redirect_to if set
$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : 'index.php';
?>
<?php include 'header.php'; ?>

<div class="container-fluid px-0 px-md-4 py-0 mt-0 mt-md-2">
    <div class="row justify-content-center m-0">
        <div class="col-12 col-md-8 col-lg-6 p-0">

            <!-- Mobile: Header with Back Button (integrated) -->
            <div class="d-flex align-items-center justify-content-between px-4 py-3 d-md-none">
                <a href="<?php echo htmlspecialchars($redirect_to); ?>" class="text-dark">
                    <i class="fas fa-arrow-left fa-lg"></i>
                </a>
                <h5 class="fw-bold mb-0">Add Transaction</h5>
                <div style="width: 24px;"></div> <!-- Spacer -->
            </div>

            <!-- Desktop: Header -->
            <div class="d-none d-md-flex align-items-center mb-3">
                <a href="<?php echo htmlspecialchars($redirect_to); ?>"
                    class="btn btn-light rounded-circle shadow-sm me-3">
                    <i class="fas fa-arrow-left text-muted"></i>
                </a>
                <h4 class="fw-bold mb-0">New Transaction</h4>
            </div>

            <div class="glass-card-mobile-transparent p-0 p-md-4">
                <!-- SUCCESS/ERROR ALERTS -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center rounded-3 border-0 shadow-sm mb-4 mx-3"
                        role="alert">
                        <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                        <div><?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success d-flex align-items-center rounded-3 border-0 shadow-sm mb-4 mx-3"
                        role="alert">
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div><?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?></div>
                    </div>
                <?php endif; ?>

                <form action="add_transaction.php" method="POST" id="transactionForm">
                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirect_to); ?>">
                    <input type="hidden" name="type" id="transactionType" value="expense">

                    <!-- 1. TYPE TOGGLE (Segmented Control) -->
                    <div class="px-4 mt-2 mb-4">
                        <div class="d-flex position-relative bg-light rounded-pill p-1 border"
                            style="overflow: hidden;">
                            <div class="position-absolute" id="typeIndicator"
                                style="top: 4px; left: 4px; height: calc(100% - 8px); width: calc(50% - 4px); background: var(--expense-gradient); border-radius: 20px; transition: all 0.3s cubic-bezier(0.2, 0.6, 0.2, 1); z-index: 1;">
                            </div>
                            <button type="button"
                                class="btn w-50 rounded-pill border-0 py-2 small fw-bold z-2 type-btn active text-white"
                                data-type="expense" onclick="selectType('expense')">Expense</button>
                            <button type="button"
                                class="btn w-50 rounded-pill border-0 py-2 small fw-bold z-2 type-btn text-muted"
                                data-type="income" onclick="selectType('income')">Income</button>
                        </div>
                    </div>

                    <!-- 2. HERO AMOUNT INPUT -->
                    <div class="text-center mb-5 px-4 animate-enter delay-1">
                        <label class="d-block text-muted text-uppercase fw-bold small mb-1 ls-1">Amount</label>
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="display-4 fw-bold text-dark me-1"
                                id="currencySymbol"><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?></span>
                            <input type="number" step="1" name="amount"
                                class="form-control border-0 bg-transparent text-dark p-0 fw-bold" value="0" required
                                style="font-size: 4rem; width: 220px; text-align: center; outline: none; box-shadow: none;"
                                onfocus="if(this.value==0){this.value=''}" onblur="if(this.value==''){this.value=0}"
                                autofocus>
                        </div>
                    </div>

                    <!-- 3. DETAILS CARD (Date, Category, Note) -->
                    <div class="mx-3 rounded-4 bg-white shadow-sm border p-3 animate-enter delay-2">
                        <!-- Date -->
                        <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                            <div class="icon-circle bg-light text-muted me-3"><i class="fas fa-calendar-alt"></i></div>
                            <div class="flex-grow-1">
                                <label class="small text-muted fw-bold d-block">Date</label>
                                <input type="date" name="date" class="form-control border-0 p-0 fw-bold text-dark"
                                    value="<?php echo date('Y-m-d'); ?>" required style="font-size: 1rem;">
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                            <div class="icon-circle bg-light text-muted me-3"><i class="fas fa-tag"></i></div>
                            <div class="flex-grow-1 position-relative">
                                <label class="small text-muted fw-bold d-block">Category</label>
                                <input type="text" name="category" id="categoryInput"
                                    class="form-control border-0 p-0 fw-bold text-dark" placeholder="Select or type..."
                                    required autocomplete="off" list="dl_cats">
                                <datalist id="dl_cats"></datalist>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-light text-muted me-3"><i class="fas fa-pen"></i></div>
                            <div class="flex-grow-1">
                                <label class="small text-muted fw-bold d-block">Note</label>
                                <input type="text" name="description"
                                    class="form-control border-0 p-0 fw-bold text-dark"
                                    placeholder="Add a note (optional)">
                            </div>
                        </div>
                    </div>

                    <!-- 4. CHIPS (Below details) -->
                    <div class="px-3 mt-3 animate-enter delay-3">
                        <div class="d-flex flex-wrap gap-2 justify-content-center" id="categoryChips">
                            <!-- Chips injected via JS -->
                        </div>
                    </div>

                    <!-- SUBMIT BUTTON (Normal Flow) -->
                    <div class="px-4 mt-5 mb-5 animate-enter delay-4">
                        <button type="submit"
                            class="btn w-100 py-3 rounded-4 text-white fw-bold shadow-lg transform-active-scale"
                            id="submitBtn" style="background: var(--expense-gradient); font-size: 1.1rem;">
                            Save Transaction
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

</form>
</div>
</div>
</div>
</div>

<style>
    /* Custom utility for transparent card on mobile */
    @media (max-width: 768px) {
        .glass-card-mobile-transparent {
            background: transparent;
            box-shadow: none;
            border: none;
        }

        .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    @media (min-width: 769px) {
        .glass-card-mobile-transparent {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<!-- SCRIPTS -->
<script>
    const categoriesByType = <?php echo json_encode($categories_by_type); ?>;
    const typeIndicator = document.getElementById('typeIndicator');
    const submitBtn = document.getElementById('submitBtn');
    const categoryChips = document.getElementById('categoryChips');
    const categoryInput = document.getElementById('categoryInput');
    const datalist = document.getElementById('dl_cats');
    const transactionIdInput = document.getElementById('transactionType');

    function selectType(type) {
        transactionIdInput.value = type;

        // Visual Updates
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.classList.remove('active', 'text-white');
            btn.classList.add('text-muted');
        });

        // Set active button
        const activeBtn = document.querySelector(`.type-btn[data-type="${type}"]`);
        activeBtn.classList.remove('text-muted');
        activeBtn.classList.add('active', 'text-white');

        // Animate Indicator & Button Color
        if (type === 'expense') {
            typeIndicator.style.transform = 'translateX(0)';
            typeIndicator.style.background = 'var(--expense-gradient)';
            submitBtn.style.background = 'var(--expense-gradient)';
            submitBtn.innerHTML = '<i class="fas fa-minus-circle me-2"></i> Save Expense';
        } else {
            typeIndicator.style.transform = 'translateX(100%)';
            typeIndicator.style.background = 'var(--income-gradient)';
            submitBtn.style.background = 'var(--income-gradient)';
            submitBtn.innerHTML = '<i class="fas fa-plus-circle me-2"></i> Save Income';
        }

        renderChips(type);
    }

    function renderChips(type) {
        categoryChips.innerHTML = '';
        datalist.innerHTML = '';

        const cats = categoriesByType[type] || [];

        // Add "Add New" hint if empty
        if (cats.length === 0) {
            categoryChips.innerHTML = '<span class="text-muted small fst-italic">No history yet. Type a category to start!</span>';
            return;
        }

        // Create Chips
        cats.forEach(cat => {
            // Chip Element
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-light btn-sm rounded-pill px-3 py-2 border';
            chip.style.fontSize = '0.9rem';
            chip.innerHTML = cat;
            chip.onclick = () => {
                categoryInput.value = cat;
                // Visual feedback
                document.querySelectorAll('#categoryChips .btn').forEach(b => b.classList.remove('btn-dark', 'text-white'));
                chip.classList.remove('btn-light');
                chip.classList.add('btn-dark', 'text-white');
            };
            categoryChips.appendChild(chip);

            // Datalist Option (Fallback)
            const opt = document.createElement('option');
            opt.value = cat;
            datalist.appendChild(opt);
        });
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        selectType('expense'); // Default
    });

</script>

<?php include 'footer.php'; ?>