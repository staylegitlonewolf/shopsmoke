# Change log

## v0.0.39 — 2026-11-08 · TV links are https only again

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `.htaccess` changes back to the v0.0.36 rules, so it must be replaced too. shopsmoke.online uses the `-shopsmoke` zip. Nobody needs to log in again.

- **The real fix for older TVs was the certificate.** The Galaxy Goulds LG TV did not trust SSL.com's 2022 root, but it does trust Let's Encrypt's ISRG Root X1. shopsmoke.online now uses a Let's Encrypt certificate installed with acme.sh from cPanel Terminal, and the TV opens its normal `https://` link with no warning. See the certificate note in `DEPLOY.md`.
- **http:// TV links removed.** Namecheap's SSL plugin redirects every `http://` request to `https://` before `.htaccess` runs, so the v0.0.37 exceptions never worked on this host. They are gone, and every page, including TV pages, moves to HTTPS again.
- **Help text:** TV Manager and the Start guide (English and Spanish) no longer suggest `http://`. They now say to update the TV's software or plug in a streaming stick with a web browser.
- The older-browser TV player from v0.0.37 stays.

## v0.0.38 — 2026-11-07 · No corner label on TVs

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `.htaccess` is the same as v0.0.37. shopsmoke.online uses the `-shopsmoke` zip. Nobody needs to log in again.

- **TV screens:** the "SHOPSMOKE • STORE DISPLAY" label in the bottom-right corner is gone, so menus show with nothing on top of them.
- The TV page loads `player.js` and `player.css` with `?v=0.0.38`, so TVs pick up the change on their next reload.
- **TV links over http:// need one cPanel setting:** if cPanel → Domains has "Force HTTPS Redirect" turned on for the domain, the server moves every `http://` address to `https://` before `.htaccess` runs, so the v0.0.37 TV exception never applies. Turn it off there; `.htaccess` still moves logins and every other page to HTTPS.

## v0.0.37 — 2026-11-07 · Older TVs

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `.htaccess` changes, so it must be replaced too. shopsmoke.online uses the `-shopsmoke` zip. Nobody needs to log in again.

- **TV player on older TV browsers:** `tv/player.js` is now written in older JavaScript (no arrow functions, `fetch`, `async` or `?.`). Browsers that can't read newer code, such as LG TVs from 2016 to 2021, skipped the whole file and stayed on "Waiting for your first published menu…".
- **TV player styles:** `tv/player.css` no longer uses `inset`, flex `gap` or 8-digit colours, which older browsers ignore. Without `inset` the picture area had no size, so nothing showed. Each `clamp()` size has a plain size before it.
- **TV links also work with http://:** an older TV that says "The server's security certificate is not valid" can open the same link starting with `http://`. Only what a TV needs skips the HTTPS redirect: the TV page, `tv/`, `tv-content/` and `api/index.php?action=screen`. Logins, the editor, publishing and every other page still move to HTTPS. A TV link typed with `http://` on the bare domain goes to `http://www.`.
- **Why the warning:** the site's certificate now comes from SSL.com, under a root from 2022. Phones and computers know it; older TVs only learn new roots through firmware updates.
- **Help text:** TV Manager and the Start guide (English and Spanish) mention the `http://` option.
- The TV page loads `player.js` and `player.css` with `?v=0.0.37` (was `0.0.24`), so TVs pick up the new files.

## v0.0.36 — 2026-11-07 · iPad top and bottom space

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip. Nobody needs to log in again, and the Home Screen icon stays as it is.

- **iPad header:** iPadOS 26 and later fog the top of a Home Screen app, about 60px down, and the header buttons there stopped working. Every page now has a solid black bar across the top of the screen, which is what makes the iPad drop the fog, and the header starts below it.
- **Top space:** 44px in the installed iPad app, 3vh in an iPad Safari tab. It's one number in `viewport.js`.
- **Bottom space:** at least 20px on iPads, so the timeline and bottom bars stay clear of the home bar.
- **No more sliding:** the page no longer bounces down and back when it's pulled. After the keyboard closes, a page with nothing to scroll goes back to the top.
- Phones and computers keep the same spacing.
- **Settings:** removed the "Public website publishing: Disabled" row. It was fixed text and looked like a fault.

## v0.0.35 — 2026-11-07 · Stay logged in for 30 days, App updates in Settings

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `.htaccess` changes, so it must be replaced too. Everyone logs in once after this update, because logins move to a new folder. shopsmoke.online uses the `-shopsmoke` zip.

- **Settings → App updates:** shows the version on this device and on the server, with **Check for updates**, **Update now** when a newer version is ready, and **Clear cache**. Photos, drafts and the login are not touched. Clear cache needs the internet.
- **No more stuck pages:** pages always ask the server for a newer copy, through both the service worker and a no-cache header in `.htaccess`.

- **A login lasts 30 days on each device**, even after closing the browser. It used to end after an hour without server activity, or when the browser closed. Log out still ends it straight away.
- **Sessions moved to the private folder** (`sessions` inside `.4tier-marketing-private`), because hosts clear their shared session folder after about 24 minutes.
- **Team passwords:** resetting a team member's password now ends their other logins, as changing the owner password already did. Blocking or deleting an account also ends its logins.

