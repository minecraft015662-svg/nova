<div class="page-head">
    <div>
        <span class="eyebrow">Profile</span>
        <h1>Your account</h1>
        <p>Update your display name, sign-in email and password.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <form class="form-stack spacious" method="post" action="<?= e(url('/admin/account')) ?>">
            <?= csrf_field() ?>
            <div class="form-section">
                <div class="form-section-head">
                    <h2>Identity</h2>
                    <p>Used for console access and license audit records.</p>
                </div>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">Display name</span>
                        <input class="input" type="text" name="name" maxlength="80" value="<?= e($user['name']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field-label">Email address</span>
                        <input class="input" type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head">
                    <h2>Password</h2>
                    <p>Leave the new password fields blank to keep the current one.</p>
                </div>
                <label class="field">
                    <span class="field-label">Current password</span>
                    <input class="input" type="password" name="current_password" autocomplete="current-password" required>
                    <span class="field-help">Required for every change on this page.</span>
                </label>
                <div class="form-grid two">
                    <label class="field">
                        <span class="field-label">New password</span>
                        <input class="input" type="password" name="password" minlength="10" autocomplete="new-password" placeholder="Leave blank to keep">
                    </label>
                    <label class="field">
                        <span class="field-label">Confirm new password</span>
                        <input class="input" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" placeholder="Repeat new password">
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= nexa_icon('check') ?> Save account</button>
            </div>
        </form>
    </section>

    <aside class="settings-side">
        <section class="panel">
            <div class="panel-head"><div><h2>Account details</h2><p>Read-only session information.</p></div></div>
            <dl class="detail-list">
                <div><dt>Role</dt><dd><span class="badge is-good"><?= e($user['role']) ?></span></dd></div>
                <div><dt>Account ID</dt><dd>#<?= e((string) $user['id']) ?></dd></div>
                <div><dt>Created</dt><dd><?= e(nice_datetime((string) $user['created_at'])) ?></dd></div>
                <div><dt>Last session</dt><dd>Current browser</dd></div>
            </dl>
        </section>
        <section class="panel">
            <div class="panel-head"><div><h2>Security notes</h2><p>Keep the console locked down.</p></div></div>
            <ul class="doc-list tight">
                <li>Sign-in attempts are rate limited per IP.</li>
                <li>Changing your password keeps this session active; other sessions are not tracked.</li>
                <li>Use a unique password of 10+ characters with a manager.</li>
                <li>Serve the suite over HTTPS so session cookies receive the Secure flag.</li>
            </ul>
        </section>
        <section class="panel">
            <div class="panel-head"><div><h2>Recovery</h2><p>If you get locked out.</p></div></div>
            <div class="code-shell flush">
                <pre class="code-block"><code>php bin/nexa user:create \
  --name="Recovery" \
  --email="admin@example.com" \
  --password="new-long-password"</code></pre>
            </div>
        </section>
    </aside>
</div>
