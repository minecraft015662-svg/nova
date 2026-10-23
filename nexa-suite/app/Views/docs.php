<?php
$code = function (string $id, string $language, string $source): void {
    ?>
    <div class="code-shell">
        <div class="code-head">
            <span class="code-lang"><?= e($language) ?></span>
            <button class="copy-btn" type="button" data-copy-target="<?= e($id) ?>"><?= nexa_icon('copy') ?> Copy</button>
        </div>
        <pre class="code-block" id="<?= e($id) ?>"><code><?= e($source) ?></code></pre>
    </div>
    <?php
};

$install = <<<'CODE'
cd nexa-suite
php bin/nexa install
php bin/nexa serve --port=4411
# open http://127.0.0.1:4411 and sign in
CODE;

$webSetup = <<<'CODE'
# Point your web server document root at:
/var/www/nexa-suite/public

# Open in a browser:
https://nexa.example.com/setup
# The first-run wizard creates storage/nexa.sqlite and the admin account.
CODE;

$validate = <<<'CODE'
curl -s http://your-host:4411/api/v1/licenses/validate \
  -H "Content-Type: application/json" \
  -d '{
    "license": "NEXA-7K2M9-QP4RX-W8TNL-H3VJD",
    "domain": "panel.example.com"
  }'
CODE;

$activate = <<<'CODE'
curl -s http://your-host:4411/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{
    "license": "NEXA-7K2M9-QP4RX-W8TNL-H3VJD",
    "domain": "panel.example.com",
    "instance": "main-panel"
  }'
CODE;

$botCreate = <<<'CODE'
curl -s http://your-host:4411/api/v1/bots \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Music Bot",
    "memory": 1024,
    "disk": 4096,
    "cpu": 100
  }'
CODE;

$botPower = <<<'CODE'
# power signals: start, stop, restart, kill
curl -s -X POST http://your-host:4411/api/v1/bots/4/power \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"signal":"restart"}'

curl -s -X DELETE http://your-host:4411/api/v1/bots/4 \
  -H "Authorization: Bearer nexa_your_api_key"
CODE;

$cli = <<<'CODE'
php bin/nexa license:create --plan=pro --days=365 --activations=3 --email=customer@example.com
php bin/nexa license:list
php bin/nexa license:revoke 12
php bin/nexa license:validate NEXA-XXXXX-XXXXX-XXXXX-XXXXX --domain=panel.example.com

php bin/nexa key:create --name="Billing automation" --scopes=bots:write,licenses:read
php bin/nexa key:list

php bin/nexa extension:list
php bin/nexa extension:enable discord-auth
php bin/nexa extension:add --name="My Addon" --category=Community --url=https://example.com/addon

php bin/nexa bot:create --name="Music Bot" --memory=1024 --disk=4096 --cpu=100
php bin/nexa bot:list
php bin/nexa bot:start 4
CODE;

$cliInstall = <<<'CODE'
php bin/nexa install --site="Nexa Suite" --url="https://nexa.example.com" \
  --name="Admin" --email="admin@example.com" --password="a-long-password"

php bin/nexa settings:show
php bin/nexa settings:set pterodactyl_url=https://panel.example.com
php bin/nexa settings:set pterodactyl_application_key=ptla_xxxxxxxx
php bin/nexa settings:set pterodactyl_client_key=ptlc_xxxxxxxx
php bin/nexa settings:set pterodactyl_owner_id=1
php bin/nexa settings:set pterodactyl_default_node=1
php bin/nexa settings:set pterodactyl_default_nest=1
php bin/nexa settings:set pterodactyl_default_egg=1
CODE;

$nginx = <<<'CODE'
server {
    listen 80;
    server_name nexa.example.com;
    root /var/www/nexa-suite/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }

    location ~* \.(css|js|svg|png|jpg|woff2)$ {
        expires 7d;
        access_log off;
    }
}
CODE;

