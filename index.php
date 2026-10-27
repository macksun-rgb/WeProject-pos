<?php
/**
 * index.php
 * Router / entry point. Handles auth check, page selection, dispatches
 * POST actions to the right handler, then renders header -> sidebar ->
 * topbar -> the selected page -> footer.
 *
 * Folder layout:
 *   config.php              DB connection (getDBConnection())
 *   functions.php           Shared helper functions
 *   handlers/                POST-action handlers, one per page that needs them
 *   includes/                Shared layout pieces (header, sidebar, topbar, footer, flash)
 *   pages/                   One file per menu page (dashboard, pos, products, ...)
 *   assets/css/style.css     All styling
 *   assets/js/               Page-specific + shared JS
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
require_once 'functions.php';

$conn = getDBConnection();

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowedPages = ['dashboard', 'pos', 'products', 'categories', 'employees', 'orders', 'reports', 'settings'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

// ============ LOGOUT ============
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ============ DISPATCH POST ACTIONS TO THE RIGHT HANDLER ============
// Each handler file itself checks isset($_POST[...]) before doing anything,
// so it's safe to just include the one matching the current page.
$handlerMap = [
    'categories' => 'handlers/categories_handler.php',
    'products'   => 'handlers/products_handler.php',
    'employees'  => 'handlers/employees_handler.php',
    'settings'   => 'handlers/settings_handler.php',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($handlerMap[$page])) {
    require $handlerMap[$page];
}

// Flash message (set by a handler on the previous request, read + cleared here)
$flash = getFlashMessage();

// ============ RENDER ============
require 'includes/header.php';
require 'includes/sidebar.php';
require 'includes/flash.php';
require 'includes/topbar.php';

$pageFile = "pages/{$page}.php";
if (is_file($pageFile)) {
    require $pageFile;
} else {
    require 'pages/welcome.php';
}

require 'includes/footer.php';

$conn->close();
