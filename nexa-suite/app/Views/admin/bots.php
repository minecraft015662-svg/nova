<?php
$defaultNode = (string) ($defaults['pterodactyl_default_node'] ?? '');
$defaultNest = (string) ($defaults['pterodactyl_default_nest'] ?? '');
$defaultEgg = (string) ($defaults['pterodactyl_default_egg'] ?? '');
$defaultOwner = (string) ($defaults['pterodactyl_owner_id'] ?? '');
$powerEnabled = $clientConfigured;
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Game infrastructure</span>
        <h1>Bot hosting</h1>
        <p>Provision Pterodactyl servers with the Application API and control them with a Client API key.</p>
    </div>
    <div class="page-actions">
        <span class="page-chip"><?= nexa_icon('bot') ?> <?= e((string) $stats['total']) ?> total</span>
        <?php if (!$panelConfigured): ?>
            <a class="btn btn-primary" href="<?= e(url('/admin/settings')) ?>">Connect panel <?= nexa_icon('arrow') ?></a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$panelConfigured): ?>
    <div class="connect-banner">
        <span class="connect-icon"><?= nexa_icon('server') ?></span>
        <div>
            <strong>Pterodactyl is not connected</strong>
            <span>Add the panel URL, Application API key and an owner user in Settings to enable bot provisioning.</span>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/settings')) ?>">Open settings <?= nexa_icon('arrow') ?></a>
    </div>
<?php elseif (!$clientConfigured): ?>
    <div class="notice warn">
        <?= nexa_icon('alert') ?>
        <div>
            <strong>Power controls are locked</strong>
            <span>Add a Client API key for the server owner in Settings to enable start, stop and restart. Server creation and deletion work with the Application API.</span>
        </div>
    </div>
<?php endif; ?>

<?php if ($panelError !== null): ?>
    <div class="alert alert-error">
        <?= nexa_icon('alert', 'alert-icon') ?>
        <span><strong>Pterodactyl API error:</strong> <?= e($panelError) ?> Use numeric IDs in the form below, or fix the panel connection.</span>
    </div>
<?php endif; ?>