## v0.0.34 — 2026-11-07 · Login first

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

- **Login splash:** Home, Library, Editor, Publish, TV Manager, the form pages and Help all open on a login screen until someone logs in, with Forgot password on it. Logging out returns to it.
- **TV screens** don't need a login and keep playing.
- **No internet:** a device that has logged in still opens and works with what's saved on it. A device that hasn't is told to try again.
- **Site not set up yet:** the splash points to setup instead of asking for a login.
- This hides the app from visitors. The page files can still be downloaded, but they hold no store data, and the server already required a login for that.

## v0.0.33 — 2026-11-07 · Phone header on the Home page

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

- **Home header on phones:** the store list has its own row, with Publish, TVs, Editor, Help and Log in on the row under it. Log in no longer runs off the screen on wider iPhones.
- **Stores:** the "Preview only — nothing here is published" box and the "Preview only • Local device" text are gone.

## v0.0.32 — 2026-11-07 · Clearer Settings

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

- **Settings → About** shows the real version. It was stuck on 0.0.24.
- **Delete this device's demo data** is now **Delete everything on this device**, because it removes all of this device's photos, folders and drafts, not demo data. The warning now says what is lost and what stays: synced photos come back with ☁ Sync, and TVs don't change.
- **Install on your phone:** an old developer note is gone.

## v0.0.31 — 2026-11-07 · Pages and full screens instead of popups

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `manage.html` and `manage.js` are new, and `.htaccess` changes, so it must be replaced too. `team.html` and `team.js` from v0.0.30 are no longer used. shopsmoke.online uses the `-shopsmoke` zip.

- **New pages in the TV Manager:** Add Store (/add-store), Add TV (/add-tv), Rename TV (/rename-tv) and TV History (/tv-history) are now pages, like the Team page (/team). Saving returns to the right panel with a confirmation, and mistakes stay on the page.
- **History:** "are you sure?" opens inside the row.
- **On phones, the rest fill the screen:** the Library's photo and folder windows, the Publish page's Library, Add TV and confirm windows, the login box, and the editor's Folders, Publish, Download, drafts, New Canvas, Resize and rename windows. They work on what's open, so they can't be separate addresses without losing it.
- **Editor Folders on a phone:** one scroll for the whole window, with Add to timeline always in reach. Publish's Library keeps Close in reach too, and Publish scrolls to Confirm & publish.
- **Still small boxes:** short "are you sure?" messages and the progress card.

## v0.0.30 — 2026-11-07 · Team page, full-screen Preview, page row at the bottom

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `team.html` and `team.js` are new, and `.htaccess` changes, so it must be replaced too. shopsmoke.online uses the `-shopsmoke` zip.

- **Team accounts:** + Add person and Edit now open their own page at /team instead of a popup. Saving returns to the Team accounts list and confirms it. Mistakes show on the page and keep what was typed.
- **Preview** fills the screen instead of opening as a popup, on every device. The picture is at the top, then the page count with Make video, Publish and Close, then the playback buttons.
- **Preview:** the "Playback details & next moves" heading is gone, and the details sit two to a row. An iPad held upright now gets one column, so the picture is full width.
- **Timeline:** the row of pages is now the bottom row, under View, Page name, Hold and Transition. The status line moved above the timeline, so the pages are the footer.

## v0.0.29 — 2026-11-07 · Name and Library on the Create screen

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

- **Create from a product photo:** Design name is now **Name**, and it renames the photo in the Library. Destination is replaced by **Library**, which picks the folder the photo lives in, with ＋ New folder… at the bottom. Both save straight away.

## v0.0.28 — 2026-11-07 · 250 pages per TV, clean addresses, timeline Select

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. `.htaccess` changes in this build, so it must be replaced too. shopsmoke.online uses the `-shopsmoke` zip.

- **A TV now takes up to 250 pages per publish** (was 100). A timeline can still hold more: with more than 250, Publish offers **Make one video** or **Split into folders** (Part 1, Part 2…) instead of a note.
- **Clean addresses:** /editor, /publish, /tv-admin and /start, with no .html. Old .html links and bookmarks move to the new address by themselves.
- **Timeline: ☑ Select** next to Delete. Tick pages or use Select all, then Delete them together. Deleting every page leaves a clean Page 1.
- **Page name in Properties**, kept in step with the one under the timeline.
- **Publish:** the note about flat JPEG images is gone.

## v0.0.27 — 2026-11-07 · Delete progress, Import file, new Download screen

Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

- **Library: deleting several photos** now shows the progress card with time and Cancel, then a confirmation of what was removed and where.
- **Editor: File, Import file** is back. It adds a picture to the page you are on.
- **File, Download** is redone. Each choice shows what you get before you press it: page name and size, page count, estimated file size and time, video length.

## v0.0.26 — 2026-11-07 · Sound, Tools tab, page queue drag, All stores

Follows v0.0.24.1. The number skips v0.0.25 because a separate v0.0.25 zip exists from another build line; nothing from that zip is in here, but its main changes were remade below. Upgrade: extract over `public_html`, keep `api/config.php`, no database change. shopsmoke.online uses the `-shopsmoke` zip.

