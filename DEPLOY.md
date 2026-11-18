# ShopSmoke Marketing Studio — Root cPanel deployment (v0.0.40)

**Upgrading to v0.0.40:** same steps as below, with `marketing-editor-v0.0.40.zip` for coolwebsitedude.com and `marketing-editor-v0.0.40-shopsmoke.zip` for www.shopsmoke.online. `.htaccess` is the same as v0.0.39. If you are upgrading from v0.0.37 or v0.0.38, let it be overwritten: it goes back to the v0.0.36 HTTPS rules, without the v0.0.37 `http://` exceptions for TV links.

**Certificate (www.shopsmoke.online):** the site uses a Let's Encrypt certificate (chain YR1 → Root YR → ISRG Root X1), because older smart TVs trust that root and not SSL.com's 2022 root. It was issued with acme.sh from cPanel Terminal (`~/.acme.sh`, RSA 2048, webroot `~/public_html`) and installed with its `cpanel_uapi` deploy hook. acme.sh's cron job renews and reinstalls it on its own. Don't reinstall the SSL.com certificate that is still listed under SSL/TLS → Certificates, and don't uninstall the site's certificate: this account has no cPanel AutoSSL, and Namecheap's SSL plugin puts its free SSL.com certificate back when a domain has none. That plugin also redirects every `http://` request to `https://` before `.htaccess` runs. Let `.htaccess` be overwritten. Let `.htaccess` be overwritten: /team, /add-store, /add-tv, /rename-tv and /tv-history depend on it. Let `.htaccess` be overwritten: the clean addresses (/editor, /publish, /tv-admin, /start) depend on it.

**Moving a site from file storage to MySQL:** back up first. Upload the zip, then in cPanel File Manager delete `api/config.php`. Open `/setup/install.php` straight away, enter the same owner email and password, open **Developer: use a MySQL database**, and enter the database name, user and password. Setup copies everything across. The database details stay in `api/config.php` on the server and never go in a zip.

**Upgrading to v0.0.24.1:** extract `marketing-editor-v0.0.24.1.zip` over `public_html`, keep `api/config.php`, no database change. It adds `ftvideo.js`. Reopen the app on each device once.

- **coolwebsitedude.com:** use `marketing-editor-v0.0.24.1.zip`.
- **www.shopsmoke.online:** use `marketing-editor-v0.0.24.1-shopsmoke.zip`, which keeps that site's domain rules. Back up `public_html` and the MySQL database first.
- After extracting, open `/setup/install.php` once. It says the site is already set up and removes itself.
- **Coming from v0.0.21:** the owner logs in with their email and current password; that saves the email for Forgot password. TVs update themselves within 15 minutes.

**Upgrading to v0.0.24:** extract `marketing-editor-v0.0.24.zip` over `public_html`, keep `api/config.php`, no database change. Opening `/setup/install.php` just deletes it. Reopen the app on each device once.

## v0.0.22 on coolwebsitedude.com (test build)

1. Back up `public_html`. **Check first:** this build removes the rule that sent `coolwebsitedude.com/<store>-tv-<n>/` to `www.shopsmoke.online`. A store TV or kiosk that still starts on a coolwebsitedude.com address will now stay here, so point those at www.shopsmoke.online before uploading.
2. Upload `marketing-editor-v0.0.22.zip` into `public_html` and **Extract**, overwriting files.
3. **Keep the existing setup:** do nothing else. Your stores, TVs and logins stay; the owner logs in with a blank username or `owner`, as before. Opening `/setup/install.php` just deletes it.
4. **Try the new first-time setup:** in File Manager delete `api/config.php`, then open `/setup/install.php` **straight away** (anyone who opens it first becomes the owner). Enter an email and password and agree. It starts a new private-folder registry; your MySQL data stays in the database, untouched. To keep using that data instead, open *Developer: use a MySQL database* on the setup page and enter the same database details.
5. Reopen the app on each device once. Opening `index.html` from your computer shows the design only: logging in, drafts, sync and publishing need the uploaded site.

**Forgot the owner password:** use **Forgot password?** on the login box. It emails a code through the host's PHP mail, so check spam. If that email can't be reached: delete `api/config.php`, upload `setup/install.php` from the ZIP, open it straight away and enter a new email and password. Stores, TVs and menus are kept (a MySQL site also enters its database details under the developer option).

**Backups:** new installs keep the registry in `~/.4tier-marketing-private/` (no database), and videos in `public_html/tv-content/`. Back up both.

**Upload size for videos:** videos go up in 1 MB parts, so PHP upload limits don't matter, but they use disk space: up to 300 MB each.

