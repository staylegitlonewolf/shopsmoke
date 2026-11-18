<?php
// Playback page; public display needs no owner password. v0.0.24: pages can also play videos.
// v0.0.39: https only again. Older TVs open it because shopsmoke.online now uses a Let's Encrypt certificate (see DEPLOY.md).
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-cache, must-revalidate');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; media-src 'self' https:; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
$slug=(string)($_GET['slug']??'');
if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*-tv-[1-9][0-9]*$/D',$slug)) {http_response_code(404);exit('Unknown TV');}
// v0.0.10: rtrim avoids a protocol-relative "//tv/..." URL when installed at the web root.
$base=rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#060918"><meta name="mobile-web-app-capable" content="yes"><link rel="manifest" href="<?= htmlspecialchars($base.'/tv/manifest.php?slug='.$slug,ENT_QUOTES) ?>"><link rel="stylesheet" href="<?= htmlspecialchars($base.'/tv/player.css?v=0.0.38',ENT_QUOTES) ?>"><title>ShopSmoke Store TV</title></head><body><main id="player"><div id="stage"><img id="current" alt="Current TV menu page"><img id="next" alt="Next TV menu page"><video id="video" muted playsinline hidden></video><iframe id="embed" title="TV video" allow="autoplay; fullscreen; encrypted-media" referrerpolicy="strict-origin-when-cross-origin" hidden></iframe></div><div id="blank"><strong>ShopSmoke Store TV</strong><span id="blankMessage">Waiting for your first published menu…</span><small id="blankIdentity"></small></div><div id="error" role="status" aria-live="polite"></div><button id="fsHint" type="button" hidden>⛶ Press OK or tap for full screen<small>Presione OK o toque para pantalla completa</small></button></main><script id="ftscreen" type="application/json"><?=json_encode(['slug'=>$slug],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS)?></script><script src="<?=htmlspecialchars($base.'/tv/player.js?v=0.0.38',ENT_QUOTES)?>" defer></script></body></html>