**Videos**
- **Sound on or off for each video** in Properties: uploads, YouTube, Vimeo and links. It is off by default. A TV that refuses sound still plays the video silently; YouTube and Vimeo may wait for one press of OK.
- **Nothing plays on the canvas while Preview is open**, so two videos can't overlap. It comes back when Preview closes, and Pause in Preview also pauses the video.
- **Fixed: Delete on an uploaded video.** It kept asking you to press again because the list redrew and forgot the first press. The button now says Press again to delete.

**Editor**
- **Tools tab in Workspace:** Add, Select, Move, Undo, Redo, Zoom and Fit moved there, so the canvas gets that row back.
- **Timeline row:** the Dock button sits far left and shows only its arrow and Dock. The heading now says Timeline.
- **One Save:** the Save button on the timeline is gone. Use File, Save as Draft (or Save as Draft in Preview).
- **Creator Home is now Exit.**
- **Preview page queue:** drag a page by its ☰ to change the order, with a finger or a mouse. The timeline follows.
- **Folders: All stores** in the Store list shows every store's photos and videos on this device. Choose one store to upload.

**Create from a product photo**
- Removed Aspect ratio, Text overlay, Text size and color, Brightness, Contrast, Saturation, Save draft and Export PNG. Designs are 16:9 for TVs.

## v0.0.24.1 — 2026-11-07 · 100+ photos, Make video, upload progress

Built on v0.0.24, not on the separate v0.0.25, so the Create screen cleanup, YouTube sound and dock arrow changes from that build are not here. Upgrade: extract over `public_html`, keep `api/config.php`, no database change. Reopen the app on each device once.

**Make video**
- **🎬 Make video** in Preview, and in File, Download: the whole timeline becomes one 1920 × 1080 MP4 with the same Hold times and transitions.
- Then **Download** it, or **Save to Videos**, then **Put on a TV**. The TV plays it as one page, so a show can pass the 100-page limit.
- The video page goes away after publishing, so the timeline goes back to the photos.
- Videos already on the timeline show as a still picture inside a made video. Made videos have no sound.
- Browsers without the H.264 encoder record the show as it plays, which takes as long as the show.

**Bulk photos**
- **Preview starts at once** and prepares the other pages while it plays. It keeps only the nearby pictures in memory, which matters on an iPad.
- **All pages export is now JPG** and much smaller. **Library: ☑ Select, then ⇩ Download** gives the ticked photos as one ZIP of the originals.
- **Publish** skips a slow PNG step for each page.

**Progress and protection**
- **One progress card** for adding photos, Sync, video uploads, server drafts, exports, Make video and publishing: percent, time so far, time left, Cancel where it is safe.
- **The green tick means saved.** It comes after the server confirms the file, and the size must match. Sync also checks that the server lists each photo. The card shows the date, time, size, who and how long.
- **A dropped connection pauses an upload** for up to 10 minutes. The screen stays awake where the browser allows it, and leaving the page mid-job asks first.
- **Date and time** now show on photos and videos in the editor's Folders.

**Upgrading an older site**
- A site set up before v0.0.22 has no owner email. The owner's first login with an email and the right password saves that email, so email login and Forgot password work from then on. Leaving the email box empty still works.
- Pages now load scripts and styles with `?v=<version>`, so a device can't mix new pages with old cached code on its first open. Keep the version in the HTML and `sw.js` the same when bumping.

**TVs**
- A video now finishes before the 15-minute refresh, so a long video reaches its end. Hold can run up to 4 hours (was 10 minutes).

## v0.0.24 — 2026-11-06 · Video editing, simple Add Store, About in Settings

Upgrade over v0.0.23: extract over `public_html`, keep `api/config.php`, no database change. Reopen the app on each device once.

**Videos**
- **YouTube, Vimeo and video links are back** in Folders, Videos. **Instagram is gone**; Instagram pages in an old draft become plain pictures.
- **Properties on a video page:** change the link, or replace the uploaded video.
- **Cut a video:** set Start and End in seconds. TVs, the editor and Preview all follow it, and the page's Hold follows the cut. Vimeo only uses Start; Hold decides when it stops.
- **Right-click a video, or press ⋯ on it** (the ⋯ is for iPads and for YouTube, which keeps right-clicks for itself): replace or change the link, Start here, End here, clear the cut, duplicate, delete, preview.
- **＋ Add** now sits to the left of Select.

**Stores and TVs**
- **Add a store asks only for the display name.** The web path and TV links are made from it and locked, and a name already in use gets a number added. Existing stores keep their paths.
- The new TV's address in **TV ready** stands out in green.
- A new TV no longer shows an old "last check-in" left by a deleted TV with the same number.

**Fixes after the first v0.0.24 zip**
- Adding an uploaded video to the timeline failed with "addUploadedVideo is not defined".
- Replacing a video of unknown length kept the old Hold; it now goes back to 30 seconds.

**Settings**
- The left menu's bottom block (photos note, Open Advanced Editor, version) moved to a new **About** section in Settings, under Data summary.

## v0.0.23 — 2026-11-06 · Instagram tab, page types, real players

