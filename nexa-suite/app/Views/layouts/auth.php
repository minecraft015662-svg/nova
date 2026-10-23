<?php
$siteName = (string) app()->settings()->get('site_name', 'Nexa Suite');
$pageTitle = isset($title) ? (string) $title : 'Sign in';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preload" href="/fonts/sora-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('suite.css')) ?>">
    <link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
</head>
<body class="auth-body">
<div class="ambient" aria-hidden="true"></div>
<div class="auth-shell">
    <section class="auth-visual">
        <div class="auth-visual-bg" aria-hidden="true"></div>
        <div class="auth-visual-inner">
            <a class="brand" href="<?= e(url('/')) ?>">
                <span class="brand-mark"><img src="/assets/logo.svg" alt="" width="26" height="26"></span>
                <span class="brand-word"><?= e($siteName) ?></span>
            </a>
            <div class="auth-copy">
                <span class="eyebrow">Nexa control deck</span>
                <h1>Your Pterodactyl stack, beautifully controlled.</h1>
                <p>Issue licenses, mint API keys, provision bot servers and keep every extension in one place.</p>
                <ul class="auth-points">
                    <li><?= nexa_icon('check') ?> Hashed license keys with activation limits</li>
                    <li><?= nexa_icon('check') ?> Scoped API keys for bot provisioning</li>
                    <li><?= nexa_icon('check') ?> Blueprint extension catalog</li>
                    <li><?= nexa_icon('check') ?> Runs on PHP 8 and SQLite — port 4411</li>
                </ul>
            </div>
            <div class="auth-visual-foot">
                <span class="status-dot"></span>
                <span>Self-hosted · no external license server</span>
            </div>
        </div>
    </section>
    <section class="auth-panel">
        <div class="auth-panel-top">
            <a class="auth-back" href="<?= e(url('/')) ?>"><?= nexa_icon('arrow') ?> Back to site</a>
            <span class="auth-secure"><?= nexa_icon('lock') ?> Secure session</span>
        </div>
        <div class="auth-card">
            <?php require base_path('app/Views/partials/flashes.php'); ?>
            <?= $content ?>
        </div>
        <p class="auth-foot"><?= e($siteName) ?> · v<?= e(NEXA_VERSION) ?></p>
    </section>
</div>
<script src="<?= e(asset('suite.js')) ?>" defer></script>
</body>
</html>
