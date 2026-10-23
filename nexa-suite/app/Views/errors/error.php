<?php
$status = isset($status) ? (int) $status : 500;
$message = isset($message) ? (string) $message : 'Something went wrong.';
$headline = match ($status) {
    404 => 'This route drifted off the map.',
    419 => 'Your session token expired.',
    401 => 'Authentication required.',
    403 => 'That door is locked.',
    405 => 'That method is not allowed.',
    429 => 'Easy there — rate limit reached.',
    default => 'The control deck hit turbulence.',
};
?>
<section class="error-page container">
    <div class="error-card">
        <span class="error-code"><?= e((string) $status) ?></span>
        <h1><?= e($headline) ?></h1>
        <p><?= e($message) ?></p>
        <?php if (!empty($trace)): ?>
            <pre class="code-block"><code><?= e($trace) ?></code></pre>
        <?php endif; ?>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url(app()->auth()->check() ? '/admin' : '/')) ?>"><?= app()->auth()->check() ? 'Back to console' : 'Back to home' ?> <?= nexa_icon('arrow') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/docs')) ?>">Documentation</a>
        </div>
    </div>
</section>
