# Nexa — START HERE (Hindi / Hinglish)

Release me 2 cheezein hain:

- **nexa-theme.blueprint** — Pterodactyl theme + Nexa Editor
- **nexa-suite.zip / nexa-suite folder** — license website, API keys, bot hosting, CLI

---

## Step 1 — Theme install karo

1. Panel me **Admin → Extensions** kholo. Blueprint installed hona chahiye.
2. **Extensions → Import** me `nexa-theme.blueprint` upload karo.
3. **Nexa** ko enable karo aur panel reload karo.
4. Sidebar me **Nexa Editor** dikhega (sirf root admin ko).
5. Editor me:
   - **General** → brand name, logo, **Nexa Suite URL** (e.g. `http://panel.example.com:4411`).
   - **Background** → *Custom URL* → apna image/GIF URL paste karo (e.g. `https://i.imgur.com/xyz.gif`).
   - **Look** → preset, accent, glass blur, sidebar, density.
   - **Code editor** → syntax-highlighting editor, `Ctrl/Cmd + S` save.
6. **Save changes** dabao.

> Suite URL save karne ke baad panel sidebar/profile menu me **Nexa Suite** ka link aa jayega.

---

## Step 2 — Suite ko VPS par upload karo

```bash
# example path
cd /var/www
unzip nexa-suite.zip
cd nexa-suite
```

Requirements: **PHP 8.1+**, `pdo_sqlite`, `openssl`, `curl`.

Permissions:

```bash
sudo chown -R www-data:www-data config storage
sudo chmod -R ug+rwX config storage
```

Install:

```bash
php bin/nexa install
```

Ye site name, public URL, admin name/email/password poochega. Database `storage/nexa.sqlite` ban jayegi.

Quick test:

```bash
php bin/nexa serve --host=127.0.0.1 --port=4411
```

Browser me `http://127.0.0.1:4411` kholo → login karo.

**Production me** built-in server ki jagah Nginx/Caddy + PHP-FPM use karo. `deploy/` folder me examples hain:

- `nginx-port-4411.conf.example` — HTTPS on port 4411
- `nginx-subdomain.conf.example` — subdomain on 443
- `nexa-suite.service` — systemd (built-in server)
- `Caddyfile.example` — automatic HTTPS

Document root hamesha `nexa-suite/public` rakho, suite root nahi.

---

## Step 3 — Panel connect karo

Suite me **Settings → Pterodactyl connection**:

1. **Panel URL** — `https://panel.example.com`
2. **Application API key** — Pterodactyl Admin → Application API → create key (nodes, allocations, servers, nests, users read/write).
3. **Client API key** — server owner ke account se Account → API Credentials → create.
4. **Owner user ID**, **default node**, **nest**, **egg** bharo.
5. **Test connection** dabao.

Bot create karte waqt suite khud first free allocation choose karta hai.

---

## Step 4 — License banao

**Web:** Suite → **Licenses** → plan, days, activations, email → Create. Key sirf ek baar dikhegi, turant copy karo.

**CLI:**

```bash
php bin/nexa license:create --plan=pro --days=365 --activations=3 --email=customer@example.com
php bin/nexa license:list
php bin/nexa license:revoke 12
php bin/nexa license:regenerate 12
```

**Validate API:**

```bash
curl -s http://your-host:4411/api/v1/licenses/validate \
  -H "Content-Type: application/json" \
  -d '{"license":"NEXA-XXXXX-XXXXX-XXXXX-XXXXX","domain":"panel.example.com"}'
```

---

## Step 5 — API key banao aur bot host karo

**Web:** Suite → **API keys** → name + scopes → Create. `nexa_...` key ek hi baar dikhegi.

**CLI:**

```bash
php bin/nexa key:create --name="Automation" --scopes=bots:write,licenses:read
```

**Bot create:**

```bash
curl -s http://your-host:4411/api/v1/bots \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name":"Music Bot","memory":1024,"disk":4096,"cpu":100}'
```

Power on/off:

```bash
curl -s -X POST http://your-host:4411/api/v1/bots/4/power \
  -H "Authorization: Bearer nexa_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"signal":"restart"}'
```

---

## Step 6 — Port 4411 ko panel se link karo

Nexa Editor → **General** → **Nexa Suite URL**:

- Subdomain use kar rahe ho: `https://nexa.example.com`
- Port use kar rahe ho: `https://panel.example.com:4411`

Save karo. Ab panel ke sidebar/profile menu se suite khul jayega.

---

## Important

- `storage/.app_key` aur `storage/nexa.sqlite` dono ka backup rakho. `.app_key` ke bina encrypted Pterodactyl keys recover nahi hoti.
- License/API keys database me sirf SHA-256 hash ke roop me save hote hain. Lost key regenerate karo.
- Suite ko HTTPS par chalao.
- Theme khud license enforce nahi karta; suite validate/activate API deta hai. Apne release flow me ise call kar sakte ho.

Detailed English guide: `README.md`.
