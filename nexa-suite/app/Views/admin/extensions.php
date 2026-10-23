<?php
$categories = [];
foreach ($extensions as $entry) {
    $categories[(string) $entry['category']][] = $entry;
}
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Ecosystem</span>
        <h1>Extension catalog</h1>
        <p>Track bundled Nexa extensions and every community Blueprint add-on in one place.</p>
    </div>
    <div class="page-actions">
        <span class="page-chip"><?= nexa_icon('puzzle') ?> <?= e((string) $stats['enabled']) ?> / <?= e((string) $stats['total']) ?> enabled</span>
        <a class="btn btn-quiet" href="<?= e(url('/docs#theme')) ?>"><?= nexa_icon('book') ?> Install guide</a>
    </div>
</div>

<div class="notice">
    <?= nexa_icon('puzzle') ?>
    <div>
        <strong>Catalog, not a package manager</strong>
        <span>Enable an entry after you install its <code>.blueprint</code> in Pterodactyl. Bundled Nexa extensions ship with the theme; community slots are placeholders you can rename or replace.</span>
    </div>
</div>

<div class="extension-grid full">
    <?php foreach ($extensions as $entry): ?>
        <article class="extension-card tall">
            <div class="extension-top">
                <span class="extension-glyph"><?= nexa_icon(($entry['category'] ?? '') === 'Theme' ? 'spark' : (($entry['category'] ?? '') === 'Suite' ? 'layers' : 'puzzle')) ?></span>
                <span class="badge <?= e(state_class((string) $entry['status'])) ?>"><?= e($entry['status']) ?></span>
            </div>
            <span class="extension-cat"><?= e($entry['category']) ?><?= !empty($entry['bundled']) ? ' · bundled' : '' ?></span>
            <h3><?= e($entry['name']) ?></h3>
            <p><?= e($entry['description']) ?></p>
            <dl class="extension-meta">
                <div><dt>Version</dt><dd><?= e($entry['version']) ?></dd></div>
                <div><dt>Requires</dt><dd><?= e($entry['requires'] !== '' ? $entry['requires'] : '—') ?></dd></div>
                <div><dt>Install</dt><dd><?= e($entry['install'] !== '' ? $entry['install'] : 'Upload the extension in Pterodactyl.') ?></dd></div>
            </dl>
            <div class="extension-actions">
                <form method="post" action="<?= e(url('/admin/extensions/toggle')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e($entry['id']) ?>">
                    <input type="hidden" name="enabled" value="<?= $entry['status'] === 'enabled' ? '0' : '1' ?>">
                    <button class="btn btn-<?= $entry['status'] === 'enabled' ? 'quiet' : 'primary' ?> btn-sm" type="submit">
                        <?= $entry['status'] === 'enabled' ? 'Disable in catalog' : 'Enable in catalog' ?>
                    </button>
                </form>
                <?php if (!empty($entry['source_url'])): ?>
                    <a class="btn btn-quiet btn-sm" href="<?= e($entry['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= nexa_icon('external') ?> Source</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<details class="panel add-panel">
    <summary>
        <span class="panel-icon"><?= nexa_icon('plus') ?></span>
        <span class="add-summary"><strong>Add a custom extension</strong><span>Register any Blueprint add-on, egg pack or internal tool in the catalog.</span></span>
        <span class="add-chevron"><?= nexa_icon('arrow') ?></span>
    </summary>
    <form class="form-grid two" method="post" action="<?= e(url('/admin/extensions/add')) ?>">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label">Name</span>
            <input class="input" type="text" name="name" maxlength="80" placeholder="Subdomain Manager" required>
        </label>
        <label class="field">
            <span class="field-label">Category</span>
            <input class="input" type="text" name="category" maxlength="40" value="Community" placeholder="Community">
        </label>
        <label class="field">
            <span class="field-label">Version</span>
            <input class="input" type="text" name="version" maxlength="30" placeholder="1.0.0">
        </label>
        <label class="field">
            <span class="field-label">Requires</span>
            <input class="input" type="text" name="requires" maxlength="120" placeholder="Blueprint 2026">
        </label>
        <label class="field span-2">
            <span class="field-label">Description</span>
            <textarea class="input" name="description" rows="3" maxlength="500" placeholder="What does this extension add to the panel?"></textarea>
        </label>
        <label class="field">
            <span class="field-label">Source URL <span class="optional">optional</span></span>
            <input class="input" type="url" name="source_url" placeholder="https://example.com/addon">
        </label>
        <label class="field">
            <span class="field-label">Install notes</span>
            <input class="input" type="text" name="install" maxlength="300" placeholder="Admin → Extensions → Import">
        </label>
        <div class="span-2 form-actions">
            <button class="btn btn-primary" type="submit"><?= nexa_icon('plus') ?> Add to catalog</button>
        </div>
    </form>
</details>
