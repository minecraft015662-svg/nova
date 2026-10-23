<div class="auth-head">
    <span class="eyebrow">Console access</span>
    <h1>Welcome back.</h1>
    <p>Sign in with the administrator account for this Nexa Suite instance.</p>
</div>

<form class="form-stack" method="post" action="<?= e(url('/login')) ?>" autocomplete="on">
    <?= csrf_field() ?>
    <label class="field">
        <span class="field-label">Email address</span>
        <input class="input" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="admin@example.com" autocomplete="username" required autofocus>
    </label>
    <label class="field">
        <span class="field-label">Password</span>
        <input class="input" type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
    </label>
    <button class="btn btn-primary btn-block" type="submit">Sign in <?= nexa_icon('arrow') ?></button>
</form>

<div class="auth-divider"><span>Nexa control deck</span></div>

<p class="auth-hint">
    <?= nexa_icon('shield') ?>
    <?php if (app()->installed()): ?>
        Sessions expire with the browser. Lost access? Create an admin with <code>php bin/nexa user:create</code>.
    <?php else: ?>
        First run? <a href="<?= e(url('/setup')) ?>">Set up Nexa Suite</a>.
    <?php endif; ?>
</p>
