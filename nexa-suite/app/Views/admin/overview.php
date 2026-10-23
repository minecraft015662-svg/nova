<div class="page-head">
    <div>
        <span class="eyebrow">Control deck</span>
        <h1>Good to see you, <?= e(explode(' ', (string) app()->auth()->user()['name'])[0] ?? 'Admin') ?>.</h1>
        <p>Licenses, API keys, bot servers and extensions at a glance.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-quiet" href="<?= e(url('/docs')) ?>"><?= nexa_icon('book') ?> Docs</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/licenses')) ?>"><?= nexa_icon('plus') ?> New license</a>
    </div>
</div>

<?php if (!$panelConfigured): ?>
    <div class="connect-banner">
        <span class="connect-icon"><?= nexa_icon('server') ?></span>
        <div>
            <strong>Connect Pterodactyl to finish the setup</strong>
            <span>Bot hosting needs an Application API key, an owner user and default node/nest/egg IDs.</span>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/settings')) ?>">Open settings <?= nexa_icon('arrow') ?></a>
    </div>
<?php endif; ?>

<div class="stat-grid">
    <article class="stat-card">
        <div class="stat-top"><span class="stat-icon accent"><?= nexa_icon('key') ?></span><span class="badge is-good"><?= e((string) $licenseStats['active']) ?> active</span></div>
        <span class="stat-value"><?= e((string) $licenseStats['total']) ?></span>
        <span class="stat-label">Licenses issued</span>
        <a class="stat-foot" href="<?= e(url('/admin/licenses')) ?>"><?= e((string) $licenseStats['activations']) ?> activations · manage <?= nexa_icon('arrow') ?></a>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span class="stat-icon"><?= nexa_icon('code') ?></span><span class="badge is-neutral"><?= e((string) $apiStats['active']) ?> active</span></div>
        <span class="stat-value"><?= e((string) $apiStats['total']) ?></span>
        <span class="stat-label">API keys minted</span>
        <a class="stat-foot" href="<?= e(url('/admin/api-keys')) ?>">Scoped Bearer access <?= nexa_icon('arrow') ?></a>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span class="stat-icon"><?= nexa_icon('bot') ?></span><span class="badge <?= $botStats['running'] > 0 ? 'is-good' : 'is-neutral' ?>"><?= e((string) $botStats['running']) ?> running</span></div>
        <span class="stat-value"><?= e((string) $botStats['total']) ?></span>
        <span class="stat-label">Bot servers</span>
        <a class="stat-foot" href="<?= e(url('/admin/bots')) ?>">Provisioned via Pterodactyl <?= nexa_icon('arrow') ?></a>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span class="stat-icon"><?= nexa_icon('puzzle') ?></span><span class="badge is-good"><?= e((string) $extStats['enabled']) ?> on</span></div>
        <span class="stat-value"><?= e((string) $extStats['total']) ?></span>
        <span class="stat-label">Catalog entries</span>
        <a class="stat-foot" href="<?= e(url('/admin/extensions')) ?>">Bundled + community <?= nexa_icon('arrow') ?></a>
    </article>
</div>

