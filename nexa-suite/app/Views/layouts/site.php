<?php
$siteName = (string) app()->settings()->get('site_name', 'Nexa Suite');
$currentUser = app()->auth()->user();
$pageTitle = isset($title) ? (string) $title : $siteName;
$navActive = $active ?? '';
$fullTitle = str_contains($pageTitle, $siteName) ? $pageTitle : $pageTitle . ' · ' . $siteName;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($meta_description ?? 'Nexa Suite is the self-hosted control deck for your Pterodactyl stack: licenses, API keys, extensions and game-server bot hosting.') ?>">
    <link rel="preload" href="/fonts/sora-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('suite.css')) ?>">
    <link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
</head>
<body class="site-body">
<div class="ambient" aria-hidden="true"></div>

<header class="site-nav" id="site-nav">
    <div class="site-nav-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> home">
            <span class="brand-mark"><img src="/assets/logo.svg" alt="" width="26" height="26"></span>
            <span class="brand-word"><?= e($siteName) ?></span>
        </a>
        <nav class="site-links" id="site-links">
            <a href="<?= e(url('/#features')) ?>" class="<?= $navActive === 'home' ? 'is-active' : '' ?>">Platform</a>
            <a href="<?= e(url('/#extensions')) ?>">Extensions</a>
            <a href="<?= e(url('/#api')) ?>">API</a>
            <a href="<?= e(url('/#pricing')) ?>">Licenses</a>
            <a href="<?= e(url('/docs')) ?>" class="<?= $navActive === 'docs' ? 'is-active' : '' ?>">Docs</a>
        </nav>
        <div class="site-nav-actions">
            <?php if ($currentUser !== null): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/admin')) ?>">Open console <?= nexa_icon('arrow') ?></a>
            <?php elseif (app()->installed()): ?>
                <a class="btn btn-quiet btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/login')) ?>">Open console <?= nexa_icon('arrow') ?></a>
            <?php else: ?>
                <a class="btn btn-quiet btn-sm" href="<?= e(url('/docs')) ?>">Docs</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/setup')) ?>">Set up Nexa <?= nexa_icon('arrow') ?></a>
            <?php endif; ?>
            <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" data-nav-toggle>
                <?= nexa_icon('menu') ?>
            </button>
        </div>
    </div>
</header>

<div class="site-flashes container">
    <?php require base_path('app/Views/partials/flashes.php'); ?>
</div>

<main>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="<?= e(url('/')) ?>">
                <span class="brand-mark"><img src="/assets/logo.svg" alt="" width="26" height="26"></span>
                <span class="brand-word"><?= e($siteName) ?></span>
            </a>
            <p>Licenses, API keys, extensions and Pterodactyl bot hosting in one crimson-glass control deck.</p>
            <span class="version-pill">v<?= e(NEXA_VERSION) ?> · self-hosted</span>
        </div>
        <div class="footer-col">
            <h4>Platform</h4>
            <a href="<?= e(url('/#features')) ?>">Features</a>
            <a href="<?= e(url('/#extensions')) ?>">Extension catalog</a>
            <a href="<?= e(url('/#api')) ?>">API</a>
            <a href="<?= e(url('/#pricing')) ?>">License tiers</a>
        </div>
        <div class="footer-col">
            <h4>Developers</h4>
            <a href="<?= e(url('/docs#quick-start')) ?>">Quick start</a>
            <a href="<?= e(url('/docs#api')) ?>">API reference</a>
            <a href="<?= e(url('/docs#cli')) ?>">CLI reference</a>
            <a href="<?= e(url('/docs#deploy')) ?>">Deploy on port 4411</a>
        </div>
        <div class="footer-col">
            <h4>Console</h4>
            <?php if ($currentUser !== null): ?>
                <a href="<?= e(url('/admin')) ?>">Overview</a>
                <a href="<?= e(url('/admin/licenses')) ?>">Licenses</a>
                <a href="<?= e(url('/admin/bots')) ?>">Bot hosting</a>
            <?php else: ?>
                <a href="<?= e(url('/login')) ?>">Sign in</a>
                <a href="<?= e(url(app()->installed() ? '/login' : '/setup')) ?>">First-run setup</a>
                <a href="<?= e(url('/docs')) ?>">Documentation</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= e(date('Y')) ?> <?= e($siteName) ?>. Built for Pterodactyl.</span>
        <span class="footer-meta">PHP <?= e(PHP_VERSION) ?> · SQLite · <?= nexa_icon('shield') ?> secrets encrypted at rest</span>
    </div>
</footer>

<script src="<?= e(asset('suite.js')) ?>" defer></script>
</body>
</html>
