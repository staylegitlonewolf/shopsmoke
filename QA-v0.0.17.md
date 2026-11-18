# QA checkpoint: 4Tier Marketing Studio v0.0.17

**Test date:** 2026-10-24 · PHP 8.3.36 + MariaDB 11.4.13 (portable, localhost), cPanel-style folders, fresh installs and databases. Browser pane at about 375–480px wide.

## v0.0.17 API suite — 52/52 pass (fresh install, database `coolqdzx_v17`)
- **Installer and accounts:** installer; status reports the owner, version and perms; owner creates manager and staff; duplicate login, short password and "owner" username refused; the team list never contains password hashes.
- **Logins:** wrong password → 401; usernames are case-insensitive; a **phone-number login** created as "(305) 555-1234" signs in typed as "305-555-1234".
- **Manager:** sees only assigned stores and TVs; publishes to its own store; 403 on another store, adding stores, diagnostics, or adding TVs elsewhere.
- **Staff:** 403 on TV page upload, publish and activity; dashboard limited to its store.
- **Custom "publish only" account:** can publish; 403 for drafts, adding TVs and activity.
- **Drafts:** a 1 MB draft is saved in 300 KB chunks and downloads byte-for-byte with a thumbnail. Other-store staff can't list or download it; staff update their own drafts but can't delete others'; a manager deletes one in their store; non-project uploads are refused.
- **Photos:** chunked upload and byte-for-byte download; the same id is not stored twice; the folder name is kept; other stores are blocked; non-images are refused.
- **Editable copies:** a revision links to its editable copy (kind "published"); deleting the revision removes the copy.
- **Access removal:** a disabled account is signed out (401) and can't sign in; a deleted account loses access; the owner signs in with a blank username.

## Core regression — 33/33 pass (another fresh install, `coolqdzx_v17core`)
Installer, MySQL storage, TVs, publish, history delete rules, cron, diagnostics, `.md` and `config.php` blocking, kill-switch worker, preview reads, paged activity and clearing.

## Browser checks
- **Creator:** no logo in the top bar; **⚙ Settings** replaces Photo; Settings → **Account** signs in through the login box ("Signed in as Owner — Full access") with Sign out; Start screen shows **✎ Editor (blank canvas)** (`?new=1`).
- **☁ Sync:** first run "7 uploaded, 1 downloaded" (the staff photo arrived in a new "Vapes" folder), leaving device and server with identical photo ids; second run "Already in sync — 8 photos". **Team drafts** lists both server drafts linking to `editor.html?draft=…`, which asks "Open “Weekend promo”?".
- **TV Manager Team:** Add person "José Pérez" with "(786) 555-0101". Staff preset = drafts + photos; Manager ticks all; unticking one switches to Custom; the account is created and listed with its phone number.
- **Signed in as staff (Wyn):** only Galaxy Wynwood; Add Store/TV, Rename, Edit, Delete TV, Team, Deployment and Activity hidden; Copy URL and History visible.
- **Editor:**
  - File menu has Save as Draft…, Open draft…, Account…; Pages menu has Preview / Save.
  - Tools grid: Select, red ＋Add, Move, Brush / Eraser, Text, Rectangle, More tools; timeline without Hide/Folders.
  - Preview window has no title, and ☁ Save as Draft is the first save button.
  - **Save as Draft → server** (login box) saved 1.2 MB with a thumbnail; **Open draft…** restored the editable text layer.
  - **Publish** stored an editable copy; **✎ Edit** then reopened the editable layers ("Loaded the editable menu…").
- **Help:** new EN/ES sections plus **About & License** with the logo; the logo is gone from the top bar.

## Not verified
Real phones on the live host; very large real-world drafts (tens of MB) over mobile data; multiple people editing the same draft at once (last save wins).
