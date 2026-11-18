# ShopSmoke Marketing Studio v0.0.40

**v0.0.40:** the home page adds the standard `mobile-web-app-capable` tag, so Chrome no longer shows a deprecation warning. iPhones still use the Apple tag.

**v0.0.39:** TV links are `https://` only again; older TVs work because the site now uses a Let's Encrypt certificate.

**v0.0.38:** TV screens no longer show the "SHOPSMOKE • STORE DISPLAY" corner label.

**v0.0.37:** the TV player runs on older TV browsers. (Its `http://` TV links were removed again in v0.0.39.) See `CHANGELOG.md`.

**v0.0.36:** iPads keep room at the top and bottom of the screen, and every page has a solid top bar that stops the iPadOS fog. See `CHANGELOG.md`.

**v0.0.35:** a login lasts 30 days on each device, and Settings has App updates with Clear cache. See `CHANGELOG.md`.

**v0.0.34:** every app page opens on a login splash. TV screens stay public. See `CHANGELOG.md`.

**v0.0.33:** the store list has its own row on phones, and the Stores view has no preview-only messages. See `CHANGELOG.md`.

**v0.0.32:** Settings shows the real version, and its delete button says what it deletes. See `CHANGELOG.md`.

**v0.0.31:** the TV Manager's forms are pages, and on phones the other forms and lists fill the screen. See `CHANGELOG.md`.

**v0.0.30:** team accounts have their own page, Preview fills the screen with the picture first, and the timeline's page row is at the bottom. See `CHANGELOG.md`.

**v0.0.29:** the Create screen sets the photo's Name and Library folder. See `CHANGELOG.md`.

**v0.0.28:** 250 pages per TV with split or video beyond that, clean addresses without .html, timeline Select, page name in Properties. See `CHANGELOG.md`.

**v0.0.27:** progress card when deleting photos, Import file in the File menu, clearer Download screen. See `CHANGELOG.md`.

**v0.0.26:** sound toggle per video, Tools tab, page queue drag, All stores in Folders, simpler Create screen, video Delete fixed. See `CHANGELOG.md`.

**v0.0.24.1:** 🎬 Make video from the timeline, faster Preview and bulk export for 100+ photos, Library ZIP download, and a progress card with time and a saved confirmation. Built on v0.0.24, without the separate v0.0.25 changes. See `CHANGELOG.md`.

**v0.0.24:** video links are back, video Properties and Start/End cuts, one-field Add Store, About in Settings. **v0.0.23:** Photo / Video / Embed labels on the timeline and real players in the editor and Preview. See `CHANGELOG.md`.

**v0.0.22 (test build for coolwebsitedude.com):** the editor is back as a page timeline for hundreds of photos and for videos, setup asks only for an email and password and then deletes itself, Log in / Log out button, hamburger menu, shorter tab names, Library with multi-delete, who-added-it names, "On TV" ribbons and a sync alert. See `CHANGELOG.md`.

**v0.0.21:** photo-only publishing (take / upload / gallery), owner approval for staff photos, Galaxy logo, bottom bar on every page, editor removed from the app. **v0.0.20:** renamed from 4Tier to ShopSmoke, new domain www.shopsmoke.online (see `MIGRATE-v0.0.19.md`), black top bar, 1-second TV updates. See `CHANGELOG.md`.

**Start with `HANDOFF-v0.0.17.md`** (complete overview). v0.0.17: team accounts with phone-number logins and per-person permissions, server drafts, photo sync, editable published menus. See `CHANGELOG.md` and `QA-v0.0.17.md`.

**v0.0.15:** editor menus and image insert fixed on phones, ✎ Edit on TV cards (edit what a TV is showing), Editor link in the home bar, and paged Recent activity with Download and Delete. See `CHANGELOG.md` and `QA-v0.0.15.md`.

**v0.0.14:** clean light theme everywhere, slide shapes (Story designs stay 9:16 on the TV), auto-filled store slugs, and no zooming into fields on iPhone. See `CHANGELOG.md` and `QA-v0.0.14.md`.

