<?php
/**
 * pages/pos.php
 * Cashier screen: product grid + category filter + cart + numpad payment + receipt modal.
 */

// 1. ດຶງຂໍ້ມູນໝວດໝູ່ທັງໝົດ
$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");

// 2. ດຶງຂໍ້ມູນສິນຄ້າ
// ✅ ແກ້ໄຂ: ຕັດເງື່ອນໄຂ "p.stock > 0" ອອກ ເພາະສິນຄ້າໃໝ່ທີ່ບໍ່ໄດ້ໃສ່ຈຳນວນ stock
//    ຈະຖືກເກັບເປັນ 0 ໂດຍອັດຕະໂນມັດ ແລ້ວຈະຫາຍໄປຈາກໜ້າ POS ທັງທີ່ຍັງ available ຢູ່
//    ຕອນນີ້ໃຫ້ດຶງມາໝົດ ແລ້ວໄປສະແດງ badge "ໝົດ" ແທນຢູ່ດ້ານລຸ່ມ
$sql = "
    SELECT p.*, COALESCE(c.name, 'ທົ່ວໄປ') AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE (p.status = 'available' OR p.status = 'active' OR p.status IS NULL OR p.status = '1')
    ORDER BY p.id DESC
";

$products = $conn->query($sql);

if (!$products) {
    $products = $conn->query("SELECT *, 'ທົ່ວໄປ' AS category_name FROM products ORDER BY id DESC");
}

// 3. ສ້າງເລກທີອໍເດີອັດໂນມັດ
$today = date('Ymd');
$next_order_number = "ORD-{$today}-0001";

$order_query = $conn->query("SELECT order_number FROM orders WHERE order_number LIKE 'ORD-{$today}-%' ORDER BY id DESC LIMIT 1");

if ($order_query && $order_query->num_rows > 0) {
    $last_order = $order_query->fetch_assoc()['order_number'];
    $last_num = (int)substr($last_order, -4);
    $next_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    $next_order_number = "ORD-{$today}-{$next_num}";
}
?>

<style>
/* Modern Coffee POS Theme Styles */
:root {
    --coffee-primary: #6F4E37;
    --coffee-hover: #5A3E2B;
    --coffee-light: #FDF8F5;
    --coffee-accent: #C8A2C8;
    --border-color: #EAE6E1;
    --text-dark: #2C221E;
    --text-muted: #8C827A;
}

/* UI Layout & Containers */
.cart-container, .card-glass {
    background: #FFFFFF;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(111, 78, 55, 0.05);
}

.text-coffee { color: var(--coffee-primary) !important; }
.bg-coffee-light { background-color: var(--coffee-light) !important; }

/* Category Filter Tabs */
.category-tab-container {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 8px;
    margin-bottom: 15px;
    scrollbar-width: thin;
}

.category-btn {
    border: 1.5px solid var(--border-color);
    background-color: #FAFAFA;
    color: var(--text-dark);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}

.category-btn:hover {
    border-color: var(--coffee-primary);
    background-color: var(--coffee-light);
}

.category-btn.active {
    background-color: var(--coffee-primary);
    color: #FFFFFF;
    border-color: var(--coffee-primary);
    box-shadow: 0 4px 10px rgba(111, 78, 55, 0.2);
}

/* Order Type & Payment Cards */
.payment-card, .order-type-card {
    background: #FAFAFA;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 10px 6px;
    height: 72px;
}

.payment-card:hover, .order-type-card:hover {
    border-color: var(--coffee-primary);
    background-color: var(--coffee-light);
    transform: translateY(-2px);
}

.btn-check:checked + .payment-card,
.btn-check:checked + .order-type-card {
    border-color: var(--coffee-primary) !important;
    background-color: var(--coffee-light) !important;
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.12);
}

.payment-card i, .order-type-card i {
    font-size: 1.25rem;
    margin-bottom: 4px;
}

.payment-card span, .order-type-card span {
    font-size: 12px !important;
    font-weight: 600;
    color: var(--text-dark);
}