Upgrade over v0.0.22: extract over `public_html`, keep `api/config.php`, no database change. Reopen the app on each device once.

- **Folders has an Instagram tab:** paste Instagram's embed code, or a post or profile link, and add it to the timeline. Only the post's address is kept; the pasted code is never run.
- TVs aren't logged in to Instagram, so **posts from restricted or private accounts show an error on a TV**, and a video may wait for a tap. Uploaded videos are the reliable way to play video on a TV.
- **The video-link box is gone:** Videos is upload only. Pages already made from YouTube, Vimeo or a video link still play.
- **The timeline shows what each page is:** Photo, Video, Instagram or Embed, on every card.
- **Real players:** a video or embed page now plays in the editor and in Preview, instead of showing a still picture.

## v0.0.22 — 2026-11-06 · Page timeline editor, videos, simple setup, Library

Test build for **coolwebsitedude.com**. See the top of `DEPLOY.md` before uploading: it removes the rule that sent coolwebsitedude.com TV links to www.shopsmoke.online.

**First-time setup**
- `/setup/install.php` asks only for **email and password**. A popup confirms; on agreeing, the account is created and the page **deletes itself**. An upgrade ZIP brings the file back; it deletes itself again on first open.
- New installs need **no database**: everything is kept in the private folder outside `public_html`. MySQL stays available under *Developer: use a MySQL database*.
- Until setup has run, whoever opens the page first becomes the owner (the database password used to prove that). Run it right after uploading.
- **Settings, Account:** the owner can change their email and password after giving the current password. Changing the password logs out the owner's other devices. An older install can add its email here.
- **Forgot password?** on every login box drops down an email box. The owner gets an 8-digit code by email, good for 15 minutes and 5 tries, then sets a new password. The reply never says whether the email matched, a new code needs a 60-second wait, and team members still ask the owner. Mail goes through the host's PHP `mail()`, so it can land in spam.
- **Passwords are 4 to 20 characters** for the owner and team, so a simple PIN works. Existing longer passwords keep working.
- The owner logs in with that **email** (not case-sensitive). Older installs still use a blank username or `owner`. A team login can't use the owner's email.

**Advanced editor is back, built around the page timeline**
- Back in the top bar, the left menu, Creator and a photo's Manage menu; ✎ Edit is back on TV cards. The timeline starts open and can be **docked above or below** the canvas (remembered per device).
- Top tools are now **Select · Add · Move** plus undo, redo, Zoom and Fit. Gone: brush, eraser, text, shapes, More tools, brush size, opacity, guides, snap, Colors. **Move** now drags the photo.
- **＋ Add** opens **Folders**: Photos (the Library), Videos, Pages and Drafts. Tick photos and **Add to timeline**: one page each, whole photo on black at 1920 × 1080. Photos are shrunk to TV size as they come in, so 120 photos were about 19 MB in testing. A project holds up to 1000 pages.
- Save is **☁ Save as Draft** only (timeline, Preview, Folders → Drafts, Ctrl+S). Removed: save .json, save slideshow .html, the Slideshows folder and the Preview note. Drafts can be up to 400 MB and hold every page (was 80 MB; reopening cut projects to 60 pages).
- Menus trimmed. Removed: add text, templates, align, clear, fill, gradient, add blank layer, merge from the menu, convert to pixels, Account. **Layer Properties → Properties** (no opacity, "Editable image" or Convert to pixels), **Layer workspace → Workspace**, **Delete Page → Delete**. Text already in old drafts can still be edited.
- **iPad:** ◀ ▶ buttons reorder pages (dragging is unreliable on touch), bigger handles on touch screens, the timeline no longer falls off the bottom in Safari, photos use JPEG there and canvas memory is freed after each photo.
- A TV takes up to **100 pages per publish** (was 30). Publishing sends what the timeline's **View** shows, so one folder can go to a TV from a larger project. TVs now load one page ahead instead of every page at once, and no longer download the page list on every 1-second check.

**Videos on TVs**
- Folders → **Videos**: upload an MP4 or WebM (up to 300 MB, stored in `tv-content/`), or paste a **YouTube, Vimeo or direct https video** link. A video page is a normal timeline page with a ▶ badge.
- TVs play videos **muted**, on top of the page's picture. If a TV can't play it, the picture shows for the page's time. Uploaded videos move on when they end; YouTube and Vimeo play for *Hold (sec)*, now up to 600.
- A video a TV is showing can't be deleted. Upload needs the *photos* permission.

**Login, menu and Library**
- **Log in / Log out** button pinned top right on every page, next to the store picker (green dot when logged in). TV Manager's Sign out button is gone.
- **Hamburger** closes the left menu (remembered per device). Left tabs: **Home, Library, Creator, Publish, Drafts, Manager, Settings, Owner**. Top bar: Publish, TVs, Editor, Help. Store Preview left the menu and top bar; phones still have Stores in the bottom bar.
- **Library** (was Media Library): **☑ Select** several photos and delete them together; each photo shows date, time and **who added it**.
- Deleting a photo now also removes it from the server, because it would otherwise come back on the next ☁ Sync. An account can only remove its own server photos unless allowed to delete others' work.
- **"On TV" / "Not on TV" ribbon** on Library and Recent photos while logged in. Only pages published from v0.0.22 on are tracked, by photo; a photo placed inside a bigger page isn't.
- Quick Publish no longer has the "Save a copy" checkbox: new photos always go in the Library.
- **Sync alert:** a red number beside ☁ Sync and "All photos →" counts photos waiting to upload or download.
- Team view is **Drafts** again: team drafts open in the editor, with approvals below.

