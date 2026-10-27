<?php
/**
 * pages/dashboard.php
 * Overview stats + Daily Close Shift feature + recent orders.
 */

// -------------------------------------------------------------
// A. ຈັດການການປິດ Shift ເມື່ອມີການກົດ Confirm (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_shift') {
    // ອັບເດດ Shift ປັດຈຸບັນໃຫ້ເປັນ closed
    $stmt = $conn->prepare("UPDATE shifts SET status = 'closed', closed_at = NOW() WHERE status = 'open'");
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }
    
    // ສ້າງ Shift ໃໝ່ (Open Shift ໃໝ່ອັດໂນມັດ ເພື່ອເລີ່ມຕົ້ນຍອດ 0)
    $stmt_new = $conn->prepare("INSERT INTO shifts (opened_at, status) VALUES (NOW(), 'open')");
    if ($stmt_new) {
        $stmt_new->execute();
        $stmt_new->close();
    }

    // Refresh ໜ້າ Dashboard ຫຼັງຈາກປິດ Shift
    echo "<script>window.location.href='?page=dashboard';</script>";
    exit;
}

// -------------------------------------------------------------
// B. ກວດຫາ Shift ປັດຈຸບັນທີ່ກຳລັງ Open ຢູ່
// -------------------------------------------------------------
$current_shift = $conn->query("SELECT * FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1")->fetch_assoc();

// ຖ້າຫາກຍັງບໍ່ມີ Shift ຢູ່ໃນລະບົບ ເລີ່ມສ້າງ Shift ທຳອິດ
if (!$current_shift) {
    $conn->query("INSERT INTO shifts (opened_at, status) VALUES (NOW(), 'open')");
    $current_shift = $conn->query("SELECT * FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1")->fetch_assoc();
}

$shift_start_time = $current_shift['opened_at'];