**Upgrading v0.0.16 → v0.0.17:** same steps with `marketing-editor-v0.0.17.zip`: extract over `public_html`, keep `api/config.php`, no installer, no manual database change. Then sign in to TV Manager as the owner and add your team under **Team accounts**. From now on back up `/home/coolqdzx/.4tier-marketing-private/` as well (drafts and synced photos live there). Rollback = extract `marketing-editor-v0.0.16.zip` (team logins stop working until you upgrade again).

**Upgrading v0.0.15 → v0.0.16:** same steps with `marketing-editor-v0.0.16.zip`: extract over `public_html`, keep `api/config.php`, no installer, no database change. Reopen the app on phones once. Rollback = extract `marketing-editor-v0.0.15.zip`.

**Upgrading v0.0.14 → v0.0.15:** same steps with `marketing-editor-v0.0.15.zip`: extract over `public_html`, keep `api/config.php`, no installer, no database change. Reopen the app on phones once. Rollback = extract `marketing-editor-v0.0.14.zip`.

**Upgrading v0.0.13 → v0.0.14:** same steps with `marketing-editor-v0.0.14.zip`: extract over `public_html`, keep `api/config.php`, no installer, no database change. Reopen the app on phones once. Rollback = extract `marketing-editor-v0.0.13.zip`.

**Upgrading v0.0.12 → v0.0.13:** follow the same steps below with `marketing-editor-v0.0.13.zip`. There is still no `api/config.php` in the ZIP, no database change, and no installer run. On phones, reopen the app once so it loads the new layout. Rollback = extract `marketing-editor-v0.0.12.zip`.

## Upgrading the live site from v0.0.11 to v0.0.12

1. In cPanel File Manager, back up `public_html` (Compress → download) and export the database in **phpMyAdmin** (Export → Quick).
2. Upload **marketing-editor-v0.0.12.zip** into `public_html` and **Extract**, overwriting files. The ZIP has **no `api/config.php`**, so your settings stay. **Do not run the installer again** and do not delete `api/config.php`.
3. Make sure `public_html/service-worker.js` exists (the old-site cleanup file). Turn on *Show Hidden Files* to confirm the three `.htaccess` files (`public_html/`, `api/`, `tv-content/`) are still there.
4. Open `https://www.shopsmoke.online/tv-admin.html` → **Run deployment check**, then try **History → Delete** on an old test menu and **Save to Recent photos**.
5. Phones that installed the app pick up v0.0.12 on their next visit (new cache name).

Rollback: extract `marketing-editor-v0.0.11.zip` over it (database and config are compatible; v0.0.11 simply has no Delete button).

---

The original v0.0.11 first-install notes follow.


**Read before uploading.** This is a testing/pilot release, not an approved production system. Back up your live files, database, and any old `api/config.php` or private media before uploading. **Do not overwrite a working v0.0.10 until you are ready to test.** This archive contains **no** MySQL passwords or live configuration secrets.

## 1. Install at the domain root

- Domain: `https://www.shopsmoke.online/` (v0.0.19), on the new cPanel account `shoprbbv` (database `shoprbbv_backend`, user `shoprbbv_owner`), with a Let's Encrypt certificate (acme.sh, see the certificate note above) for `shopsmoke.online` and `www.shopsmoke.online`. The old coolwebsitedude.com site is on account `coolqdzx`; the `coolqdzx` paths and database names below refer to it.
- cPanel destination: `/home/shoprbbv/public_html/` (new site)
- Upload **marketing-editor-v0.0.11.zip**, extract so `public_html/index.html`, `public_html/publish.html`, `public_html/api/index.php`, `public_html/setup/install.php` exist directly (not under a `marketing-editor-v0.0.11` folder).
- Confirm `.htaccess` files are present in `public_html/`, `public_html/api/` and `public_html/tv-content/`. cPanel may hide dotfiles; enable *Show Hidden Files*.
- Set PHP to **8.1+** with `pdo_mysql` enabled; recommend PHP 8.2/8.3. Enable standard GD/Fileinfo/Image handling as available. Enable HTTPS for this domain. Prefer `upload_max_filesize=8M` and `post_max_size=12M` or higher, within hosting limits.
- Ensure PHP can write `/home/coolqdzx/.4tier-marketing-private/` (outside public_html), `public_html/api/config.php` (installer creation), and `public_html/tv-content/`.

## 2. Run installer only on your HTTPS site

If the installer currently says **“Already installed”**, do NOT delete `api/config.php`; preserve the existing configuration and sign in to TV Manager instead. Only an uninstalled site should use the form.

For a new installation, visit **https://www.shopsmoke.online/setup/install.php**. The owner created the following database account (verify in cPanel before entering):

