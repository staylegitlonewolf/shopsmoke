# Integration Audit — 4Tier Marketing Studio v0.0.9 → v0.0.10

**Date:** 2026-10-23 · **Auditor:** Claude (Anthropic) · **Scope:** the `marketing-editor-v0.0.9/` folder and `marketing-editor-v0.0.9.zip` on the owner's desktop, plus the 23 Oct handoff document.
**No live hosting was accessed.** Nothing here says anything about `coolwebsitedude.com`. Every finding comes from reading the code and running it locally.

---

## 1. Release identity — which v0.0.9 is "real"?

| Item | Finding |
|---|---|
| `marketing-editor-v0.0.9.zip` | 24 files, root-flat, as described in the handoff. |
| `marketing-editor-v0.0.9/` folder | Same files **except `editor.html`**: the folder copy is 4 KB larger and newer than the ZIP copy (it adds the "unified toolbar" / header-Layers patch). |
| Consequence | Two different v0.0.9 editors exist. Whichever one is on the server is unknown until the owner checks. **v0.0.10 is built from the newer folder copy, with its crash fixed** (see B1). |

## 2. Defects found (and fixed in v0.0.10)

| # | Severity | Where | Problem | Evidence |
|---|---|---|---|---|
| B1 | **High** | `editor.html` (folder copy), last block | `$('.toolbar')` is called, but `$` is `getElementById`, so it returns `null` → `TypeError` at line 505. The unified toolbar, the removal of the "Tool:" readout and the double-click suppression never ran. | Reproduced in Chromium: `Uncaught TypeError: Cannot read properties of null (reading 'classList') at editor.html:505`. |
| B2 | **Critical — data loss** | `editor.html` `openProject()` | Multi-page timelines only restore when `version` is `0.0.4`–`0.0.7`. v0.0.9 saves `0.0.9`, so **reopening a v0.0.9 project file or recovering its autosave keeps only one page**, and the next autosave overwrites the rest. | Reproduced: a 3-page autosave tagged `0.0.9` reopens as 1 page in the v0.0.9 editor; v0.0.10 restores all 3. |
| B3 | Medium | `api/index.php` `screen` action | Every TV poll (every 15 s per TV, sent without cookies) ran `session_start()`, so **each poll created a new PHP session file**. That is about 5,760 files per TV per day. | Fixed: TV polls now run before the session starts. Verified: 5 polls create 0 session files and send no `Set-Cookie`. |
| B4 | Medium | `tv/player.js` | The API accepts `slide-left`, `slide-up` and `zoom`, but the TV only ever faded or cut. The TV did not match the editor preview. | Fixed: real CSS transitions. The geometry was measured in Chromium and matches the editor's preview math. |
| B5 | Low | `api/index.php` `release` | A page with no `transition` read an undefined key. On hosts that display PHP warnings, this **breaks the JSON reply**, even though the release was saved. The editor always sends a transition, so this is latent. | Reproduced with PHP 8.3 built-in server; fixed. |
| B6 | Low | `editor.html` | Opening or recovering a project with a different canvas size kept the old zoom. On phones, a recovered 1920×1080 board overflowed the screen until Fit was pressed. | Fixed: `openProject` refits. |
| B7 | Low | `tv/player.php` | When installed at the web root, asset URLs became `//tv/player.css` (protocol-relative to a host named "tv"). | Fixed with `rtrim`. |
| B8 | Low | `api/index.php` | Sites behind a proxy or CDN that terminates TLS (e.g. Cloudflare) reported plain HTTP → every action returned **426**. | Now also honours `X-Forwarded-Proto: https`. Verified with curl. |
| B9 | Info | `editor.html` About page | Still said "v0.0.8 … No live store publishing". | Updated. |

## 3. Architecture as built (verified by reading and running)

