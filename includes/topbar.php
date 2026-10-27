<?php
/**
 * includes/topbar.php
 * Renders the page title/subtitle plus the contextual action buttons
 * (e.g. "Add product" button only shows on the products page).
 * Expects $page to already be set by index.php.
 */

$pageTitles = [
    'dashboard'  => ['icon' => 'fa-th-large',       'title' => 'ແດຊບອດ',        'sub' => 'ພາບລວມທຸລະກິດ'],
    'pos'        => ['icon' => 'fa-cash-register',  'title' => 'ຂາຍໜ້າຮ້ານ',     'sub' => 'ບັນທຶກການຂາຍ'],
    'products'   => ['icon' => 'fa-coffee',         'title' => 'ຈັດການສິນຄ້າ',   'sub' => 'ເພີ່ມ / ແກ້ໄຂ / ລຶບສິນຄ້າ'],
    'categories' => ['icon' => 'fa-tags',           'title' => 'ປະເພດສິນຄ້າ',    'sub' => 'ຈັດການປະເພດ'],
    'employees'  => ['icon' => 'fa-users',          'title' => 'ພະນັກງານ',      'sub' => 'ຈັດການພະນັກງານ'],
    'orders'     => ['icon' => 'fa-shopping-cart',  'title' => 'ຄຳສັ່ງຊື້',      'sub' => 'ປະຫວັດການຂາຍ'],
    'reports'    => ['icon' => 'fa-chart-bar',      'title' => 'ລາຍງານ',        'sub' => 'ວິເຄາະຂໍ້ມູນ'],
    'settings'   => ['icon' => 'fa-cog',            'title' => 'ຕັ້ງຄ່າ',        'sub' => 'ຈັດການລະບົບ']
];
$p = $pageTitles[$page] ?? ['icon' => 'fa-store', 'title' => 'ລະບົບ POS', 'sub' => 'KaTunYu Coffee'];
?>
<div class="top-bar">
    <div class="page-title">
        <h4><i class="fas <?= $p['icon'] ?> text-coffee me-2"></i> <?= $p['title'] ?></h4>
        <small><?= $p['sub'] ?></small>
    </div>
    <div class="actions">
        <?php if ($page != 'pos'): ?>
            <a href="?page=pos" class="btn-pos"><i class="fas fa-cash-register"></i> ໄປທີ່ຂາຍໜ້າຮ້ານ</a>
        <?php endif; ?>
        <?php if ($page == 'products'): ?>
            <button class="btn-pos" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus"></i> ເພີ່ມສິນຄ້າ</button>
        <?php endif; ?>
        <?php if ($page == 'categories'): ?>
            <button class="btn-pos" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fas fa-plus"></i> ເພີ່ມປະເພດ</button>
        <?php endif; ?>
        <?php if ($page == 'employees'): ?>
            <button class="btn-pos" data-bs-toggle="modal" data-bs-target="#addEmployeeModal"><i class="fas fa-user-plus"></i> ເພີ່ມພະນັກງານ</button>
        <?php endif; ?>
    </div>
</div>
