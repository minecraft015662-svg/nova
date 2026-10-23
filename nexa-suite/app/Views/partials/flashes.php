<?php foreach (flashes() as $flash): ?>
    <?php
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $icon = $type === 'success' ? 'check' : ($type === 'error' ? 'alert' : ($type === 'warning' ? 'alert' : 'spark'));
    ?>
    <div class="alert alert-<?= e($type) ?>" role="status">
        <?= nexa_icon($icon, 'alert-icon') ?>
        <span><?= e($flash['message']) ?></span>
    </div>
<?php endforeach; ?>
