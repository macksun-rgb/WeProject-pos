<?php
// ============================================================
// save_order.php (ສະບັບແກ້ໄຂບັກສົມບູນ + ໃບຮັບເງິນແບບ Thermal)
// ============================================================

ini_set('display_errors', 0);   // ບໍ່ໃຫ້ PHP print error ອອກມາເປັນ HTML
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start(); // ເປີດ buffer ໄວ້ "ຄ້າງ" ຕະຫຼອດ script

header('Content-Type: application/json; charset=utf-8');
session_start();

// -------------------------------------------------------------
// ຕົວດັກ Fatal Error ສຸດທ້າຍ (shutdown handler)
// -------------------------------------------------------------
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) { ob_end_clean(); }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        error_log('save_order FATAL: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Server error: ' . $error['message']
        ]);
    }
});

try {
    // ຊອກຫາ db_connect.php ທັງໃນໂຟນເດີດຽວກັນ ແລະ ໃນ root ຂອງໂປຣເຈັກ
    $db_file = __DIR__ . '/db_connect.php';
    if (!file_exists($db_file)) {
        $db_file = __DIR__ . '/../db_connect.php';
    }
    if (!file_exists($db_file)) {
        throw new Exception('Missing db_connect.php (checked ' . __DIR__ . '/db_connect.php and ' . __DIR__ . '/../db_connect.php)');
    }
    require_once $db_file;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('DB connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_order'])) {
        throw new Exception('Invalid request');
    }

    $customer_id    = (int)($_POST['customer_id'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $cash_given     = is_numeric($_POST['cash_given'] ?? null) ? (float)$_POST['cash_given'] : 0.00;

    $cart_json = $_POST['cart_data'] ?? '';
    $cart = json_decode($cart_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($cart) || count($cart) === 0) {
        throw new Exception('Invalid or empty cart data');
    }

    $conn->begin_transaction();

    // ສ້າງເລກທີ order ໃໝ່ຢູ່ໃນ server ເອງ (ເພື່ອປ້ອງກັນ duplicate order_number)
    $today = date('Ymd');
    $numRes = $conn->query("SELECT order_number FROM orders WHERE order_number LIKE 'ORD-{$today}-%' ORDER BY id DESC LIMIT 1 FOR UPDATE");
    if ($numRes && $numRes->num_rows > 0) {
        $lastOrderNumber = $numRes->fetch_assoc()['order_number'];
        $lastNum = (int)substr($lastOrderNumber, -4);
        $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNum = '0001';
    }
    $order_number = "ORD-{$today}-{$nextNum}";

    $total_amount   = 0;
    $items_prepared = [];

    $selStmt = $conn->prepare("SELECT id, price, stock, name FROM products WHERE id = ? FOR UPDATE");
    if (!$selStmt) throw new Exception('DB prepare error: ' . $conn->error);

    foreach ($cart as $c) {
        $pid = (int)($c['id'] ?? 0);
        $qty = (int)($c['qty'] ?? 0);
        if ($pid <= 0 || $qty <= 0) throw new Exception('Invalid cart item');

        $selStmt->bind_param('i', $pid);
        $selStmt->execute();
        $res = $selStmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Product ID {$pid} not found");
        $prod  = $res->fetch_assoc();
        $price = (float)$prod['price'];
        $stock = $prod['stock']; // ຮັກສາຄ່າ NULL ຫຼື INT ໄວ້
        $name  = $prod['name'] ?? '';

        // ກວດສອບ Stock ສະເພາະສິນຄ້າທີ່ມີການກຳນົດ stock (ບໍ່ແມ່ນ NULL)
        if ($stock !== null && (int)$stock < $qty) {
            throw new Exception("Not enough stock for {$name} (Available: {$stock}, Requested: {$qty})");
        }

        $line_total = $price * $qty;
        $total_amount += $line_total;
        $items_prepared[] = [
            'product_id' => $pid,
            'qty'        => $qty,
            'price'      => $price,
            'line_total' => $line_total,
            'name'       => $name,
            'has_stock'  => ($stock !== null) // ມາກໄວ້ວ່າຕ້ອງອັບເດດ stock ບໍ
        ];
    }
    $selStmt->close();

    // ບັນທຶກ Order
    $insOrder = $conn->prepare(
        "INSERT INTO orders (order_number, customer_id, total_amount, payment_method, cash_given, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    if (!$insOrder) throw new Exception('DB prepare error (insert order): ' . $conn->error);
    $insOrder->bind_param('sidsd', $order_number, $customer_id, $total_amount, $payment_method, $cash_given);
    if (!$insOrder->execute()) throw new Exception('Failed to insert order: ' . $insOrder->error);
    $order_id = $conn->insert_id;
    $insOrder->close();

    // Prepare Statements
    $insItem  = $conn->prepare("INSERT INTO order_items (order_id, product_id, price, qty, total) VALUES (?, ?, ?, ?, ?)");
    $updStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    if (!$insItem || !$updStock) throw new Exception('DB prepare error (items/stock): ' . $conn->error);

    foreach ($items_prepared as $it) {
        // ເພີ່ມລາຍການອໍເດີ
        $insItem->bind_param('iidid', $order_id, $it['product_id'], $it['price'], $it['qty'], $it['line_total']);
        if (!$insItem->execute()) throw new Exception('Failed to insert order item: ' . $insItem->error);

        // ຕັດ stock ສະເພາະສິນຄ້າທີ່ມີການຕັ້ງ Stock ໄວ້ເທົ່ານັ້ນ
        if ($it['has_stock']) {
            $updStock->bind_param('ii', $it['qty'], $it['product_id']);
            if (!$updStock->execute()) throw new Exception('Failed to update stock: ' . $updStock->error);
        }
    }
    $insItem->close();
    $updStock->close();

    $conn->commit();

    // ດຶງຂໍ້ມູນພະນັກງານ
    $emp_id   = $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? '051';
    $emp_name = $_SESSION['user_name'] ?? $_SESSION['emp_name'] ?? 'MAX';

    // ສ້າງ receipt HTML ຕາມແບບ Thermal Paper
    $receipt_html = '
    <div style="font-family: \'Courier New\', Courier, monospace, Arial; font-size: 12px; color: #000; width: 100%; max-width: 280px; margin: 0 auto; line-height: 1.3;">
        
        <!-- Header / Logo & Store Info -->
        <div style="text-align: center; margin-bottom: 8px;">
            <div style="font-size: 18px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">KATUNYU COFFEE</div>
            <div style="font-size: 14px; font-weight: bold; margin-bottom: 4px;">Receipt</div>
            
            <div style="font-size: 11px; color: #333;">
                Tel: 020 77327109<br>
                WiFi: Katunyu Guest WiFi<br>
                POS ID: '.$emp_name.'
            </div>
        </div>

        <div style="border-top: 1px solid #000; margin: 6px 0;"></div>

        <!-- Order Type & Number -->
        <div style="font-size: 16px; font-weight: bold; margin: 6px 0;">
            Order No. #'.substr($order_number, -5).'
        </div>

        <div style="border-top: 1px solid #000; margin: 6px 0;"></div>

        <!-- Date & Staff Info -->
        <div style="font-size: 11px; display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span>'.date('d-M-Y h:i A').'</span>
            <span>#'.$emp_id.' '.$emp_name.'</span>
        </div>

        <!-- Items Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 8px;">';

    foreach ($items_prepared as $it) {
        $receipt_html .= '
            <tr>
                <td style="padding: 2px 0; vertical-align: top;" colspan="2">
                    <strong>'.$it['qty'].' '.$it['name'].'</strong>
                </td>
                <td style="text-align: right; vertical-align: top; padding: 2px 0;">
                    '.number_format($it['line_total']).'
                </td>
            </tr>';
    }

    $receipt_html .= '
        </table>

        <!-- Summary Totals -->
        <div style="border-top: 1px dashed #000; padding-top: 6px; font-size: 12px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <span>Subtotal</span>
                <span>'.number_format($total_amount).'</span>
            </div>';

    if ($payment_method === 'cash') {
        $change = max(0, $cash_given - $total_amount);
        $receipt_html .= '
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin: 4px 0;">
                <span>Total</span>
                <span>'.number_format($total_amount).' ₭</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <span>Cash</span>
                <span>'.number_format($cash_given).'</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <span>Change</span>
                <span>'.number_format($change).'</span>
            </div>';
    } else {
        $receipt_html .= '
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin: 4px 0;">
                <span>Total ('.strtoupper($payment_method).')</span>
                <span>'.number_format($total_amount).' ₭</span>
            </div>';
    }

    $receipt_html .= '
        </div>

        <div style="border-top: 1px dashed #000; margin: 10px 0 6px 0;"></div>

        <!-- Footer -->
        <div style="text-align: center; font-size: 11px;">
            Thank you<br>
            ຂອບໃຈ<br>
            <span style="font-size: 9px; color: #555; margin-top: 4px; display: block;">Powered by KATUNYU POS</span>
        </div>

    </div>';

    if (ob_get_length()) { ob_end_clean(); }
    echo json_encode([
        'status'           => 'success',
        'new_order_number' => $order_number,
        'receipt_html'     => $receipt_html,
    ]);
    exit;

} catch (\Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    error_log('save_order error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (ob_get_length()) { ob_end_clean(); }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}