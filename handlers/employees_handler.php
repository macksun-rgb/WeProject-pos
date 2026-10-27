<?php
/**
 * handlers/employees_handler.php
 * Handles all POST actions for the "employees" page.
 * Included by index.php only when $page == 'employees'.
 */

if (isset($_POST['add_employee'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']);
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $role = $_POST['role'] == 'admin' ? 'admin' : 'staff';
    $status = $_POST['status'] ?? 'active';
    $conn->query("INSERT INTO employees (username, password, fullname, role, status) VALUES ('$username', '$password', '$fullname', '$role', '$status')");
    setFlashMessage('success', 'ເພີ່ມພະນັກງານສຳເລັດ');
    redirect('?page=employees');
}

if (isset($_POST['edit_employee'])) {
    $id = (int)$_POST['employee_id'];
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $role = $_POST['role'] == 'admin' ? 'admin' : 'staff';
    $status = $_POST['status'] ?? 'active';

    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $conn->query("UPDATE employees SET fullname='$fullname', role='$role', status='$status', password='$password' WHERE id=$id");
    } else {
        $conn->query("UPDATE employees SET fullname='$fullname', role='$role', status='$status' WHERE id=$id");
    }
    setFlashMessage('success', 'ອັບເດດພະນັກງານສຳເລັດ');
    redirect('?page=employees');
}

if (isset($_POST['delete_employee'])) {
    $id = (int)$_POST['delete_id'];
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM employees WHERE id = $id");
        setFlashMessage('success', 'ລຶບພະນັກງານສຳເລັດ');
    } else {
        setFlashMessage('danger', 'ບໍ່ສາມາດລຶບບັນຊີຂອງທ່ານເອງໄດ້');
    }
    redirect('?page=employees');
}
