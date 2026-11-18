# Move to www.shopsmoke.online (v0.0.19)

From the old cPanel account **`coolqdzx`** (coolwebsitedude.com, database `coolqdzx_smokeshopdatabase`)
to the new account **`shoprbbv`** (www.shopsmoke.online, database `shoprbbv_backend`, user `shoprbbv_owner`).
Stores, TVs, menus, history, schedules, team accounts, team drafts and synced photos all come across.

Everything is in three places on the old account. All three must be copied:

| What | Old account | New account |
|---|---|---|
| Database table `ft_registry` (stores, TVs, menus, team accounts, ...) | `coolqdzx_smokeshopdatabase` | `shoprbbv_backend` |
| Private folder (drafts, synced photos, staged images) | `/home/coolqdzx/.4tier-marketing-private/` | `/home/shoprbbv/.4tier-marketing-private/` |
| Published TV images | `/home/coolqdzx/public_html/tv-content/` | `/home/shoprbbv/public_html/tv-content/` |

In cPanel File Manager turn on **Settings → Show Hidden Files** on both accounts (the private folder and `.htaccess` start with a dot).

## 1. Before you start (old address, every phone)

1. On each phone, open the **old** address and do **Media Library → ☁ Sync** (pick each store).
2. In the Editor, anything still only on the device: **File → Save as Draft → server**.
3. Tell the team **not to publish or edit** until step 6 is done. Anything changed on the old site after step 3 is not copied.

## 2. Prepare the new account (`shoprbbv`)

1. **SSL/TLS Status → Run AutoSSL**: `shopsmoke.online` and `www.shopsmoke.online` must both have a certificate.
2. **Select PHP Version** (or MultiPHP Manager): PHP **8.1 or newer** with `pdo_mysql`, same as the old account.
3. File Manager → `public_html` → upload **marketing-editor-v0.0.19.zip** → Extract. `index.html`, `api/`, `setup/` should sit directly in `public_html`.
4. **Do not open `/setup/install.php` yet.**

## 3. Copy from the old account (`coolqdzx`)

1. **phpMyAdmin** → `coolqdzx_smokeshopdatabase` → table **`ft_registry`** → **Export** → Quick, SQL → **Go**. Save the `.sql` file.
2. **File Manager** → home folder `/home/coolqdzx` → right-click **`.4tier-marketing-private`** → **Compress** → Zip → download the zip.
3. **File Manager** → `public_html` → right-click **`tv-content`** → **Compress** → Zip → download the zip.

## 4. Put it on the new account (`shoprbbv`)

1. **phpMyAdmin** → `shoprbbv_backend` → **Import** → choose the `.sql` file → **Go**. You should now see the table `ft_registry`.
   - If it says the table already exists, the installer already ran here: select `ft_registry` → **Operations → Drop the table**, import again, then delete `public_html/api/config.php` before step 5.
2. **File Manager** → home folder `/home/shoprbbv` → upload the private-folder zip → **Extract** here. You should get `/home/shoprbbv/.4tier-marketing-private/` (not a folder inside another folder).
3. **File Manager** → `public_html` → upload the `tv-content` zip → **Extract** here, overwrite if asked. You should get `public_html/tv-content/` full of `.jpg` files.

## 5. Run the installer on the new domain

Open **https://www.shopsmoke.online/setup/install.php** and enter:

- Database name `shoprbbv_backend` · Database user `shoprbbv_owner` · Database host `localhost`
- The database password you created in cPanel
- An owner password (12+ characters; it can be the same as before)

The installer keeps the imported data and only sets the owner password. Team members keep their own logins.

## 6. Check the new site

1. **https://www.shopsmoke.online/tv-admin.html** → sign in → **Run deployment check**: every row ✓.
2. All stores, TVs, History, schedules and Team accounts are there.
3. Open one TV link on the new address, e.g. `https://www.shopsmoke.online/galaxy-goulds-tv-1/`: it shows that TV's current menu (not "not registered").
4. In the Editor, **File → Open draft…** lists the team drafts; Media Library → ☁ Sync downloads the photos.

Only continue when all four are right.

## 7. Move the TVs (old account)

1. Old account **File Manager** → `public_html` → rename `.htaccess` to `.htaccess-v0.0.18-backup`.
2. Upload **`.htaccess` from v0.0.19** into the old `public_html` (only this file).
3. Within 15 minutes every TV reloads, lands on `https://www.shopsmoke.online/<store>-tv-<n>/` and shows up as checked in on the **new** TV Manager.
4. Update the start address in any kiosk app (e.g. Fully Kiosk) when convenient.

To undo: delete that `.htaccess` and rename the backup back.

## 8. Afterwards

- Everyone uses **https://www.shopsmoke.online/** from now on and signs in again there (logins and on-phone photos belong to each address).
- If you used the cron job, add it on the new account: `php /home/shoprbbv/public_html/api/index.php cron` every minute.
- Back up the new database and `/home/shoprbbv/.4tier-marketing-private/`.
- Keep the old account and domain running for a few weeks so any missed TV or phone still finds its way, then cancel.