// -------------------------------------------------------------
// C. ດຶງຂໍ້ມູນສະຖິຕິ (ຄິດໄລ່ສະເພາະ Shift ປັດຈຸບັນ $shift_start_time ເປັນຕົ້ນມາ)
// -------------------------------------------------------------
$stats = [
    'total_orders'    => $conn->query("SELECT COUNT(*) AS t FROM orders")->fetch_assoc()['t'] ?? 0,
    'total_revenue'   => $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE status != 'cancelled'")->fetch_assoc()['t'] ?? 0,
    'total_products'  => $conn->query("SELECT COUNT(*) AS t FROM products")->fetch_assoc()['t'] ?? 0,
    'total_employees' => $conn->query("SELECT COUNT(*) AS t FROM employees WHERE status='active'")->fetch_assoc()['t'] ?? 0,
    
    // ຍອດຂາຍ Shift ປັດຈຸບັນ (ຖ້າປິດ Shift ແລ້ວ ຍອດຈະເລີ່ມນັບ 0 ໃໝ່ທັນທີ)
    'today_orders'    => $conn->query("SELECT COUNT(*) AS t FROM orders WHERE created_at >= '$shift_start_time' AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0,
    'today_revenue'   => $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE created_at >= '$shift_start_time' AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0,
    
    'month_orders'    => $conn->query("SELECT COUNT(*) AS t FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0,
    'month_revenue'   => $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0,
];

// -------------------------------------------------------------
// D. ດຶງຂໍ້ມູນສະຫຼຸບປິດຍອດ Shift ປັດຈຸບັນ (Daily Shift Summary)
// -------------------------------------------------------------
$shift_finished_orders = $conn->query("SELECT COUNT(*) AS t FROM orders WHERE created_at >= '$shift_start_time' AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0;
$shift_unfinished_orders = $conn->query("SELECT COUNT(*) AS t FROM orders WHERE created_at >= '$shift_start_time' AND status = 'pending'")->fetch_assoc()['t'] ?? 0;

$shift_cash = $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE created_at >= '$shift_start_time' AND payment_method = 'cash' AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0;
$shift_onepay = $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE created_at >= '$shift_start_time' AND (payment_method = 'transfer' OR payment_method = 'bcel') AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0;
$shift_qr = $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE created_at >= '$shift_start_time' AND payment_method = 'qr' AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0;
$shift_other = $conn->query("SELECT SUM(total_amount) AS t FROM orders WHERE created_at >= '$shift_start_time' AND payment_method NOT IN ('cash', 'transfer', 'bcel', 'qr') AND status != 'cancelled'")->fetch_assoc()['t'] ?? 0;

$shift_total_sales = $shift_cash + $shift_onepay + $shift_qr + $shift_other;
?>

<style>
/* Close Shift Modal Custom Styling */
.shift-modal-header {
    background-color: #3897c0;
    color: #ffffff;
    border-top-left-radius: .5rem;
    border-top-right-radius: .5rem;
    padding: 12px 20px;
    text-align: center;
}

.shift-modal-header .modal-title {
    font-weight: 600;
    font-size: 1.2rem;
    width: 100%;
}

.shift-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.95rem;
    color: #333;
}

.shift-row-bold {
    font-weight: bold;
}

.shift-divider {
    border-top: 1px solid #e0e0e0;
    margin: 6px 0;
}

.text-unfinished {
    color: #d9534f;
    font-weight: bold;
}

.btn-shift-cancel {
    background-color: #4eb3d3;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 28px;
    font-weight: 500;
}

.btn-shift-confirm {
    background-color: #3897c0;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 28px;
    font-weight: 500;
}

.btn-shift-cancel:hover, .btn-shift-confirm:hover {
    opacity: 0.9;
    color: white;
}
</style>

<!-- Header & Close Shift Button -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">ແຜງຄວບຄຸມ (Dashboard)</h4>
        <small class="text-muted">ສະຫຼຸບລາຍງານ ແລະ ຍອດຂາຍປະຈຳ Shift ປັດຈຸບັນ (ເປີດ Shift: <?= date('d/m/Y H:i', strtotime($shift_start_time)) ?>)</small>
    </div>
    <button type="button" class="btn btn-danger btn-lg shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
        <i class="fas fa-lock me-2"></i> ປິດຍອດຂາຍປະຈຳວັນ (Close Shift)
    </button>
</div>

<div class="row g-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ອໍເດີທັງໝົດ</div>
                    <div class="stat-number"><?= number_format($stats['total_orders']) ?></div>
                </div>
                <div class="stat-icon bg-coffee-light text-coffee"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ລາຍໄດ້ລວມ</div>
                    <div class="stat-number text-success"><?= formatPrice($stats['total_revenue']) ?> ₭</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-coins"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ສິນຄ້າທັງໝົດ</div>
                    <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-box"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ພະນັກງານ</div>
                    <div class="stat-number"><?= number_format($stats['total_employees']) ?></div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ຍອດຂາຍ Shift ນີ້ (ປັດຈຸບັນ)</div>
                    <div class="stat-number text-danger"><?= formatPrice($stats['today_revenue']) ?> ₭</div>
                    <div class="mt-1">
                        <span class="badge-pos bg-coffee-light text-coffee"><i class="fas fa-receipt"></i> <?= $stats['today_orders'] ?> ລາຍການ</span>
                    </div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">ຍອດຂາຍເດືອນນີ້</div>
                    <div class="stat-number text-primary"><?= formatPrice($stats['month_revenue']) ?> ₭</div>
                    <div class="mt-1">
                        <span class="badge-pos bg-coffee-light text-coffee"><i class="fas fa-receipt"></i> <?= $stats['month_orders'] ?> ລາຍການ</span>
                    </div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-12">
        <div class="card-glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-clock text-coffee me-2"></i> ຄຳສັ່ງຊື້ຫຼ້າສຸດ</h5>
                <a href="?page=orders" class="btn-pos-light btn-sm">ເບິ່ງທັງໝົດ <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-pos">
                    <thead>
                        <tr><th>ເລກທີ</th><th>ລູກຄ້າ</th><th>ວັນທີ</th><th class="text-end">ຍອດລວມ</th><th>ສະຖານະ</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $recent = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                    if ($recent && $recent->num_rows > 0):
                        while ($row = $recent->fetch_assoc()):
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                            <td>Walk-in</td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td class="text-end fw-bold text-danger"><?= formatPrice($row['total_amount']) ?> ₭</td>
                            <td><?= getStatusBadge('completed') ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ບໍ່ມີຄຳສັ່ງຊື້</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Close Shift Modal -->
<div class="modal fade" id="closeShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <form method="POST" id="closeShiftForm">
                <input type="hidden" name="action" value="close_shift">
                
                <div class="shift-modal-header">
                    <h5 class="modal-title mb-0">Shift <?= $current_shift['id'] ?> (ສະຫຼຸບຍອດຂາຍ)</h5>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="shift-row">
                        <span>Finished Orders</span>
                        <span><?= number_format($shift_finished_orders) ?></span>
                    </div>
                    <div class="shift-row">
                        <span>Unfinish Orders</span>
                        <span class="text-unfinished"><?= number_format($shift_unfinished_orders) ?></span>
                    </div>

                    <div class="shift-divider"></div>

                    <div class="shift-row">
                        <span>Open Shift</span>
                        <span>0.00</span>
                    </div>
                    <div class="shift-row shift-row-bold">
                        <span>End Shift</span>
                        <span><?= number_format($shift_total_sales) ?></span>
                    </div>

                    <div class="shift-divider"></div>

                    <div class="shift-row">
                        <span>Cash (ເງິນສົດ)</span>
                        <span><?= number_format($shift_cash, 2) ?></span>
                    </div>
                    <div class="shift-row">
                        <span>BCEL Onepay (ໂອນ)</span>
                        <span><?= number_format($shift_onepay, 2) ?></span>
                    </div>
                    <div class="shift-row">
                        <span>QR Pay</span>
                        <span><?= number_format($shift_qr, 2) ?></span>
                    </div>
                    <div class="shift-row">
                        <span>Credit card</span>
                        <span>0.00</span>
                    </div>
                    <div class="shift-row">
                        <span>Foodpanda</span>
                        <span>0.00</span>
                    </div>
                    <div class="shift-row">
                        <span>Voucher</span>
                        <span>0.00</span>
                    </div>
                    <div class="shift-row">
                        <span>Other (ອື່ນໆ)</span>
                        <span><?= number_format($shift_other, 2) ?></span>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-shift-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-shift-confirm" onclick="printAndSubmit(event)">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function printAndSubmit(e) {
    e.preventDefault();
    // 1. ສັ່ງພິມລາຍງານ
    window.print();
    // 2. ສົ່ງ Form ເພື່ອປິດ Shift ໃນ Database
    document.getElementById('closeShiftForm').submit();
}
</script>