<?php
$recentActivations = [];
$prefixes = [];
foreach ($licenses as $licenseRow) {
    $prefixes[(int) $licenseRow['id']] = (string) $licenseRow['key_prefix'];
}
foreach ($activations as $licenseId => $rows) {
    foreach ($rows as $row) {
        $row['license_id'] = $licenseId;
        $recentActivations[] = $row;
    }
}
usort($recentActivations, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);
$recentActivations = array_slice($recentActivations, 0, 12);
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Licensing</span>
        <h1>License keys</h1>
        <p>Issue, activate, revoke and audit keys for <?= e((string) app()->settings()->get('license_product', 'Nexa Theme')) ?>.</p>
    </div>
    <div class="page-actions">
        <span class="page-chip"><?= nexa_icon('activity') ?> <?= e((string) $totalActivations) ?> activations</span>
        <a class="btn btn-quiet" href="<?= e(url('/docs#licenses')) ?>"><?= nexa_icon('book') ?> License guide</a>
    </div>
</div>

<?php if ($newKey !== null): ?>
    <div class="key-reveal">
        <span class="key-reveal-icon"><?= nexa_icon('key') ?></span>
        <div class="key-reveal-copy">
            <span class="eyebrow">Copy this key now</span>
            <strong><?= e(license_plan((string) $newKey['plan'])['name']) ?> license created</strong>
            <code id="new-license-key"><?= e($newKey['key']) ?></code>
            <span class="key-reveal-help">Only the SHA-256 hash is stored. This key will not be shown again — regenerate it if lost.</span>
        </div>
        <button class="btn btn-primary" type="button" data-copy-target="new-license-key"><?= nexa_icon('copy') ?> Copy key</button>
    </div>
<?php endif; ?>