/* Numpad POS Styling */
.numpad-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    margin-top: 10px;
}

.btn-numpad {
    background-color: #F8F9FA;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 12px;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-dark);
    transition: all 0.15s ease;
}

.btn-numpad:active, .btn-numpad:hover {
    background-color: #E9ECEF;
    border-color: #CED4DA;
}

.btn-numpad-backspace {
    background-color: #E2E8F0;
    color: #495057;
}

.cash-display-box {
    background: #F8F9FA;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 10px 14px;
}

/* Buttons Styling */
.btn-pos {
    background-color: var(--coffee-primary);
    color: #FFFFFF;
    border: none;
    border-radius: 12px;
    padding: 12px;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-pos:hover {
    background-color: var(--coffee-hover);
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.25);
}

.btn-pos-outline {
    background-color: transparent;
    color: #DC3545;
    border: 1.5px solid #F8D7DA;
    border-radius: 12px;
    padding: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-pos-outline:hover {
    background-color: #DC3545;
    color: #FFFFFF;
    border-color: #DC3545;
}

/* Cart Items & Actions */
.cart-item {
    border: 1px solid var(--border-color) !important;
    border-radius: 10px !important;
}

.cart-item-actions .btn {
    width: 28px;
    height: 28px;
    padding: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.btn-coffee-light {
    background-color: var(--coffee-light);
    color: var(--coffee-primary);
    border: 1px solid var(--border-color);
}

.btn-coffee-light:hover {
    background-color: var(--coffee-primary);
    color: #FFFFFF;
}

/* Product Cards Grid */
.product-card {
    background: #FFFFFF;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--coffee-primary);
    box-shadow: 0 8px 20px rgba(111, 78, 55, 0.1);
}

/* ✅ ເພີ່ມ: ສະໄຕລ໌ສິນຄ້າໝົດສະຕ໋ອກ */
.product-card.out-of-stock {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.product-icon {
    font-size: 2rem;
    color: var(--coffee-primary);
    margin-bottom: 8px;
}

.product-name {
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 2px;
}

.product-category {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 6px;
}

.product-price {
    font-weight: 700;
    color: var(--coffee-primary);
    font-size: 16px;
}

.search-input {
    width: 100%;
    padding: 10px 16px;
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    outline: none;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    border-color: var(--coffee-primary);
    box-shadow: 0 0 0 3px rgba(111, 78, 55, 0.1);
}

@media print {
    body * { visibility: hidden; }
    body.print-receipt #receipt-content, body.print-receipt #receipt-content * { visibility: visible; }
    body.print-receipt #receipt-content { position: absolute; left: 0; top: 0; width: 100%; }
    body.print-kitchen #kitchen-ticket-content, body.print-kitchen #kitchen-ticket-content * { visibility: visible; }
    body.print-kitchen #kitchen-ticket-content { position: absolute; left: 0; top: 0; width: 100%; display: block !important; }
    body.print-bar #bar-ticket-content, body.print-bar #bar-ticket-content * { visibility: visible; }
    body.print-bar #bar-ticket-content { position: absolute; left: 0; top: 0; width: 100%; display: block !important; }
}
</style>

<div class="row g-4">
    <!-- ສ່ວນລາຍການສິນຄ້າ (Product Grid) -->
    <div class="col-lg-7 col-xl-8">
        <div class="card-glass p-4">
            <div class="mb-3">
                <input type="text" id="searchProduct" class="search-input" placeholder="ຄົ້ນຫາສິນຄ້າ...">
            </div>

            <!-- Category Filter Tabs -->
            <div class="category-tab-container">
                <button class="category-btn active" data-category="all">ທັງໝົດ (All)</button>
                <?php if ($categories && $categories->num_rows > 0): ?>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <button class="category-btn" data-category="<?= htmlspecialchars($cat['name']) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <div class="row product-grid g-3" id="product-list">
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()): ?>
                    <?php 
                        $stock_val = array_key_exists('stock', $row) && $row['stock'] !== null ? (int)$row['stock'] : 9999;
                        $display_stock = array_key_exists('stock', $row) && $row['stock'] !== null ? $row['stock'] : null;
                        $cat_name = $row['category_name'] ?? 'ທົ່ວໄປ';
                        $is_out_of_stock = ($display_stock !== null && $stock_val <= 0);
                    ?>
                    <div class="col-6 col-md-4 col-lg-3 product-item" 
                         data-name="<?= strtolower(htmlspecialchars($row['name'] ?? '')) ?>"
                         data-category="<?= htmlspecialchars($cat_name) ?>">
                        <div class="product-card <?= $is_out_of_stock ? 'out-of-stock' : 'add-to-cart' ?>"
                             data-id="<?= $row['id'] ?>"
                             data-name="<?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES) ?>"
                             data-price="<?= $row['price'] ?? 0 ?>"
                             data-stock="<?= $stock_val ?>">
                            <div class="product-icon"><i class="fas fa-mug-hot"></i></div>
                            <div class="product-name"><?= htmlspecialchars($row['name'] ?? 'ບໍ່ມີຊື່') ?></div>
                            <div class="product-category"><?= htmlspecialchars($cat_name) ?></div>
                            <div class="product-price"><?= number_format($row['price'] ?? 0) ?> ₭</div>
                            <?php if ($is_out_of_stock): ?>
                                <div class="text-danger small fw-bold"><i class="fas fa-ban"></i> ໝົດ</div>
                            <?php elseif ($display_stock !== null): ?>
                                <div class="product-stock"><i class="fas fa-box"></i> <?= $display_stock ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-coffee fa-3x mb-3 d-block" style="color: #ddd;"></i>
                            <h6>ຍັງບໍ່ມີສິນຄ້າໃນ sistema</h6>
                            <small>ກະລຸນາກວດສອບຕາຕະລາງ products ໃນ Database</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ສ່ວນກະຕ່າສິນຄ້າ (Cart System & Numpad) -->
    <div class="col-lg-5 col-xl-4">
        <div class="cart-container">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-shopping-basket text-coffee me-2"></i> ກະຕ່າ
                <span class="badge bg-coffee-light text-coffee ms-2" id="item-count">0 ລາຍການ</span>
            </h5>

            <div id="cart-items" style="max-height: 220px; overflow-y: auto; min-height: 100px;">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-cart-plus fa-2x mb-2 d-block" style="color: #ddd;"></i>
                    <small>ຍັງບໍ່ມີສິນຄ້າ</small>
                </div>
            </div>

            <hr class="divider my-2">

            <!-- ສະແດງເລກທີອໍເດີ -->
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold small">ເລກທີຄຳສັ່ງ</span>
                <span class="badge bg-coffee-light text-coffee fs-6" id="order-number-display">
                    <i class="fas fa-hashtag me-1"></i> <span id="current-order-number"><?= $next_order_number ?></span>
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold">ລວມທັງໝົດ</span>
                <span class="fs-4 fw-bold text-coffee" id="total-cart">0 ₭</span>
            </div>

            <form method="POST" id="order-form" action="handlers/save_order.php">
                <input type="hidden" name="order_number" value="<?= $next_order_number ?>">
                <input type="hidden" name="cart_data" id="cart-data">
                <input type="hidden" name="customer_id" value="1">

                <!-- ✅ ຕົວເລືອກ ປະເພດການສັ່ງຊື້ (ເພີ່ມຄືນມາ ເພາະຫາຍໄປໃນຮອບກ່ອນ ເຮັດໃຫ້ JS ຫາ input[name="order_type"]:checked ບໍ່ເຈິ) -->
                <div class="mb-3">
                    <div class="d-flex gap-2 justify-content-center">
                        <input type="radio" class="btn-check" name="order_type" id="type_dinein" value="dine_in" checked autocomplete="off">
                        <label class="btn order-type-card flex-fill d-flex flex-column align-items-center justify-content-center" for="type_dinein">
                            <i class="fas fa-utensils text-primary"></i>
                        </label>
                    </div>
                </div>

                <!-- ຕົວເລືອກ ຊຳລະເງິນ -->
                <div class="mb-3">
                    <label class="fw-bold mb-2 d-block small">ຊຳລະເງິນ</label>
                    <div class="payment-methods-grid d-flex gap-2 justify-content-center">
                        <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" autocomplete="off" checked>
                        <label class="btn payment-card flex-fill d-flex flex-column align-items-center justify-content-center" for="pay_cash">
                            <i class="fas fa-money-bill-wave text-success"></i>
                            <span>ເງິນສົດ</span>
                        </label>

                        <input type="radio" class="btn-check" name="payment_method" id="pay_transfer" value="transfer" autocomplete="off">
                        <label class="btn payment-card flex-fill d-flex flex-column align-items-center justify-content-center" for="pay_transfer">
                            <i class="fas fa-university text-primary"></i>
                            <span>ໂອນເງິນ</span>
                        </label>
                    </div>
                </div>

                <!-- Numpad & Cash Input Container -->
                <div class="mb-3" id="cash-given-container">
                    <div class="cash-display-box mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Cash:</span>
                            <span class="fw-bold fs-5" id="display-cash-given">0 ₭</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Change:</span>
                            <span class="fw-bold fs-6 text-success" id="display-change-amount">0 ₭</span>
                        </div>
                    </div>
                    <input type="hidden" id="cash-given" name="cash_given" value="0">

                    <!-- Touch Numpad Grid -->
                    <div class="numpad-grid">
                        <button type="button" class="btn btn-numpad numkey" data-val="1">1</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="2">2</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="3">3</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="4">4</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="5">5</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="6">6</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="7">7</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="8">8</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="9">9</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="000">000</button>
                        <button type="button" class="btn btn-numpad numkey" data-val="0">0</button>
                        <button type="button" class="btn btn-numpad btn-numpad-backspace" id="num-backspace"><i class="fas fa-backspace"></i></button>
                    </div>
                </div>

                <button type="button" class="btn-pos w-100" id="save-order-btn">
                    <i class="fas fa-check-circle"></i> ບັນທຶກຄຳສັ່ງຊື້
                </button>

                <button type="button" class="btn-pos-outline w-100 mt-2" id="clear-cart-btn">
                    <i class="fas fa-trash"></i> ລ້າງກະຕ່າ
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade modal-pos" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 border-bottom-0">
                <h6 class="modal-title fw-bold text-muted"><i class="fas fa-receipt me-1"></i> ໃບຮັບເງິນ / Receipt</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-3 receipt-print" id="receipt-content"></div>
            <div class="modal-body p-3 d-none" id="kitchen-ticket-content"></div>
            <div class="modal-body p-3 d-none" id="bar-ticket-content"></div>

            <div class="modal-footer border-top-0 pt-0 d-flex flex-column gap-1">
                <button type="button" class="btn btn-sm btn-dark w-100" id="print-receipt-btn"><i class="fas fa-print me-1"></i> ພິມໃບຮັບເງິນ ,ພິມໃບບິນບາຮ໌ (Bar)</button>
                <button type="button" class="btn btn-sm btn-light w-100 mt-1" data-bs-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = [];
    let lastSavedCart = [];
    let lastOrderNo = '';
    let lastOrderType = 'dine_in';
    let currentCategory = 'all';
    let rawCashGiven = '';

    const productGrid = document.querySelectorAll('.add-to-cart');
    const cartContainer = document.getElementById('cart-items');
    const totalSpan = document.getElementById('total-cart');
    const itemCountSpan = document.getElementById('item-count');
    const cartDataInput = document.getElementById('cart-data');
    const searchInput = document.getElementById('searchProduct');
    const clearBtn = document.getElementById('clear-cart-btn');
    const saveBtn = document.getElementById('save-order-btn');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const cashGivenContainer = document.getElementById('cash-given-container');
    const cashGivenInput = document.getElementById('cash-given');
    const displayCashGiven = document.getElementById('display-cash-given');
    const displayChangeAmount = document.getElementById('display-change-amount');
    const categoryBtns = document.querySelectorAll('.category-btn');

    // 1. Numpad Logic
    document.querySelectorAll('.numkey').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.val;
            if (rawCashGiven === '' && (val === '0' || val === '000')) return;
            rawCashGiven += val;
            updateNumpadDisplay();
        });
    });

    document.getElementById('num-backspace').addEventListener('click', function() {
        rawCashGiven = rawCashGiven.slice(0, -1);
        updateNumpadDisplay();
    });

    function updateNumpadDisplay() {
        const cash = parseInt(rawCashGiven) || 0;
        const total = getCartTotal();

        cashGivenInput.value = cash;
        displayCashGiven.textContent = `${formatPrice(cash)} ₭`;

        if (cash >= total && total > 0) {
            displayChangeAmount.className = 'fw-bold fs-6 text-success';
            displayChangeAmount.textContent = `${formatPrice(cash - total)} ₭`;
        } else if (cash > 0 && cash < total) {
            displayChangeAmount.className = 'fw-bold fs-6 text-danger';
            displayChangeAmount.textContent = 'ເງິນບໍ່ພໍ!';
        } else {
            displayChangeAmount.className = 'fw-bold fs-6 text-muted';
            displayChangeAmount.textContent = '0 ₭';
        }
    }

    function resetNumpad() {
        rawCashGiven = '';
        updateNumpadDisplay();
    }

    function getCartTotal() {
        return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    }

    // 2. ຟອງຊັນການກັ່ນຕອງສິນຄ້າ
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase().trim();

        document.querySelectorAll('.product-item').forEach(el => {
            const matchesCategory = (currentCategory === 'all' || el.dataset.category === currentCategory);
            const matchesSearch = el.dataset.name.includes(searchTerm);

            if (matchesCategory && matchesSearch) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            filterProducts();
        });
    });

    searchInput.addEventListener('input', filterProducts);

    // 3. ເພີ່ມສິນຄ້າໃສ່ກະຕ່າ
    productGrid.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseInt(this.dataset.price);
            const stock = parseInt(this.dataset.stock);

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                if (existingItem.qty < stock) existingItem.qty++;
                else { alert('ສິນຄ້າບໍ່ພຽງພໍໃນສຕັອກ!'); return; }
            } else {
                cart.push({ id, name, price, qty: 1, stock });
            }
            updateCartUI();
        });
    });

    // 4. ອັບເດດ UI ກະຕ່າ
    function updateCartUI() {
        if (cart.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-cart-plus fa-2x mb-2 d-block" style="color: #ddd;"></i>
                    <small>ຍັງບໍ່ມີສິນຄ້າ</small>
                </div>
            `;
            totalSpan.textContent = '0 ₭';
            itemCountSpan.textContent = '0 ລາຍການ';
            cartDataInput.value = '';
            resetNumpad();
            return;
        }

        let html = '';
        let total = 0;
        let totalItems = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.qty;
            total += itemTotal;
            totalItems += item.qty;

            html += `
                <div class="cart-item d-flex justify-content-between align-items-center bg-white p-2 mb-2 rounded border">
                    <div>
                        <div class="fw-bold small">${item.name}</div>
                        <div class="small text-muted">${formatPrice(item.price)} ₭ x ${item.qty}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-coffee me-2">${formatPrice(itemTotal)} ₭</span>
                        <div class="cart-item-actions d-flex gap-1">
                            <button class="btn btn-sm btn-coffee-light qty-change" data-index="${index}" data-action="minus"><i class="fas fa-minus"></i></button>
                            <button class="btn btn-sm btn-coffee-light qty-change" data-index="${index}" data-action="plus"><i class="fas fa-plus"></i></button>
                            <button class="btn btn-sm btn-outline-danger remove-item" data-index="${index}"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                </div>
            `;
        });

        cartContainer.innerHTML = html;
        totalSpan.textContent = `${formatPrice(total)} ₭`;
        itemCountSpan.textContent = `${totalItems} ລາຍການ`;
        cartDataInput.value = JSON.stringify(cart);
        updateNumpadDisplay();

        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                cart.splice(parseInt(this.dataset.index), 1);
                updateCartUI();
            });
        });

        document.querySelectorAll('.qty-change').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                if (this.dataset.action === 'plus' && cart[idx].qty < cart[idx].stock) {
                    cart[idx].qty++;
                } else if (this.dataset.action === 'minus' && cart[idx].qty > 1) {
                    cart[idx].qty--;
                } else {
                    return;
                }
                updateCartUI();
            });
        });
    }

    // 5. ລ້າງກະຕ່າ
    clearBtn.addEventListener('click', function() {
        if(cart.length > 0 && confirm('ທ່ານແນ່ໃຈບໍວ່າຕ້ອງການລ້າງກະຕ່າທັງໝົດ?')) {
            cart = [];
            updateCartUI();
        }
    });

    // 6. ເລືອກຊຳລະເງິນ
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            cashGivenContainer.style.display = (this.value === 'cash') ? 'block' : 'none';
        });
    });

    // 7. ສົ່ງຟອມບັນທຶກ
    saveBtn.addEventListener('click', function(e) {
        if(cart.length === 0) {
            alert('ກະລຸນາເລືອກສິນຄ້າກ່ອນ'); return;
        }

        const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
        const total = getCartTotal();
        const cashGiven = parseInt(cashGivenInput.value) || 0;

        if (selectedPayment === 'cash' && cashGiven < total) {
            alert('ຈຳນວນເງິນສົດທີ່ຮັບມາບໍ່ພໍກັບຍອດລວມ!');
            return;
        }

        const form = document.getElementById('order-form');
        const formData = new FormData(form);
        formData.append('save_order', '1');

        const selectedType = document.querySelector('input[name="order_type"]:checked').value;

        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ກຳລັງບັນທຶກ...';
        saveBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Server response error:', text);
                throw new Error('Server returned non-JSON response');
            }

            saveBtn.innerHTML = '<i class="fas fa-check-circle"></i> ບັນທຶກຄຳສັ່ງຊື້';
            saveBtn.disabled = false;

            if(data.status === 'success') {
                lastSavedCart = [...cart];
                lastOrderNo = data.new_order_number;
                lastOrderType = selectedType;

                cart = [];
                updateCartUI();
                
                document.getElementById('receipt-content').innerHTML = data.receipt_html;
                generateKitchenTicket(lastOrderNo, lastSavedCart, lastOrderType);
                generateBarTicket(lastOrderNo, lastSavedCart, lastOrderType);

                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
                
                document.getElementById('current-order-number').textContent = data.new_order_number;
                document.querySelector('input[name="order_number"]').value = data.new_order_number;
                resetNumpad();
            } else {
                alert('ເກີດຂໍ້ຜິດພາດ: ' + data.message);
            }
        })
        .catch(error => {
            saveBtn.innerHTML = '<i class="fas fa-check-circle"></i> ບັນທຶກຄຳສັ່ງຊື້';
            saveBtn.disabled = false;
            alert('ເກີດຂໍ້ຜິດພາດ: ' + error.message);
        });
    });

    function getOrderTypeLabel(type) {
        switch(type) {
            case 'takeaway': return 'Takeaway (ຫໍ່ກັບບ້ານ)';
            case 'bar': return 'Bar (ບາຮ໌ / ເຄື່ອງດື່ມ)';
            default: return 'Dine-in (ທານຢູ່ຮ້ານ)';
        }
    }

    function generateKitchenTicket(orderNo, items, orderType) {
        const now = new Date();
        const timeStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        
        let itemsHtml = '';
        items.forEach(it => {
            itemsHtml += `
                <tr>
                    <td style="padding: 6px 0; font-size: 16px; font-weight: bold; border-bottom: 1px dashed #ccc;">
                        ${it.qty} x ${it.name}
                    </td>
                </tr>
            `;
        });

        document.getElementById('kitchen-ticket-content').innerHTML = `
            <div style="font-family: 'Courier New', Courier, monospace, Arial; color: #000; width: 100%; max-width: 280px; margin: 0 auto; line-height: 1.3;">
                <div style="text-align: center; margin-bottom: 8px;">
                    <div style="font-size: 20px; font-weight: bold; border: 2px solid #000; padding: 4px; display: inline-block;">ໃບບິນຄົວ / KITCHEN</div>
                </div>
                <div style="border-top: 1px solid #000; margin: 6px 0;"></div>
                <div style="font-size: 16px; font-weight: bold;">Order No. #${orderNo.slice(-5)}</div>
                <div style="font-size: 14px; font-weight: bold; color: #d9534f; margin-top: 2px;">ປະເພດ: ${getOrderTypeLabel(orderType)}</div>
                <div style="font-size: 11px; margin-top: 4px;">ເວລາ: ${timeStr}</div>
                <div style="border-top: 1px solid #000; margin: 6px 0;"></div>
                <table style="width: 100%; border-collapse: collapse;">${itemsHtml}</table>
                <div style="border-top: 1px solid #000; margin: 10px 0 6px 0;"></div>
                <div style="text-align: center; font-size: 12px; font-weight: bold;">--- ຈົບລາຍການ ---</div>
            </div>
        `;
    }

    function generateBarTicket(orderNo, items, orderType) {
        const now = new Date();
        const timeStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        
        let itemsHtml = '';
        items.forEach(it => {
            itemsHtml += `
                <tr>
                    <td style="padding: 6px 0; font-size: 16px; font-weight: bold; border-bottom: 1px dashed #ccc;">
                        ${it.qty} x ${it.name}
                    </td>
                </tr>
            `;
        });

        document.getElementById('bar-ticket-content').innerHTML = `
            <div style="font-family: 'Courier New', Courier, monospace, Arial; color: #000; width: 100%; max-width: 280px; margin: 0 auto; line-height: 1.3;">
                <div style="text-align: center; margin-bottom: 8px;">
                    <div style="font-size: 20px; font-weight: bold; border: 2px double #000; padding: 4px; display: inline-block;">ໃບບິນບາຮ໌ / BAR TICKET</div>
                </div>
                <div style="border-top: 1px solid #000; margin: 6px 0;"></div>
                <div style="font-size: 16px; font-weight: bold;">Order No. #${orderNo.slice(-5)}</div>
                <div style="font-size: 14px; font-weight: bold; color: #0275d8; margin-top: 2px;">ປະເພດ: ${getOrderTypeLabel(orderType)}</div>
                <div style="font-size: 11px; margin-top: 4px;">ເວລາ: ${timeStr}</div>
                <div style="border-top: 1px solid #000; margin: 6px 0;"></div>
                <table style="width: 100%; border-collapse: collapse;">${itemsHtml}</table>
                <div style="border-top: 1px solid #000; margin: 10px 0 6px 0;"></div>
                <div style="text-align: center; font-size: 12px; font-weight: bold;">--- ຈົບລາຍການ ---</div>
            </div>
        `;
    }

    document.getElementById('print-receipt-btn').addEventListener('click', function() {
        document.body.classList.remove('print-kitchen', 'print-bar');
        document.body.classList.add('print-receipt');
        window.print();
    });

    document.getElementById('print-kitchen-btn').addEventListener('click', function() {
        document.body.classList.remove('print-receipt', 'print-bar');
        document.body.classList.add('print-kitchen');
        window.print();
    });

    document.getElementById('print-bar-btn').addEventListener('click', function() {
        document.body.classList.remove('print-receipt', 'print-kitchen');
        document.body.classList.add('print-bar');
        window.print();
    });

    function formatPrice(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
});
</script>
