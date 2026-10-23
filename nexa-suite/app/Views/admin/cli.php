<?php
$installBlock = <<<'CODE'
php bin/nexa install
php bin/nexa serve --host=127.0.0.1 --port=4411
CODE;

$licenseBlock = <<<'CODE'
# Issue a 1-year Pro key with 3 activations
php bin/nexa license:create --plan=pro --days=365 --activations=3 \
  --email=customer@example.com --note="Order #1042"

# List, validate, revoke and regenerate
php bin/nexa license:list
php bin/nexa license:validate NEXA-XXXXX-XXXXX-XXXXX-XXXXX --domain=panel.example.com
php bin/nexa license:revoke 12
php bin/nexa license:regenerate 12
CODE;

$keyBlock = <<<'CODE'
php bin/nexa key:create --name="Billing automation" --scopes=licenses:read,licenses:write
php bin/nexa key:create --name="Bot manager" --scopes=bots:write
php bin/nexa key:list
php bin/nexa key:revoke 3
CODE;

$extBlock = <<<'CODE'
php bin/nexa extension:list
php bin/nexa extension:enable discord-auth
php bin/nexa extension:disable status-page
php bin/nexa extension:add --name="Internal Tools" --category=Custom \
  --description="Our internal Blueprint add-on" --install="Admin → Extensions → Import"
CODE;

$botBlock = <<<'CODE'
php bin/nexa bot:create --name="Music Bot" --memory=1024 --disk=4096 --cpu=100
php bin/nexa bot:create --name="Ticket Bot" --memory=2048 --disk=8192 --egg=3 --nest=1 --node=1
php bin/nexa bot:list
php bin/nexa bot:start 4
php bin/nexa bot:stop 4
php bin/nexa bot:delete 4
CODE;

$settingsBlock = <<<'CODE'
php bin/nexa settings:show
php bin/nexa settings:set pterodactyl_url=https://panel.example.com
php bin/nexa settings:set pterodactyl_application_key=ptla_xxxxx
php bin/nexa settings:set pterodactyl_client_key=ptlc_xxxxx
php bin/nexa settings:set pterodactyl_owner_id=1
php bin/nexa settings:set pterodactyl_default_node=1
php bin/nexa settings:set pterodactyl_default_nest=1
php bin/nexa settings:set pterodactyl_default_egg=1
CODE;

$block = function (string $id, string $source): void {
    ?>
    <div class="code-shell">
        <div class="code-head">
            <span class="code-lang">bash</span>
            <button class="copy-btn" type="button" data-copy-target="<?= e($id) ?>"><?= nexa_icon('copy') ?> Copy</button>
        </div>
        <pre class="code-block" id="<?= e($id) ?>"><code><?= e($source) ?></code></pre>
    </div>
    <?php
};
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Terminal</span>
        <h1>CLI reference</h1>
        <p>Script the same license, extension and bot operations from the command line.</p>
    </div>
    <div class="page-actions">
        <span class="page-chip"><?= nexa_icon('terminal') ?> <?= e($baseUrl) ?></span>
        <a class="btn btn-quiet" href="<?= e(url('/docs#cli')) ?>"><?= nexa_icon('book') ?> Full guide</a>
    </div>
</div>

<div class="notice">
    <?= nexa_icon('shield') ?>
    <div><strong>Run from the suite root</strong><span>The CLI boots the same application, database and encryption key as the web console. File permissions must allow the shell user to read <code>storage/</code> and <code>config/</code>.</span></div>
</div>

<div class="two-col">
    <section class="panel">
        <div class="panel-head"><div><h2>Install &amp; serve</h2><p>First-run and local development.</p></div></div>
        <?php $block('cli-install', $installBlock); ?>
        <div class="command-table">
            <div class="command-row"><code>install</code><span>Create config, database and admin. Supports flags for automation.</span></div>
            <div class="command-row"><code>serve</code><span>Start the built-in server on port 4411 by default.</span></div>
            <div class="command-row"><code>user:create</code><span>Add or recover a console administrator.</span></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Licenses</h2><p>Creation and lifecycle.</p></div></div>
        <?php $block('cli-licenses', $licenseBlock); ?>
        <p class="cell-sub">Pass <code>--days=0</code> for a lifetime key. Activation limits are clamped between 1 and 1000.</p>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>API keys</h2><p>Scoped automation credentials.</p></div></div>
        <?php $block('cli-keys', $keyBlock); ?>
        <p class="cell-sub">Scopes: <code>licenses:read</code>, <code>licenses:write</code>, <code>bots:read</code>, <code>bots:write</code>, <code>extensions:read</code>.</p>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Extensions</h2><p>Catalog state in one command.</p></div></div>
        <?php $block('cli-extensions', $extBlock); ?>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Bot servers</h2><p>Provision and power Pterodactyl bots.</p></div></div>
        <?php $block('cli-bots', $botBlock); ?>
        <p class="cell-sub">Power commands require a Client API key in settings. Creation and deletion use the Application API.</p>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Settings</h2><p>Non-interactive configuration.</p></div></div>
        <?php $block('cli-settings', $settingsBlock); ?>
        <p class="cell-sub">Secret values are encrypted before they touch SQLite. <code>settings:show</code> masks them.</p>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><div><h2>Automation examples</h2><p>Wrap the CLI in cron or a deployment script.</p></div></div>
    <div class="two-col inner">
        <div>
            <h3 class="mini-heading">Nightly license audit</h3>
            <div class="code-shell flush">
                <pre class="code-block"><code>#!/usr/bin/env bash
cd /var/www/nexa-suite
php bin/nexa license:list --json > /var/backups/nexa-licenses.json
</code></pre>
            </div>
        </div>
        <div>
            <h3 class="mini-heading">Rotate an API key</h3>
            <div class="code-shell flush">
                <pre class="code-block"><code>php bin/nexa key:create --name="Billing v2" --scopes=licenses:read
php bin/nexa key:revoke 4
</code></pre>
            </div>
        </div>
    </div>
</section>
