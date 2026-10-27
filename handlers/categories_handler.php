<?php
/**
 * handlers/categories_handler.php
 * Handles all POST actions for the "categories" page.
 * Included by index.php only when $page == 'categories'.
 */

if (isset($_POST['add_category'])) {
    $name = $conn->real_escape_string($_POST['category_name']);
    $desc = $conn->real_escape_string($_POST['category_description']);
    $conn->query("INSERT INTO categories (name, description) VALUES ('$name', '$desc')");
    setFlashMessage('success', 'ເພີ່ມປະເພດສິນຄ້າສຳເລັດ');
    redirect('?page=categories');
}

if (isset($_POST['delete_category'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM categories WHERE id = $id");
    setFlashMessage('success', 'ລຶບປະເພດສິນຄ້າສຳເລັດ');
    redirect('?page=categories');
}