**v0.0.13:** tool-style layout. The phone editor fits one screen, pages share a pinned header, and the Creator home is lean (its wording moved to Help). See `CHANGELOG.md` and `QA-v0.0.13.md`.

**v0.0.12:** History → Delete (with confirmation), Save to Recent photos from Quick Publish and History, readable TV Broadcast card, and `/service-worker.js` to clear the old website's worker. See `CHANGELOG.md`, `QA-v0.0.12.md`, and the upgrade steps at the top of `DEPLOY.md`.

---

A multi-store digital signage and artwork publishing pilot for the owner of Galaxy Goulds, Galaxy Wynwood, The Farmacy, 57th Ave and The Square Smoke Shop. Goal: place each TV on a permanent web address; create a design from a phone or Advanced Editor; select a store and TV; preview; publish now or schedule; let the TV update without changing its address.

**v0.0.11 completion from Claude's WIP:** new responsive `publish.html`, `publish.js`, `publish.css`; MySQL setup at `setup/install.php` (already in WIP); phone photo-to-TV hooks in Creator; EN/ES start guide; root setup; and PHP TV polling/fullscreen behavior. v0.0.10 source stays separate.

## Main routes

| URL | Purpose |
| --- | --- |
| `/` | Creator PWA, photo capture, local library and drafts |
| `/editor.html` | Page timeline: photos and videos from the Library, timing, preview, drafts, publish |
| `/publish.html` | Owner Quick Publish: store, TV, text/photo, preview, publish now/later |
| `/tv-admin.html` | Owner login, stores and screens, schedules, history/rollback, diagnostics |
| `/setup/install.php` | First-time owner email + password; deletes itself after the owner agrees |
| `/start.html` | Owner quick start (English/Spanish) |
| `/{store-slug}-tv-N/` | Public read-only TV player, per-screen permanent URL |

## Architecture

- PHP 8.1+ `api/index.php` with owner session authentication, CSRF protection, upload validation, 1-second TV checks and independent screen revisions.
- MySQL installer writes `api/config.php` (not in release) and creates one transactional `ft_registry` InnoDB row with a versioned JSON registry (stores, screens, revisions, schedules). This is a pilot storage design; not a normalized ERP catalog database.
- Published JPEG slides go into `tv-content/`. Staged images, login-rate files, heartbeats and cron timestamps belong in `.4tier-marketing-private` outside `public_html`.
- Creator photos/drafts remain **local IndexedDB**, but the Send to TV action sends a flattened image to Quick Publish on that same device; the owner chooses where to publish it.
- When an online TV gets a new revision, it loads images and loops with transitions. The first fullscreen action usually requires a tap or TV-remote OK. Fullscreen and screen-awake cannot be guaranteed on all smart TVs.
- Schedules are saved in UTC. They activate when a TV/dashboard queries the API or when an optional cPanel cron command runs; cron isn't configured by this package.

## Important deployment facts

This ZIP has flat entries for extracting directly into `/home/coolqdzx/public_html/`. No database password, owner password, live API config, or test credentials are included. After upload open `/setup/install.php` straight away: until the owner has run it, whoever opens it first becomes the owner. If it says “Already installed”, keep the configuration and use the TV Manager directly. New installs keep everything in the private folder; a developer can choose MySQL on the setup page. Never paste credentials into chat. Detailed steps: **DEPLOY.md**.

**Verification status (v0.0.12):** the installer, MySQL registry, cron, history delete and Recent-photos saving were tested locally against MariaDB 11.4 in a copy of the cPanel folder layout (`QA-v0.0.12.md`). The owner has installed v0.0.11 on the live host and reported it working. Real Android/iPhone and TV-browser checks are still owner acceptance items; this is a pilot, not a production-certified system.

## Out of scope for v0.0.11

No cross-device Creator asset sync, POS inventory price synchronization, staff roles, live public store website publishing, or automated content approval/compliance enforcement. Internal TV menus and public retail website are separate destinations.
