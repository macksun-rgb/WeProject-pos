<?php
/**
 * pages/settings.php
 * Shop info form + system info panel.
 * Expects $conn to already be set by index.php.
 * POST handling lives in handlers/settings_handler.php.
 */
$settings = [];
$result = $conn->query("SELECT key_name, value FROM settings WHERE key_name IN ('shop_name', 'address', 'phone', 'tax_id')");
while ($row = $result->fetch_assoc()) {
    $settings[$row['key_name']] = $row['value'];
}
?>
<div class="row g-4">
    <div class="col-md-7">
        <div class="card-glass p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-store text-coffee me-2"></i> ຂໍ້ມູນຮ້ານ</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">ຊື່ຮ້ານ</label>
                    <input type="text" name="shop_name" class="form-pos w-100" value="<?= htmlspecialchars($settings['shop_name'] ?? 'KaTunYu Coffee') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">ທີ່ຢູ່</label>
                    <textarea name="address" class="form-pos w-100" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ເບີໂທ</label>
                        <input type="text" name="phone" class="form-pos w-100" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ເລກທີ່ເສຍພາສີ</label>
                        <input type="text" name="tax_id" class="form-pos w-100" value="<?= htmlspecialchars($settings['tax_id'] ?? '') ?>">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="update_settings" class="btn-pos"><i class="fas fa-save"></i> ບັນທຶກຂໍ້ມູນ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card-glass p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-info-circle text-coffee me-2"></i> ຂໍ້ມູນລະບົບ</h5>
            <table class="table table-borderless">
                <tr><td class="fw-bold">ສະບັບລະບົບ</td><td class="text-end">v2.0.1</td></tr>
                <tr><td class="fw-bold">PHP Version</td><td class="text-end"><?= phpversion() ?></td></tr>
                <tr><td class="fw-bold">MySQL Version</td><td class="text-end"><?= $conn->server_info ?></td></tr>
                <tr><td class="fw-bold">ສິນຄ້າທັງໝົດ</td><td class="text-end"><?= $conn->query("SELECT COUNT(*) AS t FROM products")->fetch_assoc()['t'] ?? 0 ?></td></tr>
                <tr><td class="fw-bold">ຄຳສັ່ງຊື້ທັງໝົດ</td><td class="text-end"><?= $conn->query("SELECT COUNT(*) AS t FROM orders")->fetch_assoc()['t'] ?? 0 ?></td></tr>
                <tr><td class="fw-bold">ພະນັກງານ</td><td class="text-end"><?= $conn->query("SELECT COUNT(*) AS t FROM employees WHERE status='active'")->fetch_assoc()['t'] ?? 0 ?></td></tr>
            </table>
        </div>
    </div>
</div>
