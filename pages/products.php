<?php
/**
 * pages/products.php
 * Product list table + add/edit modals.
 * Expects $conn to already be set by index.php.
 * Requires get_product.php (AJAX endpoint) for the edit modal to work.
 */

// ===================== จัดการ POST (เพิ่ม / แก้ไข / ลบ) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // เพิ่มสินค้า
    if (isset($_POST['add_product'])) {
        $name       = trim($_POST['name']);
        $categoryId = ($_POST['category_id'] !== '') ? (int) $_POST['category_id'] : null;
        $price      = (float) $_POST['price'];
        $cost       = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float) $_POST['cost'] : 0;
        $stock      = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int) $_POST['stock'] : 0;
        $status     = $_POST['status'] ?? 'available';

        $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, cost, stock, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siddis", $name, $categoryId, $price, $cost, $stock, $status);
        $stmt->execute();
        $stmt->close();

        header("Location: ?page=products");
        exit;
    }

    // แก้ไขสินค้า
    if (isset($_POST['edit_product'])) {
        $id         = (int) $_POST['product_id'];
        $name       = trim($_POST['name']);
        $categoryId = ($_POST['category_id'] !== '') ? (int) $_POST['category_id'] : null;
        $price      = (float) $_POST['price'];
        $cost       = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float) $_POST['cost'] : 0;
        $stock      = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int) $_POST['stock'] : 0;
        $status     = $_POST['status'] ?? 'available';

        $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, cost=?, stock=?, status=? WHERE id=?");
        $stmt->bind_param("siddisi", $name, $categoryId, $price, $cost, $stock, $status, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: ?page=products");
        exit;
    }

    // ลบสินค้า
    if (isset($_POST['delete_product'])) {
        $id = (int) $_POST['delete_id'];

        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: ?page=products");
        exit;
    }
}

// ดึงรายการหมวดหมู่ สำหรับ dropdown
$categoryResults = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = [];
if ($categoryResults) {
    while ($category = $categoryResults->fetch_assoc()) {
        $categories[] = $category;
    }
}

// ดึงรายการสินค้า
$products = $conn->query(
    "SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC"
);
?>
<div class="card-glass p-4">
    <div class="table-responsive">
        <table class="table table-pos" id="productsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ສິນຄ້າ</th>
                    <th>ປະເພດ</th>
                    <th class="text-end">ລາຄາ</th>
                    <th>ສະຖານະ</th>
                    <th class="text-center">ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($row = $products->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                    <td><?= number_format($row['price']) ?> ₭</td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-pos-light me-1" onclick="editProduct(<?= $row['id'] ?>)"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-pos-light text-danger" onclick="deleteProduct(<?= $row['id'] ?>)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">ຍັງບໍ່ມີສິນຄ້າ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade modal-pos" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle text-coffee me-2"></i> ເພີ່ມສິນຄ້າໃໝ່</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=products">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ສິນຄ້າ</label>
                        <input name="name" class="form-pos w-100" placeholder="ຊື່ສິນຄ້າ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ປະເພດ</label>
                        <select name="category_id" class="form-pos-select w-100">
                            <option value="">ບໍ່ລະບຸ</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ລາຄາຂາຍ</label>
                            <input name="price" type="number" step="0.01" class="form-pos w-100" placeholder="ລາຄາຂາຍ" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ຕົ້ນທຶນ</label>
                            <input name="cost" type="number" step="0.01" class="form-pos w-100" placeholder="ຕົ້ນທຶນ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ສຕ໋ອກ</label>
                            <input name="stock" type="number" class="form-pos w-100" placeholder="ຈຳນວນ">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ສະຖານະ</label>
                            <select name="status" class="form-pos-select w-100">
                                <option value="available">ພ້ອມຂາຍ</option>
                                <option value="unavailable">ຢຸດຂາຍ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" name="add_product" class="btn-pos">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade modal-pos" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit text-coffee me-2"></i> ແກ້ໄຂສິນຄ້າ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=products">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ສິນຄ້າ</label>
                        <input name="name" id="edit_name" class="form-pos w-100" placeholder="ຊື່ສິນຄ້າ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ປະເພດ</label>
                        <select name="category_id" id="edit_category_id" class="form-pos-select w-100">
                            <option value="">ບໍ່ລະບຸ</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ລາຄາຂາຍ</label>
                            <input name="price" id="edit_price" type="number" step="0.01" class="form-pos w-100" placeholder="ລາຄາຂາຍ" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ຕົ້ນທຶນ</label>
                            <input name="cost" id="edit_cost" type="number" step="0.01" class="form-pos w-100" placeholder="ຕົ້ນທຶນ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ສຕ໋ອກ</label>
                            <input name="stock" id="edit_stock" type="number" class="form-pos w-100" placeholder="ຈຳນວນ">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ສະຖານະ</label>
                            <select name="status" id="edit_status" class="form-pos-select w-100">
                                <option value="available">ພ້ອມຂາຍ</option>
                                <option value="unavailable">ຢຸດຂາຍ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" name="edit_product" class="btn-pos">ອັບເດດ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteProduct(id) {
    if (confirm('ຕ້ອງການລຶບສິນຄ້ານີ້?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=products';
        form.innerHTML = `<input type="hidden" name="delete_id" value="${id}"><input type="hidden" name="delete_product" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => response.json())
        .then(data => { 
            if (data.success) {
                document.getElementById('edit_product_id').value = data.id;
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_category_id').value = data.category_id ?? '';
                document.getElementById('edit_price').value = data.price;
                document.getElementById('edit_cost').value = data.cost || 0;
                document.getElementById('edit_stock').value = data.stock || 0;
                document.getElementById('edit_status').value = data.status;
                new bootstrap.Modal(document.getElementById('editProductModal')).show();
            } else {
                alert('ບໍ່ສາມາດດຶງຂໍ້ມູນ: ' + (data.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('ເກີດຂໍ້ຜິດພາດໃນການດຶງຂໍ້ມູນ');
        });
}
</script>ห