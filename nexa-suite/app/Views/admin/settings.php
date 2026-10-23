<?php
$appKeySet = (string) $values['pterodactyl_application_key'] !== '';
$clientKeySet = (string) $values['pterodactyl_client_key'] !== '';
$defaultSuiteUrl = rtrim(app()->url('/'), '/');
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Configuration</span>
        <h1>Settings</h1>
        <p>Identity, Pterodactyl credentials and the defaults used for bot provisioning.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-quiet" href="<?= e(url('/docs#connect')) ?>"><?= nexa_icon('book') ?> Connection guide</a>
        <form method="post" action="<?= e(url('/admin/settings/test')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit"><?= nexa_icon('refresh') ?> Test connection</button>
        </form>
    </div>
</div>

<div class="split">
    <section class="panel">
        <form class="form-stack spacious" method="post" action="<?= e(url('/admin/settings')) ?>">
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-head">
                    <h2>Suite identity</h2>
                    <p>Shown across the site, console and license records.</p>
                </div>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Site name</span>
                        <input class="input" type="text" name="site_name" maxlength="60" value="<?= e($values['site_name']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field-label">License product</span>
                        <input class="input" type="text" name="license_product" maxlength="60" value="<?= e($values['license_product']) ?>">
                    </label>
                    <label class="field">
                        <span class="field-label">Support email <span class="optional">optional</span></span>
                        <input class="input" type="email" name="support_email" value="<?= e($values['support_email']) ?>" placeholder="support@example.com">
                    </label>
                    <label class="field">
                        <span class="field-label">Suite URL <span class="optional">optional</span></span>
                        <input class="input" type="url" name="suite_url" value="<?= e($values['suite_url']) ?>" placeholder="<?= e($defaultSuiteUrl) ?>">
                        <span class="field-help">Public URL of this suite. Used in links and documentation.</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head">
                    <h2>Pterodactyl panel</h2>
                    <p>Create keys in Pterodactyl admin and in the owner account.</p>
                </div>
                <label class="field">
                    <span class="field-label">Panel URL</span>
                    <input class="input" type="text" name="pterodactyl_url" value="<?= e($values['pterodactyl_url']) ?>" placeholder="https://panel.example.com">
                    <span class="field-help">Base URL only — do not include <code>/api</code>.</span>
                </label>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Application API key <?= $appKeySet ? '<span class="badge is-good">stored</span>' : '' ?></span>
                        <input class="input" type="password" name="pterodactyl_application_key" value="" autocomplete="new-password" placeholder="<?= $appKeySet ? '•••••••• saved · leave blank to keep' : 'ptla_...' ?>">
                        <span class="field-help">Admin → Application API. Needs node, nest, server and user read/write.</span>
                    </label>
                    <label class="field">
                        <span class="field-label">Client API key <?= $clientKeySet ? '<span class="badge is-good">stored</span>' : '' ?></span>
                        <input class="input" type="password" name="pterodactyl_client_key" value="" autocomplete="new-password" placeholder="<?= $clientKeySet ? '•••••••• saved · leave blank to keep' : 'ptlc_...' ?>">
                        <span class="field-help">Owner account → API Credentials. Required for power actions.</span>
                    </label>
                </div>
                <?php if ($appKeySet || $clientKeySet): ?>
                    <div class="checkbox-row">
                        <?php if ($appKeySet): ?>
                            <label class="check-option"><input type="checkbox" name="clear_application_key" value="1"> Clear saved Application API key</label>
                        <?php endif; ?>
                        <?php if ($clientKeySet): ?>
                            <label class="check-option"><input type="checkbox" name="clear_client_key" value="1"> Clear saved Client API key</label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <label class="check-option">
                    <input type="checkbox" name="pterodactyl_verify_tls" value="1" <?= checked(($values['pterodactyl_verify_tls'] ?? '1') !== '0') ?>>
                    Verify the panel TLS certificate
                </label>
                <span class="field-help">Leave verification on for production. Disable only for a trusted panel using a self-signed certificate.</span>
            </div>

            <div class="form-section">
                <div class="form-section-head">
                    <h2>Bot defaults</h2>
                    <p>Prefills for the create form and API requests that omit IDs.</p>
                </div>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Owner user ID</span>
                        <input class="input" type="number" name="pterodactyl_owner_id" min="1" value="<?= e($values['pterodactyl_owner_id']) ?>" placeholder="1">
                        <span class="field-help">Pterodactyl Admin → Users. The user who will own bot servers.</span>
                    </label>
                    <label class="field">
                        <span class="field-label">Default node ID</span>
                        <input class="input" type="number" name="pterodactyl_default_node" min="1" value="<?= e($values['pterodactyl_default_node']) ?>" placeholder="1">
                    </label>
                    <label class="field">
                        <span class="field-label">Default nest ID</span>
                        <input class="input" type="number" name="pterodactyl_default_nest" min="1" value="<?= e($values['pterodactyl_default_nest']) ?>" placeholder="1">
                    </label>
                    <label class="field">
                        <span class="field-label">Default egg ID</span>
                        <input class="input" type="number" name="pterodactyl_default_egg" min="1" value="<?= e($values['pterodactyl_default_egg']) ?>" placeholder="1">
                    </label>
                </div>
                <div class="notice">
                    <?= nexa_icon('server') ?>
                    <div><strong>Allocations</strong><span>Nexa Suite picks the first unassigned allocation on the selected node. Add at least one free port in Pterodactyl before creating bots.</span></div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= nexa_icon('check') ?> Save settings</button>
                <span class="cell-sub">Secrets are encrypted with AES-256-GCM before storage.</span>
            </div>
        </form>
    </section>

    <aside class="settings-side">
        <section class="panel">
            <div class="panel-head"><div><h2>Connection</h2><p>Current integration state.</p></div></div>
            <ul class="status-list">
                <li>
                    <span class="status-dot <?= $panelConfigured ? 'is-good' : 'is-bad' ?>"></span>
                    <div><strong>Application API</strong><span><?= $panelConfigured ? 'Credentials configured' : 'Not configured' ?></span></div>
                </li>
                <li>
                    <span class="status-dot <?= $clientConfigured ? 'is-good' : 'is-neutral' ?>"></span>
                    <div><strong>Client API</strong><span><?= $clientConfigured ? 'Power actions enabled' : 'Power actions disabled' ?></span></div>
                </li>
                <li>
                    <span class="status-dot <?= $values['pterodactyl_default_node'] !== '' && $values['pterodactyl_default_egg'] !== '' ? 'is-good' : 'is-neutral' ?>"></span>
                    <div><strong>Bot defaults</strong><span><?= $values['pterodactyl_default_node'] !== '' && $values['pterodactyl_default_egg'] !== '' ? 'Node and egg defaults set' : 'Optional defaults missing' ?></span></div>
                </li>
            </ul>
            <div class="form-actions">
                <form method="post" action="<?= e(url('/admin/settings/test')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-quiet btn-sm" type="submit"><?= nexa_icon('refresh') ?> Test now</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>Environment</h2><p>PHP and storage checks.</p></div></div>
            <ul class="check-rows">
                <?php foreach ($checks as $check): ?>
                    <li>
                        <span class="check-row-icon <?= $check['ok'] ? 'is-ok' : 'is-bad' ?>"><?= nexa_icon($check['ok'] ? 'check' : 'x') ?></span>
                        <div><strong><?= e($check['label']) ?></strong><span><?= e((string) $check['value']) ?></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>Need help?</h2><p>Connection references.</p></div></div>
            <div class="side-links">
                <a href="<?= e(url('/docs#connect')) ?>"><?= nexa_icon('book') ?> Pterodactyl setup <span><?= nexa_icon('arrow') ?></span></a>
                <a href="<?= e(url('/docs#security')) ?>"><?= nexa_icon('shield') ?> Security checklist <span><?= nexa_icon('arrow') ?></span></a>
                <a href="<?= e(url('/admin/cli')) ?>"><?= nexa_icon('terminal') ?> CLI settings:set <span><?= nexa_icon('arrow') ?></span></a>
            </div>
        </section>
    </aside>
</div>
