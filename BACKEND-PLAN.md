# ShopSmoke v0.0.11 backend — implementation and next phase

## Current pilot behavior

- Root installation: `https://www.shopsmoke.online/` with `api/index.php`.
- Configurable storage backend: `FT_STORAGE='mysql'` after successful installer; `ft_registry` InnoDB table stores one JSON document with transactional `SELECT ... FOR UPDATE`. The file registry remains solely for backward-compatible testing/migration and should not be used as a production substitute once MySQL is installed.
- Server-side state: five store records, independently managed TV records and permanent slugs, revisions, published image paths, schedules, activity and owner password hash.
- Images: original Creator photos stay in browser IndexedDB, published JPEGs live under `tv-content/`, staged uploads and heartbeats under `.4tier-marketing-private` outside `public_html`.
- Public TV player only reads assigned screen manifests and polls every second (v0.0.18; was ~15 s) without creating PHP session files. A full player reload every 15 minutes is a resilience fallback, not the update mechanism.
- Owner authentication: PHP session + HTTP-only, SameSite=Strict cookie, CSRF for mutations, HTTPS enforcement, login throttling, explicit destination review. No shared Creator account authorization or staffing roles yet.
- Web installer: `setup/install.php` validates PHP/PDO/MySQL, initializes registry, writes server-only `api/config.php`, signs owner in and locks afterward; if already installed, leave config and data untouched.

## Limitations / why this is a pilot

The single JSON registry row is adequate for a small number of store TVs but will serialize all updates. Published JPEG files are not automatically garbage-collected; staging and backups need retention policy. No multi-owner/staff system, price approval, normalized products/tax/location schema, audit export, centralized product library, network health push, paid subscriptions or server-side editing. Public TV URL is visible without login by design, so do not publish confidential or unapproved products/prices.

## Suggested v0.0.12 roadmap (after real cPanel testing)

1. Verify real `pdo_mysql` and actual hosting installer/database credentials; never send secrets to AI/chat.
2. Owner permission controls plus per-store staff access with review/approval queues.
3. Add server media library with safe image transforms, checksums and deduplicated uploads. Migrate Creator assets from device storage to server without data loss.
4. Normalize catalogs, stores, products, prices and media records; integrate with inventory through audited read-only sync before enabling price edits.
5. Add TV health/last-seen alerts, quotas, log rotation, scheduled cron with monitoring, safe rollback + image retention, CDN caching strategy.
6. Compliance review of external retail website advertising and the age-restricted product categories.
