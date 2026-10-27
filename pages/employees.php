<?php
/**
 * pages/employees.php
 * Employee list + add/edit modals.
 * Expects $conn to already be set by index.php.
 * Requires get_employee.php (AJAX endpoint) for the edit modal to work.
 */
$employees = $conn->query("SELECT * FROM employees ORDER BY id");
?>
<div class="card-glass p-4">
    <div class="table-responsive">
        <table class="table table-pos">
            <thead>
                <tr><th>ID</th><th>ຊື່ຜູ້ໃຊ້</th><th>ຊື່-ນາມສະກຸນ</th><th>ບົດບາດ</th><th>ສະຖານະ</th><th class="text-center">ຈັດການ</th></tr>
            </thead>
            <tbody>
            <?php if ($employees && $employees->num_rows > 0): ?>
                <?php while ($row = $employees->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td>
                        <?php if ($row['role'] == 'admin'): ?>
                            <span class="badge-pos bg-danger text-white"><i class="fas fa-crown me-1"></i> ຜູ້ດູແລ</span>
                        <?php else: ?>
                            <span class="badge-pos bg-coffee-light text-coffee"><i class="fas fa-user me-1"></i> ພະນັກງານ</span>
                        <?php endif; ?>
                    </td>
                    <td><?= getStatusBadge($row['status']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-pos-light me-1" onclick="editEmployee(<?= $row['id'] ?>)"><i class="fas fa-edit"></i></button>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-pos-light text-danger" onclick="deleteEmployee(<?= $row['id'] ?>)"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <span class="text-muted small">(ບັນຊີປັດຈຸບັນ)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">ຍັງບໍ່ມີພະນັກງານ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade modal-pos" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus text-coffee me-2"></i> ເພີ່ມພະນັກງານໃໝ່</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ຜູ້ໃຊ້</label>
                        <input type="text" name="username" class="form-pos w-100" placeholder="ຊື່ຜູ້ໃຊ້" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ລະຫັດຜ່ານ</label>
                        <input type="password" name="password" class="form-pos w-100" placeholder="ລະຫັດຜ່ານ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່-ນາມສະກຸນ</label>
                        <input type="text" name="fullname" class="form-pos w-100" placeholder="ຊື່-ນາມສະກຸນ" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ບົດບາດ</label>
                            <select name="role" class="form-pos-select w-100">
                                <option value="staff">ພະນັກງານຂາຍ</option>
                                <option value="admin">ຜູ້ດູແລລະບົບ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ສະຖານະ</label>
                            <select name="status" class="form-pos-select w-100">
                                <option value="active">ປະຕິບັດງານ</option>
                                <option value="inactive">ບໍ່ເຄື່ອນໄຫວ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" name="add_employee" class="btn-pos">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade modal-pos" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit text-coffee me-2"></i> ແກ້ໄຂພະນັກງານ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່-ນາມສະກຸນ</label>
                        <input type="text" name="fullname" id="edit_fullname" class="form-pos w-100" placeholder="ຊື່-ນາມສະກຸນ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ລະຫັດຜ່ານໃໝ່</label>
                        <input type="password" name="password" class="form-pos w-100" placeholder="ປ່ອຍຫວ່າງຖ້າບໍ່ຕ້ອງການປ່ຽນ">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ບົດບາດ</label>
                            <select name="role" id="edit_role" class="form-pos-select w-100">
                                <option value="staff">ພະນັກງານຂາຍ</option>
                                <option value="admin">ຜູ້ດູແລລະບົບ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ສະຖານະ</label>
                            <select name="status" id="edit_status" class="form-pos-select w-100">
                                <option value="active">ປະຕິບັດງານ</option>
                                <option value="inactive">ບໍ່ເຄື່ອນໄຫວ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pos-light" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" name="edit_employee" class="btn-pos">ອັບເດດ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteEmployee(id) {
    if (confirm('ຕ້ອງການລຶບພະນັກງານນີ້?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="delete_id" value="${id}"><input type="hidden" name="delete_employee">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function editEmployee(id) {
    fetch(`get_employee.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_employee_id').value = data.id;
                document.getElementById('edit_fullname').value = data.fullname;
                document.getElementById('edit_role').value = data.role;
                document.getElementById('edit_status').value = data.status;
                new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
            } else {
                showToast('ບໍ່ສາມາດດຶງຂໍ້ມູນ', 'danger');
            }
        });
}
</script>
