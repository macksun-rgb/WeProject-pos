<?php
/**
 * pages/orders.php
 * Order history list, filterable by date range, plus order-detail modal.
 * Expects $conn to already be set by index.php.
 * Requires get_order_detail.php (AJAX endpoint) for the detail modal to work.
 */
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ✅ ແກ້ SQL: ປ່ຽນ o.order_date ເປັນ o.created_at ແລະ ຕັດການ JOIN ກັບ customers, employees ອອກກ່ອນ
$stmt = $conn->prepare("SELECT o.* FROM orders o WHERE DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at DESC");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$orders = $stmt->get_result();
?>
<div class="card-glass p-4">
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <input type="hidden" name="page" value="orders">
        <div class="col-md-4">
            <label class="form-label fw-bold">ວັນທີເລີ່ມຕົ້ນ</label>
            <input type="date" name="start_date" class="form-pos w-100" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">ວັນທີສິ້ນສຸດ</label>
            <input type="date" name="end_date" class="form-pos w-100" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn-pos"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
                <a href="?page=orders" class="btn-pos-light"><i class="fas fa-sync"></i> ຣີເຊັດ</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-pos" id="orders-table">
            <thead>
                <tr><th>ເລກທີ</th><th>ລູກຄ້າ</th><th>ພະນັກງານ</th><th>ວັນທີ</th><th class="text-end">ຍອດລວມ</th><th>ຊຳລະ</th><th class="text-center">ລາຍລະອຽດ</th></tr>
            </thead>
            <tbody>
            <?php if ($orders && $orders->num_rows > 0): ?>
                <?php while ($row = $orders->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                    <td>Walk-in</td> <!-- ✅ ປ່ຽນຊື່ລູກຄ້າເປັນ Walk-in ກ່ອນ ເພາະຍັງບໍ່ມີຕາລາງ customers -->
                    <td>N/A</td>    <!-- ✅ ປ່ຽນຊື່ພະນັກງານເປັນ N/A ກ່ອນ ເພາະຍັງບໍ່ມີຕາລາງ employees -->
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td class="text-end fw-bold text-coffee"><?= formatPrice($row['total_amount']) ?> ₭</td>
                    <td>
                        <span class="badge-pos <?= $row['payment_method'] == 'cash' ? 'bg-success text-white' : ($row['payment_method'] == 'qr' ? 'bg-info text-white' : 'bg-warning text-dark') ?>">
                            <?= $row['payment_method'] == 'cash' ? '💵 ເງິນສົດ' : ($row['payment_method'] == 'qr' ? '📱 QR' : '🏦 ໂອນເງິນ') ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-pos-light" onclick="viewOrder(<?= $row['id'] ?>)"><i class="fas fa-eye"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນຄຳສັ່ງຊື້</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade modal-pos" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice text-coffee me-2"></i> ລາຍລະອຽດຄຳສັ່ງຊື້</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order-detail-content"></div>
            <div class="modal-footer">
                <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(id) {
    fetch(`get_order_detail.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>ເລກທີ:</strong> ${data.order_number}<br>
                            <strong>ວັນທີ:</strong> ${data.order_date}
                        </div>
                        <div class="col-md-6">
                            <strong>ລູກຄ້າ:</strong> ${data.customer_name}<br>
                            <strong>ພະນັກງານ:</strong> ${data.emp_name}
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-pos">
                            <thead>
                                <tr><th>ສິນຄ້າ</th><th class="text-center">ຈຳນວນ</th><th class="text-end">ລາຄາ</th><th class="text-end">ລວມ</th></tr>
                            </thead>
                            <tbody>
                `;
                data.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${escapeHtml(item.product_name)}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatNumber(item.price)} ₭</td>
                            <td class="text-end">${formatNumber(item.subtotal)} ₭</td>
                        </tr>
                    `;
                });
                html += `
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">ລວມທັງໝົດ:</td>
                                    <td class="text-end text-coffee">${formatNumber(data.total)} ₭</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">ຊຳລະດ້ວຍ:</td>
                                    <td class="text-end">${data.payment_method}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                document.getElementById('order-detail-content').innerHTML = html;
                new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
            } else {
                showToast('ບໍ່ສາມາດດຶງຂໍ້ມູນ', 'danger');
            }
        });
}
</script>
້<