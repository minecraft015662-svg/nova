<?php
$siteName = (string) app()->settings()->get('site_name', 'Nexa Suite');
$currentUser = app()->auth()->user();
$pageTitle = isset($title) ? (string) $title : 'Console';
$navActive = $active ?? '';
$panelConfigured = app()->pterodactyl()->configured();
$licenseProduct = (string) app()->settings()->get('license_product', 'Nexa Theme');
$navItems = [
    ['key' => 'overview', 'label' => 'Overview', 'href' => '/admin', 'icon' => 'grid'],
    ['key' => 'licenses', 'label' => 'Licenses', 'href' => '/admin/licenses', 'icon' => 'key'],
    ['key' => 'api-keys', 'label' => 'API keys', 'href' => '/admin/api-keys', 'icon' => 'code'],
    ['key' => 'bots', 'label' => 'Bot hosting', 'href' => '/admin/bots', 'icon' => 'bot'],
    ['key' => 'extensions', 'label' => 'Extensions', 'href' => '/admin/extensions', 'icon' => 'puzzle'],
];
$systemItems = [
    ['key' => 'settings', 'label' => 'Settings', 'href' => '/admin/settings', 'icon' => 'settings'],
    ['key' => 'cli', 'label' => 'CLI reference', 'href' => '/admin/cli', 'icon' => 'terminal'],
    ['key' => 'account', 'label' => 'Account', 'href' => '/admin/account', 'icon' => 'user'],
];
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
<body class="app-body">
<div class="ambient" aria-hidden="true"></div>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <a class="brand" href="<?= e(url('/admin')) ?>">
                <span class="brand-mark"><img src="/assets/logo.svg" alt="" width="26" height="26"></span>
                <span class="brand-copy">
                    <span class="brand-word"><?= e($siteName) ?></span>
                    <span class="brand-sub">Control deck</span>
                </span>
            </a>
            <button class="sidebar-close" type="button" aria-label="Close navigation" data-sidebar-close><?= nexa_icon('x') ?></button>
        </div>

        <nav class="sidebar-nav" aria-label="Console navigation">
            <span class="nav-label">Workspace</span>
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link <?= e(nav_class($navActive, $item['key'])) ?>" href="<?= e(url($item['href'])) ?>">
                    <?= nexa_icon($item['icon']) ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($item['key'] === 'licenses'): ?>
                        <span class="nav-count"><?= e((string) app()->licenses()->stats()['active']) ?></span>
                    <?php elseif ($item['key'] === 'bots'): ?>
                        <span class="nav-count"><?= e((string) app()->bots()->stats()['total']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <span class="nav-label">System</span>
            <?php foreach ($systemItems as $item): ?>
                <a class="nav-link <?= e(nav_class($navActive, $item['key'])) ?>" href="<?= e(url($item['href'])) ?>">
                    <?= nexa_icon($item['icon']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">
            <div class="connection-card <?= $panelConfigured ? 'is-connected' : '' ?>">
                <span class="connection-top">
                    <span class="status-dot <?= $panelConfigured ? 'is-good' : 'is-neutral' ?>"></span>
                    <?= $panelConfigured ? 'Panel connected' : 'Panel not connected' ?>
                </span>
                <span class="connection-sub"><?= $panelConfigured ? 'Bot hosting and license delivery ready' : 'Add your panel API details' ?></span>
                <a class="connection-link" href="<?= e(url('/admin/settings')) ?>"><?= $panelConfigured ? 'Manage connection' : 'Connect now' ?> <?= nexa_icon('arrow') ?></a>
            </div>

            <div class="sidebar-user">
                <span class="avatar"><?= e(strtoupper(substr((string) ($currentUser['name'] ?? 'N'), 0, 1))) ?></span>
                <span class="sidebar-user-copy">
                    <strong><?= e($currentUser['name'] ?? 'Admin') ?></strong>
                    <span><?= e($currentUser['email'] ?? '') ?></span>
                </span>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="icon-btn" type="submit" title="Sign out" aria-label="Sign out"><?= nexa_icon('logout') ?></button>
                </form>
            </div>
            <span class="sidebar-version"><?= e($licenseProduct) ?> · Suite v<?= e(NEXA_VERSION) ?></span>
        </div>
    </aside>

    <div class="sidebar-scrim" data-sidebar-close aria-hidden="true"></div>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn sidebar-open" type="button" aria-label="Open navigation" data-sidebar-open><?= nexa_icon('menu') ?></button>
                <a class="topbar-crumb" href="<?= e(url('/admin')) ?>">Console</a>
                <span class="topbar-sep">/</span>
                <span class="topbar-current"><?= e($pageTitle) ?></span>
            </div>
            <div class="topbar-right">
                <a class="topbar-site" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View site <?= nexa_icon('external') ?></a>
                <span class="topbar-product"><span class="status-dot is-good"></span><?= e($licenseProduct) ?></span>
                <a class="avatar avatar-sm" href="<?= e(url('/admin/account')) ?>" aria-label="Account"><?= e(strtoupper(substr((string) ($currentUser['name'] ?? 'N'), 0, 1))) ?></a>
            </div>
        </header>

        <main class="app-content">
            <?php require base_path('app/Views/partials/flashes.php'); ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('suite.js')) ?>" defer></script>
</body>
</html>
