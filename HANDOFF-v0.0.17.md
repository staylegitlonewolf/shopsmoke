# ShopSmoke Marketing Studio — Handoff (v0.0.17)

**Date:** 2026-10-24 · **Live site:** https://www.shopsmoke.online/ (cPanel user `coolqdzx`, LiteSpeed/PHP, MySQL `coolqdzx_smokeshopdatabase`) · **Status:** working pilot used by the owner; not a certified production system. This document is the current reference for anyone continuing the project.

---

## 1. What it is

A phone-first marketing and TV-menu tool for the five Galaxy / smoke-shop stores. Take or upload a product photo, make a slide or multi-page menu, pick a store and a TV, and publish now or later. Each TV is a browser on a permanent link that updates itself. Since v0.0.17 a team can share it: the owner creates logins (phone number or username) and ticks what each person may do.

| Page | URL | Purpose |
|---|---|---|
| Creator (home) | `/` | ＋ Create · ⚙ Settings · 📺 Publish to TV, TV schedule, Recent photos; Media Library with ☁ Sync; Saved Drafts with Team drafts; Settings → Account (sign in/out) |
| Quick Publish | `/publish.html` | Phone-first: store → TV (or ＋ Add TV) → headline/photo → slide shape → Publish now / Later → confirm → "TV is showing it" |
| Advanced Editor | `/editor.html` | Layers, text, photos (red ＋ Add), multi-page timeline, Preview → Publish; File → Save as Draft / Open draft / Account; ✎ Edit reopens a TV's editable menu |
| TV Manager | `/tv-admin.html` | Stores (auto slugs), TVs, online status, History (put back / delete / save), schedules, **Team accounts**, Deployment Check, activity (paged, by person, download, delete) |
| Help | `/start.html` | Owner guide (EN/ES), team / drafts / sync, TVs that stay on, **About & License** (logo + draft terms) |
| TV player | `/<store>-tv-<n>/` | Full-screen slideshow (press OK/tap once), checks every 1 s |
| Installer | `/setup/install.php` | One-time: MySQL + owner password → `api/config.php`, then locks |

## 2. Accounts and permissions