**Storage (Settings)**
- A **Storage** card shows this device's use to everyone. The owner also sees photos, videos, TV slides and drafts on the server, and the hosting account's space and file count with a bar that turns amber at 80% and red at 95%.
- The account totals come from cPanel's own `uapi` command on the server, so no API token is stored. If the host blocks that command, the card counts ShopSmoke's files only and the owner can type the plan size. Totals refresh every 10 minutes, or with ↻ Refresh.

**Server:** new actions `account_update`, `recover_request`, `recover_reset`, `storage`, `storage_limit`, `video_list`, `video_commit`, `video_delete`, `media_usage`. Registry reads no longer rewrite the registry: it was being saved on every TV check, once a second per TV.

## v0.0.21 — 2026-11-02 · Photo-only publishing, owner approval, Galaxy logo

Upgrade over v0.0.20 (same steps; `api/config.php` is not in the ZIP; no database change: the registry gains an `approvals` list automatically). Reopen the app on phones once.

**Publishing is photos only**
- **Quick Publish:** 1 Choose the TV → 2 Photo → 3 Publish. "Design your slide" (headline, colours, font size, slide shape, photo fit) is gone. Right under the preview: **◉ Take photo**, **↑ Upload photo**, **▦ Gallery** (photos on this device for the store). The photo fills the slide; it keeps its own shape (16:9 fills a landscape TV; portrait photos fill a portrait TV and show with side bars on a landscape one). AI images made at 1920 × 1080 fill the screen exactly.
- **Advanced editor removed from the app:** no Editor in the top bar, no "Open Advanced Editor", no "Editor (blank canvas)" or "Continue in advanced editor", no "Advanced editor" in a photo's Manage menu, no ✎ Edit in TV Manager, no editor drafts list. `editor.html` stays in the ZIP so old links still open.

**Team and approval**
- **New permissions** (TV Manager → Team accounts): *Publish to TVs now*, *Schedule TV updates for later*, *Send photos to the owner for approval*, *Approve or decline photos*. Manager = everything; **Staff = upload photos, owner approves** (drafts, photos, send for approval). Accounts saved before v0.0.21 that could publish keep scheduling.
- **Send for approval:** an account without publish rights gets **Send for approval** in Quick Publish (as soon as approved, or a requested date/time, plus an optional note). Nothing changes on the TV until approved.
- **Team** (was Drafts/Saved Drafts): photos waiting for approval with **Approve** (publishes now, or at the requested time; a time already passed publishes now) and **Decline**; staff see their own requests and the decision. The owner gets a **👥 Team accounts** shortcut. Designs saved on this device stay below.
- Server: new actions `approval_submit`, `approval_list`, `approval_image`, `approval_decide`; `release` needs *publish* for now and *schedule* for later; `upload_page` accepts publish, schedule or submit. The dashboard reports `approvals_pending`. Every request and decision is in Activity.

**Look and navigation**
- **The Galaxy Smoke Shop logo** replaces the S★S mark (sidebar, Help → About & License) and is the favicon, home-screen icon (iPhone and Android) and installed-app icon. Manifest icons are now separate "any" and "maskable" icons (fixes the DevTools "any maskable" warnings).
- **Home is `/`:** links go to `https://www.shopsmoke.online/`; `/index.html` redirects there (301). Other pages open Home screens with `/?view=gallery|create|team|stores`.
- **The bottom bar (Home · Gallery · Create · Team · Stores) is on every page** at phone/tablet size: Publish, TV Manager and Help too. **Editor → Stores** in the top bar; on phones the top bar hides Stores because the bottom bar has it.
- **Wider store picker** so full store names show (phones included).
- Help (EN/ES) and the installer's next steps describe photo publishing and approval. The offline cache no longer stores the editor or `index.html` separately.

## v0.0.20 — 2026-11-02 · Renamed to ShopSmoke

Upgrade over v0.0.19 (same steps; `api/config.php` is not in the ZIP; no database change). Includes everything from v0.0.18 (black top bar, 1-second TV updates) and v0.0.19 (www.shopsmoke.online, SSL-check fixes). Reopen the app on phones once.

- **4Tier → ShopSmoke everywhere people see it:** page titles, the sidebar logo (S★S · SHOPSMOKE), the installed app name ("ShopSmoke Studio"), Help in English and Spanish (including About & License), the TV waiting screen and badge, the installer, error messages and download file names (`shopsmoke-…`).
- **Kept on purpose, so nothing is lost:** on-device storage names (`fourTierMarketingPWA`, `fourTierEditorBackupV007`, saved settings), the server's private folder `~/.4tier-marketing-private/`, and the `"app":"4Tier Marketing Editor"` / `"4Tier Marketing Studio"` markers inside project and backup files. Existing photos, drafts, backups, team drafts and logins keep working; new files keep the same marker so older versions can still open them.
- **Fixed: TVs could keep the old 15-second player.** The TV page loaded `player.js?v=0.0.17`, so v0.0.18's 1-second player could stay cached; it now loads `?v=0.0.20`. The API reports version 0.0.20 (was 0.0.17).
- **Every version label now says 0.0.20**, including the Creator sidebar.
- License text in Help → About & License now names ShopSmoke; it is still draft wording for legal review.

