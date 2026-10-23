<?php
$consoleHref = app()->auth()->check() ? '/admin' : (app()->installed() ? '/login' : '/setup');
$consoleLabel = app()->auth()->check() ? 'Open console' : (app()->installed() ? 'Open console' : 'Set up Nexa');

$apiSample = <<<'CODE'
# Validate a license from your panel
curl -X POST http://your-host:4411/api/v1/licenses/validate \
  -H "Content-Type: application/json" \
  -d '{"license":"NEXA-XXXXX-XXXXX-XXXXX-XXXXX","domain":"panel.example.com"}'

# Host a bot with a scoped API key
curl -X POST http://your-host:4411/api/v1/bots \
  -H "Authorization: Bearer nexa_xxxxx" \
  -H "Content-Type: application/json" \
  -d '{"name":"Music Bot","memory":1024,"disk":4096,"cpu":100}'
CODE;
?>
<section class="hero container">
    <div class="hero-copy">
        <span class="hero-badge"><span class="status-dot is-good"></span> Nexa 1.0 · Pterodactyl suite</span>
        <h1>Every license.<br>Every key.<br><span class="text-gradient">Every server.</span></h1>
        <p class="hero-sub">Nexa Suite is the self-hosted control deck for your Pterodactyl stack. Issue license keys, mint scoped API keys, provision bot servers and curate every Blueprint extension — from one crimson-glass console.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url($consoleHref)) ?>"><?= e($consoleLabel) ?> <?= nexa_icon('arrow') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/docs')) ?>"><?= nexa_icon('book') ?> Read the docs</a>
        </div>
        <div class="hero-meta">
            <span><?= nexa_icon('check') ?> PHP 8 + SQLite</span>
            <span><?= nexa_icon('check') ?> Runs on port 4411</span>
            <span><?= nexa_icon('check') ?> No external license server</span>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="shot-frame hero-shot">
            <div class="shot-bar">
                <span class="shot-dots"><i></i><i></i><i></i></span>
                <span class="shot-url">panel.example.com — Nexa theme</span>
                <span class="shot-badge">LIVE</span>
            </div>
            <img src="/img/showcase-dashboard.png" alt="Nexa Theme dashboard inside a Pterodactyl panel">
        </div>
        <div class="float-card float-license">
            <span class="float-icon"><?= nexa_icon('key') ?></span>
            <div>
                <strong>License active</strong>
                <span>NEXA-•••••-••••• · 3 / 3 installs</span>
            </div>
            <span class="status-dot is-good"></span>
        </div>
        <div class="float-card float-terminal">
            <span class="terminal-prompt">$</span>
            <code>php bin/nexa license:create</code>
            <span class="terminal-out">NEXA-7K2M9-QP4RX-W8TNL-H3VJD</span>
        </div>
    </div>
</section>

<div class="trust-strip">
    <div class="container trust-inner">
        <span class="trust-label">Built around the tools you already run</span>
        <span class="trust-item">Pterodactyl 1.x</span>
        <span class="trust-divider"></span>
        <span class="trust-item">Blueprint</span>
        <span class="trust-divider"></span>
        <span class="trust-item">PHP 8</span>
        <span class="trust-divider"></span>
        <span class="trust-item">SQLite</span>
        <span class="trust-divider"></span>
        <span class="trust-item">Nginx · Caddy · Apache</span>
    </div>
</div>