<div class="two-col">
    <section class="panel">
        <div class="panel-head">
            <div><h2>Recent licenses</h2><p>The latest keys issued by this suite.</p></div>
            <a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/licenses')) ?>">View all</a>
        </div>
        <?php if ($recentLicenses === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= nexa_icon('key') ?></span>
                <h3>No licenses yet</h3>
                <p>Issue the first key for <?= e($licenseProduct) ?>.</p>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/licenses')) ?>">Create license</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>License</th><th>Plan</th><th>Installs</th><th>Expires</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentLicenses as $license): ?>
                        <?php $expired = $license['expires_at'] !== null && strtotime((string) $license['expires_at']) < time(); ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= e($license['key_prefix']) ?></span>
                                <span class="cell-sub"><?= e($license['email'] !== '' ? $license['email'] : 'Unassigned') ?></span>
                            </td>
                            <td><span class="mini-pill"><?= e(license_plan((string) $license['plan'])['name']) ?></span></td>
                            <td><?= e((string) $license['activations']) ?> / <?= e((string) $license['max_activations']) ?></td>
                            <td><?= e($license['expires_at'] === null ? 'Lifetime' : nice_date((string) $license['expires_at'])) ?></td>
                            <td><span class="badge <?= e(state_class($expired ? 'expired' : (string) $license['status'])) ?>"><?= e($expired ? 'expired' : $license['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div><h2>Launch checklist</h2><p>Everything needed for a complete stack.</p></div>
        </div>
        <ol class="checklist">
            <li class="is-done">
                <span class="checklist-mark"><?= nexa_icon('check') ?></span>
                <div><strong>Suite installed</strong><span>Admin account, SQLite database and extension catalog are live.</span></div>
            </li>
            <li class="<?= $panelConfigured ? 'is-done' : '' ?>">
                <span class="checklist-mark"><?= $panelConfigured ? nexa_icon('check') : '2' ?></span>
                <div><strong>Connect Pterodactyl</strong><span><?= $panelConfigured ? 'Application API is configured.' : 'Add the panel URL and Application API key.' ?></span>
                    <?php if (!$panelConfigured): ?><a href="<?= e(url('/admin/settings')) ?>">Connect now <?= nexa_icon('arrow') ?></a><?php endif; ?>
                </div>
            </li>
            <li class="<?= $licenseStats['total'] > 0 ? 'is-done' : '' ?>">
                <span class="checklist-mark"><?= $licenseStats['total'] > 0 ? nexa_icon('check') : '3' ?></span>
                <div><strong>Issue a license</strong><span><?= $licenseStats['total'] > 0 ? $licenseStats['total'] . ' key(s) created.' : 'Create a key for your first customer or panel.' ?></span></div>
            </li>
            <li class="<?= $apiStats['active'] > 0 ? 'is-done' : '' ?>">
                <span class="checklist-mark"><?= $apiStats['active'] > 0 ? nexa_icon('check') : '4' ?></span>
                <div><strong>Mint an API key</strong><span><?= $apiStats['active'] > 0 ? 'Automation access is ready.' : 'Use a scoped key for bots or billing automation.' ?></span></div>
            </li>
            <li class="<?= $botStats['total'] > 0 ? 'is-done' : '' ?>">
                <span class="checklist-mark"><?= $botStats['total'] > 0 ? nexa_icon('check') : '5' ?></span>
                <div><strong>Host a bot</strong><span><?= $botStats['total'] > 0 ? $botStats['total'] . ' bot server(s) provisioned.' : 'Create a server through the Application API.' ?></span></div>
            </li>
        </ol>
    </section>
</div>

<div class="two-col">
    <section class="panel">
        <div class="panel-head">
            <div><h2>Bot fleet</h2><p>Servers provisioned by Nexa Suite.</p></div>
            <a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/bots')) ?>">Manage</a>
        </div>
        <?php if ($recentBots === []): ?>
            <div class="empty-state slim">
                <span class="empty-icon"><?= nexa_icon('bot') ?></span>
                <h3>No bots provisioned</h3>
                <p><?= $panelConfigured ? 'Create one from the bot hosting page.' : 'Connect Pterodactyl first to enable bot hosting.' ?></p>
                <a class="btn btn-quiet btn-sm" href="<?= e(url($panelConfigured ? '/admin/bots' : '/admin/settings')) ?>"><?= $panelConfigured ? 'Create a bot' : 'Open settings' ?></a>
            </div>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($recentBots as $bot): ?>
                    <li>
                        <span class="mini-list-icon"><?= nexa_icon('bot') ?></span>
                        <div class="mini-list-copy">
                            <strong><?= e($bot['name']) ?></strong>
                            <span><?= e($bot['server_identifier']) ?> · <?= e((string) $bot['memory']) ?> MB RAM · <?= e((string) $bot['cpu']) ?>% CPU</span>
                        </div>
                        <span class="badge <?= e(state_class((string) $bot['status'])) ?>"><?= e($bot['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div><h2>Quick commands</h2><p>The same actions from your terminal.</p></div>
            <a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/cli')) ?>">CLI reference</a>
        </div>
        <div class="code-shell flush">
            <pre class="code-block"><code>php bin/nexa license:create --plan=pro --days=365
php bin/nexa key:create --name="Automation"
php bin/nexa bot:create --name="Music Bot" --memory=1024
php bin/nexa extension:list</code></pre>
        </div>
        <div class="panel-foot-split">
            <span class="cell-sub"><?= nexa_icon('shield') ?> Encrypted secrets · hashed keys</span>
            <a href="<?= e(url('/docs#cli')) ?>">Read the guide <?= nexa_icon('arrow') ?></a>
        </div>
    </section>
</div>