## v0.0.19 — 2026-11-02 · New domain: www.shopsmoke.online

New cPanel account **`shoprbbv`** (files in `/home/shoprbbv/public_html`, database `shoprbbv_backend`, database user `shoprbbv_owner`). The old site stays on `coolqdzx`. The new domain needs an SSL certificate (SSL/TLS Status → AutoSSL) for both `shopsmoke.online` and `www.shopsmoke.online`, because the app only works over HTTPS. Extract the ZIP into the new `public_html`, then run `/setup/install.php` with the new database. To keep the existing stores, TVs, menus and team accounts, import the old `ft_registry` table into `shoprbbv_backend` **before** running the installer (it keeps existing data) and copy `public_html/tv-content/` and `~/.4tier-marketing-private/` across.

- **New address `https://www.shopsmoke.online/`.** `shopsmoke.online` (without www) now redirects to `www.shopsmoke.online`, so everyone uses one address. A browser keeps logins, saved photos and drafts separately for each address.
- **TVs move themselves:** old TV links (`coolwebsitedude.com/<store>-tv-<n>/`) redirect to `https://www.shopsmoke.online/<store>-tv-<n>/` on the TV's next full reload (within 15 minutes). The old address's API keeps working so TVs stay online until then. This rule only acts on the **old** account, so upload v0.0.19's `.htaccess` there too, and only after the new site has the same stores and TVs (imported data); otherwise TVs would land on "not registered". Update the start address in any kiosk app when convenient; the redirect lasts as long as the old account and domain are kept.
- **Before switching, on each phone:** Media Library → ☁ Sync, and save editor work with File → Save as Draft → server. Photos and drafts that were only saved on the device stay with the old address.
- The Creator's store preview link uses the current address instead of the old domain; Help, README, DEPLOY and the handoff show the new domain.

## v0.0.18 — 2026-11-02 · Black top bar, 1-second TV updates

Upgrade over v0.0.17 (same steps; `api/config.php` is not in the ZIP; no database change). Reopen the app on phones once so the new look loads.

- **Black top bar:** the pinned bar on the Creator, Quick Publish, TV Manager and Help is now black (was navy `#121a39`); its link text and store picker are neutral grey to match. The phone status bar / installed-app colour is black too.
- **TVs pick up a new menu within about a second** (was up to 15 s): the TV player checks the server every second. After publishing, Quick Publish looks for the TV's check-in every second (same ~30 s window), so "Confirmed" shows almost right away. Wording in Quick Publish, the Editor, TV Manager, Help and the installer now says "about a second".
- **Server load:** each online TV now makes one small request per second (was one per 15 s). Fine for a handful of TVs; with many TVs on shared hosting, watch the cPanel resource usage (CPU / entry processes).

## v0.0.17 — 2026-10-24 · Team accounts, server drafts, photo sync, editable TV menus

Upgrade over v0.0.16 (same steps; `api/config.php` is not in the ZIP; the registry gains new lists automatically). Back up `~/.4tier-marketing-private/` from now on: it holds drafts and synced photos.

