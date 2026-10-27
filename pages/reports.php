<?php
/**
 * pages/reports.php
 * Daily / monthly sales summary + top-selling products.
 * Expects $conn to already be set by index.php.
 */
// ✅ ປ່ຽນ order_date ເປັນ created_at ເພື່ອໃຫ້ກົງກັບຖານຂໍ້ມູນ
$daily = $conn->query("SELECT DATE(created_at) AS d, SUM(total_amount) AS total, COUNT(*) AS orders FROM orders GROUP BY d ORDER BY d DESC LIMIT 7");
$monthly = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total_amount) AS total, COUNT(*) AS orders FROM orders GROUP BY month ORDER BY month DESC LIMIT 12");

// ✅ ແກ້ SQL: ປ່ຽນ oi.quantity ເປັນ oi.qty ເພື່ອໃຫ້ກົງກັບຊື່ຄໍລຳໃນຖານຂໍ້ມູນ
$topProducts = $conn->query("
    SELECT
        p.id,
        p.name,
        SUM(oi.qty) AS total_qty,
        SUM(oi.qty * oi.price) AS total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id, p.name
    ORDER BY total_qty DESC
    LIMIT 10
");
?>
<div class="card-glass p-4">
    <ul class="nav tab-pos mb-4" id="reportTab">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#dailyReport"><i class="fas fa-calendar-day me-1"></i> ລາຍວັນ</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#monthlyReport"><i class="fas fa-calendar-alt me-1"></i> ລາຍເດືອນ</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#productReport"><i class="fas fa-trophy me-1"></i> ສິນຄ້າຂາຍດີ</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="dailyReport">
            <h5 class="fw-bold"><i class="fas fa-chart-line text-coffee me-2"></i> ສະຫຼຸບຍອດຂາຍລາຍວັນ (7 ວັນຫຼ້າສຸດ)</h5>
            <div class="table-responsive mt-3">
                <table class="table table-pos">
                    <thead>
                        <tr><th>ວັນທີ</th><th class="text-end">ຍອດຂາຍລວມ</th><th class="text-center">ຈຳນວນຄຳສັ່ງ</th><th class="text-end">ສະເລ່ຍ/ຄຳສັ່ງ</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($daily && $daily->num_rows > 0): ?>
                        <?php while ($r = $daily->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['d'])) ?></td>
                            <td class="text-end fw-bold text-coffee"><?= formatPrice($r['total']) ?> ₭</td>
                            <td class="text-center"><?= $r['orders'] ?></td>
                            <td class="text-end"><?= formatPrice($r['orders'] > 0 ? $r['total'] / $r['orders'] : 0) ?> ₭</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="monthlyReport">
            <h5 class="fw-bold"><i class="fas fa-chart-bar text-coffee me-2"></i> ສະຫຼຸບຍອດຂາຍລາຍເດືອນ (12 ເດືອນຫຼ້າສຸດ)</h5>
            <div class="table-responsive mt-3">
                <table class="table table-pos">
                    <thead>
                        <tr><th>ເດືອນ</th><th class="text-end">ຍອດຂາຍລວມ</th><th class="text-center">ຈຳນວນຄຳສັ່ງ</th><th class="text-end">ສະເລ່ຍ/ຄຳສັ່ງ</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($monthly && $monthly->num_rows > 0): ?>
                        <?php while ($r = $monthly->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['month'] ?></td>
                            <td class="text-end fw-bold text-coffee"><?= formatPrice($r['total']) ?> ₭</td>
                            <td class="text-center"><?= $r['orders'] ?></td>
                            <td class="text-end"><?= formatPrice($r['orders'] > 0 ? $r['total'] / $r['orders'] : 0) ?> ₭</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="productReport">
            <h5 class="fw-bold"><i class="fas fa-trophy text-coffee me-2"></i> ສິນຄ້າຂາຍດີ (Top 10)</h5>
            <div class="table-responsive mt-3">
                <table class="table table-pos">
                    <thead>
                        <tr><th>ລຳດັບ</th><th>ສິນຄ້າ</th><th class="text-center">ຈຳນວນທີ່ຂາຍ</th><th class="text-end">ລາຍໄດ້</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($topProducts && $topProducts->num_rows > 0): ?>
                        <?php $rank = 1; while ($r = $topProducts->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($rank == 1): ?>
                                    <span class="badge-pos bg-warning text-dark">🥇</span>
                                <?php elseif ($rank == 2): ?>
                                    <span class="badge-pos bg-secondary text-white">🥈</span>
                                <?php elseif ($rank == 3): ?>
                                    <span class="badge-pos bg-coffee-light text-coffee">🥉</span>
                                <?php else: ?>
                                    <span class="text-muted">#<?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td class="text-center"><strong><?= $r['total_qty'] ?></strong></td>
                            <td class="text-end fw-bold text-coffee"><?= formatPrice($r['total_revenue']) ?> ₭</td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>