<section class="section container" id="features">
    <div class="section-head">
        <span class="eyebrow">The platform</span>
        <h2>A control deck for the <span class="text-gradient">whole stack</span>.</h2>
        <p>Six systems that usually live in six different tools, designed to sit together behind one login.</p>
    </div>
    <div class="feature-grid">
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('key') ?></span>
            <h3>License engine</h3>
            <p>Generate grouped license keys with plan, expiry, activation limits and live domain activation records. Only SHA-256 hashes are stored.</p>
            <span class="feature-meta">Issue · validate · activate · revoke</span>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('shield') ?></span>
            <h3>Scoped API keys</h3>
            <p>Mint Bearer keys for licenses, extensions and bots. Limit a key to read-only or bot provisioning only, then revoke it in one click.</p>
            <span class="feature-meta">Hashed · scoped · last-used tracking</span>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('bot') ?></span>
            <h3>Bot hosting</h3>
            <p>Provision Pterodactyl servers through the Application API, then start, stop, restart or delete them through the Client API.</p>
            <span class="feature-meta">Automatic free allocation &amp; egg defaults</span>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('puzzle') ?></span>
            <h3>Extension catalog</h3>
            <p>Keep Nexa Theme, Nexa Editor and every community Blueprint add-on in one catalog. Add your own entries with install notes.</p>
            <span class="feature-meta">Enable · disable · extend</span>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('spark') ?></span>
            <h3>Nexa Editor</h3>
            <p>The admin-only theme studio bundled with Nexa Theme. Branding, presets, sidebar placement, glass blur, effects and GIF backgrounds.</p>
            <span class="feature-meta">In-panel · live preview · saved for all users</span>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><?= nexa_icon('terminal') ?></span>
            <h3>Command line</h3>
            <p>Every core action is scriptable: licenses, keys, extensions, bots, settings and the server itself. Perfect for cron and remote setup.</p>
            <span class="feature-meta">php bin/nexa · JSON output</span>
        </article>
    </div>
</section>

<section class="section container">
    <div class="showcase split">
        <div class="showcase-copy">
            <span class="eyebrow">Nexa Theme + Editor</span>
            <h2>Your panel, <span class="text-gradient">reimagined</span>.</h2>
            <p class="lead">A complete Pterodactyl skin with a custom dashboard, fleet grid, quick switcher, privacy mode and a full-width editor — no core patches, no paid Arix assets.</p>
            <ul class="check-list">
                <li><?= nexa_icon('check') ?> Live preview while you edit branding, logos and support links</li>
                <li><?= nexa_icon('check') ?> Custom image or animated GIF background from any URL</li>
                <li><?= nexa_icon('check') ?> Dark, light and Crimson glass presets with accent control</li>
                <li><?= nexa_icon('check') ?> Sidebar placement, density, effects and animation switches</li>
                <li><?= nexa_icon('check') ?> Add your Suite URL on port 4411 to the panel sidebar</li>
            </ul>
            <div class="hero-actions">
                <a class="btn btn-primary btn-sm" href="<?= e(url('/docs#theme')) ?>">Theme install steps <?= nexa_icon('arrow') ?></a>
                <a class="btn btn-quiet btn-sm" href="<?= e(url($consoleHref)) ?>">Open console</a>
            </div>
        </div>
        <div class="showcase-visual">
            <div class="shot-frame">
                <div class="shot-bar">
                    <span class="shot-dots"><i></i><i></i><i></i></span>
                    <span class="shot-url">Nexa Editor — Nexa Theme</span>
                </div>
                <img src="/img/showcase-editor.png" alt="Nexa Editor theme studio inside Pterodactyl admin">
            </div>
        </div>
    </div>
</section>