**Team and accounts**
- **Team accounts (TV Manager → Team accounts, owner only):** add a person with name, **phone number or username** as their login, password, stores, and permission checkboxes (publish, add/rename TVs, history, activity, drafts, photos, delete others' work). Presets: Manager / Staff / Custom. Edit, reset password, disable, delete.
- **Phone-number logins:** "(305) 555-1234", "305-555-1234" and "3055551234" all sign in to the same account. The owner still signs in with the password only (Username blank).
- **Enforced everywhere:** the server checks each action against the person's stores and permissions; TV Manager, Quick Publish and the Editor hide what an account can't use. Disabled or deleted accounts lose access immediately. Activity shows who did each action.
- **Settings → Account** in the Creator (and **File → Account…** in the Editor): who's signed in, Sign in / Sign out. **⚙ Settings** replaces ◉ Photo on the home page, so Settings is reachable on phones (Photo is still on the Create screen).

**Drafts, photos, editable menus**
- **Save as Draft** (File menu and the Preview window): save the editable project to the **server** (team, any device) or **this device**. **File → Open draft…** lists both, with thumbnails; open or delete. **Saved Drafts → Team drafts** in the Creator opens them in the editor.
- **☁ Sync** in the Media Library: uploads this device's photos for the selected store and downloads the team's (same ids, so no duplicates; folders kept).
- **Editable published menus:** publishing from the Editor also stores an editable copy, so TV Manager → ✎ Edit reopens the real text and layers (older menus still open as flat images). Deleting a menu from History removes its copy.
- Large drafts and full-size photos upload in ≤1 MB parts, so the host's upload limit doesn't matter (80 MB per draft, 20 MB per photo).

**Interface**
- Editor: red **＋ Add** next to Select (adds a photo as an editable layer); timeline loses Hide timeline and Folders (they're in the header); "Play preview" → **Preview**, "Save playable slideshow" → **Save**; the Preview window loses its big title and gains ☁ Save as Draft.
- Creator Start screen: **✎ Editor (blank canvas)** button.
- The 4Tier logo moved from the top bars to **Help → About & License**, with draft software terms (EN/ES, marked for legal review). Help also covers team/drafts/sync and kiosk TVs.

## v0.0.16 — 2026-10-24 · Final polish

Upgrade over v0.0.15 (same steps; `api/config.php` is not in the ZIP; no database change).

- **Fixed: the editor's menu bar covered dialogs on phones** (Play Preview, Publish and others; a v0.0.15 regression). There is now one stacking order (bars and panels below menus, below dialogs, below the right-click menu, below toasts).
- **Fixed: Quick Publish's confirm and Add TV dialogs could sit under the pinned top bar**, and the desktop sticky preview slid under it.
- **Opacity slider now works on photos and text:** with Select active and a photo or text layer selected, the toolbar slider reads "Layer opacity" and changes that layer (same value as Layer Properties → Opacity; Undo works). With brush, eraser or shapes it reads "Brush opacity". "Size" is now labelled "Brush size" and dims while Select is active, because it only affects drawing.
- **Accurate status text in the Creator:** sidebar now says "Photos saved on this device / TV publishing uses the owner login"; Settings → Current capabilities shows TV publishing as connected (owner login) and staff accounts as "owner only for now" (it still said "Not connected / demo").
- **New `HANDOFF-v0.0.16.md`:** complete final handoff (pages, architecture, files, deploy/upgrade/recover, owner workflow, testing approach, limits).
- Final regression on a brand-new install and database: 33/33 API checks plus phone (360px) and desktop browser checks.

## v0.0.15 — 2026-10-23 · Editor fixes on phones, Edit what's on a TV, paged activity

Upgrade over v0.0.14 (same steps; `api/config.php` is not in the ZIP; no database change).

- **Fixed: editor menus (File / Edit / View / Image / Layers / Pages) did not open on phones.** The v0.0.13 phone layout let the menu bar scroll sideways, which clipped the dropdowns. Menus now open as a full-width list under the menu bar. All 42 menu actions were exercised at phone size.
- **Fixed: "Could not insert uploaded image" although the photo was inserted.** Cause: every tool change wrote to a toolbar label that the v0.0.9 toolbar patch deleted (that patch only started running after the v0.0.10 crash fix). So inserts, tool switches and "hide handles" threw an error *after* doing their work, sometimes skipping the canvas redraw. The label is now hidden instead of deleted, and the writes are guarded.
- **New: ✎ Edit on each TV card (TV Manager).** Opens the menu that TV is showing in the editor (after a confirmation), one page per published page, keeping page names, durations and transitions. Published pages are flat images, so you add text or photos on top or replace a page. Play Preview → Publish preselects the same TV. The editor reads the public TV feed with `preview=1`, so this is not counted as a TV check-in.
- **New: Editor link** in the Creator's pinned top bar (Publish · TVs · Editor · Help); the bar was tightened to fit narrow phones.
- **Recent activity:**
  - shows 10 entries per page with ‹ Newer / Older ›, and auto-refresh no longer jumps you back from an older page;
  - **⇩ Download** saves the log as a CSV (opens in Excel, keeps accents);
  - **🗑 Delete** clears it after an "Are you sure?" confirmation, leaving one "activity-cleared" entry as a record;
  - the server now keeps the last 500 entries (was 150). New API actions: `activity`, `activity_clear`.

## v0.0.14 — 2026-10-23 · Clean light theme, slide shapes, auto store slugs, no iPhone zoom

Upgrade over v0.0.13 (same steps; `api/config.php` is not in the ZIP; no database change).

- **Add Store auto-fill (TV Manager):** typing the display name fills the URL slug and website path (`Galaxy Kendall` → `galaxy-kendall`, `/galaxy-kendall/`; accents and "&" are cleaned up), and shows what the TV links will look like. Once you edit the slug or path yourself, the name stops overwriting it.
- **Send to TV keeps the design's aspect ratio:** a Creator design set to Story 9:16 (or 1:1, 4:5) opens in Quick Publish at that shape, showing the whole image, and publishes at that size (e.g. 1080×1920). Quick Publish has new **Slide shape** and **Photo fit** (fill / show whole photo) controls. On a landscape TV a portrait slide shows centred with side bars; on a portrait-mounted TV it fills the screen. The confirm step shows the shape.
- **Clean light theme:** the Creator PWA drops the dark purple look for the same palette as Quick Publish, TV Manager and the editor: light page, white cards, navy top bar, red primary buttons, white sidebar and bottom bar. TV Manager stat tiles are white; the app icon background and theme colour are navy.
- **No more zooming into fields on iPhone:**
  - new `viewport.js` on every page makes text fields 16px on touch screens (iOS Safari zooms into anything smaller);
  - the viewport is locked (`maximum-scale=1`) and double-tap zoom on controls is off;
  - dialogs follow the visible area, so they sit above the on-screen keyboard instead of behind it;
  - the focused field scrolls into view once the keyboard opens.
- Quick Publish's compression step no longer assumes 16:9 when it has to shrink a large slide.
- PWA cache → `four-tier-marketing-v0014-shell`.

## v0.0.13 — 2026-10-23 · Tool-style layout, one-screen phone editor, pinned headers

Upgrade over v0.0.12 (same steps; `api/config.php` is not in the ZIP). No API or data changes besides the version number.

- **Advanced Editor on phones fits one screen:**
  - header on one row (`← Home` · Layers · Colors · Timeline · Folders);
  - every toolbar control (undo/redo, size, opacity, zoom, fit, guides, snap) in one swipeable row, where opacity and zoom used to be hidden inside a scroll box;
  - Layers and Colors open as a panel **under the canvas** instead of an off-screen drawer;
  - the canvas refits automatically when panels, the timeline or rotation change its space;
  - timeline buttons and page settings each in one swipeable row.
  Also fixes the Layers button looking "on" at startup while its phone panel was closed, and an 8px browser body margin that caused a little stray scrolling on desktop.
- **Pinned global header:** Quick Publish, TV Manager and Help share one sticky bar (Home · Publish · TVs · Editor · Help) via `ftbar.css`; the Creator's top bar is now sticky with Publish / TVs / Help links next to the store picker.
- **Creator home is a tool, not a brochure:** removed "Welcome to your content workspace / Create once… / 4Tier Creator Mode / From camera to creative" and the sample image. The home page is now one action row (＋ Create · ◉ Photo · 📺 Publish to TV), a compact four-number stats row, a short TV schedule card and Recent photos. Phone page subtitles are hidden. The removed wording lives in **Help → About** (English and Spanish).
- **Fewer words:** shorter TV Manager status ("✓ Connected · v0.0.13"); section descriptions hidden on phones; Quick Publish intro and long hints shortened.
- **Quick Publish:** fixed the "Save a copy to Recent photos" checkbox, which was stretched full width and squeezed its text into a narrow column on iPhone.
- PWA cache → `four-tier-marketing-v0013-shell`.

## v0.0.12 — 2026-10-23 · History delete, Recent photos, old-site cleanup

Upgrade over a working v0.0.11. **api/config.php is not in this ZIP**, so your owner login, database settings, TVs and menus are kept.

- **History → 🗑 Delete** (TV Manager): removes an old menu after an *“Are you sure you want to delete…?”* confirmation. The menu on air and menus with a pending schedule cannot be deleted (cancel the schedule first). Its images are removed from the server unless another menu still uses them. New API action `revision_delete`.
- **Save to Recent photos:** Quick Publish has *“Save a copy to Recent photos on this device”* (on by default); TV Manager History has **Save to Recent photos** for every page of a menu. The images appear in the Creator's Recent photos / Media Library for that store (device-local, shared `creator-library.js` helper).
- **TV Broadcast Center card** on Creator Home now uses the dark theme (it was white with light text and hard to read).
- **Old-site service worker kill switch:** `/service-worker.js` frees browsers that visited the previous coolwebsitedude.com site (their old worker answered with 404s). It deletes only the old site's caches, unregisters itself and reloads; the 4Tier worker (`sw.js`) is untouched.
- Version strings and PWA cache → v0.0.12 (`four-tier-marketing-v0012-shell`). Restored an editor code comment that a blanket v0.0.11 search-and-replace had made misleading.

## v0.0.11 — completion of partial Claude WIP

- Root domain deployment under `https://coolwebsitedude.com/` (instead of only `/marketing-editor/`), clean TV URLs and compatible `/tv-admin` alias.
- First-time MySQL installer with hosting checks, owner password and automatic configuration lock (from the uploaded WIP).
- New mobile-friendly `publish.html` one-page workflow: owner login, store/TV selection, add TV, editable headline/subtitle, background/photo, 16:9 preview, adjustable hold length, publish now/later, explicit destination confirmation and TV check-in feedback.
- JPEG compression steps honor dashboard maximum upload size rather than assuming 5 MB; advanced editor's publishing uses adaptive compression too.
- Creator Home links to Quick Publish, and the gallery/Creator/drafts have `📺 TV` / `Send to TV` actions. The existing editable design is flattened on this device before passing to Quick Publish.
- English and Spanish owner quick-start guide at `/start.html`.
- Updated PWA shell version; auth/API/TV/Quick Publish routes remain network-first/not cached by Service Worker.
- Security: generic API failures avoid exposing internal exception details; deny config temp files under `api/`; no live secret configs in ZIP.
- Local PHP file-mode integration check for multi-TV isolation, publishing, schedule/cancel, rollback, session/CSRF, heartbeat and cron; MySQL and live browser/device verification still pending.

## v0.0.10 — previous stabilization baseline (Claude)

- Fixed multi-page project recovery regression, startup toolbar issue, transition matching, root/subfolder links, TV session-file load, hosting diagnostics, TV heartbeat/status and history rollback.

## v0.0.9 — first TV publishing pilot

- Store/TV CRUD and public TV URLs, server-side image revisions, Publish Now/Schedule flow.