$systemd = <<<'CODE'
[Unit]
Description=Nexa Suite
After=network.target

[Service]
Type=simple
User=nexa
Group=nexa
WorkingDirectory=/var/www/nexa-suite
ExecStart=/usr/bin/php /var/www/nexa-suite/bin/nexa serve --host=127.0.0.1 --port=4411
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
CODE;

$themeSteps = <<<'CODE'
1. Pterodactyl Admin → Extensions → install Blueprint (beta-2026-06).
2. Extensions → Import → upload nexa-theme.blueprint.
3. Enable "Nexa" and reload the panel.
4. Open the Nexa Editor from the panel sidebar (root admin only).
5. General → Nexa Suite URL → http://your-host:4411 → Save Changes.
CODE;
?>
<section class="docs-hero container">
    <span class="eyebrow">Documentation</span>
    <h1>Ship the whole stack<br><span class="text-gradient">without guesswork</span>.</h1>
    <p>Install Nexa Suite, connect Pterodactyl, issue your first license and host a bot — with copy-ready commands for every step.</p>
    <div class="docs-hero-meta">
        <span><?= nexa_icon('terminal') ?> PHP 8.1+</span>
        <span><?= nexa_icon('clock') ?> 10 minute setup</span>
        <span><?= nexa_icon('shield') ?> Self-hosted</span>
    </div>
</section>

