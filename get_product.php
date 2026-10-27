<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

$conn = getDBConnection();
$id = (int)$_GET['id'];
$stmt = $conn->prepare('SELECT id, name, category_id, price, cost, stock, status FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$product['category_id'] = $product['category_id'] === null ? null : (int)$product['category_id'];
$product['price'] = (float)$product['price'];
$product['cost'] = (float)$product['cost'];
$product['stock'] = (int)$product['stock'];

echo json_encode(array_merge(['success' => true], $product));
