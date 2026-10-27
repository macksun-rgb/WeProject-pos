<?php
/**
 * handlers/settings_handler.php
 * Handles all POST actions for the "settings" page.
 * Included by index.php only when $page == 'settings'.
 */

if (isset($_POST['update_settings'])) {
    $shop_name = $conn->real_escape_string($_POST['shop_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $tax_id = $conn->real_escape_string($_POST['tax_id']);

    $conn->query("INSERT INTO settings (key_name, value) VALUES
        ('shop_name', '$shop_name'),
        ('address', '$address'),
        ('phone', '$phone'),
        ('tax_id', '$tax_id')
        ON DUPLICATE KEY UPDATE value = VALUES(value)");

    setFlashMessage('success', 'ອັບເດດຂໍ້ມູນສຳເລັດ');
    redirect('?page=settings');
}
