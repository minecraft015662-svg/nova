<?php
$apiBase = rtrim(app()->url('/'), '/') . '/api/v1';
$sample = "curl -s {$apiBase}/bots \\\n  -H \"Authorization: Bearer nexa_your_key\"";
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Automation</span>
        <h1>API keys</h1>
        <p>Scoped Bearer keys for bot provisioning, license automation and extension discovery.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-quiet" href="<?= e(url('/docs#api')) ?>"><?= nexa_icon('book') ?> API reference</a>
    </div>
</div>

<?php if ($newKey !== null): ?>
    <div class="key-reveal">
        <span class="key-reveal-icon"><?= nexa_icon('code') ?></span>
        <div class="key-reveal-copy">
            <span class="eyebrow">Copy this key now</span>
            <strong>API key minted</strong>
            <code id="new-api-key"><?= e($newKey) ?></code>
            <span class="key-reveal-help">Send it as <code>Authorization: Bearer …</code>. The full value is not stored and cannot be recovered.</span>
        </div>
        <button class="btn btn-primary" type="button" data-copy-target="new-api-key"><?= nexa_icon('copy') ?> Copy key</button>
    </div>
<?php endif; ?>

<div class="api-base-card">
    <div>
        <span class="eyebrow">API base URL</span>
        <code><?= e($apiBase) ?></code>
    </div>
    <button class="btn btn-quiet btn-sm" type="button" data-copy-value="<?= e($apiBase) ?>"><?= nexa_icon('copy') ?> Copy URL</button>
</div>

<div class="split">
    <section class="panel">
        <div class="panel-head">
            <div><h2>Active credentials</h2><p><?= e((string) count($keys)) ?> key(s) created for this suite.</p></div>
            <span class="panel-chip"><?= nexa_icon('shield') ?> hashed at rest</span>
        </div>

        <?php if ($keys === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= nexa_icon('code') ?></span>
                <h3>No API keys yet</h3>
                <p>Mint a scoped key to provision bots or automate license delivery.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Key</th><th>Scopes</th><th>Last used</th><th>Status</th><th class="align-right">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($keys as $key): ?>
                        <?php
                        $keyScopes = json_decode((string) $key['scopes'], true);
                        $keyScopes = is_array($keyScopes) ? $keyScopes : [];
                        $allScopes = count($keyScopes) >= count($scopes);
                        ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= e($key['name']) ?></span>
                                <span class="cell-sub mono"><?= e($key['key_prefix']) ?></span>
                            </td>
                            <td>
                                <div class="tag-row">
                                    <?php if ($allScopes): ?>
                                        <span class="mini-pill">All scopes</span>
                                    <?php else: ?>
                                        <?php foreach ($keyScopes as $scope): ?>
                                            <span class="mini-pill"><?= e((string) $scope) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="cell-title"><?= e(time_ago($key['last_used_at'] !== null ? (string) $key['last_used_at'] : null)) ?></span><span class="cell-sub">created <?= e(nice_date((string) $key['created_at'])) ?></span></td>
                            <td><span class="badge <?= e(state_class((string) $key['status'])) ?>"><?= e($key['status']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <?php if ($key['status'] === 'active'): ?>
                                        <form method="post" action="<?= e(url('/admin/api-keys/' . $key['id'] . '/revoke')) ?>" data-confirm="Revoke this API key? Any automation using it stops immediately.">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger-ghost btn-xs" type="submit">Revoke</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="cell-sub">Revoked</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="panel-foot-split">
            <span class="cell-sub"><?= nexa_icon('terminal') ?> <code><?= e($sample) ?></code></span>
        </div>
    </section>

    <aside class="panel form-panel">
        <div class="panel-head">
            <div><h2>Create API key</h2><p>Choose only the scopes the job needs.</p></div>
            <span class="panel-icon"><?= nexa_icon('plus') ?></span>
        </div>
        <form class="form-stack" method="post" action="<?= e(url('/admin/api-keys')) ?>">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label">Key name</span>
                <input class="input" type="text" name="name" maxlength="80" placeholder="Billing automation" required>
            </label>
            <div class="field">
                <span class="field-label">Scopes</span>
                <div class="scope-list">
                    <?php foreach ($scopes as $scope): ?>
                        <label class="scope-option">
                            <input type="checkbox" name="scopes[]" value="<?= e($scope) ?>" checked>
                            <span><?= e($scope) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span class="field-help">Write scopes include their read counterpart. Use fewer scopes for production automation.</span>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= nexa_icon('key') ?> Mint API key</button>
        </form>
        <div class="form-note">
            <?= nexa_icon('lock') ?>
            <span>Only a SHA-256 hash is stored. Copy the key immediately after creation.</span>
        </div>
    </aside>
</div>
