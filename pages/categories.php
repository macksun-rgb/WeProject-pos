<?php
/**
 * pages/categories.php
 * Category list + add modal.
 * Expects $conn to already be set by index.php.
 */
$categories = $conn->query("SELECT * FROM categories ORDER BY id");
?>

<style>
/* ເພີ່ມ CSS ເພື່ອໃຫ້ກົງກັບສະຕາຍລ໌ POS */
.card-glass {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    padding: 25px;
}
.table-pos th {
    font-weight: 600;
    color: #8B5A2B;
    border-bottom: 2px solid #e9ecef;
}
.table-pos td {
    vertical-align: middle;
    padding: 12px 8px;
    border-bottom: 1px solid #f1f3f5;
}
.badge-pos {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}
.bg-coffee-light {
    background-color: #FDF0E0;
    color: #8B5A2B;
}
.text-coffee { color: #8B5A2B; }
.btn-pos {
    background-color: #8B5A2B;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    transition: all 0.2s;
}
.btn-pos:hover {
    background-color: #6e4522;
    color: #fff;
}
.btn-pos-light {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #333;
    padding: 8px 20px;
    border-radius: 10px;
    transition: all 0.2s;
}
.btn-pos-light:hover {
    background-color: #e9ecef;
    color: #8B5A2B;
}
</style>

<div class="card-glass p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
         
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-pos">
            <thead>
                <tr>
                    <th style="width:80px;">ID</th>
                    <th>ຊື່ປະເພດ</th>
                    <th>ຄຳອະທິບາຍ</th>
                    <th class="text-center" style="width:100px;">ສິນຄ້າ</th>
                    <th class="text-center" style="width:100px;">ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($categories && $categories->num_rows > 0): ?>
                <?php while ($row = $categories->fetch_assoc()): 
                    // ✅ ປິດການນັບຈຳນວນສິນຄ້າທີ່ເກີດ Error ໄວ້ກ່ອນ (ຖ້າຢາກໃຊ້ຕົວຈິງ ໃຫ້ກັບມາເພີ່ມຄໍລຳ category_id ໃສ່ຕາລາງ products ກ່ອນ)
                    $count = 0; 
                ?>
                <tr>
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td class="text-center">
                        <span class="badge-pos bg-coffee-light text-coffee"><?= $count ?></span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-pos-light text-danger" onclick="deleteCategory(<?= $row['id'] ?>)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ຍັງບໍ່ມີປະເພດສິນຄ້າ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade modal-pos" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle text-coffee me-2"></i> ເພີ່ມປະເພດໃໝ່</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ປະເພດ</label>
                        <input type="text" name="category_name" class="form-pos w-100" placeholder="ຊື່ປະເພດ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຄຳອະທິບາຍ</label>
                        <textarea name="category_description" class="form-pos w-100" rows="3" placeholder="ຄຳອະທິບາຍ"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" name="add_category" class="btn-pos">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    if (confirm('ຕ້ອງການລຶບປະເພດນີ້?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="delete_id" value="${id}"><input type="hidden" name="delete_category">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>