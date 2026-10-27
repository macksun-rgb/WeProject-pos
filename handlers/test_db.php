<?php
// ໄຟລ໌ທົດສອບຊົ່ວຄາວ - ວາງໄວ້ບ່ອນດຽວກັບ save_order.php ແລ້ວເປີດຜ່ານ browser ໂດຍກົງ
// ຕົວຢ່າງ: http://localhost/ຊື່ໂຟນເດີ/handlers/test_db.php

header('Content-Type: application/json; charset=utf-8');

$result = [];

// 1. ກວດ mysqli extension
$result['mysqli_extension_loaded'] = extension_loaded('mysqli') ? 'YES' : 'NO - ນີ້ຄືສາເຫດ! ຕ້ອງເປີດ extension=mysqli ໃນ php.ini';

// 2. ກວດ PHP version
$result['php_version'] = phpversion();

// 3. ລອງເຊື່ອມ DB ໂດຍກົງ
if (extension_loaded('mysqli')) {
    try {
        $conn = new mysqli('localhost', 'root', '', 'katunyu_coffee');
        if ($conn->connect_error) {
            $result['db_connect'] = 'FAILED: ' . $conn->connect_error;
        } else {
            $result['db_connect'] = 'SUCCESS';
            $result['db_charset_set'] = $conn->set_charset('utf8mb4') ? 'OK' : 'FAILED';

            // ກວດວ່າ table orders/order_items/products ມີແທ້ບໍ
            foreach (['orders', 'order_items', 'products'] as $t) {
                $chk = $conn->query("SHOW TABLES LIKE '{$t}'");
                $result["table_{$t}_exists"] = ($chk && $chk->num_rows > 0) ? 'YES' : 'NO - ຂາດ table ນີ້!';
            }

            // ຖ້າມີ table orders, ໂຊວ໌ column ຕົວຈິງ
            $cols = $conn->query("SHOW COLUMNS FROM orders");
            if ($cols) {
                $colNames = [];
                while ($row = $cols->fetch_assoc()) $colNames[] = $row['Field'];
                $result['orders_columns'] = $colNames;
            }
        }
    } catch (\Throwable $e) {
        $result['db_connect'] = 'EXCEPTION: ' . $e->getMessage();
    }
}

// 4. ກວດ current path (ເພື່ອກວດ routing)
$result['current_script_path'] = __FILE__;
$result['current_dir'] = __DIR__;

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