- MySQL host: `localhost` (default; consult cPanel if different)
- Database: `coolqdzx_smokeshopdatabase`
- Database user: `coolqdzx_owner`
- Password: **enter privately into the installer; never send it in chat, documents, screenshots, or public files**
- Owner password: **choose a separate long, unique password (12+ characters)**

The installer creates table `ft_registry`, saves its settings in `api/config.php` (not included in the ZIP), stores the registry in MySQL, creates a private image folder **outside** `public_html`, and signs in the owner. It then locks itself after installation. Protect the installation URL while unconfigured, preferably by cPanel directory access controls where available. The installer can read a prior file registry from the same private folder; **export/backup old data first**.

**If a `api/config.php` already exists:** the installer intentionally refuses to overwrite it. Back up first and follow a reviewed migration plan; do not simply delete it unless you understand which storage backend is active. Deleting it temporarily unlocks the installer; keep the site restricted during an intentional reset.

## 3. Owner's first live-TV check

1. Open **https://www.shopsmoke.online/tv-admin.html**, sign in, and click **Run deployment check**. Verify no failed security checks (especially config-source exposure and clean TV URLs).
2. Choose **Galaxy Goulds**, **+ Add TV**, name it “Front Counter TV”; open the newly assigned URL on a second monitor or physical TV. Example: `https://www.shopsmoke.online/galaxy-goulds-tv-1/`.
3. On the TV browser, tap/press OK once for fullscreen (if supported) or use kiosk mode for unattended operation. Some smart TVs need an external mini-PC or signage player for reliable autoplay/fullscreen/wake support. If a TV says the security certificate is not valid, update the TV's software or use a streaming stick with a web browser, and check that the site still has its Let's Encrypt certificate (see the certificate note above).
4. On your phone open **https://www.shopsmoke.online/publish.html**, sign in, choose **Galaxy Goulds** and **Front Counter TV**, type `HELLO`, preview, **Review destination**, confirm and **Update now**.
5. Confirm TV 1 changes. TV checks every second when online. The phone will only say playback is confirmed after the TV reports displaying the new revision. Mobile/TV tests on your actual equipment are still necessary.
6. Add TV 2, publish a different slide to TV 2 and verify TV 1 remains unchanged.
7. Try **Publish later** with a test time; scheduled changes activate on the next TV/dashboard poll at or after that time. Optional cron enables time-based activation even if all browsers are offline: **do not configure cron until PHP and MySQL diagnostics pass**.

## 4. Links

- Creator home: `/` or `/index.html`
- Quick Publish: `/publish.html`
- TV Manager: `/tv-admin.html`
- Advanced Editor: `/editor.html`
- English/Spanish owner guide: `/start.html`
- Installer: `/setup/install.php` (locks itself after setup)
- TV #1: `/galaxy-goulds-tv-1/` (created in Manager)
- Old extensionless `/tv-admin` bookmarked URLs also rewrite to TV Manager.

The public TV URLs are read-only; **publishing endpoints require authenticated owner login, CSRF protection and HTTPS**. The Creator's source photos and editable drafts currently remain in local IndexedDB; only exported slide images are sent to the server for TV playback. **This build does not publish to your product-selling website or connect to Global Inventory Master.**

## 5. Backups / rollback

- Keep a copy of the previous release ZIP and `api/config.php` in a private location; do not package secrets with public release ZIPs.
- Back up MySQL `ft_registry` and private `/home/coolqdzx/.4tier-marketing-private/` plus `public_html/tv-content/` (published JPEGs), since DB-only recovery cannot restore image files.
- For rollback, restore the prior code **without deleting the live MySQL table or image data**. Root-vs-subfolder changes require checking `FT_BASE_PATH`, the TV URLs, and the old backend's storage mode. A rollback to JSON-backed v0.0.10 is **not** an automatic database downgrade; coordinate migration rather than pointing old code at new MySQL and assuming it works.
- Web server logs are private; never share passwords or `api/config.php` contents when reporting errors.

## 6. Security and browser caveats

- A browser extension's `contentscript.js` EventEmitter/MetaMask warnings are usually not application errors. A `/tv-admin` 404 may be a missing route before this ZIP has been extracted; new UI links use `/tv-admin.html`.
- For displays, fullscreen/wake-lock require permission and device support; no site can silently force fullscreen on every smart TV. Use browser kiosk/autostart when appropriate.
- Public TV URLs are intentionally viewable without login. Do not show unapproved demo prices to customers. Age-restricted products and promotional copy require compliance review.
- This has not been deployed to live cPanel by ChatGPT. A MySQL-backed test on **your** hosting is mandatory before production use.
