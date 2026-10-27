<?php
/**
 * functions.php
 * Global helper functions used across the whole KaTunYu Coffee POS system.
 */

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

function getStatusBadge($status) {
    $badges = [
        'available'   => 'bg-success',
        'unavailable' => 'bg-secondary',
        'active'      => 'bg-success',
        'inactive'    => 'bg-secondary',
        'pending'     => 'bg-warning',
        'completed'   => 'bg-success',
        'cancelled'   => 'bg-danger'
    ];
    $labels = [
        'available'   => 'ພ້ອມຂາຍ',
        'unavailable' => 'ຢຸດຂາຍ',
        'active'      => 'ປະຕິບັດງານ',
        'inactive'    => 'ບໍ່ເຄື່ອນໄຫວ',
        'pending'     => 'ລໍຖ້າ',
        'completed'   => 'ສຳເລັດ',
        'cancelled'   => 'ຍົກເລີກ'
    ];
    return '<span class="badge ' . ($badges[$status] ?? 'bg-secondary') . '">' . ($labels[$status] ?? $status) . '</span>';
}
