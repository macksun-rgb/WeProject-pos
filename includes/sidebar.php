        <!-- ===== SIDEBAR ===== -->
        <div class="col-lg-2 p-0 sidebar">
            <div class="sidebar-brand">
                <div class="logo-icon"><i class="fas fa-mug-hot"></i></div>
                <h4>KaTunYu</h4>
                <small>Coffee POS</small>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">ຫຼັກ</div>
                <a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> ແດຊບອດ
                </a>
                <a href="?page=pos" class="<?= $page == 'pos' ? 'active' : '' ?>">
                    <i class="fas fa-cash-register"></i> ຂາຍໜ້າຮ້ານ
                </a>

                <div class="nav-label mt-2">ຈັດການ</div>
                <a href="?page=products" class="<?= $page == 'products' ? 'active' : '' ?>">
                    <i class="fas fa-coffee"></i> ສິນຄ້າ
                </a>
                <a href="?page=categories" class="<?= $page == 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i> ປະເພດ
                </a>
                <a href="?page=employees" class="<?= $page == 'employees' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> ພະນັກງານ
                </a>

                <div class="nav-label mt-2">ລາຍງານ</div>
                <a href="?page=orders" class="<?= $page == 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i> ຄຳສັ່ງຊື້
                </a>
                <a href="?page=reports" class="<?= $page == 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> ລາຍງານ
                </a>
                <a href="?page=settings" class="<?= $page == 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> ຕັ້ງຄ່າ
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['fullname'] ?? 'G', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Guest') ?></div>
                        <div class="user-role">
                            <?php if (($_SESSION['role'] ?? '') == 'admin'): ?>
                                <i class="fas fa-crown" style="color: var(--secondary);"></i> ຜູ້ດູແລ
                            <?php else: ?>
                                <i class="fas fa-user"></i> ພະນັກງານ
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="?logout" class="btn btn-sm btn-pos-light ms-auto" onclick="return confirm('ທ່ານຕ້ອງການອອກຈາກລະບົບ?')">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== MAIN CONTENT WRAPPER OPENS ===== -->
        <div class="col-lg-10 main-content">
