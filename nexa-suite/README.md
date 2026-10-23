# Nexa Suite

Self-hosted license, API-key and bot-hosting console for Pterodactyl. Built as a plain PHP application with SQLite, so it runs on the same VPS as your panel without Composer, Node or an external database.

The release also contains **Nexa**, a Crimson Glass theme for Pterodactyl with an admin-only live editor (custom GIF/image backgrounds, suite URL, presets and more).

---

## What is included

| File | Purpose |
| --- | --- |
| `nexa-theme.blueprint` | Blueprint extension: theme + Nexa Editor |
| `nexa-suite/` | License website, REST API, bot hosting and CLI |
| `deploy/` | Nginx, Caddy and systemd examples |
| `README.md` | This guide |

## Requirements

- PHP **8.1+** (8.2/8.3 recommended) with `pdo_sqlite`, `openssl`, `curl`, `json`
- SQLite (bundled with most PHP builds)
- A Pterodactyl 1.x panel with [Blueprint](https://blueprint.zip) installed for the theme
- Write access to `nexa-suite/config/` and `nexa-suite/storage/`
- HTTPS strongly recommended (use Nginx/Caddy or a proxy in front of PHP-FPM)

---

## 1. Install Nexa Theme

1. In Pterodactyl, open **Admin → Extensions** and make sure Blueprint is installed for your panel version.
2. Open **Admin → Extensions → Import**, upload `nexa-theme.blueprint`, then enable **Nexa**.
3. Reload the panel. The Nexa look is applied to the client area and admin theme.
4. Open **Nexa Editor** in the panel sidebar (root administrators only):
   - **General** → set brand name, logo and **Nexa Suite URL** (for example `http://panel.example.com:4411`).
   - **Background** → choose *Custom URL* and paste an image or animated GIF URL.
   - **Look** → presets, accent, radius, glass blur, sidebar position, density and effects.
   - **Code editor** → themed syntax-highlighting editor, word wrap and `Ctrl`/`Cmd` + `S` to save.
5. Click **Save changes**. Settings are stored by the extension and apply to every panel user.

The suite link appears in the sidebar and profile menu once the URL is set. Keep the suite on a separate directory/subdomain from the panel.

---

## 2. Install Nexa Suite

Upload the `nexa-suite` folder to your server, for example `/var/www/nexa-suite`, then make the writable directories owned by the PHP user:

```bash
cd /var/www/nexa-suite
sudo chown -R www-data:www-data config storage
sudo chmod -R ug+rwX config storage
```

### Option A — CLI (recommended)

```bash
php bin/nexa install
```

The installer asks for a site name, public suite URL, and the first admin account. It writes `config/config.php`, creates `storage/nexa.sqlite` and seeds the extension catalog.

Non-interactive version (useful for automation):

```bash
php bin/nexa install --no-interaction \
  --site="Nexa Suite" \
  --url="https://nexa.example.com" \
  --name="Admin" \
  --email="admin@example.com" \
  --password="use-a-long-password"
```

### Option B — first-run web setup

Point the document root at `nexa-suite/public` and open `https://your-suite.example.com/setup`. The wizard creates the database and first admin. After an admin exists, `/setup` closes itself and sign-in lives at `/login`.

> Never point the document root at the suite root. `config/`, `storage/`, `app/` and `routes/` must not be web-accessible. `public/index.php` is the only entry point.

### Start it

For a quick test:

```bash
php bin/nexa serve --host=127.0.0.1 --port=4411
# open http://127.0.0.1:4411
```

For production use Nginx/Caddy + PHP-FPM (section 3), or the systemd example for small private installs.

---

## 3. Deploy on port 4411

The suite defaults to port **4411** so it can live next to Pterodactyl.

### Nginx + PHP-FPM (recommended)

Copy `deploy/nginx-port-4411.conf.example` to `/etc/nginx/sites-available/nexa-suite`, adjust the domain, certificate paths and PHP-FPM socket, then enable it:

```bash
sudo ln -s /etc/nginx/sites-available/nexa-suite /etc/nginx/sites-enabled/nexa-suite
sudo nginx -t && sudo systemctl reload nginx
```

If you use a dedicated subdomain instead of a port, use `deploy/nginx-subdomain.conf.example`. If Cloudflare proxies your DNS, note that it only proxies a limited set of ports — use a subdomain on 443 or put the suite behind a Tunnel instead of port 4411.

### Caddy

`deploy/Caddyfile.example` gives automatic HTTPS for a subdomain. Point it at the PHP-FPM socket, or reverse-proxy the built-in server if you keep it bound to localhost.

### systemd + built-in server

Good for a private admin-only install. Keep it on localhost and proxy through Nginx/Caddy for TLS:

```bash
sudo cp deploy/nexa-suite.service /etc/systemd/system/nexa-suite.service
sudo systemctl daemon-reload
sudo systemctl enable --now nexa-suite
sudo systemctl status nexa-suite
```

The unit runs `php bin/nexa serve --host=127.0.0.1 --port=4411` as `www-data` from `/var/www/nexa-suite`. Adjust the user/path if needed. The PHP built-in server is single-threaded by default; for more concurrency set `PHP_CLI_SERVER_WORKERS=4` in the unit, or use PHP-FPM.

### Apache

A `public/.htaccess` with front-controller rewrites ships with the suite. Set `AllowOverride All` for the site (or paste the rules into your vhost) and point the document root at `public/`.

### Link it back to the theme

In **Nexa Editor → General → Nexa Suite URL**, enter the public address (for example `https://panel.example.com:4411` or `https://nexa.example.com`). Save. The panel sidebar and profile menu now link to the console.

---

## 4. Connect Pterodactyl

In the suite open **Settings → Pterodactyl connection**:

| Field | Where to find it |
| --- | --- |
| Panel URL | Your panel base URL, e.g. `https://panel.example.com` |
| Application API key | Panel **Admin → Application API → Create** — needs read/write access to nodes, allocations, servers, nests and users |
| Client API key | Log in as the server owner → **Account → API Credentials** — used for power/resource calls |
| Owner user ID | **Admin → Users**; the ID is in the URL (`/admin/users/view/1`) |
| Default node / nest / egg | **Admin → Nodes** and **Admin → Nests**; used as bot-hosting defaults |

Use **Test connection** to verify. The Application key is only used for provisioning; the Client key performs start/stop/restart and status calls. Both are encrypted with AES-256-GCM using `storage/.app_key`.

If provisioning fails, check that the node has a free allocation, the owner exists, and the selected egg has a Docker image. The panel's exact error is shown in the console and in `storage/logs/app.log`.

---

## 5. License flow

Create a key in **Licenses**, with the CLI, or through the API. The full key is shown **once**; only its SHA-256 hash and a short prefix are stored.

```bash
php bin/nexa license:create --plan=pro --days=365 --activations=3 --email=customer@example.com
php bin/nexa license:list
php bin/nexa license:revoke 12
php bin/nexa license:regenerate 12   # old key stops working immediately
```

Plans and activation limits come from `config/config.php` (`starter`, `pro`, `business`, `lifetime`) and can be changed there.

### Validate from a panel or service

```bash
curl -s http://your-host:4411/api/v1/licenses/validate \
  -H "Content-Type: application/json" \
  -d '{"license":"NEXA-XXXXX-XXXXX-XXXXX-XXXXX","domain":"panel.example.com"}'
```

The response contains `valid`, `reason`, plan, expiry, `days_left`, activation limit and activation count. Validate is read-only and rate-limited per IP; activation is a separate call:

```bash
curl -s http://your-host:4411/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{"license":"NEXA-XXXXX-XXXXX-XXXXX-XXXXX","domain":"panel.example.com","instance":"main"}'

curl -s http://your-host:4411/api/v1/licenses/deactivate \
  -H "Content-Type: application/json" \
  -d '{"license":"NEXA-XXXXX-XXXXX-XXXXX-XXXXX","domain":"panel.example.com"}'
```

Each domain/instance pair counts as one activation. Deactivation frees the slot. Revoking invalidates validation immediately and keeps activation history for audit.

> Licensing model: the suite is the license authority and exposes validation over HTTP. Nexa Theme itself is delivered unlocked and does not phone home; wire the `/validate` and `/activate` calls into your own release flow if you want client-side enforcement.

---

## 6. API keys and bot hosting

Create a scoped API key in **API keys** or with the CLI. The full `nexa_...` value is shown once.

```bash
php bin/nexa key:create --name="Automation" --scopes=bots:write,licenses:read
php bin/nexa key:list
php bin/nexa key:revoke 3
```

Scopes: `licenses:read`, `licenses:write`, `bots:read`, `bots:write`, `extensions:read`. A write scope implies its read scope; `*` grants everything. Send the key as `Authorization: Bearer nexa_...` or `X-Nexa-Key`.

### Create a bot

```bash
curl -s http://your-host:4411/api/v1/bots \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name":"Music Bot","memory":1024,"disk":4096,"cpu":100}'
```

The suite reads node/nest/egg/owner from Settings, selects the first free allocation automatically, fills the egg environment defaults and creates the server through the Pterodactyl Application API. Override `node_id`, `nest_id`, `egg_id`, `allocation_id`, `owner_id` or `environment` per request if needed.

```bash
# power: start | stop | restart | kill
curl -s -X POST http://your-host:4411/api/v1/bots/4/power \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"signal":"restart"}'

curl -s http://your-host:4411/api/v1/bots -H "Authorization: Bearer nexa_your_api_key"
curl -s -X DELETE http://your-host:4411/api/v1/bots/4 -H "Authorization: Bearer nexa_your_api_key"
```

Endpoints: `GET /api/v1/health`, public license `validate|activate|deactivate`, protected `licenses`, `extensions`, `bots`. Full reference is also available at `/docs` in the running suite.

### Manage extensions

```bash
php bin/nexa extension:list
php bin/nexa extension:enable discord-auth
php bin/nexa extension:disable status-page
php bin/nexa extension:add --name="My Addon" --category=Community --url=https://example.com/addon
```

The catalog ships with Nexa Theme, Nexa Editor, Nexa Suite and Blueprint plus five community entries. Toggle any entry from the web console or CLI.

---

## 7. CLI cheat sheet

```bash
php bin/nexa help                  # complete command map
php bin/nexa install               # setup wizard
php bin/nexa serve --port=4411     # development server
php bin/nexa user:create           # extra admin (or reset with --force)
php bin/nexa user:list

php bin/nexa license:create --plan=pro --days=365 --activations=3 --email=user@example.com
php bin/nexa license:list --json
php bin/nexa license:validate NEXA-... --domain=panel.example.com
php bin/nexa license:revoke 12
php bin/nexa license:restore 12
php bin/nexa license:regenerate 12
php bin/nexa license:delete 12 --force

php bin/nexa key:create --name="Automation" --scopes=bots:write
php bin/nexa key:list
php bin/nexa key:revoke 3

php bin/nexa bot:create --name="Music Bot" --memory=1024 --disk=4096 --cpu=100
php bin/nexa bot:list
php bin/nexa bot:start 4
php bin/nexa bot:stop 4
php bin/nexa bot:restart 4
php bin/nexa bot:kill 4
php bin/nexa bot:delete 4 --force

php bin/nexa settings:show
php bin/nexa settings:set pterodactyl_url=https://panel.example.com
php bin/nexa settings:set pterodactyl_application_key=ptla_xxxxxxxx
php bin/nexa settings:set pterodactyl_client_key=ptlc_xxxxxxxx
php bin/nexa settings:set pterodactyl_owner_id=1
php bin/nexa settings:set pterodactyl_default_node=1
php bin/nexa settings:set pterodactyl_default_nest=1
php bin/nexa settings:set pterodactyl_default_egg=1
```

Use `--json` for machine-readable output on supported listing/creation commands. `--no-interaction` disables prompts for cron and CI. Destructive commands (`license:delete`, `bot:delete`, non-interactive user password reset) require `--force`.

---

## 8. Security and backups

- Serve the suite over HTTPS. Sessions are HttpOnly, SameSite=Lax; the cookie becomes Secure behind TLS.
- Keep the suite on its own directory, separate from the panel. Only `public/` is web-accessible.
- Back up `storage/nexa.sqlite`, `storage/extensions.json` and **`storage/.app_key`** together. Without `.app_key`, encrypted Pterodactyl credentials cannot be recovered.
- License keys and API keys are hashed; a lost value cannot be recovered — regenerate it.
- Use scoped API keys for automation. Avoid `*` unless the integration truly needs it.
- The suite issues keys but does not itself gate the theme; see the licensing note above.
- For upgrades, stop the service, replace everything **except** `config/config.php`, `storage/` and `.app_key`, then restart.

Recommended permissions:

```bash
sudo chown -R www-data:www-data config storage
sudo find config storage -type d -exec chmod 750 {} \;
sudo find config storage -type f -exec chmod 640 {} \;
```

---

## 9. Troubleshooting

| Problem | Fix |
| --- | --- |
| `Could not write storage/.app_key` | `chown -R www-data:www-data storage config && chmod -R ug+rwX storage config` |
| Setup redirects to login | An admin already exists. Sign in, or create another with `php bin/nexa user:create`. |
| Bot creation fails | Check Application API scopes, owner ID, node/nest/egg IDs and a free allocation. See `storage/logs/app.log`. |
| Power buttons say client key missing | Add a Client API key created while logged in as the server owner. |
| Theme did not load | Blueprint must be installed and enabled; clear panel caches (`php artisan optimize:clear`) and check asset 404s. |
| Suite link missing in panel | Set **Nexa Editor → General → Nexa Suite URL**, save and reload. |
| 419 page on POST | CSRF token expired — reload the form. Make sure session storage is writable. |
| Port 4411 unreachable | Open the port in the firewall/security group, or proxy through Nginx/Caddy on 443. |

---

## Directory map

```
nexa-suite/
├── app/                 controllers, services, console, router
├── bin/nexa             CLI entry point
├── config/              config.example.php (copy is written by install)
├── public/              only web-accessible directory
├── routes/              web + API routes
├── storage/             SQLite database, app key, logs, extension state
├── deploy/              nginx, caddy, systemd examples
└── README.md
```

---

## Support notes

- The theme is original work built for Pterodactyl 1.x + Blueprint; no paid Arix code or assets are included.
- The suite has no external license server, telemetry or callbacks. Everything runs on your VPS.
- Test on a staging panel before enabling on a live community.
