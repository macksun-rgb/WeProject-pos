<?php
/**
 * includes/flash.php
 * Renders the flash message toast (success / danger / warning / info).
 * Expects $flash to already be set by index.php (via getFlashMessage()).
 */
?>
<?php if ($flash): ?>
    <div class="flash-toast <?= $flash['type'] ?>">
        <span class="toast-icon">
            <?php if ($flash['type'] == 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php elseif ($flash['type'] == 'danger'): ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php elseif ($flash['type'] == 'warning'): ?>
                <i class="fas fa-exclamation-triangle"></i>
            <?php else: ?>
                <i class="fas fa-info-circle"></i>
            <?php endif; ?>
        </span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>
        setTimeout(() => { document.querySelector('.flash-toast')?.remove(); }, 4000);
    </script>
<?php endif; ?>
