<?php
/**
 * handlers/products_handler.php
 * Handles all POST actions for the "products" page.
 * Included by index.php only when $page == 'products'.
 */

if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $cat_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $price = (float)$_POST['price'];
    $cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float)$_POST['cost'] : 0.0;
    $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : 0;
    $status = $_POST['status'] ?? 'available';

    if ($cat_id === null) {
        $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, cost, stock, status) VALUES (?, NULL, ?, ?, ?, ?)");
        $stmt->bind_param('sddis', $name, $price, $cost, $stock, $status);
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, cost, stock, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('siddis', $name, $cat_id, $price, $cost, $stock, $status);
    }
    $stmt->execute();
    $stmt->close();

    setFlashMessage('success', 'ເພີ່ມສິນຄ້າສຳເລັດ');
    redirect('?page=products');
}

if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['product_id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $cat_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $price = (float)$_POST['price'];
    $cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float)$_POST['cost'] : 0.0;
    $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : 0;
    $status = $_POST['status'] ?? 'available';

    if ($cat_id === null) {
        $stmt = $conn->prepare("UPDATE products SET name=?, category_id=NULL, price=?, cost=?, stock=?, status=? WHERE id=?");
        $stmt->bind_param('sddisi', $name, $price, $cost, $stock, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, cost=?, stock=?, status=? WHERE id=?");
        $stmt->bind_param('siddisi', $name, $cat_id, $price, $cost, $stock, $status, $id);
    }
    $stmt->execute();
    $stmt->close();

    setFlashMessage('success', 'ອັບເດດສິນຄ້າສຳເລັດ');
    redirect('?page=products');
}

if (isset($_POST['delete_product'])) {
    $id = (int)$_POST['delete_id'];

    // Check whether this product already has sales recorded in order_items
    $check = $conn->query("SELECT COUNT(*) AS count FROM order_items WHERE product_id = $id");
    $row = $check->fetch_assoc();

    if ($row['count'] > 0) {
        // Already sold before -> just mark unavailable instead of deleting
        $conn->query("UPDATE products SET status = 'unavailable' WHERE id = $id");
        setFlashMessage('warning', 'ບໍ່ສາມາດລຶບສິນຄ້າທີ່ມີການຂາຍແລ້ວ ໄດ້ປ່ຽນສະຖານະເປັນ "ຢຸດຂາຍ" ແລ້ວ');
    } else {
        // Never sold -> safe to delete
        $conn->query("DELETE FROM products WHERE id = $id");
        setFlashMessage('success', 'ລຶບສິນຄ້າສຳເລັດ');
    }
    redirect('?page=products');
}