* **Owner:** signs in with the password only (Username blank). Can do everything, and is the only one who manages stores, team accounts, the deployment check, deleting TVs and clearing activity.
* **Team accounts:** created in TV Manager → Team accounts. Login = a **phone number** (any format; stored as digits) or a username, plus a password (10+). Each account has **stores** it can see and **permissions**:
  `publish` (publish now/later, put back) · `tvs` (add/rename TVs) · `history` (delete old menus, cancel schedules) · `activity` · `drafts` · `photos` · `delete_any` (delete other people's drafts/photos).
  Presets: **Manager** = all of these; **Staff** = drafts + photos; anything else shows as **Custom**.
* The server enforces every rule; the pages also hide what an account can't use. Disabling or deleting an account ends its access on the next request.
* Activity entries record who did what ("· Maria Lopez").

## 3. Architecture

```
Browser ── Creator photos & drafts: IndexedDB "fourTierMarketingPWA"  (device; ☁ Sync copies photos to/from the server)
        ── Editor autosave + device drafts: IndexedDB "fourTierEditorBackupV007"
        ── ftapi.js: login box, chunked uploads (≤1 MB parts, under the host limit), drafts, photo library
api/index.php (PHP 8.1+) ── registry in MySQL ft_registry (one JSON row): stores, TVs, revisions (+draft_id), schedules,
                             activity (500), users (hashed passwords, stores, perms), drafts index, media index
                         ── /home/coolqdzx/.4tier-marketing-private/  staged JPEGs, drafts/<id>.json(+.jpg), media/<hash>.<ext>(+thumb),
                             uploads/ (temporary parts), heartbeats, login limits, cron stamp
                         ── public_html/tv-content/  published JPEGs only
TV browser ── /<slug>/ → tv/player.php → player.js polls ?action=screen every 1 s (v0.0.18)
```

* **On the server:** stores, TVs, menus and history, schedules, activity, team accounts, **team drafts**, **synced photos**, editable copies of published menus.
* **Device-only until saved/synced:** Creator photos (☁ Sync), Creator photo designs, editor autosave and device drafts.
* **Not connected:** Retail OS / inventory, POS, prices, ecommerce pages.

## 4. Files

Creator `index.html app.js style.css tv-home.js creator-library.js creator-cloud.js` · Quick Publish `publish.html/.js/.css` · Editor `editor.html` · TV Manager `tv-admin.html/.js/.css` · Help `start.html` · shared `ftapi.js viewport.js ftbar.css sw.js service-worker.js manifest.webmanifest` · server `api/index.php api/config.example.php api/.htaccess setup/install.php tv/* tv-content/.htaccess .htaccess`. **Never in a ZIP:** `api/config.php`.

## 5. Deploy / upgrade / recover

* **Upgrade:** back up `public_html` and export the database, then extract the ZIP into `public_html`. Keep `api/config.php`; no installer run. Reopen the app on phones once. The registry gains `users`, `drafts` and `media` lists automatically; nothing to migrate.
* **Back up regularly:** database (phpMyAdmin) **and** `/home/coolqdzx/.4tier-marketing-private/`, which now holds drafts and synced photos.
* **Forgot owner password:** delete `api/config.php`, run `/setup/install.php` with the same database. Team accounts, TVs and menus are kept.
* **Cron (optional):** `php /home/coolqdzx/public_html/api/index.php cron` every minute.
* **Rollback:** extract the v0.0.16 ZIP. Older versions ignore the new lists and treat everyone as owner-only, so team logins stop working until you upgrade again.

## 6. Daily use

1. **TV:** open its link in the TV browser and press OK once (kiosk app for all-day TVs).
2. **Phone:** Publish → store → TV → text/photo → Review → Confirm.
3. **Team:** staff build in the Editor and **Save as Draft**; a manager or the owner opens it (Open draft / Team drafts), then Preview → Publish.
4. **Photos everywhere:** Media Library → ☁ Sync on each phone.
5. **Undo:** TV Manager → History → Put back on TV.

## 7. Version history

v0.0.10 audit and core fixes · v0.0.11 root install, MySQL installer, Quick Publish · v0.0.12 history delete, Recent photos · v0.0.13 one-screen phone editor, pinned headers · v0.0.14 light theme, slide shapes, auto slugs, no zoom · v0.0.15 phone menus, ✎ Edit, paged activity · v0.0.16 dialog layering, opacity, final polish · **v0.0.17 team accounts + permissions (phone logins), server drafts, photo sync, editable published menus, Settings/Account, interface tweaks, logo → Help license.**

## 8. Testing approach

Portable PHP 8.3 + MariaDB 11.4 in a scratch folder, mirroring the cPanel layout. Scripted suites: **core (33 checks)** for installer, publishing, delete rules, cron and security; **v0.0.17 (52 checks)** for accounts, phone logins, every permission boundary, chunked drafts and photos byte-for-byte, and editable copies. Browser checks at phone widths. See `QA-v0.0.17.md`.

## 9. Known limits / next ideas

* Photo **deletions** don't sync (delete on each device; the server keeps what was uploaded). Creator photo **designs** (Saved Drafts → designs) stay on the device; editor drafts are the shared format.
* Drafts embed their images, so a big multi-photo project can be tens of MB (limit 80 MB per draft; chunked upload handles host limits).
* No password reset by SMS or email: the owner resets a person's password in Team accounts.
* TV keep-awake and full screen depend on the TV browser (kiosk app recommended).
* No link to inventory or prices. License terms in Help are a **draft** to be reviewed by a lawyer.
* Ideas: registry/backup download, offline-TV alerts, storage usage per store, read-only product picker from Retail OS after an audit.