<section class="section container" id="extensions">
    <div class="section-head split-head">
        <div>
            <span class="eyebrow">Extension catalog</span>
            <h2>One catalog for the <span class="text-gradient">entire ecosystem</span>.</h2>
            <p>Nexa ships bundled. Community Blueprint extensions slot into the same catalog, with install notes and a clean on/off state.</p>
        </div>
        <a class="btn btn-quiet btn-sm" href="<?= e(url($consoleHref)) ?>">Manage catalog <?= nexa_icon('arrow') ?></a>
    </div>
    <div class="extension-grid">
        <?php foreach (array_slice($extensions, 0, 6) as $entry): ?>
            <article class="extension-card">
                <div class="extension-top">
                    <span class="extension-glyph"><?= nexa_icon(($entry['category'] ?? '') === 'Theme' ? 'spark' : (($entry['category'] ?? '') === 'Suite' ? 'layers' : 'puzzle')) ?></span>
                    <span class="badge <?= e(state_class((string) $entry['status'])) ?>"><?= e($entry['status']) ?></span>
                </div>
                <span class="extension-cat"><?= e($entry['category']) ?></span>
                <h3><?= e($entry['name']) ?></h3>
                <p><?= e($entry['description']) ?></p>
                <div class="extension-foot">
                    <span>v<?= e($entry['version']) ?></span>
                    <?php if (!empty($entry['bundled'])): ?><span class="mini-pill">Bundled</span><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container" id="api">
    <div class="showcase split reverse">
        <div class="showcase-copy">
            <span class="eyebrow">HTTP API</span>
            <h2>A small API with <span class="text-gradient">sharp teeth</span>.</h2>
            <p class="lead">Every console action has a JSON endpoint. Scoped API keys can create bot servers, mint licenses and read the extension catalog without a browser session.</p>
            <div class="endpoint-cloud">
                <span>POST /licenses/validate</span>
                <span>POST /licenses/activate</span>
                <span>GET /extensions</span>
                <span>POST /bots</span>
                <span>POST /bots/{id}/power</span>
            </div>
            <ul class="check-list">
                <li><?= nexa_icon('check') ?> Bearer authentication with per-key scopes</li>
                <li><?= nexa_icon('check') ?> IP rate limits on public license checks</li>
                <li><?= nexa_icon('check') ?> JSON errors with stable machine-readable codes</li>
            </ul>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/docs#api')) ?>">API reference <?= nexa_icon('arrow') ?></a>
        </div>
        <div class="code-shell">
            <div class="code-head">
                <span class="shot-dots"><i></i><i></i><i></i></span>
                <span>terminal — nexa-suite</span>
                <button class="copy-btn" type="button" data-copy-target="api-sample"><?= nexa_icon('copy') ?> Copy</button>
            </div>
            <pre class="code-block" id="api-sample"><code><?= e($apiSample) ?></code></pre>
        </div>
    </div>
</section>

<section class="section container" id="pricing">
    <div class="section-head">
        <span class="eyebrow">License tiers</span>
        <h2>Licenses that fit <span class="text-gradient">every install</span>.</h2>
        <p>Issue keys from your own console. Tiers control how many panel domains can activate the same key.</p>
    </div>
    <div class="plan-grid">
        <?php $index = 0; foreach ($plans as $key => $plan): $index++; ?>
            <article class="plan-card <?= $key === 'pro' ? 'is-featured' : '' ?>">
                <?php if ($key === 'pro'): ?><span class="plan-flag">Most popular</span><?php endif; ?>
                <span class="plan-name"><?= e($plan['name']) ?></span>
                <div class="plan-limit"><strong><?= e((string) $plan['activations']) ?></strong><span>activation<?= ((int) $plan['activations']) === 1 ? '' : 's' ?></span></div>
                <p class="plan-label"><?= e($plan['label']) ?></p>
                <ul class="plan-features">
                    <li><?= nexa_icon('check') ?> Hashed, revocable license keys</li>
                    <li><?= nexa_icon('check') ?> Domain activation records</li>
                    <li><?= nexa_icon('check') ?> Public validation endpoint</li>
                    <li><?= nexa_icon('check') ?> Nexa Editor unlock</li>
                    <?php if ($index > 1): ?><li><?= nexa_icon('check') ?> Priority support link</li><?php endif; ?>
                </ul>
                <a class="btn <?= $key === 'pro' ? 'btn-primary' : 'btn-quiet' ?> btn-block" href="<?= e(url($consoleHref)) ?>">Issue <?= e($plan['name']) ?> key</a>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="pricing-note"><?= nexa_icon('shield') ?> Self-hosted billing is not included. Keys are created by the administrator or through the CLI/API.</p>
</section>

<section class="section container">
    <div class="cta-band">
        <div class="cta-band-bg" aria-hidden="true"></div>
        <div class="cta-band-copy">
            <span class="eyebrow">Ready when you are</span>
            <h2>The whole Nexa stack.<br><span class="text-gradient">One port. 4411.</span></h2>
            <p>Install the theme in Pterodactyl, run the suite next to it, and keep licenses, bots and extensions in one place.</p>
        </div>
        <div class="cta-band-actions">
            <a class="btn btn-primary" href="<?= e(url($consoleHref)) ?>"><?= e($consoleLabel) ?> <?= nexa_icon('arrow') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('/docs#quick-start')) ?>">Installation guide</a>
        </div>
    </div>
</section>