<div class="split">
    <section class="panel">
        <div class="panel-head">
            <div><h2>Provisioned bots</h2><p>Servers created by Nexa Suite.</p></div>
            <span class="panel-chip"><?= nexa_icon('activity') ?> <?= e((string) $stats['running']) ?> running</span>
        </div>

        <?php if ($bots === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= nexa_icon('bot') ?></span>
                <h3>No bot servers yet</h3>
                <p><?= $panelConfigured ? 'Create one with the form — Nexa picks a free allocation automatically.' : 'Connect Pterodactyl first, then create a server in seconds.' ?></p>
                <?php if (!$panelConfigured): ?><a class="btn btn-primary btn-sm" href="<?= e(url('/admin/settings')) ?>">Open settings</a><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bot-list">
                <?php foreach ($bots as $bot): ?>
                    <article class="bot-card">
                        <div class="bot-top">
                            <span class="bot-glyph"><?= nexa_icon('bot') ?></span>
                            <div class="bot-title">
                                <h3><?= e($bot['name']) ?></h3>
                                <span><code><?= e($bot['server_identifier']) ?></code> · server #<?= e((string) $bot['server_id']) ?> · node <?= e((string) $bot['node_id']) ?> · egg <?= e((string) $bot['egg_id']) ?></span>
                            </div>
                            <span class="badge <?= e(state_class((string) $bot['status'])) ?>"><?= e($bot['status']) ?></span>
                        </div>
                        <div class="bot-metrics">
                            <div><span>Memory</span><strong><?= e((string) $bot['memory']) ?> MB</strong></div>
                            <div><span>Disk</span><strong><?= e((string) $bot['disk']) ?> MB</strong></div>
                            <div><span>CPU</span><strong><?= e((string) $bot['cpu']) ?>%</strong></div>
                            <div><span>Created</span><strong><?= e(nice_date((string) $bot['created_at'])) ?></strong></div>
                        </div>
                        <div class="bot-foot">
                            <div class="power-actions">
                                <form method="post" action="<?= e(url('/admin/bots/' . $bot['id'] . '/power')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="signal" value="start">
                                    <button class="btn btn-quiet btn-xs" type="submit" <?= $powerEnabled ? '' : 'disabled' ?> title="Start"><?= nexa_icon('play') ?> Start</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/bots/' . $bot['id'] . '/power')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="signal" value="restart">
                                    <button class="btn btn-quiet btn-xs" type="submit" <?= $powerEnabled ? '' : 'disabled' ?> title="Restart"><?= nexa_icon('restart') ?> Restart</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/bots/' . $bot['id'] . '/power')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="signal" value="stop">
                                    <button class="btn btn-quiet btn-xs" type="submit" <?= $powerEnabled ? '' : 'disabled' ?> title="Stop"><?= nexa_icon('stop') ?> Stop</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/bots/' . $bot['id'] . '/power')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="signal" value="kill">
                                    <button class="btn btn-danger-ghost btn-xs" type="submit" <?= $powerEnabled ? '' : 'disabled' ?> title="Force stop">Kill</button>
                                </form>
                            </div>
                            <form method="post" action="<?= e(url('/admin/bots/' . $bot['id'] . '/delete')) ?>" data-confirm="Delete this server from Pterodactyl permanently? This also removes its files and backups.">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger-ghost btn-xs" type="submit"><?= nexa_icon('trash') ?> Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="panel form-panel">
        <div class="panel-head">
            <div><h2>Create bot server</h2><p>Starts on completion via Pterodactyl.</p></div>
            <span class="panel-icon"><?= nexa_icon('plus') ?></span>
        </div>

        <form class="form-stack" method="post" action="<?= e(url('/admin/bots')) ?>">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label">Server name</span>
                <input class="input" type="text" name="name" maxlength="60" placeholder="Music Bot" value="<?= e(old('name')) ?>" required>
            </label>

            <?php if ($nodes !== []): ?>
                <label class="field">
                    <span class="field-label">Node</span>
                    <select class="input" name="node_id">
                        <?php foreach ($nodes as $node): ?>
                            <option value="<?= e((string) $node['id']) ?>" <?= selected(old('node_id', $defaultNode), $node['id']) ?>>
                                <?= e($node['name']) ?> · #<?= e((string) $node['id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php else: ?>
                <label class="field">
                    <span class="field-label">Node ID</span>
                    <input class="input" type="number" name="node_id" min="1" value="<?= e(old('node_id', $defaultNode)) ?>" required>
                </label>
            <?php endif; ?>

            <?php if ($nests !== []): ?>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Nest</span>
                        <select class="input" name="nest_id" id="nest-select" data-nest-select>
                            <?php foreach ($nests as $nest): ?>
                                <option value="<?= e((string) $nest['id']) ?>" <?= selected(old('nest_id', $defaultNest), $nest['id']) ?>>
                                    <?= e($nest['name']) ?> · #<?= e((string) $nest['id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">Egg</span>
                        <select class="input" name="egg_id" id="egg-select" data-egg-select>
                            <?php foreach ($nests as $nest): ?>
                                <?php foreach ($eggsByNest[(int) $nest['id']] ?? [] as $egg): ?>
                                    <option
                                        value="<?= e((string) $egg['id']) ?>"
                                        data-nest="<?= e((string) $nest['id']) ?>"
                                        <?= selected(old('egg_id', $defaultEgg), $egg['id']) ?>>
                                        <?= e($egg['name']) ?> · #<?= e((string) $egg['id']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            <?php else: ?>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Nest ID</span>
                        <input class="input" type="number" name="nest_id" min="1" value="<?= e(old('nest_id', $defaultNest)) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field-label">Egg ID</span>
                        <input class="input" type="number" name="egg_id" min="1" value="<?= e(old('egg_id', $defaultEgg)) ?>" required>
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-grid three">
                <label class="field">
                    <span class="field-label">Memory MB</span>
                    <input class="input" type="number" name="memory" min="256" max="131072" step="128" value="<?= e(old('memory', '1024')) ?>" required>
                </label>
                <label class="field">
                    <span class="field-label">Disk MB</span>
                    <input class="input" type="number" name="disk" min="256" max="524288" step="128" value="<?= e(old('disk', '4096')) ?>" required>
                </label>
                <label class="field">
                    <span class="field-label">CPU %</span>
                    <input class="input" type="number" name="cpu" min="10" max="3200" step="10" value="<?= e(old('cpu', '100')) ?>" required>
                </label>
            </div>

            <label class="field">
                <span class="field-label">Owner user ID <span class="optional">optional</span></span>
                <input class="input" type="number" name="owner_id" min="1" value="<?= e(old('owner_id', $defaultOwner)) ?>" placeholder="Uses the default owner from settings">
            </label>

            <button class="btn btn-primary btn-block" type="submit" <?= $panelConfigured ? '' : 'disabled' ?>><?= nexa_icon('bot') ?> Create server</button>
        </form>
        <div class="form-note">
            <?= nexa_icon('server') ?>
            <span>Nexa picks the first free allocation on the node. The selected egg supplies the Docker image, startup command and variable defaults.</span>
        </div>
    </aside>
</div>
