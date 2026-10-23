<div class="auth-head">
    <span class="eyebrow">First-run setup</span>
    <h1>Create your control deck.</h1>
    <p>This account becomes the owner of the suite. The setup route locks itself once it exists.</p>
</div>

<?php if (!$writable): ?>
    <div class="alert alert-error">
        <?= nexa_icon('alert', 'alert-icon') ?>
        <span><code>storage/</code> or <code>config/</code> is not writable. Fix permissions before continuing:<br><code>chmod -R ug+rwX storage config</code></span>
    </div>
<?php endif; ?>

<div class="requirement-grid">
    <?php foreach ($requirements as $requirement): ?>
        <div class="requirement <?= $requirement['ok'] ? 'is-ok' : 'is-bad' ?>">
            <?= nexa_icon($requirement['ok'] ? 'check' : 'alert') ?>
            <span><?= e($requirement['label']) ?></span>
            <small><?= e($requirement['value']) ?></small>
        </div>
    <?php endforeach; ?>
</div>

<form class="form-stack" method="post" action="<?= e(url('/setup')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-grid two">
        <label class="field">
            <span class="field-label">Site name</span>
            <input class="input" type="text" name="site_name" maxlength="60" value="<?= e(old('site_name', 'Nexa Suite')) ?>" required>
        </label>
        <label class="field">
            <span class="field-label">Your name</span>
            <input class="input" type="text" name="name" maxlength="80" value="<?= e(old('name')) ?>" placeholder="Nexa Admin" required>
        </label>
    </div>

    <label class="field">
        <span class="field-label">Admin email</span>
        <input class="input" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="admin@example.com" autocomplete="username" required>
    </label>

    <div class="form-grid two">
        <label class="field">
            <span class="field-label">Password</span>
            <input class="input" type="password" name="password" minlength="10" autocomplete="new-password" required>
            <span class="field-help">At least 10 characters.</span>
        </label>
        <label class="field">
            <span class="field-label">Confirm password</span>
            <input class="input" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required>
        </label>
    </div>

    <label class="field">
        <span class="field-label">Suite URL <span class="optional">optional</span></span>
        <input class="input" type="url" name="suite_url" value="<?= e(old('suite_url')) ?>" placeholder="http://panel.example.com:4411">
        <span class="field-help">Used for links in licenses and the panel. Leave blank to derive it from requests.</span>
    </label>

    <button class="btn btn-primary btn-block" type="submit" <?= $writable ? '' : 'disabled' ?>>Create suite <?= nexa_icon('arrow') ?></button>
    <p class="auth-hint centered"><?= nexa_icon('lock') ?> Secrets and Pterodactyl API keys are encrypted with a local app key.</p>
</form>