<div class="docs-layout container">
    <aside class="docs-toc">
        <span class="toc-label">On this page</span>
        <a href="#quick-start">Quick start</a>
        <a href="#theme">Theme + Editor</a>
        <a href="#connect">Connect Pterodactyl</a>
        <a href="#licenses">License flow</a>
        <a href="#api">HTTP API</a>
        <a href="#cli">CLI reference</a>
        <a href="#deploy">Deploy on 4411</a>
        <a href="#security">Security &amp; backups</a>
        <a href="#troubleshooting">Troubleshooting</a>
    </aside>

    <article class="docs-body">
        <section class="doc-section" id="quick-start">
            <span class="eyebrow">01 · Install</span>
            <h2>Quick start</h2>
            <p>Nexa Suite is a plain PHP application. It needs <strong>PHP 8.1+</strong>, the <code>pdo_sqlite</code>, <code>openssl</code> and ideally <code>curl</code> extensions, plus write access to <code>storage/</code> and <code>config/</code>.</p>
            <h3>Option A — CLI install</h3>
            <?php $code('doc-install', 'bash', $install); ?>
            <p>The install command asks for a site name, admin name, email and password, creates the SQLite database and seeds the extension catalog.</p>
            <h3>Option B — first-run web setup</h3>
            <?php $code('doc-websetup', 'bash', $webSetup); ?>
            <p>Open <code>/setup</code> once. After the first admin exists, the setup route closes and the console lives at <code>/admin</code>.</p>
            <div class="notice">
                <?= nexa_icon('shield') ?>
                <div><strong>Production tip</strong><span>Point the document root at <code>public/</code>, never at the suite root. The <code>storage/</code> and <code>config/</code> directories must not be web-accessible.</span></div>
            </div>
        </section>

        <section class="doc-section" id="theme">
            <span class="eyebrow">02 · Theme</span>
            <h2>Install Nexa Theme + Nexa Editor</h2>
            <p>The theme is a Blueprint extension for Pterodactyl 1.x. It ships as a single <code>.blueprint</code> file with the admin-only editor included.</p>
            <?php $code('doc-theme', 'text', $themeSteps); ?>
            <h3>Editor highlights</h3>
            <ul class="doc-list">
                <li><strong>General</strong> — brand name, tagline, panel logo (dark and light), logo size, hide title, support Discord/website, Nexa Suite URL.</li>
                <li><strong>Look</strong> — presets, accent, radius, glass blur, sidebar position, density, effects and animation switches.</li>
                <li><strong>Background</strong> — built-in artwork, solid colour or a custom image/GIF URL. Paste <code>https://.../background.gif</code> and save.</li>
                <li><strong>Code editor</strong> — every user can switch the panel file editor between Nexa themes and toggle word wrap; <kbd>Ctrl</kbd>/<kbd>Cmd</kbd> + <kbd>S</kbd> saves.</li>
            </ul>
            <p>Only root administrators can open the editor. Saved settings apply to every panel user; per-user tweaks (privacy, compact density, animations) stay in that browser.</p>
        </section>

        <section class="doc-section" id="connect">
            <span class="eyebrow">03 · Integration</span>
            <h2>Connect your Pterodactyl panel</h2>
            <p>Open <strong>Nexa Suite → Settings</strong> and fill in the connection block. You need two credentials:</p>
            <ul class="doc-list">
                <li><strong>Application API key</strong> — Pterodactyl Admin → Application API → create a key with read/write access to nodes, servers, nests and users. Used for creating and deleting bot servers.</li>
                <li><strong>Client API key</strong> — log in as the server owner → Account → API Credentials → create a key. Used for start/stop/restart and live state.</li>
                <li><strong>Owner user ID</strong> — Admin → Users → open the user; the ID is in the URL (<code>/admin/users/view/1</code>).</li>
                <li><strong>Default node / nest / egg</strong> — found in Admin → Nodes and Admin → Nests. These prefills keep bot provisioning one-click.</li>
            </ul>
            <p>Use <strong>Test connection</strong> on the Settings page. If your panel uses a self-signed certificate, either install a valid certificate or disable TLS verification for the connection (not recommended for public deployments).</p>
            <?php $code('doc-cli-connect', 'bash', $cliInstall); ?>
        </section>

        <section class="doc-section" id="licenses">
            <span class="eyebrow">04 · Licensing</span>
            <h2>License flow</h2>
            <p>Keys are generated in the console, by the CLI, or through <code>POST /api/v1/licenses</code>. A key is displayed <strong>once</strong>; the database stores only its SHA-256 hash and a short prefix for identification.</p>
            <ol class="doc-steps">
                <li><strong>Create</strong> — choose plan, duration, activation limit and optional customer email.</li>
                <li><strong>Deliver</strong> — copy the key immediately. Use <em>Regenerate</em> if it is lost; the old key stops working.</li>
                <li><strong>Activate</strong> — call the activate endpoint from the licensed panel with its domain. Each domain/instance pair counts toward the limit.</li>
                <li><strong>Validate</strong> — call the validate endpoint on a schedule or at login. It returns plan, expiry and activation state.</li>
                <li><strong>Revoke</strong> — instantly invalidates validation and activation. Activation records are preserved for audit.</li>
            </ol>
            <?php $code('doc-validate', 'bash', $validate); ?>
            <?php $code('doc-activate', 'bash', $activate); ?>
        </section>

        <section class="doc-section" id="api">
            <span class="eyebrow">05 · API</span>
            <h2>HTTP API</h2>
            <p>Base URL: <code><?= e($baseUrl) ?></code>. Public license endpoints are rate-limited per IP. Everything else requires an API key created under <strong>API keys</strong>.</p>
            <div class="api-table-wrap">
                <table class="data-table compact">
                    <thead><tr><th>Method</th><th>Endpoint</th><th>Scope</th></tr></thead>
                    <tbody>
                        <tr><td><span class="method get">GET</span></td><td><code>/api/v1/health</code></td><td>Public</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/licenses/validate</code></td><td>Public</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/licenses/activate</code></td><td>Public</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/licenses/deactivate</code></td><td>Public</td></tr>
                        <tr><td><span class="method get">GET</span></td><td><code>/api/v1/licenses</code></td><td>licenses:read</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/licenses</code></td><td>licenses:write</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/licenses/{id}/revoke</code></td><td>licenses:write</td></tr>
                        <tr><td><span class="method get">GET</span></td><td><code>/api/v1/extensions</code></td><td>extensions:read</td></tr>
                        <tr><td><span class="method get">GET</span></td><td><code>/api/v1/bots</code></td><td>bots:read</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/bots</code></td><td>bots:write</td></tr>
                        <tr><td><span class="method get">GET</span></td><td><code>/api/v1/bots/{id}</code></td><td>bots:read</td></tr>
                        <tr><td><span class="method post">POST</span></td><td><code>/api/v1/bots/{id}/power</code></td><td>bots:write</td></tr>
                        <tr><td><span class="method delete">DELETE</span></td><td><code>/api/v1/bots/{id}</code></td><td>bots:write</td></tr>
                    </tbody>
                </table>
            </div>
            <h3>Authentication</h3>
            <p>Send <code>Authorization: Bearer nexa_...</code> (or <code>X-Nexa-Key</code>). Keys are hashed at rest and can be revoked instantly. <code>bots:write</code> implies <code>bots:read</code>; <code>*</code> grants every scope.</p>
            <h3>Host a bot</h3>
            <p>Defaults for node, nest, egg and owner come from Settings. The suite picks the first free allocation on the node automatically. Override <code>node_id</code>, <code>nest_id</code>, <code>egg_id</code>, <code>allocation_id</code> or <code>environment</code> per request.</p>
            <?php $code('doc-bot-create', 'bash', $botCreate); ?>
            <?php $code('doc-bot-power', 'bash', $botPower); ?>
            <h3>Response shape</h3>
            <?php $code('doc-response', 'json', "{\n  \"ok\": true,\n  \"data\": { ... },\n  \"meta\": { \"count\": 1 }\n}\n\n{\n  \"ok\": false,\n  \"error\": \"This license has expired.\",\n  \"code\": \"expired\"\n}"); ?>
        </section>

        <section class="doc-section" id="cli">
            <span class="eyebrow">06 · CLI</span>
            <h2>CLI reference</h2>
            <p>Run commands from the suite root. The CLI uses the same database, services and encryption as the web console.</p>
            <?php $code('doc-cli', 'bash', $cli); ?>
            <div class="api-table-wrap">
                <table class="data-table compact">
                    <thead><tr><th>Command</th><th>What it does</th></tr></thead>
                    <tbody>
                        <tr><td><code>install</code></td><td>Interactive installer or flag-driven setup</td></tr>
                        <tr><td><code>serve</code></td><td>Start the PHP development server (default 4411)</td></tr>
                        <tr><td><code>user:create</code></td><td>Create an additional console admin</td></tr>
                        <tr><td><code>license:create | list | revoke | regenerate | validate</code></td><td>Manage license keys and activations</td></tr>
                        <tr><td><code>key:create | list | revoke</code></td><td>Manage scoped API keys</td></tr>
                        <tr><td><code>extension:list | enable | disable | add</code></td><td>Manage the extension catalog</td></tr>
                        <tr><td><code>bot:create | list | start | stop | restart | delete</code></td><td>Provision and power Pterodactyl bot servers</td></tr>
                        <tr><td><code>settings:show | set</code></td><td>Read or update configuration (secrets encrypted)</td></tr>
                    </tbody>
                </table>
            </div>
            <p>Add <code>--json</code> to supported listing and creation commands for machine-readable output. Run <code>php bin/nexa help</code> for the full command map.</p>
        </section>

        <section class="doc-section" id="deploy">
            <span class="eyebrow">07 · Deploy</span>
            <h2>Run on port 4411</h2>
            <p>The development server listens on <code>127.0.0.1:4411</code> by default:</p>
            <?php $code('doc-serve', 'bash', "php bin/nexa serve --host=0.0.0.0 --port=4411"); ?>
            <p>For production, put Nginx or Caddy in front of the app and let PHP-FPM handle requests. Keep the suite in its own directory, separate from the Pterodactyl panel.</p>
            <?php $code('doc-nginx', 'nginx', $nginx); ?>
            <h3>Run as a systemd service</h3>
            <p>If you expose the built-in server directly (small private installs), keep it bound to localhost and proxy through a TLS web server:</p>
            <?php $code('doc-systemd', 'ini', $systemd); ?>
            <p>Then <code>sudo systemctl daemon-reload &amp;&amp; sudo systemctl enable --now nexa-suite</code>. Open port 4411 only if you are not using a reverse proxy.</p>
            <div class="notice">
                <?= nexa_icon('link') ?>
                <div><strong>Link it from the panel</strong><span>In Nexa Editor → General → Nexa Suite URL, enter your suite address (for example <code>http://panel.example.com:4411</code>). A Nexa Suite link appears in the sidebar and profile menu.</span></div>
            </div>
        </section>

        <section class="doc-section" id="security">
            <span class="eyebrow">08 · Operations</span>
            <h2>Security &amp; backups</h2>
            <ul class="doc-list">
                <li>Serve the console over HTTPS. Session cookies are HttpOnly, SameSite=Lax and become Secure automatically behind TLS.</li>
                <li>Open <code>/setup</code> only for the first run; it locks itself after the first admin is created.</li>
                <li>Use scoped API keys. Give automation only <code>bots:write</code> or <code>licenses:read</code>, never <code>*</code>, when possible.</li>
                <li>Pterodactyl API keys are encrypted with AES-256-GCM using <code>storage/.app_key</code>. Back up that file with the database; without it, secrets cannot be decrypted.</li>
                <li>Back up <code>storage/nexa.sqlite</code>, <code>storage/.app_key</code> and <code>storage/extensions.json</code> together.</li>
                <li>Restrict file permissions: <code>chmod 750 storage config</code>, <code>chmod 640 storage/nexa.sqlite storage/.app_key</code>, owned by the web user.</li>
                <li>License keys and API keys are stored as SHA-256 hashes. There is no way to recover a lost key — regenerate it.</li>
            </ul>
        </section>

        <section class="doc-section" id="troubleshooting">
            <span class="eyebrow">09 · Help</span>
            <h2>Troubleshooting</h2>
            <div class="faq-list">
                <details class="faq">
                    <summary>“Could not write storage/.app_key”</summary>
                    <p>Make <code>storage/</code> writable by the PHP user: <code>sudo chown -R www-data:www-data storage config && chmod -R ug+rwX storage</code>. On shared hosting, use the account user that runs PHP.</p>
                </details>
                <details class="faq">
                    <summary>Bot creation returns a Pterodactyl error</summary>
                    <p>Confirm the Application API key has server create/delete permissions, the owner user ID exists, the egg and node IDs are correct, and the node has at least one free allocation. The exact API error is shown in the console and in <code>storage/logs/app.log</code>.</p>
                </details>
                <details class="faq">
                    <summary>Power buttons say a Client API key is missing</summary>
                    <p>Application API keys cannot power servers. Create a Client API key while logged in as the server owner (Account → API Credentials) and add it in Settings. The key must belong to the same user that owns the bot servers.</p>
                </details>
                <details class="faq">
                    <summary>The theme did not load after install</summary>
                    <p>Confirm Blueprint is installed and matches your Pterodactyl version, the Nexa extension is enabled, and you reloaded the panel. Clear the panel cache with <code>php artisan optimize:clear</code> and check the browser console for asset 404s.</p>
                </details>
                <details class="faq">
                    <summary>I lost a license key</summary>
                    <p>Keys are hashed, so they cannot be shown again. Use <em>Regenerate</em> on the license row, then deliver the new key. The previous key stops validating immediately.</p>
                </details>
                <details class="faq">
                    <summary>Setup page reloads to login</summary>
                    <p>An admin already exists. Sign in instead. If you need a second admin, use <code>php bin/nexa user:create</code>.</p>
                </details>
            </div>
        </section>
    </article>
</div>