<div class="split">
    <section class="panel">
        <div class="panel-head">
            <div><h2>All licenses</h2><p><?= e((string) count($licenses)) ?> key(s)<?= $search !== '' ? ' matching “' . e($search) . '”' : '' ?>.</p></div>
            <form class="search-form" method="get" action="<?= e(url('/admin/licenses')) ?>">
                <?= nexa_icon('activity') ?>
                <input class="input input-sm" type="search" name="q" value="<?= e($search) ?>" placeholder="Search prefix, email, note…">
                <button class="btn btn-quiet btn-sm" type="submit">Search</button>
            </form>
        </div>

        <?php if ($licenses === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><?= nexa_icon('key') ?></span>
                <h3>No licenses found</h3>
                <p><?= $search !== '' ? 'Try a different search term.' : 'Use the form to issue your first license key.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>License</th><th>Plan</th><th>Installs</th><th>Expires</th><th>Status</th><th class="align-right">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($licenses as $license): ?>
                        <?php
                        $expired = $license['expires_at'] !== null && strtotime((string) $license['expires_at']) < time();
                        $left = days_left($license['expires_at'] === null ? null : (string) $license['expires_at']);
                        ?>
                        <tr>
                            <td>
                                <span class="cell-title mono"><?= e($license['key_prefix']) ?></span>
                                <span class="cell-sub"><?= e($license['email'] !== '' ? $license['email'] : 'No customer email') ?><?= $license['note'] !== '' ? ' · ' . e($license['note']) : '' ?></span>
                            </td>
                            <td><span class="mini-pill"><?= e(license_plan((string) $license['plan'])['name']) ?></span></td>
                            <td><span class="cell-title"><?= e((string) $license['activations']) ?> / <?= e((string) $license['max_activations']) ?></span><span class="cell-sub">domains</span></td>
                            <td>
                                <span class="cell-title"><?= e($license['expires_at'] === null ? 'Lifetime' : nice_date((string) $license['expires_at'])) ?></span>
                                <span class="cell-sub"><?= e($license['expires_at'] === null ? 'never expires' : ($expired ? 'expired' : ($left !== null ? $left . ' days left' : ''))) ?></span>
                            </td>
                            <td><span class="badge <?= e(state_class($expired ? 'expired' : (string) $license['status'])) ?>"><?= e($expired && $license['status'] === 'active' ? 'expired' : $license['status']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <?php if ($license['status'] === 'active'): ?>
                                        <form method="post" action="<?= e(url('/admin/licenses/' . $license['id'] . '/revoke')) ?>" data-confirm="Revoke this license? Every install using it will fail validation.">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger-ghost btn-xs" type="submit">Revoke</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/admin/licenses/' . $license['id'] . '/restore')) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-quiet btn-xs" type="submit">Restore</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('/admin/licenses/' . $license['id'] . '/regenerate')) ?>" data-confirm="Generate a new key? The current key will stop working immediately.">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-quiet btn-xs" type="submit">Regenerate</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <aside class="panel form-panel">
        <div class="panel-head">
            <div><h2>Create license</h2><p>Keys are generated locally.</p></div>
            <span class="panel-icon"><?= nexa_icon('plus') ?></span>
        </div>
        <form class="form-stack" method="post" action="<?= e(url('/admin/licenses')) ?>">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label">Plan</span>
                <select class="input" name="plan">
                    <?php foreach ($plans as $key => $plan): ?>
                        <option value="<?= e($key) ?>" <?= selected(old('plan', 'pro'), $key) ?>><?= e($plan['name']) ?> — <?= e((string) $plan['activations']) ?> install<?= ((int) $plan['activations']) === 1 ? '' : 's' ?></option>
                    <?php endforeach; ?>
                    <option value="custom" <?= selected(old('plan'), 'custom') ?>>Custom</option>
                </select>
            </label>
            <div class="form-grid two">
                <label class="field">
                    <span class="field-label">Duration</span>
                    <select class="input" name="duration">
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365" selected>1 year</option>
                        <option value="lifetime">Lifetime</option>
                        <option value="custom">Custom days</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Custom days</span>
                    <input class="input" type="number" name="custom_days" min="1" max="36500" value="<?= e(old('custom_days', '365')) ?>">
                </label>
            </div>
            <label class="field">
                <span class="field-label">Activation limit</span>
                <input class="input" type="number" name="max_activations" min="1" max="1000" value="<?= e(old('max_activations', '3')) ?>">
                <span class="field-help">How many domain/instance pairs may activate one key.</span>
            </label>
            <label class="field">
                <span class="field-label">Customer email <span class="optional">optional</span></span>
                <input class="input" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="customer@example.com">
            </label>
            <label class="field">
                <span class="field-label">Internal note <span class="optional">optional</span></span>
                <textarea class="input" name="note" rows="2" maxlength="500" placeholder="Order #1042 · Discord renewal"><?= e(old('note')) ?></textarea>
            </label>
            <button class="btn btn-primary btn-block" type="submit"><?= nexa_icon('plus') ?> Generate license</button>
        </form>
        <div class="form-note">
            <?= nexa_icon('lock') ?>
            <span>Keys are hashed with SHA-256. Delivery is manual: copy the key at creation or use the API/CLI.</span>
        </div>
    </aside>
</div>

<section class="panel">
    <div class="panel-head">
        <div><h2>Recent activations</h2><p>Domain and instance bindings from the license API.</p></div>
        <span class="panel-chip"><?= nexa_icon('activity') ?> latest 12</span>
    </div>
    <?php if ($recentActivations === []): ?>
        <div class="empty-state slim">
            <span class="empty-icon"><?= nexa_icon('activity') ?></span>
            <h3>Nothing activated yet</h3>
            <p>Call <code>POST /api/v1/licenses/activate</code> from a licensed panel to bind a domain.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>License</th><th>Domain</th><th>Instance</th><th>Activated</th><th>Last seen</th></tr></thead>
                <tbody>
                <?php foreach ($recentActivations as $activation): ?>
                    <tr>
                        <td class="mono"><?= e($prefixes[(int) $activation['license_id']] ?? '—') ?></td>
                        <td><span class="cell-title"><?= e($activation['domain']) ?></span></td>
                        <td><?= e($activation['instance_id'] !== '' ? $activation['instance_id'] : 'default') ?></td>
                        <td><?= e(nice_datetime((string) $activation['created_at'])) ?></td>
                        <td><?= e(time_ago((string) $activation['last_seen_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