```
Creator PWA (index.html, app.js) ── IndexedDB "fourTierMarketingPWA" (device-local photos/drafts)
Advanced Editor (editor.html) ───── IndexedDB "fourTierEditorBackupV007" (device-local autosave)
        │ Play Preview → Publish: renders each page → 1920×1080 JPEG → upload_page → release
        ▼
api/index.php (PHP 8.1+, no MySQL) ── ~/.4tier-marketing-private/ (OUTSIDE public_html)
        │                                 v009-data.json  (stores, screens, revisions, schedules, activity, pw hash)
        │                                 staged/*.jpg    (private uploads)
        │                                 heartbeats/*.json, login-rates/, cron-last-run.txt  (v0.0.10)
        ▼ activate_revision copies JPEGs →  public_html/marketing-editor/tv-content/*.jpg (random 26-hex names)
TV browser: /marketing-editor/<store>-tv-N/ ─(.htaccess)→ tv/player.php → player.js polls ?action=screen every 15 s
```

* **Shared on the server:** stores, TV screens, published and scheduled revisions, activity log, and (new) TV check-ins.
* **Device-local only:** Creator photos and folders, editable projects, autosave. Clearing site data destroys them unless `.marketing.json` backups exist.
* **Not connected:** Global Inventory Master / Retail OS, MySQL, POS, ecommerce storefront pages. Nothing in this code writes to them.

## 4. Security review (pilot level — not a certification)

| Area | Status |
|---|---|
| Secrets | No credentials ship. Setup needs `api/config.php` (≥32-char key), created by the owner on the server. Password is stored with `password_hash`. ✅ |
| Session / CSRF | `HttpOnly`, `SameSite=Strict`, `Secure` on HTTPS. All writes need the `X-FT-CSRF` header. Sessions idle out after 1 h. ✅ Verified: a bad token gets 403 and a logged-out dashboard gets 401. |
| Brute force | 5 tries per session, then 8 per IP per 15 min. ✅ Basic, but adequate for a single owner. |
| Uploads | `getimagesize` JPEG check, 100 B–5 MB, ≤4096 px. Stored privately, then copied publicly with random names. `tv-content/.htaccess` blocks script, HTML and SVG types. ✅ Verified: a PHP payload with a `.jpg` name is rejected. |
| Public TV data | Manifests are public by design and slugs are guessable (`galaxy-goulds-tv-1`). **Never publish anything private to a TV.** |
| Residual risks | Single owner account (no staff roles, no 2FA). Revisions and staged images are never deleted (unbounded disk use). The JSON registry is one file with no automatic backup. `config.php` exposure depends on PHP running in `api/`; the new Deployment Check tests for it. |

## 5. What is verified vs. what still needs the real world

**Verified locally in this session (PHP 8.3.36 built-in server + Chromium):** see `QA-v0.0.10.md`. That covers a 48-check API suite, the TV Manager GUI, an editor Publish → TV round trip, measured TV transitions, phone-width layout, and DST time display.

**Still NOT verified (needs the owner's hosting and hardware):**
1. cPanel Apache: `mod_rewrite`/`AllowOverride`, PHP version and limits, writable `~/.4tier-marketing-private`. *The new **Deployment Check** button answers all of these in one click.*
2. Real TVs: autoplay/kiosk mode, full-screen, 15-second pickup, 15-minute reload, power and network loss, legibility at viewing distance.
3. Android and iPhone: PWA install, camera and gallery, autosave survival.
4. Cron on the real host (optional), and Eastern-time scheduling from the owner's actual devices.
5. Content: real prices and product approval, compliance review, real QR target.

## 6. Recommendation (unchanged from the handoff, now easier)

Deploy v0.0.10 to **staging** first. Run **TV Manager → Deployment Check**. Then follow the "First real Galaxy Goulds TV" checklist in `QA-v0.0.10.md` with two TVs. Each TV card now shows whether that TV has checked in and whether it is showing the latest menu, so the owner can confirm "TV #1 changed, TV #2 didn't" without standing in front of both screens.
